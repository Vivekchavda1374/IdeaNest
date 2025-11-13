<?php

require_once __DIR__ . '/../includes/autoload_simple.php';
require_once __DIR__ . '/../Login/Login/db.php';

// Weekly notifications without vendor dependencies
function sendWeeklyNotifications() {
    global $conn;
    
    try {
        // Get new projects from last week
        $projectQuery = "SELECT title, category, created_at FROM admin_approved_projects 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $projectResult = mysqli_query($conn, $projectQuery);
        $projects = [];
        
        while ($row = mysqli_fetch_assoc($projectResult)) {
            $projects[] = $row;
        }
        
        // Get new ideas from last week
        $ideaQuery = "SELECT title, author, created_at FROM blog 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $ideaResult = mysqli_query($conn, $ideaQuery);
        $ideas = [];
        
        while ($row = mysqli_fetch_assoc($ideaResult)) {
            $ideas[] = $row;
        }
        
        if (empty($projects) && empty($ideas)) {
            echo "No new content this week.\n";
            return;
        }
        
        // Get all users who want weekly notifications
        $userQuery = "SELECT email, name FROM register WHERE role = 'user'";
        $userResult = mysqli_query($conn, $userQuery);
        
        $emailHelper = new EmailHelper();
        $sentCount = 0;
        
        while ($user = mysqli_fetch_assoc($userResult)) {
            if ($emailHelper->sendWeeklyDigest($user['email'], $projects, $ideas)) {
                $sentCount++;
                echo "Sent weekly digest to: " . $user['email'] . "\n";
            } else {
                echo "Failed to send to: " . $user['email'] . "\n";
            }
        }
        
        echo "Weekly notifications sent to {$sentCount} users.\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Run the function
sendWeeklyNotifications();