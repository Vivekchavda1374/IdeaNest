<?php
class NotificationService {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->initTables();
    }

    private function initTables() {
        $sql = file_get_contents(__DIR__ . '/notifications_schema.sql');
        $this->conn->multi_query($sql);
        while ($this->conn->next_result()) {;}
    }

    public function createNotification($userId, $type, $title, $message, $data = null) {
        $stmt = $this->conn->prepare("INSERT INTO realtime_notifications (user_id, type, title, message, data) VALUES (?, ?, ?, ?, ?)");
        $dataJson = $data ? json_encode($data) : null;
        $stmt->bind_param("issss", $userId, $type, $title, $message, $dataJson);
        
        if ($stmt->execute()) {
            $notificationId = $this->conn->insert_id;
            $this->sendRealTimeNotification($userId, [
                'id' => $notificationId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'timestamp' => time()
            ]);
            return $notificationId;
        }
        return false;
    }

    public function getUnreadNotifications($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM realtime_notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $row['data'] = $row['data'] ? json_decode($row['data'], true) : null;
            $notifications[] = $row;
        }
        return $notifications;
    }

    public function markAsRead($notificationId, $userId) {
        $stmt = $this->conn->prepare("UPDATE realtime_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notificationId, $userId);
        return $stmt->execute();
    }

    private function sendRealTimeNotification($userId, $notification) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/send");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'user_id' => $userId,
            'notification' => $notification
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_exec($ch);
        curl_close($ch);
    }

    // Notification triggers
    public function projectApproved($userId, $projectName, $projectId) {
        return $this->createNotification(
            $userId,
            'project_approved',
            'Project Approved!',
            "Your project '{$projectName}' has been approved.",
            ['project_id' => $projectId]
        );
    }

    public function projectRejected($userId, $projectName, $projectId, $reason = '') {
        return $this->createNotification(
            $userId,
            'project_rejected',
            'Project Update',
            "Your project '{$projectName}' needs revision." . ($reason ? " Reason: {$reason}" : ''),
            ['project_id' => $projectId, 'reason' => $reason]
        );
    }

    public function newComment($userId, $projectName, $projectId, $commenterName) {
        return $this->createNotification(
            $userId,
            'new_comment',
            'New Comment',
            "{$commenterName} commented on your project '{$projectName}'",
            ['project_id' => $projectId, 'commenter' => $commenterName]
        );
    }

    public function projectLiked($userId, $projectName, $projectId, $likerName) {
        return $this->createNotification(
            $userId,
            'project_liked',
            'Project Liked',
            "{$likerName} liked your project '{$projectName}'",
            ['project_id' => $projectId, 'liker' => $likerName]
        );
    }
}
?>