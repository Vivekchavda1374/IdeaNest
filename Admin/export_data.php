<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="admin_overview_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Admin Overview Report - ' . date('Y-m-d H:i:s')]);
fputcsv($output, []);

// Statistics
fputcsv($output, ['SYSTEM STATISTICS']);
fputcsv($output, ['Metric', 'Value']);

$stats_query = "SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN role = 'student' THEN 1 END) as students,
    COUNT(CASE WHEN role = 'mentor' THEN 1 END) as mentors
    FROM register";
$stats = $conn->query($stats_query)->fetch_assoc();

$subadmin_count = $conn->query("SELECT COUNT(*) as count FROM subadmins")->fetch_assoc();
$stats['subadmins'] = $subadmin_count['count'];

fputcsv($output, ['Total Users', $stats['total_users']]);
fputcsv($output, ['Students', $stats['students']]);
fputcsv($output, ['Mentors', $stats['mentors']]);
fputcsv($output, ['Subadmins', $stats['subadmins']]);

// Projects
$project_stats = $conn->query("SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
    FROM projects")->fetch_assoc();

fputcsv($output, ['Total Projects', $project_stats['total']]);
fputcsv($output, ['Pending Projects', $project_stats['pending']]);
fputcsv($output, ['Approved Projects', $project_stats['approved']]);
fputcsv($output, ['Rejected Projects', $project_stats['rejected']]);

fputcsv($output, []);
fputcsv($output, ['USER DETAILS']);
fputcsv($output, ['ID', 'Name', 'Email', 'Role', 'Department']);

$users = $conn->query("SELECT * FROM register ORDER BY id DESC");
while ($user = $users->fetch_assoc()) {
    fputcsv($output, [
        $user['id'],
        $user['name'],
        $user['email'],
        ucfirst($user['role']),
        $user['department']
    ]);
}

fclose($output);
exit();
?>