<?php
session_start();
require_once '../config/config.php';
include '../Login/Login/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$export_type = $_GET['type'] ?? 'csv';

// Get all data
$users = $conn->query("SELECT * FROM register")->fetch_all(MYSQLI_ASSOC);
$ideas = $conn->query("SELECT b.*, r.name as author FROM blog b LEFT JOIN register r ON b.user_id = r.id")->fetch_all(MYSQLI_ASSOC);
$subadmins = $conn->query("SELECT * FROM subadmins")->fetch_all(MYSQLI_ASSOC);

// Check if tables exist
$projects = [];
$reports = [];
$mentors = [];

$project_check = $conn->query("SHOW TABLES LIKE 'admin_approved_projects'");
if ($project_check->num_rows > 0) {
    $projects = $conn->query("SELECT * FROM admin_approved_projects")->fetch_all(MYSQLI_ASSOC);
}

$report_check = $conn->query("SHOW TABLES LIKE 'idea_reports'");
if ($report_check->num_rows > 0) {
    $reports = $conn->query("SELECT ir.*, b.project_name, r.name as reporter FROM idea_reports ir LEFT JOIN blog b ON ir.idea_id = b.id LEFT JOIN register r ON ir.reporter_id = r.id")->fetch_all(MYSQLI_ASSOC);
}

$mentors = $conn->query("SELECT * FROM register WHERE role = 'mentor'")->fetch_all(MYSQLI_ASSOC);

if ($export_type === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ideanest_data_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // Users
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

    // Ideas
    fputcsv($output, []);
    fputcsv($output, ['=== IDEAS ===']);
    fputcsv($output, ['ID', 'Name', 'Author', 'Type', 'Status', 'Description', 'Submitted']);
    foreach ($ideas as $idea) {
        fputcsv($output, [
            $idea['id'],
            $idea['project_name'],
            $idea['author'] ?? 'Unknown',
            $idea['project_type'],
            $idea['status'],
            substr($idea['description'], 0, 100),
            $idea['submission_datetime']
        ]);
    }

    // Projects
    if (!empty($projects)) {
        fputcsv($output, []);
        fputcsv($output, ['=== APPROVED PROJECTS ===']);
        fputcsv($output, ['ID', 'Name', 'Category', 'Status', 'Approved Date']);
        foreach ($projects as $project) {
            fputcsv($output, [
                $project['id'],
                $project['project_name'],
                $project['project_category'] ?? 'N/A',
                $project['status'] ?? 'approved',
                $project['submission_date'] ?? 'N/A'
            ]);
        }
    }

    // Subadmins
    fputcsv($output, []);
    fputcsv($output, ['=== SUBADMINS ===']);
    fputcsv($output, ['ID', 'Name', 'Email', 'Department', 'Created']);
    foreach ($subadmins as $subadmin) {
        fputcsv($output, [
            $subadmin['id'],
            $subadmin['name'],
            $subadmin['email'],
            $subadmin['department'] ?? 'N/A',
            $subadmin['created_at'] ?? 'N/A'
        ]);
    }

    // Mentors
    fputcsv($output, []);
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

    // Reports
    if (!empty($reports)) {
        fputcsv($output, []);
        fputcsv($output, ['=== REPORTS ===']);
        fputcsv($output, ['ID', 'Idea', 'Reporter', 'Type', 'Status', 'Date']);
        foreach ($reports as $report) {
            fputcsv($output, [
                $report['id'],
                $report['project_name'] ?? 'Unknown',
                $report['reporter'] ?? 'Unknown',
                $report['report_type'],
                $report['status'],
                $report['created_at']
            ]);
        }
    }

    fclose($output);
    exit;
}

// HTML Export
?>
<!DOCTYPE html>
<html>
<head>
    <title>IdeaNest Data Export - <?php echo date('Y-m-d'); ?></title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="no-print mb-3">
            <h1>IdeaNest Data Export</h1>
            <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
            <a href="?type=csv" class="btn btn-success">Download CSV</a>
            <button onclick="window.print()" class="btn btn-primary">Print</button>
        </div>

        <!-- Users -->
        <h2>Users (<?php echo count($users); ?>)</h2>
        <table class="table table-sm table-striped">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Role</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($user['role'] ?? 'student'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Ideas -->
        <h2>Ideas (<?php echo count($ideas); ?>)</h2>
        <table class="table table-sm table-striped">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Author</th><th>Type</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($ideas as $idea) : ?>
                <tr>
                    <td><?php echo $idea['id']; ?></td>
                    <td><?php echo htmlspecialchars($idea['project_name']); ?></td>
                    <td><?php echo htmlspecialchars($idea['author'] ?? 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars($idea['project_type']); ?></td>
                    <td><?php echo htmlspecialchars($idea['status']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($idea['submission_datetime'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Projects -->
        <?php if (!empty($projects)) : ?>
        <h2>Approved Projects (<?php echo count($projects); ?>)</h2>
        <table class="table table-sm table-striped">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Category</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project) : ?>
                <tr>
                    <td><?php echo $project['id']; ?></td>
                    <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                    <td><?php echo htmlspecialchars($project['project_category'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($project['status'] ?? 'approved'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Subadmins -->
        <h2>Subadmins (<?php echo count($subadmins); ?>)</h2>
        <table class="table table-sm table-striped">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Department</th></tr>
            </thead>
            <tbody>
                <?php foreach ($subadmins as $subadmin) : ?>
                <tr>
                    <td><?php echo $subadmin['id']; ?></td>
                    <td><?php echo htmlspecialchars($subadmin['name']); ?></td>
                    <td><?php echo htmlspecialchars($subadmin['email']); ?></td>
                    <td><?php echo htmlspecialchars($subadmin['department'] ?? 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Mentors -->
        <h2>Mentors (<?php echo count($mentors); ?>)</h2>
        <table class="table table-sm table-striped">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Expertise</th></tr>
            </thead>
            <tbody>
                <?php foreach ($mentors as $mentor) : ?>
                <tr>
                    <td><?php echo $mentor['id']; ?></td>
                    <td><?php echo htmlspecialchars($mentor['name']); ?></td>
                    <td><?php echo htmlspecialchars($mentor['email']); ?></td>
                    <td><?php echo htmlspecialchars($mentor['department'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($mentor['expertise'] ?? 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Reports -->
        <?php if (!empty($reports)) : ?>
        <h2>Reports (<?php echo count($reports); ?>)</h2>
        <table class="table table-sm table-striped">
            <thead>
                <tr><th>ID</th><th>Idea</th><th>Reporter</th><th>Type</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report) : ?>
                <tr>
                    <td><?php echo $report['id']; ?></td>
                    <td><?php echo htmlspecialchars($report['project_name'] ?? 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars($report['reporter'] ?? 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars($report['report_type']); ?></td>
                    <td><?php echo htmlspecialchars($report['status']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($report['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>