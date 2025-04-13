<?php
require_once("../Login/Login/db.php");

function createProject($user_id, $project_name, $project_type, $description, $language, $classification = NULL, $image_path = NULL, $video_path = NULL, $code_file_path = NULL, $instruction_file_path = NULL) {
    global $conn;

    // Sanitize inputs
    $user_id = (int)$user_id;
    $project_name = mysqli_real_escape_string($conn, $project_name);
    $project_type = mysqli_real_escape_string($conn, $project_type);
    $description = mysqli_real_escape_string($conn, $description);
    $language = mysqli_real_escape_string($conn, $language);

    // Sanitize optional parameters
    $classification = $classification !== NULL ? mysqli_real_escape_string($conn, $classification) : NULL;
    $image_path = $image_path !== NULL ? mysqli_real_escape_string($conn, $image_path) : NULL;
    $video_path = $video_path !== NULL ? mysqli_real_escape_string($conn, $video_path) : NULL;
    $code_file_path = $code_file_path !== NULL ? mysqli_real_escape_string($conn, $code_file_path) : NULL;
    $instruction_file_path = $instruction_file_path !== NULL ? mysqli_real_escape_string($conn, $instruction_file_path) : NULL;

    // Build SQL query with proper handling of NULL values
    $sql = "INSERT INTO projects (user_id, project_name, project_type, description, language, 
            classification, image_path, video_path, code_file_path, instruction_file_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'issssssss',
            $user_id,
            $project_name,
            $project_type,
            $description,
            $language,
            $classification,
            $image_path,
            $video_path,
            $code_file_path,
            $instruction_file_path
        );

        $success = mysqli_stmt_execute($stmt);

        if ($success) {
            $project_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $project_id;
        } else {
            mysqli_stmt_close($stmt);
            return false;
        }
    } else {
        return false;
    }
}

function getProjectById($project_id) {
    global $conn;
    $project_id = (int)$project_id;

    $sql = "SELECT p.*, u.username FROM projects p 
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $project_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $project = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $project;
        } else {
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    return null;
}
function updateProject($project_id, $data) {
    global $conn;
    $project_id = (int)$project_id;

    // Check if project exists
    $existing_project = getProjectById($project_id);
    if (!$existing_project) {
        return false;
    }

    // Build update query
    $updates = [];
    $types = '';
    $values = [];

    $allowed_fields = [
        'project_name' => 's',
        'project_type' => 's',
        'classification' => 's',
        'description' => 's',
        'language' => 's',
        'image_path' => 's',
        'video_path' => 's',
        'code_file_path' => 's',
        'instruction_file_path' => 's',
        'status' => 's'
    ];

    foreach ($data as $field => $value) {
        if (array_key_exists($field, $allowed_fields)) {
            $updates[] = "$field = ?";
            $types .= $allowed_fields[$field];
            $values[] = $value;
        }
    }

    if (empty($updates)) {
        return false;
    }
    $updates[] = "updated_at = NOW()";

    $sql = "UPDATE projects SET " . implode(', ', $updates) . " WHERE id = ?";

    $types .= 'i'; // For the project_id parameter
    $values[] = $project_id;

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    return false;
}
function deleteProject($project_id) {
    global $conn;
    $project_id = (int)$project_id;

    $sql = "DELETE FROM projects WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $project_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    return false;
}
function getProjectsByUser($user_id, $status = null) {
    global $conn;
    $user_id = (int)$user_id;

    $sql = "SELECT * FROM projects WHERE user_id = ?";
    $types = 'i';
    $params = [$user_id];

    if ($status !== null) {
        $sql .= " AND status = ?";
        $types .= 's';
        $params[] = $status;
    }

    $sql .= " ORDER BY submission_date DESC";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $projects = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $projects;
    }

    return [];
}
function getFilteredProjects($filters = [], $page = 1, $per_page = 10) {
    global $conn;

    // Initialize variables
    $where_clauses = [];
    $params = [];
    $types = '';

    // Process filters
    if (!empty($filters['status'])) {
        $where_clauses[] = "status = ?";
        $types .= 's';
        $params[] = $filters['status'];
    }

    if (!empty($filters['project_type'])) {
        $where_clauses[] = "project_type = ?";
        $types .= 's';
        $params[] = $filters['project_type'];
    }

    if (!empty($filters['language'])) {
        $where_clauses[] = "language = ?";
        $types .= 's';
        $params[] = $filters['language'];
    }

    if (!empty($filters['search'])) {
        $search_term = '%' . $filters['search'] . '%';
        $where_clauses[] = "(project_name LIKE ? OR description LIKE ?)";
        $types .= 'ss';
        $params[] = $search_term;
        $params[] = $search_term;
    }

    // Build the WHERE clause
    $where_sql = !empty($where_clauses) ? " WHERE " . implode(' AND ', $where_clauses) : "";

    // Count total matching projects
    $count_sql = "SELECT COUNT(*) as total FROM projects" . $where_sql;
    $count_stmt = mysqli_prepare($conn, $count_sql);

    if ($count_stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($count_stmt, $types, ...$params);
        }

        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $total_count = mysqli_fetch_assoc($count_result)['total'];
        mysqli_stmt_close($count_stmt);
    } else {
        $total_count = 0;
    }

    // Calculate pagination
    $offset = ($page - 1) * $per_page;

    // Get the projects
    $sql = "SELECT p.*, u.username FROM projects p 
            JOIN users u ON p.user_id = u.id" .
        $where_sql . " 
            ORDER BY p.submission_date DESC 
            LIMIT ?, ?";

    $types .= 'ii';
    $params[] = $offset;
    $params[] = $per_page;

    $stmt = mysqli_prepare($conn, $sql);

    $projects = [];
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }

        mysqli_stmt_close($stmt);
    }

    return [
        'projects' => $projects,
        'total_count' => $total_count,
        'total_pages' => ceil($total_count / $per_page),
        'current_page' => $page
    ];
}

