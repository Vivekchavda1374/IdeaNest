<?php
/**
 * User Notification Helper
 * Manages user notifications for all activities
 */

class NotificationHelper {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($user_id, $type, $title, $message, $related_id = null, $related_type = null, $action_url = null) {
        // Set icon and color based on notification type
        $config = $this->getNotificationConfig($type);
        
        $stmt = $this->conn->prepare("
            INSERT INTO user_notifications 
            (user_id, notification_type, title, message, related_id, related_type, action_url, icon, color) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "isssissss", 
            $user_id, 
            $type, 
            $title, 
            $message, 
            $related_id, 
            $related_type, 
            $action_url,
            $config['icon'],
            $config['color']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get notification configuration (icon and color)
     */
    private function getNotificationConfig($type) {
        $configs = [
            'project_approved' => [
                'icon' => 'bi-check-circle-fill',
                'color' => 'success'
            ],
            'project_rejected' => [
                'icon' => 'bi-x-circle-fill',
                'color' => 'danger'
            ],
            'project_submitted' => [
                'icon' => 'bi-upload',
                'color' => 'info'
            ],
            'mentor_assigned' => [
                'icon' => 'bi-person-check-fill',
                'color' => 'primary'
            ],
            'mentor_request' => [
                'icon' => 'bi-person-plus-fill',
                'color' => 'warning'
            ],
            'session_scheduled' => [
                'icon' => 'bi-calendar-check-fill',
                'color' => 'info'
            ],
            'general' => [
                'icon' => 'bi-bell-fill',
                'color' => 'secondary'
            ]
        ];
        
        return $configs[$type] ?? $configs['general'];
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($user_id, $limit = 50, $unread_only = false) {
        $sql = "SELECT * FROM user_notifications WHERE user_id = ?";
        
        if ($unread_only) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        $stmt->close();
        return $notifications;
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($user_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM user_notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] ?? 0;
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id) {
        $stmt = $this->conn->prepare("
            UPDATE user_notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->bind_param("ii", $notification_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($user_id) {
        $stmt = $this->conn->prepare("
            UPDATE user_notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0
        ");
        
        $stmt->bind_param("i", $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Delete notification
     */
    public function deleteNotification($notification_id, $user_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM user_notifications 
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->bind_param("ii", $notification_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Delete all read notifications
     */
    public function deleteAllRead($user_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM user_notifications 
            WHERE user_id = ? AND is_read = 1
        ");
        
        $stmt->bind_param("i", $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get notification statistics
     */
    public function getStats($user_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read,
                SUM(CASE WHEN notification_type = 'project_approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN notification_type = 'project_rejected' THEN 1 ELSE 0 END) as rejected
            FROM user_notifications 
            WHERE user_id = ?
        ");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return $stats;
    }
    
    /**
     * Helper: Create project approved notification
     */
    public function notifyProjectApproved($user_id, $project_id, $project_name) {
        return $this->createNotification(
            $user_id,
            'project_approved',
            '🎉 Project Approved!',
            "Congratulations! Your project '{$project_name}' has been approved and is now live on IdeaNest.",
            $project_id,
            'project',
            '/user/my_projects.php'
        );
    }
    
    /**
     * Helper: Create project rejected notification
     */
    public function notifyProjectRejected($user_id, $project_id, $project_name, $reason = '') {
        $message = "Your project '{$project_name}' needs improvements before approval.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        
        return $this->createNotification(
            $user_id,
            'project_rejected',
            '⚠️ Project Needs Revision',
            $message,
            $project_id,
            'project',
            '/user/my_projects.php'
        );
    }
    
    /**
     * Helper: Create project submitted notification
     */
    public function notifyProjectSubmitted($user_id, $project_id, $project_name) {
        return $this->createNotification(
            $user_id,
            'project_submitted',
            '✅ Project Submitted',
            "Your project '{$project_name}' has been submitted successfully and is under review.",
            $project_id,
            'project',
            '/user/my_projects.php'
        );
    }
    
    /**
     * Helper: Create mentor assigned notification
     */
    public function notifyMentorAssigned($user_id, $mentor_id, $mentor_name) {
        return $this->createNotification(
            $user_id,
            'mentor_assigned',
            '👨‍🏫 Mentor Assigned',
            "You have been paired with mentor {$mentor_name}. Start your mentoring journey!",
            $mentor_id,
            'mentor',
            '/user/my_mentors.php'
        );
    }
}

/**
 * Quick helper function to create notifications
 */
function createUserNotification($conn, $user_id, $type, $title, $message, $related_id = null, $related_type = null, $action_url = null) {
    $notifier = new NotificationHelper($conn);
    return $notifier->createNotification($user_id, $type, $title, $message, $related_id, $related_type, $action_url);
}
