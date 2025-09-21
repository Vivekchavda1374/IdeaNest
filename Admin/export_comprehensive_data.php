<?php

session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Set admin_id for compatibility
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1;
}

$export_type = $_GET['type'] ?? 'csv';

// Set headers for CSV download
if ($export_type !== 'html') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ideanest_comprehensive_export_' . date('Y-m-d_H-i-s') . '.csv"');
    $output = fopen('php://output', 'w');

    // Add header with export info
    fputcsv($output, ['IdeaNest Comprehensive Data Export']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, ['Export Type: ' . ucfirst($export_type)]);
    fputcsv($output, []);
}

// Function to safely get data
function safeQuery($conn, $query)
{
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Export Users and Activities
if ($export_type === 'csv' || $export_type === 'users') {
    // Users with detailed information
    $users = safeQuery($conn, "
        SELECT r.*, 
               COUNT(DISTINCT p.id) as submitted_projects,
               COUNT(DISTINCT ap.id) as approved_projects,
               COUNT(DISTINCT b.id) as submitted_ideas,
               COUNT(DISTINCT pl.id) as project_likes,
               COUNT(DISTINCT bl.id) as idea_likes,
               COUNT(DISTINCT bm.id) as bookmarks
        FROM register r
        LEFT JOIN projects p ON r.id = p.user_id
        LEFT JOIN admin_approved_projects ap ON r.id = ap.user_id
        LEFT JOIN blog b ON r.id = b.user_id
        LEFT JOIN project_likes pl ON r.id = pl.user_id
        LEFT JOIN idea_likes bl ON r.id = bl.user_id
        LEFT JOIN bookmark bm ON r.id = bm.user_id
        GROUP BY r.id
        ORDER BY r.id
    ");

    if ($export_type !== 'html') {
        fputcsv($output, ['=== USER PROFILES & ACTIVITIES ===']);
        fputcsv($output, ['ID', 'Name', 'Email', 'Enrollment', 'Department', 'Role', 'Passout Year', 'Phone', 'About', 'Submitted Projects', 'Approved Projects', 'Ideas Submitted', 'Project Likes Given', 'Idea Likes Given', 'Bookmarks', 'Email Notifications', 'GitHub Token Status']);

        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['enrollment_number'],
                $user['department'] ?? 'N/A',
                ucfirst($user['role']),
                $user['passout_year'],
                $user['phone_no'] ?? 'N/A',
                substr($user['about'], 0, 100) . (strlen($user['about']) > 100 ? '...' : ''),
                $user['submitted_projects'],
                $user['approved_projects'],
                $user['submitted_ideas'],
                $user['project_likes'],
                $user['idea_likes'],
                $user['bookmarks'],
                $user['email_notifications'] ? 'Enabled' : 'Disabled',
                !empty($user['github_token']) ? 'Connected' : 'Not Connected'
            ]);
        }
        fputcsv($output, []);
    }
}