function getProjectTypeStats() {
    global $conn;

    $sql = "SELECT project_type, status, COUNT(*) as count FROM projects GROUP BY project_type, status";
    $result = mysqli_query($conn, $sql);

    $stats = [];

    while ($row = mysqli_fetch_assoc($result)) {
        if (!isset($stats[$row['project_type']])) {
            $stats[$row['project_type']] = [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0
            ];
        }

        $stats[$row['project_type']][$row['status']] = $row['count'];
        $stats[$row['project_type']]['total'] += $row['count'];
    }

    return $stats;
}
function uploadProjectFile($file, $type) {
    // Define allowed extensions per type
    $allowed_extensions = [
        'image' => ['jpg', 'jpeg', 'png', 'gif'],
        'video' => ['mp4', 'webm', 'ogg'],
        'code' => ['zip', 'rar', 'tar', 'gz', 'txt', 'pdf'],
        'instruction' => ['pdf', 'doc', 'docx', 'txt']
    ];

    // Check if file type is valid
    if (!array_key_exists($type, $allowed_extensions)) {
        return false;
    }

    // Check if file was uploaded properly
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Validate file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions[$type])) {
        return false;
    }

    // Set upload directory based on type
    $upload_dir = "../uploads/{$type}s/";

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
    $filepath = $upload_dir . $filename;

    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return str_replace('..', '', $filepath); // Remove the leading ../ for storage in DB
    }

    return false;
}
function handleProjectSubmission() {
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        return [
            'status' => 'error',
            'message' => 'You must be logged in to submit a project'
        ];
    }

    // Validate required fields
    $required_fields = ['project_name', 'project_type', 'description', 'language'];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            return [
                'status' => 'error',
                'message' => 'Please fill all required fields'
            ];
        }
    }

    // Process file uploads
    $file_paths = [
        'image_path' => null,
        'video_path' => null,
        'code_file_path' => null,
        'instruction_file_path' => null
    ];

    // Process image upload
    if (isset($_FILES['project_image']) && $_FILES['project_image']['size'] > 0) {
        $file_paths['image_path'] = uploadProjectFile($_FILES['project_image'], 'image');
        if ($file_paths['image_path'] === false) {
            return [
                'status' => 'error',
                'message' => 'Invalid image file. Allowed formats: JPG, JPEG, PNG, GIF'
            ];
        }
    }

    // Process video upload
    if (isset($_FILES['project_video']) && $_FILES['project_video']['size'] > 0) {
        $file_paths['video_path'] = uploadProjectFile($_FILES['project_video'], 'video');
        if ($file_paths['video_path'] === false) {
            return [
                'status' => 'error',
                'message' => 'Invalid video file. Allowed formats: MP4, WEBM, OGG'
            ];
        }
    }

    // Process code file upload
    if (isset($_FILES['code_file']) && $_FILES['code_file']['size'] > 0) {
        $file_paths['code_file_path'] = uploadProjectFile($_FILES['code_file'], 'code');
        if ($file_paths['code_file_path'] === false) {
            return [
                'status' => 'error',
                'message' => 'Invalid code file. Allowed formats: ZIP, RAR, TAR, GZ, TXT, PDF'
            ];
        }
    }

    // Process instruction file upload
    if (isset($_FILES['instruction_file']) && $_FILES['instruction_file']['size'] > 0) {
        $file_paths['instruction_file_path'] = uploadProjectFile($_FILES['instruction_file'], 'instruction');
        if ($file_paths['instruction_file_path'] === false) {
            return [
                'status' => 'error',
                'message' => 'Invalid instruction file. Allowed formats: PDF, DOC, DOCX, TXT'
            ];
        }
    }

    // Create the project
    $project_id = createProject(
        $_SESSION['user_id'],
        $_POST['project_name'],
        $_POST['project_type'],
        $_POST['description'],
        $_POST['language'],
        isset($_POST['classification']) ? $_POST['classification'] : null,
        $file_paths['image_path'],
        $file_paths['video_path'],
        $file_paths['code_file_path'],
        $file_paths['instruction_file_path']
    );

    if ($project_id) {
        return [
            'status' => 'success',
            'message' => 'Project submitted successfully',
            'project_id' => $project_id
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to submit project. Please try again.'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest Admin - Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3bc9db;
            --success-color: #10b981;
            --info-color: #60a5fa;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-color: #f9fafb;
            --dark-color: #1f2937;
            --gray-color: #9ca3af;
            --sidebar-width: 250px;
            --topbar-height: 70px;
            --card-border-radius: 12px;
            --box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.08);
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', 'Segoe UI', Roboto, -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #fff;
            box-shadow: var(--box-shadow);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0 1.5rem;
        }

        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-menu {
            padding: 1.5rem 0;
            list-style: none;
            margin: 0;
        }

        .sidebar-item {
            position: relative;
            margin-bottom: 0.5rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--dark-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar-link i {
            font-size: 1.25rem;
            margin-right: 1rem;
            color: var(--gray-color);
            transition: all 0.2s ease;
        }

        .sidebar-link:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .sidebar-link:hover i {
            color: var(--primary-color);
        }

        .sidebar-link.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }

        .sidebar-link.active i {
            color: var(--primary-color);
        }

        .sidebar-divider {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            margin: 1rem 1.5rem;
            padding: 0;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Content Area Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* Topbar Styles */
        .topbar {
            height: var(--topbar-height);
            padding: 0 1.5rem;
            background-color: #fff;
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            border-radius: var(--card-border-radius);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .topbar-action {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .topbar-action:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            background-color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Stats Card */
        .stats-card {
            height: 100%;
            border-radius: var(--card-border-radius);
            padding: 1.5rem;
            background-color: #fff;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(67, 97, 238, 0.03));
            z-index: -1;
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-icon.primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .stats-icon.success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .stats-icon.warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .stats-icon.danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .stats-info {
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.875rem;
            color: var(--gray-color);
            margin-bottom: 0.5rem;
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0;
        }

        .stats-progress {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .progress {
            flex: 1;
            height: 6px;
            border-radius: 3px;
            background-color: rgba(0, 0, 0, 0.05);
        }

        .progress-bar {
            border-radius: 3px;
        }

        .stats-percentage {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--success-color);
        }

        /* Project Card */
        .project-card {
            border-radius: var(--card-border-radius);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .project-card:hover {
            transform: translateY(-5px);
        }

        .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .project-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-color);
        }

        .badge {
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
        }

        .badge-pending {
            background-color: var(--warning-color);
            color: #fff;
        }

        .badge-approved {
            background-color: var(--success-color);
            color: #fff;
        }

        .badge-rejected {
            background-color: var(--danger-color);
            color: #fff;
        }

        /* Activity Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 7px;
            height: 100%;
            width: 2px;
            background-color: rgba(0, 0, 0, 0.05);
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-icon {
            position: absolute;
            top: 0;
            left: -2rem;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: 3px solid #fff;
        }

        .timeline-icon.approved {
            background-color: var(--success-color);
        }

        .timeline-icon.rejected {
            background-color: var(--danger-color);
        }

        .timeline-content {
            padding-top: 0.25rem;
        }

        .timeline-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .timeline-text {
            font-size: 0.875rem;
            color: var(--gray-color);
            margin-bottom: 0.25rem;
        }

        .timeline-time {
            font-size: 0.75rem;
            color: var(--gray-color);
        }

        /* Chart Styles */
        .chart-container {
            height: 280px;
        }

        /* Responsive Media Queries */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content.pushed {
                margin-left: var(--sidebar-width);
            }
        }

        /* Additional Utility Classes */
        .text-muted {
            color: var(--gray-color) !important;
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .text-success {
            color: var(--success-color) !important;
        }

        .text-warning {
            color: var(--warning-color) !important;
        }

        .text-danger {
            color: var(--danger-color) !important;
        }

        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .bg-success {
            background-color: var(--success-color) !important;
        }

        .bg-warning {
            background-color: var(--warning-color) !important;
        }

        .bg-danger {
            background-color: var(--danger-color) !important;
        }

        .mb-6 {
            margin-bottom: 4rem !important;
        }
    </style>
</head>
<body>
<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            <i class="bi bi-lightbulb"></i>
            <span><?php echo $site_name; ?></span>
        </a>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="#" class="sidebar-link active">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link">
                <i class="bi bi-kanban"></i>
                <span>Projects</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link">
                <i class="bi bi-people"></i>
                <span>Users</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <li class="sidebar-item">
            <a href="#" class="sidebar-link">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title">Dashboard</h1>
        <div class="topbar-actions">
            <a href="#" class="topbar-action position-relative">
                <i class="bi bi-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $notification_count; ?>
                </span>
            </a>
            <a href="#" class="topbar-action position-relative">
                <i class="bi bi-envelope"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                    <?php echo $message_count; ?>
                </span>
            </a>
            <div class="dropdown">
                <a href="#" class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Welcome Stats -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-1">Welcome back, <?php echo $user_name; ?>!</h2>
                    <p class="text-muted mb-3">Here's what's happening with your projects today.</p>
                    <a href="#" class="btn btn-primary">View Analytics Report</a>
                </div>
                <div class="col-md-6 d-none d-md-block text-end">
                    <img src="<?php echo $analytics_image; ?>" alt="analytics" class="img-fluid" style="max-height: 150px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="stats-icon primary">
                    <i class="bi bi-folder"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Projects</div>
                    <h3 class="stats-value"><?php echo $total_projects; ?></h3>
                </div>
                <div class="stats-progress">
                    <div class="progress">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $total_projects_percentage; ?>%" aria-valuenow="<?php echo $total_projects_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="stats-percentage"><?php echo $total_projects_growth; ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="stats-icon success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Approved Projects</div>
                    <h3 class="stats-value"><?php echo $approved_projects; ?></h3>
                </div>
                <div class="stats-progress">
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $approved_projects_percentage; ?>%" aria-valuenow="<?php echo $approved_projects_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="stats-percentage"><?php echo $approved_projects_growth; ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="stats-icon warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Pending Projects</div>
                    <h3 class="stats-value"><?php echo $pending_projects; ?></h3>
                </div>
                <div class="stats-progress">
                    <div class="progress">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pending_projects_percentage; ?>%" aria-valuenow="<?php echo $pending_projects_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="stats-percentage"><?php echo $pending_projects_growth; ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="stats-icon danger">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Rejected Projects</div>
                    <h3 class="stats-value"><?php echo $rejected_projects; ?></h3>
                </div>
                <div class="stats-progress">
                    <div class="progress">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $rejected_projects_percentage; ?>%" aria-valuenow="<?php echo $rejected_projects_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="stats-percentage"><?php echo $rejected_projects_growth; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Analytics & Activity -->
    <div class="row">
        <!-- Project Analytics -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title">Project Submissions Analytics</h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="timeRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $selected_time_range; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="timeRangeDropdown">
                            <?php foreach ($time_ranges as $range): ?>
                                <li><a class="dropdown-item <?php echo ($range == $selected_time_range) ? 'active' : ''; ?>" href="#"><?php echo $range; ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Custom Range</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="projectsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title">Recent Activity</h6>
                    <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="p-3">
                        <div class="timeline">
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon <?php echo $activity['type']; ?>"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title"><?php echo $activity['title']; ?></h6>
                                        <p class="timeline-text"><?php echo $activity['description']; ?></p>
                                        <span class="timeline-time"><?php echo $activity['time_ago']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Categories & Pending Projects -->
    <div class="row">
        <!-- Project Categories -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title">Project Categories</h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="projectCategoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $selected_category_range; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="projectCategoryDropdown">
                            <?php foreach ($category_ranges as $range): ?>
                                <li><a class="dropdown-item <?php echo ($range == $selected_category_range) ? 'active' : ''; ?>" href="#"><?php echo $range; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Pending Projects -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title">Latest Pending Projects</h6>
                    <a href="#" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Type</th>
                                <th>Submitted By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pending_projects_list as $project): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 me-2">
                                                <i class="bi bi-<?php echo $project['icon']; ?> text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo $project['name']; ?></h6>
                                                <small class="text-muted"><?php echo $project['technologies']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $project['type']; ?></td>
                                    <td><?php echo $project['submitted_by']; ?></td>
                                    <td><?php echo $project['submission_date']; ?></td>
                                    <td><span class="badge badge-<?php echo $project['status_class']; ?>"><?php echo $project['status']; ?></span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Action
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="project_details.php?id=<?php echo $project['id']; ?>">View Details</a></li>
                                                <li><a class="dropdown-item text-success" href="approve_project.php?id=<?php echo $project['id']; ?>">Approve</a></li>
                                                <li><a class="dropdown-item text-danger" href="reject_project.php?id=<?php echo $project['id']; ?>">Reject</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-auto py-3">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted">&copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. All rights reserved.</span>
                </div>
                <div>
                    <span class="text-muted">Version <?php echo $app_version; ?></span>
                </div>
            </div>
        </div>
    </footer>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Sidebar toggle functionality
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show');
        document.querySelector('.main-content').classList.toggle('pushed');
    });

    // Projects Chart
    const projectsChart = new Chart(
        document.getElementById('projectsChart'),
        {
            type: 'line',
            data: {
                labels: <?php echo json_encode($project_chart_labels); ?>,
                datasets: [
                    {
                        label: 'Submitted',
                        data: <?php echo json_encode($submitted_projects_data); ?>,
                        borderColor: '#4361ee',
                        tension: 0.3,
                        fill: false
                    },
                    {
                        label: 'Approved',
                        data: <?php echo json_encode($approved_projects_data); ?>,
                        borderColor: '#10b981',
                        tension: 0.3,
                        fill: false
                    },
                    {
                        label: 'Rejected',
                        data: <?php echo json_encode($rejected_projects_data); ?>,
                        borderColor: '#ef4444',
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        }
    );

    // Category Chart
    const categoryChart = new Chart(
        document.getElementById('categoryChart'),
        {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_data); ?>,
                    backgroundColor: <?php echo json_encode($category_colors); ?>,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        }
    );
</script>
</body>
</html>