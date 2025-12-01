<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once __DIR__ . '/../includes/security_init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../Login/Login/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$export_type = $_GET['type'] ?? 'csv';
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === '1';

// Function to check if table exists
function tableExists($conn, $tableName) {
    try {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("s", $tableName);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();
        return $exists;
    } catch (Exception $e) {
        error_log("Table check error for $tableName: " . $e->getMessage());
        return false;
    }
}

// Function to safely get data
function safeQuery($conn, $query) {
    try {
        $result = $conn->query($query);
        if (!$result) {
            error_log("Query failed: " . $conn->error . " | Query: " . substr($query, 0, 200));
            return [];
        }
        $data = $result->fetch_all(MYSQLI_ASSOC);
        return $data;
    } catch (Exception $e) {
        error_log("Query exception: " . $e->getMessage() . " | Query: " . substr($query, 0, 200));
        return [];
    }
}

if ($export_type === 'csv') {
    try {
        // Debug mode - show errors instead of downloading
        if ($debug_mode) {
            echo "<h1>Debug Mode</h1>";
            echo "<p>Database: " . $conn->server_info . "</p>";
            $test = $conn->query("SELECT COUNT(*) as cnt FROM register");
            if ($test) {
                $result = $test->fetch_assoc();
                echo "<p>Users in database: " . $result['cnt'] . "</p>";
            } else {
                echo "<p>Error: " . $conn->error . "</p>";
            }
            
            $test2 = $conn->query("SELECT COUNT(*) as cnt FROM projects");
            if ($test2) {
                $result2 = $test2->fetch_assoc();
                echo "<p>Projects in database: " . $result2['cnt'] . "</p>";
            } else {
                echo "<p>Error: " . $conn->error . "</p>";
            }
            
            echo "<p>Testing safeQuery function...</p>";
            $users = safeQuery($conn, "SELECT * FROM register ORDER BY id");
            echo "<p>Users returned by safeQuery: " . count($users) . "</p>";
            
            $projects = safeQuery($conn, "SELECT * FROM projects ORDER BY id");
            echo "<p>Projects returned by safeQuery: " . count($projects) . "</p>";
            
            exit();
        }
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ideanest_comprehensive_export_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        if (!$output) {
            throw new Exception("Failed to open output stream");
        }
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['IdeaNest Comprehensive Data Export']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);

    // Initialize ALL variables
    $users = [];
    $projects = [];
    $admin_projects = [];
    $ideas = [];
    $subadmins = [];
    $mentors = [];
    $pairs = [];
    $tickets = [];
    $bookmarks = [];
    $project_likes = [];
    $project_comments = [];
    $idea_likes = [];
    $idea_comments = [];
    $idea_reports = [];
    $denied_projects = [];
    $mentor_requests = [];
    $sessions = [];
    $subadmin_requests = [];
    $notifications = [];
    $notification_logs = [];
    $admin_logs = [];
    $mentor_logs = [];
    $credentials = [];
    $email_logs = [];
    
    // Test connection first
    $test = $conn->query("SELECT COUNT(*) as cnt FROM register");
    if ($test) {
        $test_result = $test->fetch_assoc();
        error_log("Connection test: Found " . $test_result['cnt'] . " users in register table");
    } else {
        error_log("Connection test FAILED: " . $conn->error);
    }
    
    // Users
    $users = safeQuery($conn, "SELECT * FROM register ORDER BY id");
    fputcsv($output, ['=== USERS (' . count($users) . ') ===']);
    fputcsv($output, ['ID', 'Name', 'Email', 'Department', 'Role', 'Enrollment', 'Phone', 'Created']);
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['name'],
            $user['email'],
            $user['department'] ?? 'N/A',
            $user['role'] ?? 'student',
            $user['enrollment_number'] ?? 'N/A',
            $user['phone_no'] ?? 'N/A',
            $user['created_at'] ?? 'N/A'
        ]);
    }
    fputcsv($output, []);

    // Projects
    if (tableExists($conn, 'projects')) {
        $projects = safeQuery($conn, "
            SELECT p.*, r.name as user_name, r.email as user_email 
            FROM projects p 
            LEFT JOIN register r ON p.user_id = r.id 
            ORDER BY p.submission_date DESC
        ");
        fputcsv($output, ['=== PROJECT SUBMISSIONS (' . count($projects) . ') ===']);
        fputcsv($output, ['ID', 'Project Name', 'User Name', 'User Email', 'Type', 'Category', 'Language', 'Status', 'Submission Date', 'Description']);
        foreach ($projects as $project) {
            fputcsv($output, [
                $project['id'],
                $project['project_name'],
                $project['user_name'] ?? 'Unknown',
                $project['user_email'] ?? 'N/A',
                $project['project_type'],
                $project['project_category'] ?? 'N/A',
                $project['language'],
                $project['status'],
                $project['submission_date'],
                substr($project['description'], 0, 200) . (strlen($project['description']) > 200 ? '...' : '')
            ]);
        }
        fputcsv($output, []);
    }

    // Admin Approved Projects
    if (tableExists($conn, 'admin_approved_projects')) {
        $admin_projects = safeQuery($conn, "
            SELECT ap.*, r.name as user_name, r.email as user_email 
            FROM admin_approved_projects ap 
            LEFT JOIN register r ON ap.user_id = r.id 
            ORDER BY ap.submission_date DESC
        ");
        fputcsv($output, ['=== ADMIN APPROVED PROJECTS (' . count($admin_projects) . ') ===']);
        fputcsv($output, ['ID', 'Project Name', 'User Name', 'User Email', 'Type', 'Category', 'Language', 'Status', 'Approval Date']);
        foreach ($admin_projects as $project) {
            fputcsv($output, [
                $project['id'],
                $project['project_name'],
                $project['user_name'] ?? 'Unknown',
                $project['user_email'] ?? 'N/A',
                $project['project_type'],
                $project['project_category'] ?? 'N/A',
                $project['language'],
                $project['status'] ?? 'approved',
                $project['submission_date']
            ]);
        }
        fputcsv($output, []);
    }

    // Ideas/Blog
    if (tableExists($conn, 'blog')) {
        $ideas = safeQuery($conn, "
            SELECT b.*, r.name as user_name, r.email as user_email 
            FROM blog b 
            LEFT JOIN register r ON b.user_id = r.id 
            ORDER BY b.submission_datetime DESC
        ");
        fputcsv($output, ['=== PROJECT IDEAS (' . count($ideas) . ') ===']);
        fputcsv($output, ['ID', 'Idea Name', 'User Name', 'User Email', 'Type', 'Classification', 'Status', 'Priority', 'Submission Date', 'Description']);
        foreach ($ideas as $idea) {
            fputcsv($output, [
                $idea['id'],
                $idea['project_name'],
                $idea['user_name'] ?? 'Unknown',
                $idea['user_email'] ?? 'N/A',
                $idea['project_type'],
                $idea['classification'],
                $idea['status'],
                $idea['priority1'],
                $idea['submission_datetime'],
                substr($idea['description'], 0, 200) . (strlen($idea['description']) > 200 ? '...' : '')
            ]);
        }
        fputcsv($output, []);
    }

    // Subadmins
    if (tableExists($conn, 'subadmins')) {
        $subadmins = safeQuery($conn, "SELECT * FROM subadmins ORDER BY created_at DESC");
        fputcsv($output, ['=== SUBADMINS (' . count($subadmins) . ') ===']);
        fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Domains', 'Status', 'Created']);
        foreach ($subadmins as $subadmin) {
            fputcsv($output, [
                $subadmin['id'] ?? '',
                $subadmin['first_name'] ?? '',
                $subadmin['last_name'] ?? '',
                $subadmin['email'] ?? '',
                $subadmin['domains'] ?? 'N/A',
                $subadmin['status'] ?? 'active',
                $subadmin['created_at'] ?? ''
            ]);
        }
        fputcsv($output, []);
    }

    // Mentors
    $mentors = safeQuery($conn, "SELECT * FROM register WHERE role = 'mentor' ORDER BY id");
    fputcsv($output, ['=== MENTORS (' . count($mentors) . ') ===']);
    fputcsv($output, ['ID', 'Name', 'Email', 'Department', 'Expertise', 'Phone', 'About']);
    foreach ($mentors as $mentor) {
        fputcsv($output, [
            $mentor['id'],
            $mentor['name'],
            $mentor['email'],
            $mentor['department'] ?? 'N/A',
            $mentor['expertise'] ?? 'N/A',
            $mentor['phone_no'] ?? 'N/A',
            substr($mentor['about'] ?? '', 0, 100)
        ]);
    }
    fputcsv($output, []);

    // Mentor-Student Pairs
    if (tableExists($conn, 'mentor_student_pairs')) {
        $pairs = safeQuery($conn, "
            SELECT msp.*, 
                   rm.name as mentor_name, 
                   rs.name as student_name 
            FROM mentor_student_pairs msp 
            LEFT JOIN register rm ON msp.mentor_id = rm.id 
            LEFT JOIN register rs ON msp.student_id = rs.id 
            ORDER BY msp.paired_at DESC
        ");
        fputcsv($output, ['=== MENTOR-STUDENT PAIRS (' . count($pairs) . ') ===']);
        fputcsv($output, ['ID', 'Mentor Name', 'Student Name', 'Status', 'Paired Date', 'Completed Date', 'Rating']);
        foreach ($pairs as $pair) {
            fputcsv($output, [
                $pair['id'],
                $pair['mentor_name'] ?? 'Unknown',
                $pair['student_name'] ?? 'Unknown',
                $pair['status'],
                $pair['paired_at'],
                $pair['completed_at'] ?? 'Ongoing',
                $pair['rating'] ?? 'Not rated'
            ]);
        }
        fputcsv($output, []);
    }

    // Support Tickets
    if (tableExists($conn, 'support_tickets')) {
        $tickets = safeQuery($conn, "SELECT * FROM support_tickets ORDER BY created_at DESC");
        fputcsv($output, ['=== SUPPORT TICKETS (' . count($tickets) . ') ===']);
        fputcsv($output, ['Ticket Number', 'Subadmin Name', 'Subject', 'Category', 'Priority', 'Status', 'Created', 'Resolved']);
        foreach ($tickets as $ticket) {
            fputcsv($output, [
                $ticket['ticket_number'],
                $ticket['subadmin_name'],
                $ticket['subject'],
                $ticket['category'],
                $ticket['priority'],
                $ticket['status'],
                $ticket['created_at'],
                $ticket['resolved_at'] ?? 'Not resolved'
            ]);
        }
        fputcsv($output, []);
    }

    // Bookmarks
    if (tableExists($conn, 'bookmark')) {
        $bookmarks = safeQuery($conn, "
            SELECT b.*, r.name as user_name, p.project_name 
            FROM bookmark b 
            LEFT JOIN register r ON b.user_id = r.id 
            LEFT JOIN projects p ON b.project_id = p.id 
            ORDER BY b.bookmarked_at DESC
        ");
        fputcsv($output, ['=== BOOKMARKS (' . count($bookmarks) . ') ===']);
        fputcsv($output, ['ID', 'User Name', 'Project Name', 'Bookmarked At']);
        foreach ($bookmarks as $bookmark) {
            fputcsv($output, [
                $bookmark['id'],
                $bookmark['user_name'] ?? 'Unknown',
                $bookmark['project_name'] ?? 'N/A',
                $bookmark['bookmarked_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Project Likes
    if (tableExists($conn, 'project_likes')) {
        $project_likes = safeQuery($conn, "
            SELECT pl.*, r.name as user_name, p.project_name 
            FROM project_likes pl 
            LEFT JOIN register r ON pl.user_id = r.id 
            LEFT JOIN projects p ON pl.project_id = p.id 
            ORDER BY pl.created_at DESC
        ");
        fputcsv($output, ['=== PROJECT LIKES (' . count($project_likes) . ') ===']);
        fputcsv($output, ['ID', 'User Name', 'Project Name', 'Liked At']);
        foreach ($project_likes as $like) {
            fputcsv($output, [
                $like['id'],
                $like['user_name'] ?? 'Unknown',
                $like['project_name'] ?? 'N/A',
                $like['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Project Comments
    if (tableExists($conn, 'project_comments')) {
        $project_comments = safeQuery($conn, "
            SELECT pc.*, r.name as user_name, p.project_name 
            FROM project_comments pc 
            LEFT JOIN register r ON pc.user_id = r.id 
            LEFT JOIN projects p ON pc.project_id = p.id 
            ORDER BY pc.created_at DESC
        ");
        fputcsv($output, ['=== PROJECT COMMENTS (' . count($project_comments) . ') ===']);
        fputcsv($output, ['ID', 'User Name', 'Project Name', 'Comment', 'Created At']);
        foreach ($project_comments as $comment) {
            fputcsv($output, [
                $comment['id'],
                $comment['user_name'] ?? 'Unknown',
                $comment['project_name'] ?? 'N/A',
                substr($comment['comment'] ?? '', 0, 200),
                $comment['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Idea Likes
    if (tableExists($conn, 'idea_likes')) {
        $idea_likes = safeQuery($conn, "
            SELECT il.*, r.name as user_name, b.project_name as idea_name 
            FROM idea_likes il 
            LEFT JOIN register r ON il.user_id = r.id 
            LEFT JOIN blog b ON il.idea_id = b.id 
            ORDER BY il.created_at DESC
        ");
        fputcsv($output, ['=== IDEA LIKES (' . count($idea_likes) . ') ===']);
        fputcsv($output, ['ID', 'User Name', 'Idea Name', 'Liked At']);
        foreach ($idea_likes as $like) {
            fputcsv($output, [
                $like['id'],
                $like['user_name'] ?? 'Unknown',
                $like['idea_name'] ?? 'N/A',
                $like['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Idea Comments
    if (tableExists($conn, 'idea_comments')) {
        $idea_comments = safeQuery($conn, "
            SELECT ic.*, r.name as user_name, b.project_name as idea_name 
            FROM idea_comments ic 
            LEFT JOIN register r ON ic.user_id = r.id 
            LEFT JOIN blog b ON ic.idea_id = b.id 
            ORDER BY ic.created_at DESC
        ");
        fputcsv($output, ['=== IDEA COMMENTS (' . count($idea_comments) . ') ===']);
        fputcsv($output, ['ID', 'User Name', 'Idea Name', 'Comment', 'Created At']);
        foreach ($idea_comments as $comment) {
            fputcsv($output, [
                $comment['id'],
                $comment['user_name'] ?? 'Unknown',
                $comment['idea_name'] ?? 'N/A',
                substr($comment['comment'] ?? '', 0, 200),
                $comment['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Idea Reports
    if (tableExists($conn, 'idea_reports')) {
        $idea_reports = safeQuery($conn, "
            SELECT ir.*, r.name as reporter_name, b.project_name as idea_name 
            FROM idea_reports ir 
            LEFT JOIN register r ON ir.user_id = r.id 
            LEFT JOIN blog b ON ir.idea_id = b.id 
            ORDER BY ir.created_at DESC
        ");
        fputcsv($output, ['=== IDEA REPORTS (' . count($idea_reports) . ') ===']);
        fputcsv($output, ['ID', 'Reporter Name', 'Idea Name', 'Reason', 'Status', 'Reported At']);
        foreach ($idea_reports as $report) {
            fputcsv($output, [
                $report['id'],
                $report['reporter_name'] ?? 'Unknown',
                $report['idea_name'] ?? 'N/A',
                $report['reason'] ?? 'N/A',
                $report['status'] ?? 'pending',
                $report['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Denied/Rejected Projects
    if (tableExists($conn, 'denial_projects')) {
        $denied_projects = safeQuery($conn, "
            SELECT dp.*, r.name as user_name, r.email as user_email 
            FROM denial_projects dp 
            LEFT JOIN register r ON dp.user_id = r.id 
            ORDER BY dp.rejection_date DESC
        ");
        fputcsv($output, ['=== DENIED PROJECTS (' . count($denied_projects) . ') ===']);
        fputcsv($output, ['ID', 'Project Name', 'User Name', 'User Email', 'Type', 'Rejection Reason', 'Rejection Date']);
        foreach ($denied_projects as $project) {
            fputcsv($output, [
                $project['id'],
                $project['project_name'],
                $project['user_name'] ?? 'Unknown',
                $project['user_email'] ?? 'N/A',
                $project['project_type'],
                substr($project['rejection_reason'] ?? '', 0, 200),
                $project['rejection_date']
            ]);
        }
        fputcsv($output, []);
    }

    // Mentor Requests
    if (tableExists($conn, 'mentor_requests')) {
        $mentor_requests = safeQuery($conn, "
            SELECT mr.*, 
                   rs.name as student_name, 
                   rm.name as mentor_name,
                   p.project_name
            FROM mentor_requests mr 
            LEFT JOIN register rs ON mr.student_id = rs.id 
            LEFT JOIN register rm ON mr.mentor_id = rm.id 
            LEFT JOIN projects p ON mr.project_id = p.id 
            ORDER BY mr.created_at DESC
        ");
        fputcsv($output, ['=== MENTOR REQUESTS (' . count($mentor_requests) . ') ===']);
        fputcsv($output, ['ID', 'Student Name', 'Mentor Name', 'Project Name', 'Message', 'Status', 'Created At', 'Updated At']);
        foreach ($mentor_requests as $request) {
            fputcsv($output, [
                $request['id'],
                $request['student_name'] ?? 'Unknown',
                $request['mentor_name'] ?? 'Unknown',
                $request['project_name'] ?? 'N/A',
                substr($request['message'] ?? '', 0, 200),
                $request['status'],
                $request['created_at'],
                $request['updated_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Mentoring Sessions
    if (tableExists($conn, 'mentoring_sessions')) {
        $sessions = safeQuery($conn, "
            SELECT ms.*, 
                   msp.mentor_id, 
                   msp.student_id,
                   rm.name as mentor_name,
                   rs.name as student_name
            FROM mentoring_sessions ms 
            LEFT JOIN mentor_student_pairs msp ON ms.pair_id = msp.id 
            LEFT JOIN register rm ON msp.mentor_id = rm.id 
            LEFT JOIN register rs ON msp.student_id = rs.id 
            ORDER BY ms.session_date DESC
        ");
        fputcsv($output, ['=== MENTORING SESSIONS (' . count($sessions) . ') ===']);
        fputcsv($output, ['ID', 'Mentor Name', 'Student Name', 'Session Date', 'Duration (min)', 'Status', 'Meeting Link', 'Notes']);
        foreach ($sessions as $session) {
            fputcsv($output, [
                $session['id'],
                $session['mentor_name'] ?? 'Unknown',
                $session['student_name'] ?? 'Unknown',
                $session['session_date'],
                $session['duration_minutes'] ?? 60,
                $session['status'],
                $session['meeting_link'] ?? 'N/A',
                substr($session['notes'] ?? '', 0, 200)
            ]);
        }
        fputcsv($output, []);
    }

    // Subadmin Classification Requests
    if (tableExists($conn, 'subadmin_classification_requests')) {
        $subadmin_requests = safeQuery($conn, "
            SELECT scr.*, s.email as subadmin_email 
            FROM subadmin_classification_requests scr 
            LEFT JOIN subadmins s ON scr.subadmin_id = s.id 
            ORDER BY scr.created_at DESC
        ");
        fputcsv($output, ['=== SUBADMIN CLASSIFICATION REQUESTS (' . count($subadmin_requests) . ') ===']);
        fputcsv($output, ['ID', 'Subadmin Email', 'Requested Domains', 'Status', 'Admin Comment', 'Created At', 'Decision Date']);
        foreach ($subadmin_requests as $request) {
            fputcsv($output, [
                $request['id'],
                $request['subadmin_email'] ?? 'Unknown',
                $request['requested_domains'] ?? 'N/A',
                $request['status'],
                $request['admin_comment'] ?? 'N/A',
                $request['created_at'],
                $request['decision_date'] ?? 'Pending'
            ]);
        }
        fputcsv($output, []);
    }

    // Notifications
    if (tableExists($conn, 'notifications')) {
        $notifications = safeQuery($conn, "
            SELECT n.*, r.name as user_name 
            FROM notifications n 
            LEFT JOIN register r ON n.user_id = r.id 
            ORDER BY n.created_at DESC 
            LIMIT 1000
        ");
        fputcsv($output, ['=== NOTIFICATIONS (Last 1000) (' . count($notifications) . ') ===']);
        fputcsv($output, ['ID', 'User Name', 'Type', 'Title', 'Message', 'Is Read', 'Created At']);
        foreach ($notifications as $notification) {
            fputcsv($output, [
                $notification['id'],
                $notification['user_name'] ?? 'Unknown',
                $notification['type'],
                $notification['title'],
                substr($notification['message'], 0, 200),
                $notification['is_read'] ? 'Yes' : 'No',
                $notification['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Notification Logs
    if (tableExists($conn, 'notification_logs')) {
        $notification_logs = safeQuery($conn, "
            SELECT * FROM notification_logs 
            ORDER BY created_at DESC 
            LIMIT 1000
        ");
        fputcsv($output, ['=== NOTIFICATION LOGS (Last 1000) (' . count($notification_logs) . ') ===']);
        fputcsv($output, ['ID', 'Type', 'User ID', 'Project ID', 'Status', 'Email To', 'Email Subject', 'Created At']);
        foreach ($notification_logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['type'],
                $log['user_id'] ?? 'N/A',
                $log['project_id'] ?? 'N/A',
                $log['status'],
                $log['email_to'] ?? 'N/A',
                $log['email_subject'] ?? 'N/A',
                $log['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Admin Logs
    if (tableExists($conn, 'admin_logs')) {
        $admin_logs = safeQuery($conn, "
            SELECT * FROM admin_logs 
            ORDER BY created_at DESC 
            LIMIT 1000
        ");
        fputcsv($output, ['=== ADMIN LOGS (Last 1000) (' . count($admin_logs) . ') ===']);
        fputcsv($output, ['ID', 'Action', 'Details', 'Admin ID', 'Created At']);
        foreach ($admin_logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['action'],
                substr($log['details'] ?? '', 0, 200),
                $log['admin_id'] ?? 'N/A',
                $log['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Mentor Activity Logs
    if (tableExists($conn, 'mentor_activity_logs')) {
        $mentor_logs = safeQuery($conn, "
            SELECT mal.*, r.name as mentor_name 
            FROM mentor_activity_logs mal 
            LEFT JOIN register r ON mal.mentor_id = r.id 
            ORDER BY mal.created_at DESC 
            LIMIT 1000
        ");
        fputcsv($output, ['=== MENTOR ACTIVITY LOGS (Last 1000) (' . count($mentor_logs) . ') ===']);
        fputcsv($output, ['ID', 'Mentor Name', 'Activity Type', 'Description', 'Student ID', 'Created At']);
        foreach ($mentor_logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['mentor_name'] ?? 'Unknown',
                $log['activity_type'],
                substr($log['description'], 0, 200),
                $log['student_id'] ?? 'N/A',
                $log['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Credentials (without passwords)
    if (tableExists($conn, 'credentials')) {
        $credentials = safeQuery($conn, "
            SELECT id, user_type, user_id, email, email_sent, created_at 
            FROM credentials 
            ORDER BY created_at DESC
        ");
        fputcsv($output, ['=== CREDENTIALS (Last 1000) (' . count($credentials) . ') ===']);
        fputcsv($output, ['ID', 'User Type', 'User ID', 'Email', 'Email Sent', 'Created At']);
        foreach ($credentials as $cred) {
            fputcsv($output, [
                $cred['id'],
                $cred['user_type'],
                $cred['user_id'],
                $cred['email'],
                $cred['email_sent'] ? 'Yes' : 'No',
                $cred['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Email Logs
    if (tableExists($conn, 'email_logs')) {
        $email_logs = safeQuery($conn, "
            SELECT * FROM email_logs 
            ORDER BY created_at DESC 
            LIMIT 1000
        ");
        fputcsv($output, ['=== EMAIL LOGS (Last 1000) (' . count($email_logs) . ') ===']);
        fputcsv($output, ['ID', 'Recipient', 'Subject', 'Type', 'Status', 'Error Message', 'Created At']);
        foreach ($email_logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['recipient'],
                $log['subject'],
                $log['type'],
                $log['status'],
                $log['error_message'] ?? 'N/A',
                $log['created_at']
            ]);
        }
        fputcsv($output, []);
    }

    // Summary
    fputcsv($output, ['=== EXPORT SUMMARY ===']);
    fputcsv($output, ['Total Users', count($users)]);
    fputcsv($output, ['Total Projects', count($projects)]);
    fputcsv($output, ['Total Admin Projects', count($admin_projects)]);
    fputcsv($output, ['Total Ideas', count($ideas)]);
    fputcsv($output, ['Total Subadmins', count($subadmins)]);
    fputcsv($output, ['Total Mentors', count($mentors)]);
    fputcsv($output, ['Total Mentor Pairs', count($pairs)]);
    fputcsv($output, ['Total Support Tickets', count($tickets)]);
    fputcsv($output, ['Total Bookmarks', count($bookmarks)]);
    fputcsv($output, ['Total Project Likes', count($project_likes)]);
    fputcsv($output, ['Total Project Comments', count($project_comments)]);
    fputcsv($output, ['Total Idea Likes', count($idea_likes)]);
    fputcsv($output, ['Total Idea Comments', count($idea_comments)]);
    fputcsv($output, ['Total Idea Reports', count($idea_reports)]);
    fputcsv($output, ['Total Denied Projects', count($denied_projects)]);
    fputcsv($output, ['Total Mentor Requests', count($mentor_requests)]);
    fputcsv($output, ['Total Mentoring Sessions', count($sessions)]);
    fputcsv($output, ['Total Subadmin Requests', count($subadmin_requests)]);
    fputcsv($output, ['Total Notifications', count($notifications)]);
    fputcsv($output, ['Total Notification Logs', count($notification_logs)]);
    fputcsv($output, ['Total Admin Logs', count($admin_logs)]);
    fputcsv($output, ['Total Mentor Logs', count($mentor_logs)]);
    fputcsv($output, ['Total Credentials', count($credentials)]);
    fputcsv($output, ['Total Email Logs', count($email_logs)]);
    fputcsv($output, ['Export Generated', date('Y-m-d H:i:s')]);
    fputcsv($output, ['Exported by', $_SESSION['admin_email'] ?? 'Admin']);

        fclose($output);
        exit();
        
    } catch (Exception $e) {
        // Log the error
        error_log("CSV Export Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Clear any output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Show error page
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Export Error</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-5">
                <div class="alert alert-danger">
                    <h4><i class="bi bi-exclamation-triangle"></i> Export Error</h4>
                    <p>An error occurred while generating the export file.</p>
                    <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                    <hr>
                    <p>Please check the error logs or contact the system administrator.</p>
                    <a href="export_overview.php" class="btn btn-primary">Back to Export Overview</a>
                </div>
            </div>
        </body>
        </html>';
        exit();
    }
}

// HTML fallback
?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Data Export</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/loader.css">
    <link rel="stylesheet" href="../assets/css/loading.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Comprehensive Data Export</h1>
        <p>Export all system data in CSV format.</p>
        <a href="?type=csv" class="btn btn-success">Download CSV Export</a>
        <a href="admin.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../assets/js/loader.js"></script>
<script src="../assets/js/loading.js"></script>
</body>
</html>