// Export Projects and Ideas
if ($export_type === 'csv' || $export_type === 'projects') {
    // All project submissions
    $projects = safeQuery($conn, "
        SELECT p.*, r.name as user_name, r.email as user_email, r.department,
               COUNT(DISTINCT pl.id) as likes_count,
               COUNT(DISTINCT pc.id) as comments_count,
               COUNT(DISTINCT bm.id) as bookmarks_count
        FROM projects p
        LEFT JOIN register r ON p.user_id = r.id
        LEFT JOIN project_likes pl ON p.id = pl.project_id
        LEFT JOIN project_comments pc ON p.id = pc.project_id
        LEFT JOIN bookmark bm ON p.id = bm.project_id
        GROUP BY p.id
        ORDER BY p.submission_date DESC
    ");

    // Admin approved projects
    $admin_projects = safeQuery($conn, "
        SELECT ap.*, r.name as user_name, r.email as user_email, r.department,
               COUNT(DISTINCT pl.id) as likes_count,
               COUNT(DISTINCT pc.id) as comments_count,
               COUNT(DISTINCT bm.id) as bookmarks_count
        FROM admin_approved_projects ap
        LEFT JOIN register r ON ap.user_id = r.id
        LEFT JOIN project_likes pl ON ap.id = pl.project_id
        LEFT JOIN project_comments pc ON ap.id = pc.project_id
        LEFT JOIN bookmark bm ON ap.id = bm.project_id
        GROUP BY ap.id
        ORDER BY ap.submission_date DESC
    ");

    // Project ideas from blog
    $ideas = safeQuery($conn, "
        SELECT b.*, r.name as user_name, r.email as user_email, r.department,
               COUNT(DISTINCT il.id) as likes_count,
               COUNT(DISTINCT ic.id) as comments_count
        FROM blog b
        LEFT JOIN register r ON b.user_id = r.id
        LEFT JOIN idea_likes il ON b.id = il.idea_id
        LEFT JOIN idea_comments ic ON b.id = ic.idea_id
        GROUP BY b.id
        ORDER BY b.submission_datetime DESC
    ");

    if ($export_type !== 'html') {
        // Project submissions
        fputcsv($output, ['=== PROJECT SUBMISSIONS ===']);
        fputcsv($output, ['ID', 'Project Name', 'User Name', 'User Email', 'Department', 'Type', 'Classification', 'Category', 'Difficulty', 'Development Time', 'Team Size', 'Description', 'Language', 'Status', 'Submission Date', 'Likes', 'Comments', 'Bookmarks', 'GitHub Repo', 'Live Demo']);

        foreach ($projects as $project) {
            fputcsv($output, [
                $project['id'],
                $project['project_name'],
                $project['user_name'] ?? 'Unknown',
                $project['user_email'] ?? 'N/A',
                $project['department'] ?? 'N/A',
                $project['project_type'],
                $project['classification'] ?? 'N/A',
                $project['project_category'] ?? 'N/A',
                $project['difficulty_level'] ?? 'N/A',
                $project['development_time'] ?? 'N/A',
                $project['team_size'] ?? 'N/A',
                substr($project['description'], 0, 200) . (strlen($project['description']) > 200 ? '...' : ''),
                $project['language'],
                ucfirst($project['status']),
                $project['submission_date'],
                $project['likes_count'],
                $project['comments_count'],
                $project['bookmarks_count'],
                $project['github_repo'] ?? 'N/A',
                $project['live_demo_url'] ?? 'N/A'
            ]);
        }
        fputcsv($output, []);

        // Admin approved projects
        fputcsv($output, ['=== ADMIN APPROVED PROJECTS ===']);
        fputcsv($output, ['ID', 'Project Name', 'User Name', 'User Email', 'Department', 'Type', 'Classification', 'Category', 'Difficulty', 'Development Time', 'Team Size', 'Description', 'Language', 'Status', 'Approval Date', 'Likes', 'Comments', 'Bookmarks', 'GitHub Repo', 'Live Demo']);

        foreach ($admin_projects as $project) {
            fputcsv($output, [
                $project['id'],
                $project['project_name'],
                $project['user_name'] ?? 'Unknown',
                $project['user_email'] ?? 'N/A',
                $project['department'] ?? 'N/A',
                $project['project_type'],
                $project['classification'] ?? 'N/A',
                $project['project_category'] ?? 'N/A',
                $project['difficulty_level'] ?? 'N/A',
                $project['development_time'] ?? 'N/A',
                $project['team_size'] ?? 'N/A',
                substr($project['description'], 0, 200) . (strlen($project['description']) > 200 ? '...' : ''),
                $project['language'],
                ucfirst($project['status']),
                $project['submission_date'],
                $project['likes_count'],
                $project['comments_count'],
                $project['bookmarks_count'],
                $project['github_repo'] ?? 'N/A',
                $project['live_demo_url'] ?? 'N/A'
            ]);
        }
        fputcsv($output, []);

        // Project ideas
        fputcsv($output, ['=== PROJECT IDEAS ===']);
        fputcsv($output, ['ID', 'Idea Name', 'User Name', 'User Email', 'Department', 'Type', 'Classification', 'Description', 'Status', 'Priority', 'Assigned To', 'Submission Date', 'Completion Date', 'Likes', 'Comments']);

        foreach ($ideas as $idea) {
            fputcsv($output, [
                $idea['id'],
                $idea['project_name'],
                $idea['user_name'] ?? 'Unknown',
                $idea['user_email'] ?? 'N/A',
                $idea['department'] ?? 'N/A',
                $idea['project_type'],
                $idea['classification'],
                substr($idea['description'], 0, 200) . (strlen($idea['description']) > 200 ? '...' : ''),
                ucfirst($idea['status']),
                ucfirst($idea['priority1']),
                $idea['assigned_to'] ?? 'Unassigned',
                $idea['submission_datetime'],
                $idea['completion_date'] ?? 'N/A',
                $idea['likes_count'],
                $idea['comments_count']
            ]);
        }
        fputcsv($output, []);
    }
}

// Export Subadmin Activities
if ($export_type === 'csv' || $export_type === 'subadmins') {
    // Subadmin details
    $subadmins = safeQuery($conn, "
        SELECT s.*,
               COUNT(DISTINCT scr.id) as total_requests,
               COUNT(DISTINCT CASE WHEN scr.status = 'approved' THEN scr.id END) as approved_requests,
               COUNT(DISTINCT st.id) as support_tickets
        FROM subadmins s
        LEFT JOIN subadmin_classification_requests scr ON s.id = scr.subadmin_id
        LEFT JOIN support_tickets st ON s.id = st.subadmin_id
        GROUP BY s.id
        ORDER BY s.created_at DESC
    ");

    // Subadmin classification requests
    $subadmin_requests = safeQuery($conn, "
        SELECT scr.*, s.name as subadmin_name, s.email as subadmin_email
        FROM subadmin_classification_requests scr
        LEFT JOIN subadmins s ON scr.subadmin_id = s.id
        ORDER BY scr.request_date DESC
    ");

    // Support tickets
    $support_tickets = safeQuery($conn, "
        SELECT st.*, 
               COUNT(str.id) as reply_count
        FROM support_tickets st
        LEFT JOIN support_ticket_replies str ON st.id = str.ticket_id
        GROUP BY st.id
        ORDER BY st.created_at DESC
    ");

    if ($export_type !== 'html') {
        // Subadmin profiles
        fputcsv($output, ['=== SUBADMIN PROFILES ===']);
        fputcsv($output, ['ID', 'Name', 'Email', 'Domain', 'Software Classification', 'Hardware Classification', 'Status', 'Profile Complete', 'Total Requests', 'Approved Requests', 'Support Tickets', 'Created Date', 'Last Login']);

        foreach ($subadmins as $subadmin) {
            fputcsv($output, [
                $subadmin['id'],
                $subadmin['name'] ?? 'N/A',
                $subadmin['email'],
                $subadmin['domain'] ?? 'N/A',
                $subadmin['software_classification'] ?? 'N/A',
                $subadmin['hardware_classification'] ?? 'N/A',
                ucfirst($subadmin['status']),
                $subadmin['profile_complete'] ? 'Yes' : 'No',
                $subadmin['total_requests'],
                $subadmin['approved_requests'],
                $subadmin['support_tickets'],
                $subadmin['created_at'],
                $subadmin['last_login'] ?? 'Never'
            ]);
        }
        fputcsv($output, []);

        // Classification requests
        fputcsv($output, ['=== SUBADMIN CLASSIFICATION REQUESTS ===']);
        fputcsv($output, ['ID', 'Subadmin Name', 'Subadmin Email', 'Software Classification', 'Hardware Classification', 'Status', 'Request Date', 'Decision Date', 'Admin Comment']);

        foreach ($subadmin_requests as $request) {
            fputcsv($output, [
                $request['id'],
                $request['subadmin_name'] ?? 'Unknown',
                $request['subadmin_email'] ?? 'N/A',
                $request['requested_software_classification'] ?? 'N/A',
                $request['requested_hardware_classification'] ?? 'N/A',
                ucfirst($request['status']),
                $request['request_date'],
                $request['decision_date'] ?? 'Pending',
                $request['admin_comment'] ?? 'N/A'
            ]);
        }
        fputcsv($output, []);

        // Support tickets
        fputcsv($output, ['=== SUPPORT TICKETS ===']);
        fputcsv($output, ['Ticket Number', 'Subadmin Name', 'Subject', 'Category', 'Priority', 'Status', 'Message', 'Admin Response', 'Reply Count', 'Created Date', 'Resolved Date']);

        foreach ($support_tickets as $ticket) {
            fputcsv($output, [
                $ticket['ticket_number'],
                $ticket['subadmin_name'],
                $ticket['subject'],
                ucfirst($ticket['category']),
                ucfirst($ticket['priority']),
                ucfirst($ticket['status']),
                substr($ticket['message'], 0, 100) . (strlen($ticket['message']) > 100 ? '...' : ''),
                substr($ticket['admin_response'] ?? 'No response', 0, 100),
                $ticket['reply_count'],
                $ticket['created_at'],
                $ticket['resolved_at'] ?? 'Not resolved'
            ]);
        }
        fputcsv($output, []);
    }
}

// Export Mentor Activities
if ($export_type === 'csv' || $export_type === 'mentors') {
    // Mentor details with activity
    $mentors = safeQuery($conn, "
        SELECT r.*, m.*,
               COUNT(DISTINCT msp.id) as total_pairings,
               COUNT(DISTINCT CASE WHEN msp.status = 'completed' THEN msp.id END) as completed_pairings,
               COUNT(DISTINCT ms.id) as total_sessions,
               COUNT(DISTINCT CASE WHEN ms.status = 'completed' THEN ms.id END) as completed_sessions,
               COUNT(DISTINCT mal.id) as activity_logs,
               AVG(msp.rating) as average_rating
        FROM register r
        INNER JOIN mentors m ON r.id = m.user_id
        LEFT JOIN mentor_student_pairs msp ON r.id = msp.mentor_id
        LEFT JOIN mentoring_sessions ms ON msp.id = ms.pair_id
        LEFT JOIN mentor_activity_logs mal ON r.id = mal.mentor_id
        GROUP BY r.id
        ORDER BY r.id
    ");

    // Mentor-student pairings
    $mentor_pairings = safeQuery($conn, "
        SELECT msp.*, 
               rm.name as mentor_name, rm.email as mentor_email,
               rs.name as student_name, rs.email as student_email,
               COUNT(ms.id) as session_count
        FROM mentor_student_pairs msp
        LEFT JOIN register rm ON msp.mentor_id = rm.id
        LEFT JOIN register rs ON msp.student_id = rs.id
        LEFT JOIN mentoring_sessions ms ON msp.id = ms.pair_id
        GROUP BY msp.id
        ORDER BY msp.paired_at DESC
    ");

    // Mentoring sessions
    $mentoring_sessions = safeQuery($conn, "
        SELECT ms.*, msp.mentor_id, msp.student_id,
               rm.name as mentor_name,
               rs.name as student_name
        FROM mentoring_sessions ms
        LEFT JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
        LEFT JOIN register rm ON msp.mentor_id = rm.id
        LEFT JOIN register rs ON msp.student_id = rs.id
        ORDER BY ms.session_date DESC
    ");

    // Mentor activity logs
    $mentor_activities = safeQuery($conn, "
        SELECT mal.*, r.name as mentor_name, rs.name as student_name
        FROM mentor_activity_logs mal
        LEFT JOIN register r ON mal.mentor_id = r.id
        LEFT JOIN register rs ON mal.student_id = rs.id
        ORDER BY mal.created_at DESC
    ");

    if ($export_type !== 'html') {
        // Mentor profiles
        fputcsv($output, ['=== MENTOR PROFILES ===']);
        fputcsv($output, ['ID', 'Name', 'Email', 'Department', 'Specialization', 'Experience Years', 'Max Students', 'Current Students', 'Bio', 'LinkedIn', 'GitHub', 'Total Pairings', 'Completed Pairings', 'Total Sessions', 'Completed Sessions', 'Activity Logs', 'Average Rating']);

        foreach ($mentors as $mentor) {
            fputcsv($output, [
                $mentor['id'],
                $mentor['name'],
                $mentor['email'],
                $mentor['department'] ?? 'N/A',
                $mentor['specialization'],
                $mentor['experience_years'],
                $mentor['max_students'],
                $mentor['current_students'],
                substr($mentor['bio'] ?? '', 0, 100),
                $mentor['linkedin_url'] ?? 'N/A',
                $mentor['github_url'] ?? 'N/A',
                $mentor['total_pairings'],
                $mentor['completed_pairings'],
                $mentor['total_sessions'],
                $mentor['completed_sessions'],
                $mentor['activity_logs'],
                round($mentor['average_rating'] ?? 0, 2)
            ]);
        }
        fputcsv($output, []);

        // Mentor-student pairings
        fputcsv($output, ['=== MENTOR-STUDENT PAIRINGS ===']);
        fputcsv($output, ['ID', 'Mentor Name', 'Mentor Email', 'Student Name', 'Student Email', 'Status', 'Paired Date', 'Completed Date', 'Rating', 'Feedback', 'Session Count']);

        foreach ($mentor_pairings as $pairing) {
            fputcsv($output, [
                $pairing['id'],
                $pairing['mentor_name'] ?? 'Unknown',
                $pairing['mentor_email'] ?? 'N/A',
                $pairing['student_name'] ?? 'Unknown',
                $pairing['student_email'] ?? 'N/A',
                ucfirst($pairing['status']),
                $pairing['paired_at'],
                $pairing['completed_at'] ?? 'Ongoing',
                $pairing['rating'] ?? 'Not rated',
                substr($pairing['feedback'] ?? 'No feedback', 0, 100),
                $pairing['session_count']
            ]);
        }
        fputcsv($output, []);

        // Mentoring sessions
        fputcsv($output, ['=== MENTORING SESSIONS ===']);
        fputcsv($output, ['ID', 'Mentor Name', 'Student Name', 'Session Date', 'Duration (minutes)', 'Status', 'Notes', 'Meeting Link', 'Created Date']);

        foreach ($mentoring_sessions as $session) {
            fputcsv($output, [
                $session['id'],
                $session['mentor_name'] ?? 'Unknown',
                $session['student_name'] ?? 'Unknown',
                $session['session_date'],
                $session['duration_minutes'],
                ucfirst($session['status']),
                substr($session['notes'] ?? 'No notes', 0, 100),
                $session['meeting_link'] ?? 'N/A',
                $session['created_at']
            ]);
        }
        fputcsv($output, []);

        // Mentor activity logs
        fputcsv($output, ['=== MENTOR ACTIVITY LOGS ===']);
        fputcsv($output, ['ID', 'Mentor Name', 'Activity Type', 'Description', 'Student Name', 'Date']);

        foreach ($mentor_activities as $activity) {
            fputcsv($output, [
                $activity['id'],
                $activity['mentor_name'] ?? 'Unknown',
                $activity['activity_type'],
                $activity['description'],
                $activity['student_name'] ?? 'N/A',
                $activity['created_at']
            ]);
        }
        fputcsv($output, []);
    }
}

// Export all remaining tables for complete database export
if ($export_type === 'csv' || $export_type === 'all') {
    // All remaining database tables
    $comment_likes = safeQuery($conn, "SELECT cl.*, pc.comment_text, r.name as user_name FROM comment_likes cl LEFT JOIN project_comments pc ON cl.comment_id = pc.id LEFT JOIN register r ON cl.user_id = r.id ORDER BY cl.created_at DESC");
    $deleted_ideas = safeQuery($conn, "SELECT di.*, r.name as user_name FROM deleted_ideas di LEFT JOIN register r ON di.user_id = r.id ORDER BY di.deleted_at DESC");
    $github_data = safeQuery($conn, "SELECT r.name, r.email, r.github_token FROM register r WHERE r.github_token IS NOT NULL");
    $admin_logs = safeQuery($conn, "SELECT * FROM admin_logs ORDER BY created_at DESC");
    $admin_settings = safeQuery($conn, "SELECT * FROM admin_settings ORDER BY setting_key");
    $mentor_activity_logs = safeQuery($conn, "SELECT mal.*, rm.name as mentor_name, rs.name as student_name FROM mentor_activity_logs mal LEFT JOIN register rm ON mal.mentor_id = rm.id LEFT JOIN register rs ON mal.student_id = rs.id ORDER BY mal.created_at DESC");
    $mentor_email_logs = safeQuery($conn, "SELECT mel.*, rm.name as mentor_name, rr.name as recipient_name FROM mentor_email_logs mel LEFT JOIN register rm ON mel.mentor_id = rm.id LEFT JOIN register rr ON mel.recipient_id = rr.id ORDER BY mel.sent_at DESC");
    $student_email_preferences = safeQuery($conn, "SELECT sep.*, r.name as student_name FROM student_email_preferences sep LEFT JOIN register r ON sep.student_id = r.id");
    $temp_project_ownership = safeQuery($conn, "SELECT * FROM temp_project_ownership ORDER BY created_at DESC LIMIT 1000");
    $user_activity_log = safeQuery($conn, "SELECT ual.*, r.name as user_name FROM user_activity_log ual LEFT JOIN register r ON ual.user_id = r.id ORDER BY ual.timestamp DESC LIMIT 1000");
    $realtime_notifications = safeQuery($conn, "SELECT rn.*, r.name as user_name FROM realtime_notifications rn LEFT JOIN register r ON rn.user_id = r.id ORDER BY rn.created_at DESC LIMIT 1000");
    $notification_templates = safeQuery($conn, "SELECT * FROM notification_templates ORDER BY type");
    $notification_counters = safeQuery($conn, "SELECT * FROM notification_counters ORDER BY type, status");
    $mentor_project_access = safeQuery($conn, "SELECT mpa.*, rm.name as mentor_name, rs.name as student_name FROM mentor_project_access mpa LEFT JOIN register rm ON mpa.mentor_id = rm.id LEFT JOIN register rs ON mpa.student_id = rs.id ORDER BY mpa.granted_at DESC");
    $removed_users = safeQuery($conn, "SELECT * FROM removed_user ORDER BY id DESC");

    if ($export_type !== 'html') {
        // Comment likes
        fputcsv($output, ['=== COMMENT LIKES ===']);
        fputcsv($output, ['ID', 'Comment Text', 'User Name', 'Created Date']);
        foreach ($comment_likes as $like) {
            fputcsv($output, [
                $like['id'],
                substr($like['comment_text'] ?? 'N/A', 0, 100),
                $like['user_name'] ?? 'Unknown',
                $like['created_at']
            ]);
        }
        fputcsv($output, []);

        // Admin logs
        fputcsv($output, ['=== ADMIN LOGS ===']);
        fputcsv($output, ['ID', 'Action', 'Details', 'Admin ID', 'Date']);
        foreach ($admin_logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['action'],
                substr($log['details'] ?? '', 0, 200),
                $log['admin_id'] ?? 'System',
                $log['created_at']
            ]);
        }
        fputcsv($output, []);

        // Admin settings
        fputcsv($output, ['=== ADMIN SETTINGS ===']);
        fputcsv($output, ['Setting Key', 'Setting Value', 'Type', 'Updated']);
        foreach ($admin_settings as $setting) {
            fputcsv($output, [
                $setting['setting_key'],
                substr($setting['setting_value'] ?? '', 0, 100),
                $setting['setting_type'],
                $setting['updated_at']
            ]);
        }
        fputcsv($output, []);

        // GitHub connected users
        fputcsv($output, ['=== GITHUB CONNECTED USERS ===']);
        fputcsv($output, ['Name', 'Email', 'GitHub Status']);
        foreach ($github_data as $github) {
            fputcsv($output, [
                $github['name'],
                $github['email'],
                'Connected'
            ]);
        }
        fputcsv($output, []);

        // Student email preferences
        fputcsv($output, ['=== STUDENT EMAIL PREFERENCES ===']);
        fputcsv($output, ['Student Name', 'Session Reminders', 'Progress Updates', 'Project Feedback', 'Welcome Emails']);
        foreach ($student_email_preferences as $pref) {
            fputcsv($output, [
                $pref['student_name'] ?? 'Unknown',
                $pref['receive_session_reminders'] ? 'Yes' : 'No',
                $pref['receive_progress_updates'] ? 'Yes' : 'No',
                $pref['receive_project_feedback'] ? 'Yes' : 'No',
                $pref['receive_welcome_emails'] ? 'Yes' : 'No'
            ]);
        }
        fputcsv($output, []);

        // Mentor project access
        fputcsv($output, ['=== MENTOR PROJECT ACCESS ===']);
        fputcsv($output, ['ID', 'Mentor Name', 'Student Name', 'Project ID', 'Granted Date']);
        foreach ($mentor_project_access as $access) {
            fputcsv($output, [
                $access['id'],
                $access['mentor_name'] ?? 'Unknown',
                $access['student_name'] ?? 'Unknown',
                $access['project_id'],
                $access['granted_at']
            ]);
        }
        fputcsv($output, []);
    }
}

// Additional system data for complete export
if ($export_type === 'csv' || $export_type === 'all') {
    // System notifications
    $notifications = safeQuery($conn, "
        SELECT nl.*, r.name as user_name
        FROM notification_logs nl
        LEFT JOIN register r ON nl.user_id = r.id
        ORDER BY nl.created_at DESC
        LIMIT 1000
    ");

    // Project interactions (likes, comments, bookmarks)
    $interactions = safeQuery($conn, "
        SELECT 'project_like' as type, pl.project_id as item_id, pl.user_id, pl.created_at, r.name as user_name
        FROM project_likes pl
        LEFT JOIN register r ON pl.user_id = r.id
        UNION ALL
        SELECT 'idea_like' as type, il.idea_id as item_id, il.user_id, il.created_at, r.name as user_name
        FROM idea_likes il
        LEFT JOIN register r ON il.user_id = r.id
        UNION ALL
        SELECT 'bookmark' as type, b.project_id as item_id, b.user_id, b.bookmarked_at as created_at, r.name as user_name
        FROM bookmark b
        LEFT JOIN register r ON b.user_id = r.id
        ORDER BY created_at DESC
        LIMIT 1000
    ");

    // System notifications
    fputcsv($output, ['=== SYSTEM NOTIFICATIONS ===']);
    fputcsv($output, ['ID', 'Type', 'User Name', 'Status', 'Email To', 'Subject', 'Date', 'Error Message']);

    foreach ($notifications as $notification) {
        fputcsv($output, [
            $notification['id'],
            $notification['type'],
            $notification['user_name'] ?? 'System',
            ucfirst($notification['status']),
            $notification['email_to'] ?? 'N/A',
            substr($notification['email_subject'] ?? 'N/A', 0, 50),
            $notification['created_at'],
            substr($notification['error_message'] ?? 'None', 0, 100)
        ]);
    }
    fputcsv($output, []);

    // User interactions
    fputcsv($output, ['=== USER INTERACTIONS ===']);
    fputcsv($output, ['Type', 'Item ID', 'User Name', 'Date']);

    foreach ($interactions as $interaction) {
        fputcsv($output, [
            ucfirst(str_replace('_', ' ', $interaction['type'])),
            $interaction['item_id'],
            $interaction['user_name'] ?? 'Unknown',
            $interaction['created_at']
        ]);
    }
    fputcsv($output, []);

    // Export summary
    fputcsv($output, ['=== EXPORT SUMMARY ===']);
    fputcsv($output, ['Total Users', count($users ?? [])]);
    fputcsv($output, ['Total Projects', count($projects ?? [])]);
    fputcsv($output, ['Total Admin Projects', count($admin_projects ?? [])]);
    fputcsv($output, ['Total Ideas', count($ideas ?? [])]);
    fputcsv($output, ['Total Subadmins', count($subadmins ?? [])]);
    fputcsv($output, ['Total Mentors', count($mentors ?? [])]);
    fputcsv($output, ['Total Notifications', count($notifications)]);
    fputcsv($output, ['Total Interactions', count($interactions)]);
    fputcsv($output, ['Export Generated', date('Y-m-d H:i:s')]);
    fputcsv($output, ['Database Tables Exported', '30+ tables']);
    fputcsv($output, ['Export Type', $export_type === 'csv' ? 'Complete System Export' : ucfirst($export_type) . ' Export']);
}

if ($export_type !== 'html') {
    fclose($output);
    exit();
}
