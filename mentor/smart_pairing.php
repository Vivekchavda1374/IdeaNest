<?php
require_once '../Login/Login/db.php';

class SmartPairing {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function findBestMentor($student_id, $project_classification) {
        $query = "SELECT m.user_id, r.name, m.specialization, m.current_students, m.max_students,
                  CASE 
                    WHEN m.specialization LIKE ? THEN 3
                    WHEN r.expertise LIKE ? THEN 2
                    ELSE 1
                  END as match_score
                  FROM mentors m 
                  JOIN register r ON m.user_id = r.id 
                  WHERE m.current_students < m.max_students 
                  AND r.is_available = 1
                  ORDER by match_score DESC, m.current_students ASC
                  LIMIT 5";
        
        $like_classification = "%$project_classification%";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $like_classification, $like_classification);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function autoAssignMentor($student_id, $project_id) {
        // Get project classification
        $project_query = "SELECT classification FROM projects WHERE id = ?";
        $stmt = $this->conn->prepare($project_query);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $project = $stmt->get_result()->fetch_assoc();
        
        if (!$project) return false;
        
        $mentors = $this->findBestMentor($student_id, $project['classification']);
        
        if (empty($mentors)) return false;
        
        $best_mentor = $mentors[0];
        
        // Create pairing
        $pair_query = "INSERT INTO mentor_student_pairs (mentor_id, student_id, project_id) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($pair_query);
        $stmt->bind_param("iii", $best_mentor['user_id'], $student_id, $project_id);
        $stmt->execute();
        
        // Update mentor count
        $update_query = "UPDATE mentors SET current_students = current_students + 1 WHERE user_id = ?";
        $stmt = $this->conn->prepare($update_query);
        $stmt->bind_param("i", $best_mentor['user_id']);
        $stmt->execute();
        
        return $best_mentor;
    }
}
?>