<?php
/**
 * Optimized Query Helper Functions
 * Use these functions instead of writing raw queries for better performance
 */

class OptimizedQueries {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get projects with pagination and filters (OPTIMIZED)
     * Uses indexes and limits result set
     */
    public function getProjects($filters = [], $page = 1, $per_page = 9) {
        $offset = ($page - 1) * $per_page;
        
        // Use view for better performance
        $sql = "SELECT 
                    ap.*,
                    r.name as user_name,
                    r.email as user_email,
                    ps.likes_count,
                    ps.bookmark_count
                FROM admin_approved_projects ap
                LEFT JOIN register r ON ap.user_id = r.id
                LEFT JOIN v_project_stats ps ON ap.id = ps.id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Add filters
        if (!empty($filters['status'])) {
            $sql .= " AND ap.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['classification'])) {
            $sql .= " AND ap.classification = ?";
            $params[] = $filters['classification'];
            $types .= "s";
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND ap.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (ap.project_name LIKE ? OR ap.description LIKE ?)";
            $search_param = "%{$filters['search']}%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "ss";
        }
        
        // Order and limit
        $sql .= " ORDER BY ap.submission_date DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Get project count (OPTIMIZED)
     * Uses COUNT with indexes
     */
    public function getProjectCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM admin_approved_projects WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['classification'])) {
            $sql .= " AND classification = ?";
            $params[] = $filters['classification'];
            $types .= "s";
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    
    /**
     * Get single project with all details (OPTIMIZED)
     * Uses single query with JOINs instead of multiple queries
     */
    public function getProjectById($project_id, $user_id = null) {
        $sql = "SELECT 
                    ap.*,
                    r.name as user_name,
                    r.email as user_email,
                    r.phone_no as user_phone,
                    r.about as user_bio,
                    r.department as user_department,
                    ps.likes_count,
                    ps.bookmark_count,
                    CASE WHEN b.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked,
                    CASE WHEN pl.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked
                FROM admin_approved_projects ap
                LEFT JOIN register r ON ap.user_id = r.id
                LEFT JOIN v_project_stats ps ON ap.id = ps.id";
        
        if ($user_id) {
            $sql .= " LEFT JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?
                     LEFT JOIN project_likes pl ON ap.id = pl.project_id AND pl.user_id = ?";
        }
        
        $sql .= " WHERE ap.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($user_id) {
            $stmt->bind_param("iii", $user_id, $user_id, $project_id);
        } else {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get user statistics (OPTIMIZED)
     * Uses pre-calculated view
     */
    public function getUserStats($user_id) {
        $sql = "SELECT * FROM v_user_stats WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get ideas with pagination (OPTIMIZED)
     */
    public function getIdeas($filters = [], $page = 1, $per_page = 10) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT 
                    b.*,
                    r.name as user_name,
                    is_stats.likes_count,
                    is_stats.comments_count,
                    is_stats.views_count
                FROM blog b
                LEFT JOIN register r ON b.user_id = r.id
                LEFT JOIN v_idea_stats is_stats ON b.id = is_stats.id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $sql .= " AND b.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND b.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }
        
        $sql .= " ORDER BY b.submission_datetime DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Toggle like (OPTIMIZED)
     * Uses INSERT IGNORE and DELETE in single transaction
     */
    public function toggleProjectLike($project_id, $user_id) {
        $this->conn->begin_transaction();
        
        try {
            // Check if like exists
            $check_sql = "SELECT id FROM project_likes WHERE project_id = ? AND user_id = ? FOR UPDATE";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $project_id, $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Remove like
                $delete_sql = "DELETE FROM project_likes WHERE project_id = ? AND user_id = ?";
                $delete_stmt = $this->conn->prepare($delete_sql);
                $delete_stmt->bind_param("ii", $project_id, $user_id);
                $delete_stmt->execute();
                $action = 'removed';
            } else {
                // Add like
                $insert_sql = "INSERT INTO project_likes (project_id, user_id) VALUES (?, ?)";
                $insert_stmt = $this->conn->prepare($insert_sql);
                $insert_stmt->bind_param("ii", $project_id, $user_id);
                $insert_stmt->execute();
                $action = 'added';
            }
            
            $this->conn->commit();
            return $action;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Toggle like error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Toggle bookmark (OPTIMIZED)
     */
    public function toggleBookmark($project_id, $user_id, $idea_id = 0) {
        $this->conn->begin_transaction();
        
        try {
            $check_sql = "SELECT id FROM bookmark WHERE project_id = ? AND user_id = ? FOR UPDATE";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $project_id, $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
                $delete_stmt = $this->conn->prepare($delete_sql);
                $delete_stmt->bind_param("ii", $project_id, $user_id);
                $delete_stmt->execute();
                $action = 'removed';
            } else {
                $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
                $insert_stmt = $this->conn->prepare($insert_sql);
                $insert_stmt->bind_param("iii", $project_id, $user_id, $idea_id);
                $insert_stmt->execute();
                $action = 'added';
            }
            
            $this->conn->commit();
            return $action;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Toggle bookmark error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get dashboard statistics (OPTIMIZED)
     * Uses single query with subqueries instead of multiple queries
     */
    public function getDashboardStats($user_id) {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM bookmark WHERE user_id = ?) as bookmark_count,
                    (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ?) as my_projects_count,
                    (SELECT COUNT(*) FROM blog WHERE user_id = ?) as my_ideas_count,
                    (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ? AND status = 'pending') as my_pending_projects,
                    (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ? AND status = 'approved') as my_approved_projects,
                    (SELECT COUNT(*) FROM admin_approved_projects WHERE status = 'approved') as total_projects,
                    (SELECT COUNT(*) FROM blog) as total_ideas";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get classification statistics (OPTIMIZED)
     * Uses GROUP BY with index
     */
    public function getClassificationStats() {
        $sql = "SELECT classification, COUNT(*) as count 
                FROM admin_approved_projects 
                WHERE classification IS NOT NULL 
                  AND classification != '' 
                  AND status = 'approved'
                GROUP BY classification 
                ORDER BY count DESC
                LIMIT 10";
        
        $result = $this->conn->query($sql);
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        return $stats;
    }
    
    /**
     * Search projects (OPTIMIZED)
     * Uses FULLTEXT index if available, otherwise LIKE with index
     */
    public function searchProjects($search_term, $limit = 20) {
        $sql = "SELECT 
                    ap.id,
                    ap.project_name,
                    ap.description,
                    ap.classification,
                    ap.project_type,
                    r.name as user_name,
                    ps.likes_count
                FROM admin_approved_projects ap
                LEFT JOIN register r ON ap.user_id = r.id
                LEFT JOIN v_project_stats ps ON ap.id = ps.id
                WHERE ap.status = 'approved'
                  AND (ap.project_name LIKE ? 
                       OR ap.description LIKE ? 
                       OR ap.classification LIKE ?)
                ORDER BY ap.submission_date DESC
                LIMIT ?";
        
        $search_param = "%$search_term%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $search_param, $search_param, $search_param, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Batch insert with transaction (OPTIMIZED)
     */
    public function batchInsert($table, $columns, $rows) {
        if (empty($rows)) {
            return false;
        }
        
        $this->conn->begin_transaction();
        
        try {
            $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
            $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES $placeholders";
            
            $stmt = $this->conn->prepare($sql);
            
            foreach ($rows as $row) {
                $types = str_repeat('s', count($row));
                $stmt->bind_param($types, ...$row);
                $stmt->execute();
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Batch insert error: " . $e->getMessage());
            return false;
        }
    }
}

// Create global instance
if (isset($conn)) {
    $optimized_queries = new OptimizedQueries($conn);
}
?>
