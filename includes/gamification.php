<?php
/**
 * Gamification System - Core Functions
 * Handles points, badges, achievements, and leaderboards
 */

require_once __DIR__ . '/../Login/Login/db.php';

class Gamification {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Initialize user points record if not exists
     */
    public function initializeUser($user_id) {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO user_points (user_id, total_points, level, last_activity_date)
            VALUES (?, 0, 1, CURDATE())
        ");
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
    
    /**
     * Add points to user
     */
    public function addPoints($user_id, $points, $action_type, $reference_id = null, $description = '') {
        // Insert into points history
        $stmt = $this->conn->prepare("
            INSERT INTO points_history (user_id, points, action_type, reference_id, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisis", $user_id, $points, $action_type, $reference_id, $description);
        
        if (!$stmt->execute()) {
            return false;
        }
        
        // Update user points
        $stmt = $this->conn->prepare("
            UPDATE user_points 
            SET total_points = total_points + ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ");
        $stmt->bind_param("ii", $points, $user_id);
        
        if (!$stmt->execute()) {
            return false;
        }
        
        // Calculate and update level (100 points per level)
        $stmt = $this->conn->prepare("SELECT total_points FROM user_points WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $current_points = $row['total_points'] ?? 0;
        $new_level = floor($current_points / 100) + 1;
        
        $stmt = $this->conn->prepare("UPDATE user_points SET level = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $new_level, $user_id);
        $stmt->execute();
        
        // Check for new badges
        $this->checkAndAwardBadges($user_id);
        
        return true;
    }
    
    /**
     * Update daily streak
     */
    public function updateStreak($user_id) {
        // Get current streak data
        $stmt = $this->conn->prepare("
            SELECT last_activity_date, current_streak 
            FROM user_points 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        $last_date = $data['last_activity_date'] ?? null;
        $current_streak = $data['current_streak'] ?? 0;
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $status = 'unknown';
        
        if ($last_date === $today) {
            // Already logged in today
            $status = 'already_logged';
        } elseif ($last_date === $yesterday) {
            // Consecutive day - continue streak
            $new_streak = $current_streak + 1;
            $stmt = $this->conn->prepare("
                UPDATE user_points 
                SET current_streak = ?,
                    longest_streak = GREATEST(longest_streak, ?),
                    last_activity_date = CURDATE()
                WHERE user_id = ?
            ");
            $stmt->bind_param("iii", $new_streak, $new_streak, $user_id);
            $stmt->execute();
            
            // Award daily login points
            $this->addPointsDirectly($user_id, 5, 'daily_login', null, 'Daily login bonus');
            $status = 'streak_continued';
        } else {
            // Streak broken - reset to 1
            $stmt = $this->conn->prepare("
                UPDATE user_points 
                SET current_streak = 1,
                    last_activity_date = CURDATE()
                WHERE user_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Award daily login points
            $this->addPointsDirectly($user_id, 5, 'daily_login', null, 'Daily login bonus');
            $status = 'streak_reset';
        }
        
        // Check streak badges
        $this->checkStreakBadges($user_id);
        
        return $status;
    }
    
    /**
     * Get user stats
     */
    public function getUserStats($user_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                up.*,
                COUNT(DISTINCT ub.badge_id) as badges_earned,
                (SELECT COUNT(*) FROM badges WHERE is_active = 1) as total_badges,
                (SELECT COUNT(*) FROM projects WHERE user_id = ?) as projects_count,
                (SELECT COUNT(*) FROM blog WHERE user_id = ?) as ideas_count,
                (SELECT COUNT(*) FROM idea_likes il 
                 JOIN blog b ON il.idea_id = b.id 
                 WHERE b.user_id = ?) as likes_received,
                (SELECT COUNT(*) FROM idea_comments WHERE user_id = ?) as comments_made
            FROM user_points up
            LEFT JOIN user_badges ub ON up.user_id = ub.user_id
            WHERE up.user_id = ?
            GROUP BY up.id
        ");
        $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get user badges
     */
    public function getUserBadges($user_id) {
        $stmt = $this->conn->prepare("
            SELECT b.*, ub.earned_at, ub.is_displayed
            FROM user_badges ub
            JOIN badges b ON ub.badge_id = b.id
            WHERE ub.user_id = ?
            ORDER BY ub.earned_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get available badges (not yet earned)
     */
    public function getAvailableBadges($user_id) {
        $stmt = $this->conn->prepare("
            SELECT b.*,
                CASE b.condition_type
                    WHEN 'projects_count' THEN (SELECT COUNT(*) FROM projects WHERE user_id = ?)
                    WHEN 'likes_received' THEN (SELECT COUNT(*) FROM idea_likes il JOIN blog bg ON il.idea_id = bg.id WHERE bg.user_id = ?)
                    WHEN 'comments_made' THEN (SELECT COUNT(*) FROM idea_comments WHERE user_id = ?)
                    WHEN 'ideas_shared' THEN (SELECT COUNT(*) FROM blog WHERE user_id = ?)
                    WHEN 'mentor_sessions' THEN (SELECT COUNT(*) FROM mentoring_sessions ms JOIN mentor_student_pairs msp ON ms.pair_id = msp.id WHERE msp.student_id = ? AND ms.status = 'completed')
                    WHEN 'streak_days' THEN (SELECT current_streak FROM user_points WHERE user_id = ?)
                    ELSE 0
                END as current_progress
            FROM badges b
            WHERE b.is_active = 1
            AND b.id NOT IN (SELECT badge_id FROM user_badges WHERE user_id = ?)
            ORDER BY b.rarity, b.points_required
        ");
        $stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Check and award badges
     */
    public function checkAndAwardBadges($user_id) {
        $available = $this->getAvailableBadges($user_id);
        $awarded = [];
        
        foreach ($available as $badge) {
            if ($badge['condition_type'] !== 'special' && 
                $badge['current_progress'] >= $badge['condition_value']) {
                
                // Award badge
                $stmt = $this->conn->prepare("
                    INSERT IGNORE INTO user_badges (user_id, badge_id)
                    VALUES (?, ?)
                ");
                $stmt->bind_param("ii", $user_id, $badge['id']);
                
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    // Add points for earning badge
                    $this->addPointsDirectly($user_id, $badge['points_required'], 'badge_earned', $badge['id'], 
                        "Earned badge: {$badge['name']}");
                    $awarded[] = $badge;
                }
            }
        }
        
        return $awarded;
    }
    
    /**
     * Check streak badges
     */
    private function checkStreakBadges($user_id) {
        $stmt = $this->conn->prepare("SELECT current_streak FROM user_points WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $streak = $result['current_streak'] ?? 0;
        
        // Check if user earned any streak badges
        $stmt = $this->conn->prepare("
            SELECT b.id, b.name, b.points_required
            FROM badges b
            WHERE b.condition_type = 'streak_days'
            AND b.condition_value <= ?
            AND b.id NOT IN (SELECT badge_id FROM user_badges WHERE user_id = ?)
        ");
        $stmt->bind_param("ii", $streak, $user_id);
        $stmt->execute();
        $badges = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($badges as $badge) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $badge['id']);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $this->addPointsDirectly($user_id, $badge['points_required'], 'badge_earned', $badge['id'], 
                    "Earned badge: {$badge['name']}");
            }
        }
    }
    
    /**
     * Add points directly (without recursion)
     */
    private function addPointsDirectly($user_id, $points, $action_type, $reference_id, $description) {
        $stmt = $this->conn->prepare("
            INSERT INTO points_history (user_id, points, action_type, reference_id, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisis", $user_id, $points, $action_type, $reference_id, $description);
        $stmt->execute();
        
        $stmt = $this->conn->prepare("
            UPDATE user_points 
            SET total_points = total_points + ?,
                level = FLOOR((total_points + ?) / 100) + 1
            WHERE user_id = ?
        ");
        $stmt->bind_param("iii", $points, $points, $user_id);
        $stmt->execute();
    }
    
    /**
     * Get leaderboard
     */
    public function getLeaderboard($limit = 50, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT * FROM leaderboard_view
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get user rank
     */
    public function getUserRank($user_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) + 1 as rank
            FROM user_points
            WHERE total_points > (SELECT total_points FROM user_points WHERE user_id = ?)
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['rank'] ?? 0;
    }
    
    /**
     * Get recent activities
     */
    public function getRecentActivities($user_id, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM points_history
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Award special badge (admin only)
     */
    public function awardSpecialBadge($user_id, $badge_id) {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO user_badges (user_id, badge_id)
            VALUES (?, ?)
        ");
        $stmt->bind_param("ii", $user_id, $badge_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Get badge points
            $stmt = $this->conn->prepare("SELECT points_required, name FROM badges WHERE id = ?");
            $stmt->bind_param("i", $badge_id);
            $stmt->execute();
            $badge = $stmt->get_result()->fetch_assoc();
            
            $this->addPoints($user_id, $badge['points_required'], 'badge_earned', $badge_id, 
                "Earned special badge: {$badge['name']}");
            return true;
        }
        return false;
    }
}

// Helper function to get gamification instance
function getGamification() {
    global $conn;
    return new Gamification($conn);
}
