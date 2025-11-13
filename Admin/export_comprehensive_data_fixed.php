<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

try {
    require_once '../Login/Login/db.php';
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$export_type = $_GET['type'] ?? 'csv';

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

// Function to safely get data
function safeQuery($conn, $query) {
    try {
        $result = $conn->query($query);
        if ($result === false) {
            error_log("Query failed: " . $conn->error);
            return [];
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Query exception: " . $e->getMessage());
        return [];
    }
}

// Set headers for CSV download
if ($export_type === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ideanest_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, ['IdeaNest Comprehensive Data Export']);
    fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);

    // Users
    $users = safeQuery($conn, "SELECT * FROM register ORDER BY id");
    if (!empty($users)) {
        fputcsv($output, ['=== USERS ===']);
        fputcsv($output, ['ID', 'Name', 'Email', 'Department', 'Role', 'Created']);
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['department'] ?? 'N/A',
                $user['role'] ?? 'student',
                $user['created_at'] ?? 'N/A'
            ]);
        }
        fputcsv($output, []);
    }

    // Projects
    if (tableExists($conn, 'projects')) {
        $projects = safeQuery($conn, "
            SELECT p.*, r.name as user_name 
            FROM projects p 
            LEFT JOIN register r ON p.user_id = r.id 
            ORDER BY p.submission_date DESC
        ");
        if (!empty($projects)) {
            fputcsv($output, ['=== PROJECTS ===']);
            fputcsv($output, ['ID', 'Name', 'User', 'Type', 'Status', 'Date']);
            foreach ($projects as $project) {
                fputcsv($output, [
                    $project['id'],
                    $project['project_name'],
                    $project['user_name'] ?? 'Unknown',
                    $project['project_type'],
                    $project['status'],
                    $project['submission_date']
                ]);
            }
            fputcsv($output, []);
        }
    }

    // Ideas
    if (tableExists($conn, 'blog')) {
        $ideas = safeQuery($conn, "
            SELECT b.*, r.name as user_name 
            FROM blog b 
            LEFT JOIN register r ON b.user_id = r.id 
            ORDER BY b.submission_datetime DESC
        ");
        if (!empty($ideas)) {
            fputcsv($output, ['=== IDEAS ===']);
            fputcsv($output, ['ID', 'Name', 'User', 'Type', 'Status', 'Date']);
            foreach ($ideas as $idea) {
                fputcsv($output, [
                    $idea['id'],
                    $idea['project_name'],
                    $idea['user_name'] ?? 'Unknown',
                    $idea['project_type'],
                    $idea['status'],
                    $idea['submission_datetime']
                ]);
            }
            fputcsv($output, []);
        }
    }

    // Subadmins
    if (tableExists($conn, 'subadmins')) {
        $subadmins = safeQuery($conn, "SELECT * FROM subadmins ORDER BY created_at DESC");
        if (!empty($subadmins)) {
            fputcsv($output, ['=== SUBADMINS ===']);
            fputcsv($output, ['ID', 'Name', 'Email', 'Domain', 'Status']);
            foreach ($subadmins as $subadmin) {
                fputcsv($output, [
                    $subadmin['id'],
                    $subadmin['name'],
                    $subadmin['email'],
                    $subadmin['domain'] ?? 'N/A',
                    $subadmin['status']
                ]);
            }
            fputcsv($output, []);
        }
    }

    // Mentors
    $mentors = safeQuery($conn, "SELECT * FROM register WHERE role = 'mentor' ORDER BY id");
    if (!empty($mentors)) {
        fputcsv($output, ['=== MENTORS ===']);
        fputcsv($output, ['ID', 'Name', 'Email', 'Department', 'Expertise']);
        foreach ($mentors as $mentor) {
            fputcsv($output, [
                $mentor['id'],
                $mentor['name'],
                $mentor['email'],
                $mentor['department'] ?? 'N/A',
                $mentor['expertise'] ?? 'N/A'
            ]);
        }
        fputcsv($output, []);
    }

    // Summary
    fputcsv($output, ['=== SUMMARY ===']);
    fputcsv($output, ['Total Users', count($users)]);
    fputcsv($output, ['Total Projects', count($projects ?? [])]);
    fputcsv($output, ['Total Ideas', count($ideas ?? [])]);
    fputcsv($output, ['Total Subadmins', count($subadmins ?? [])]);
    fputcsv($output, ['Total Mentors', count($mentors)]);
    fputcsv($output, ['Export Date', date('Y-m-d H:i:s')]);

    fclose($output);
    exit();
}

// HTML fallback
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Export</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Data Export</h1>
        <p>Choose export format:</p>
        <a href="?type=csv" class="btn btn-success">Download CSV</a>
        <a href="export_all_data.php" class="btn btn-primary">Alternative Export</a>
    </div>
</body>
</html>