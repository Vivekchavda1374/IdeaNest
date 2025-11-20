<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../Login/Login/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$export_type = $_GET['type'] ?? 'csv';

// Function to check if table exists
function tableExists($conn, $tableName) {
    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
$stmt->bind_param("s", $tableName);
$stmt->execute();
$result = $stmt->get_result();
    return $result && $result->num_rows > 0;
}

// Function to safely get data
function safeQuery($conn, $query) {
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

if ($export_type === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ideanest_comprehensive_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, ['IdeaNest Comprehensive Data Export']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);

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
        fputcsv($output, ['ID', 'Name', 'Email', 'Domain', 'Domains', 'Status', 'Profile Complete', 'Created']);
        foreach ($subadmins as $subadmin) {
            fputcsv($output, [
                $subadmin['id'],
                $subadmin['name'],
                $subadmin['email'],
                $subadmin['domain'] ?? 'N/A',
                $subadmin['domains'] ?? 'N/A',
                $subadmin['status'],
                $subadmin['profile_complete'] ? 'Yes' : 'No',
                $subadmin['created_at']
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

    // Summary
    fputcsv($output, ['=== EXPORT SUMMARY ===']);
    fputcsv($output, ['Total Users', count($users)]);
    fputcsv($output, ['Total Projects', count($projects ?? [])]);
    fputcsv($output, ['Total Admin Projects', count($admin_projects ?? [])]);
    fputcsv($output, ['Total Ideas', count($ideas ?? [])]);
    fputcsv($output, ['Total Subadmins', count($subadmins ?? [])]);
    fputcsv($output, ['Total Mentors', count($mentors)]);
    fputcsv($output, ['Total Mentor Pairs', count($pairs ?? [])]);
    fputcsv($output, ['Total Support Tickets', count($tickets ?? [])]);
    fputcsv($output, ['Export Generated', date('Y-m-d H:i:s')]);

    fclose($output);
    exit();
}

// HTML fallback
?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Data Export</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Comprehensive Data Export</h1>
        <p>Export all system data in CSV format.</p>
        <a href="?type=csv" class="btn btn-success">Download CSV Export</a>
        <a href="admin.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>