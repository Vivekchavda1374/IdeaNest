<?php
session_start();
require_once("../Login/Login/db.php");

$site_name = "IdeaNest";
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : "Admin";
$notification_count = 0;
$message_count = 0;
$analytics_image = "assets/images/analytics.svg";
$app_version = "1.0.0";

// Get project statistics and chart data
function getProjectStats() {
    global $conn;
    $stats = [
        'total_projects' => 0,
        'approved_projects' => 0,
        'rejected_projects' => 0,
        'pending_projects' => 0,
        'total_projects_percentage' => 0,
        'total_projects_growth' => "0%",
        'approved_projects_percentage' => 0,
        'approved_projects_growth' => "0%",
        'pending_projects_percentage' => 0,
        'pending_projects_growth' => "0%",
        'rejected_projects_percentage' => 0,
        'rejected_projects_growth' => "0%",
        'project_chart_data' => [],
        'category_chart_data' => []
    ];

    // Get total projects count
    $total_sql = "SELECT COUNT(*) as count FROM projects";
    $total_result = mysqli_query($conn, $total_sql);
    if ($total_result && $row = mysqli_fetch_assoc($total_result)) {
        $stats['total_projects'] = $row['count'];
    }

    // Get approved projects count
    $approved_sql = "SELECT COUNT(*) as count FROM admin_approved_projects";
    $approved_result = mysqli_query($conn, $approved_sql);
    if ($approved_result && $row = mysqli_fetch_assoc($approved_result)) {
        $stats['approved_projects'] = $row['count'];
    }

    // Get rejected projects count
    $rejected_sql = "SELECT COUNT(*) as count FROM denial_projects";
    $rejected_result = mysqli_query($conn, $rejected_sql);
    if ($rejected_result && $row = mysqli_fetch_assoc($rejected_result)) {
        $stats['rejected_projects'] = $row['count'];
    }

    // Calculate pending projects (total - approved - rejected)
    $stats['pending_projects'] = $stats['total_projects'] - $stats['approved_projects'] - $stats['rejected_projects'];

    // Calculate percentages (if total > 0)
    if ($stats['total_projects'] > 0) {
        $stats['approved_projects_percentage'] = round(($stats['approved_projects'] / $stats['total_projects']) * 100);
        $stats['rejected_projects_percentage'] = round(($stats['rejected_projects'] / $stats['total_projects']) * 100);
        $stats['pending_projects_percentage'] = round(($stats['pending_projects'] / $stats['total_projects']) * 100);
        $stats['total_projects_percentage'] = 100; // Always 100% of itself
    }

    // For growth calculation, you would typically compare with previous period
    // Set some placeholder growth values for now
    $stats['approved_projects_growth'] = "+8%";
    $stats['pending_projects_growth'] = "+3%";
    $stats['rejected_projects_growth'] = "+2%";
    $stats['total_projects_growth'] = "+10%";

    // Get data for projects chart (last 6 months)
    $months = [];
    $submitted_data = [];
    $approved_data = [];
    $rejected_data = [];

    // Get the last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $month = date('M', strtotime("-$i month"));
        $months[] = $month;

        $year_month = date('Y-m', strtotime("-$i month"));
        $start_date = $year_month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        // Count submitted projects for this month
        $submitted_sql = "SELECT COUNT(*) as count FROM projects 
                         WHERE submission_date BETWEEN '$start_date' AND '$end_date'";
        $submitted_result = mysqli_query($conn, $submitted_sql);
        $submitted_count = 0;
        if ($submitted_result && $row = mysqli_fetch_assoc($submitted_result)) {
            $submitted_count = $row['count'];
        }
        $submitted_data[] = $submitted_count;

        // Count approved projects for this month
        $approved_sql = "SELECT COUNT(*) as count FROM admin_approved_projects 
                        WHERE submission_date BETWEEN '$start_date' AND '$end_date'";
        $approved_result = mysqli_query($conn, $approved_sql);
        $approved_count = 0;
        if ($approved_result && $row = mysqli_fetch_assoc($approved_result)) {
            $approved_count = $row['count'];
        }
        $approved_data[] = $approved_count;

        // Count rejected projects for this month
        $rejected_sql = "SELECT COUNT(*) as count FROM denial_projects 
                        WHERE submission_date BETWEEN '$start_date' AND '$end_date'";
        $rejected_result = mysqli_query($conn, $rejected_sql);
        $rejected_count = 0;
        if ($rejected_result && $row = mysqli_fetch_assoc($rejected_result)) {
            $rejected_count = $row['count'];
        }
        $rejected_data[] = $rejected_count;
    }

    $stats['project_chart_labels'] = $months;
    $stats['submitted_projects_data'] = $submitted_data;
    $stats['approved_projects_data'] = $approved_data;
    $stats['rejected_projects_data'] = $rejected_data;

    // Get data for categories chart
    $category_sql = "SELECT classification, COUNT(*) as count FROM projects GROUP BY project_type";
    $category_result = mysqli_query($conn, $category_sql);

    $category_labels = [];
    $category_data = [];
    $category_colors = ["#4361ee", "#10b981", "#f59e0b", "#6366f1", "#ec4899", "#8b5cf6"];
    $color_index = 0;

    if ($category_result) {
        while ($row = mysqli_fetch_assoc($category_result)) {
            $category_labels[] = $row['classification'];
            $category_data[] = (int)$row['count'];
            $color_index++;
            if ($color_index >= count($category_colors)) {
                $color_index = 0; // Reset if we run out of colors
            }
        }
    }

    $stats['category_labels'] = $category_labels;
    $stats['category_data'] = $category_data;
    $stats['category_colors'] = array_slice($category_colors, 0, count($category_labels));

    // Get the most recent activities
    $recent_activities = [];

    // Recent submitted projects
    $submitted_sql = "SELECT 'primary' as type, 'New Project Submitted' as title, 
                    CONCAT('A new ', project_type, ' project \"', project_name, '\" has been submitted for review.') as description,
                    TIMESTAMPDIFF(MINUTE, submission_date, NOW()) as minutes_ago,
                    submission_date
                    FROM projects 
                    ORDER BY submission_date DESC LIMIT 3";
    $submitted_result = mysqli_query($conn, $submitted_sql);

    if ($submitted_result) {
        while ($row = mysqli_fetch_assoc($submitted_result)) {
            $time_ago = formatTimeAgo($row['minutes_ago'], $row['submission_date']);
            $recent_activities[] = [
                "type" => $row['type'],
                "title" => $row['title'],
                "description" => $row['description'],
                "time_ago" => $time_ago
            ];
        }
    }

    // Recent approved projects
    $approved_sql = "SELECT 'success' as type, 'Project Approved' as title, 
                   CONCAT(project_type, ' project \"', project_name, '\" has been approved.') as description,
                   TIMESTAMPDIFF(MINUTE, submission_date, NOW()) as minutes_ago,
                   submission_date
                   FROM admin_approved_projects 
                   ORDER BY submission_date DESC LIMIT 2";
    $approved_result = mysqli_query($conn, $approved_sql);

    if ($approved_result) {
        while ($row = mysqli_fetch_assoc($approved_result)) {
            $time_ago = formatTimeAgo($row['minutes_ago'], $row['submission_date']);
            $recent_activities[] = [
                "type" => $row['type'],
                "title" => $row['title'],
                "description" => $row['description'],
                "time_ago" => $time_ago
            ];
        }
    }

    // Recent rejected projects
    $rejected_sql = "SELECT 'danger' as type, 'Project Rejected' as title, 
                   CONCAT(project_type, ' project \"', project_name, '\" has been rejected.') as description,
                   TIMESTAMPDIFF(MINUTE, rejection_date, NOW()) as minutes_ago,
                   rejection_date as activity_date
                   FROM denial_projects 
                   ORDER BY rejection_date DESC LIMIT 2";
    $rejected_result = mysqli_query($conn, $rejected_sql);

    if ($rejected_result) {
        while ($row = mysqli_fetch_assoc($rejected_result)) {
            $time_ago = formatTimeAgo($row['minutes_ago'], $row['activity_date']);
            $recent_activities[] = [
                "type" => $row['type'],
                "title" => $row['title'],
                "description" => $row['description'],
                "time_ago" => $time_ago
            ];
        }
    }

    // Sort activities by time (most recent first)
    usort($recent_activities, function($a, $b) {
        $time_a = strtotime(str_replace([' ago', 'min', 'hour', 'day'], ['', 'minutes', 'hours', 'days'], $a['time_ago']));
        $time_b = strtotime(str_replace([' ago', 'min', 'hour', 'day'], ['', 'minutes', 'hours', 'days'], $b['time_ago']));
        return $time_b - $time_a;
    });

    // Take only the 3 most recent activities
    $stats['recent_activities'] = array_slice($recent_activities, 0, 3);

    // Get pending projects list
    $pending_sql = "SELECT p.id, p.project_name, p.project_type, p.language as technologies, 
                  CASE 
                      WHEN p.project_type = 'Web App' THEN 'globe'
                      WHEN p.project_type = 'Mobile App' THEN 'phone'
                      WHEN p.project_type = 'API' THEN 'code-slash'
                      ELSE 'folder'
                  END as icon,
                  u.name as submitted_by, p.submission_date, 'Pending' as status, 'warning' as status_class
                  FROM projects p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.id NOT IN (SELECT id FROM admin_approved_projects)
                  AND p.id NOT IN (SELECT id FROM denial_projects)
                  ORDER BY p.submission_date DESC
                  LIMIT 5";
    $pending_result = mysqli_query($conn, $pending_sql);

    $pending_projects_list = [];
    if ($pending_result) {
        while ($row = mysqli_fetch_assoc($pending_result)) {
            // Format the date for display
            $row['submission_date'] = date('Y-m-d', strtotime($row['submission_date']));
            $pending_projects_list[] = $row;
        }
    }

    $stats['pending_projects_list'] = $pending_projects_list;

    return $stats;
}

// Helper function to format time ago
function formatTimeAgo($minutes_ago, $date) {
    if ($minutes_ago < 60) {
        return $minutes_ago . " min ago";
    } else if ($minutes_ago < 1440) { // less than a day
        $hours = floor($minutes_ago / 60);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } else {
        $days = floor($minutes_ago / 1440);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    }
}

// Call this function to get all the dashboard data
$dashboard_data = getProjectStats();

// Update all variables used in the dashboard
$total_projects = $dashboard_data['total_projects'];
$total_projects_percentage = $dashboard_data['total_projects_percentage'];
$total_projects_growth = $dashboard_data['total_projects_growth'];
$approved_projects = $dashboard_data['approved_projects'];
$approved_projects_percentage = $dashboard_data['approved_projects_percentage'];
$approved_projects_growth = $dashboard_data['approved_projects_growth'];
$pending_projects = $dashboard_data['pending_projects'];
$pending_projects_percentage = $dashboard_data['pending_projects_percentage'];
$pending_projects_growth = $dashboard_data['pending_projects_growth'];
$rejected_projects = $dashboard_data['rejected_projects'];
$rejected_projects_percentage = $dashboard_data['rejected_projects_percentage'];
$rejected_projects_growth = $dashboard_data['rejected_projects_growth'];

// Chart data
$project_chart_labels = $dashboard_data['project_chart_labels'];
$submitted_projects_data = $dashboard_data['submitted_projects_data'];
$approved_projects_data = $dashboard_data['approved_projects_data'];
$rejected_projects_data = $dashboard_data['rejected_projects_data'];
$category_labels = $dashboard_data['category_labels'];
$category_data = $dashboard_data['category_data'];
$category_colors = $dashboard_data['category_colors'];

// Recent activities & pending projects
$recent_activities = $dashboard_data['recent_activities'];
$pending_projects_list = $dashboard_data['pending_projects_list'];

// Time ranges and categories for dropdowns
$selected_time_range = "Last 30 Days";
$time_ranges = ["Last 7 Days", "Last 30 Days", "Last 3 Months", "Last Year"];
$selected_category_range = "All Time";
$category_ranges = ["Last 30 Days", "Last 3 Months", "Last Year", "All Time"];

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
            classification, image_path, video_path, code_file_path, instruction_file_path, status, submission_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'isssssssss',
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

    $sql = "SELECT p.*, u.name FROM projects p 
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
    $sql = "SELECT p.*, u.name FROM projects p 
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
    $filename = uniqid() . '_' . md5(mt_rand()) . '.' . $file_extension;
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
        /* Custom Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background-color: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
            padding: 1rem;
        }

        .sidebar-header {
            padding: 1rem 0;
            text-align: center;
            border-bottom: 1px solid #f1f1f1;
            margin-bottom: 1rem;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4361ee;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .sidebar-brand i {
            margin-right: 0.5rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-item {
            margin-bottom: 0.5rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #6c757d;
            text-decoration: none;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }

        .sidebar-link i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        .sidebar-link.active {
            background-color: #4361ee;
            color: #fff;
        }

        .sidebar-link:hover:not(.active) {
            background-color: #f8f9fa;
            color: #4361ee;
        }

        .sidebar-divider {
            margin: 1rem 0;
            border-top: 1px solid #f1f1f1;
        }

        .sidebar-footer {
            padding: 1rem 0;
            border-top: 1px solid #f1f1f1;
            margin-top: 1rem;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 1rem;
            transition: all 0.3s;
        }

        /* Topbar Styles */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
        }

        .topbar-action {
            font-size: 1.25rem;
            color: #6c757d;
            margin-left: 1rem;
            position: relative;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4361ee;
            margin-left: 1rem;
        }

        /* Stats Card Styles */
        .stats-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-icon.primary { background-color: rgba(67, 97, 238, 0.1); color: #4361ee; }
        .stats-icon.success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stats-icon.warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stats-icon.danger { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .stats-info {
            flex-grow: 1;
        }

        .stats-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .stats-progress {
            margin-top: auto;
        }

        .stats-percentage {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.5rem;
            text-align: right;
        }

        /* Timeline Styles */
        .timeline {
            position: relative;
        }

        .timeline-item {
            padding-left: 2rem;
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .timeline-icon.primary { background-color: rgba(67, 97, 238, 0.1); border: 2px solid #4361ee; }
        .timeline-icon.success { background-color: rgba(16, 185, 129, 0.1); border: 2px solid #10b981; }
        .timeline-icon.danger { background-color: rgba(239, 68, 68, 0.1); border: 2px solid #ef4444; }

        .timeline-content {
            position: relative;
        }

        .timeline-title {
            font-size: 0.9375rem;
            margin-bottom: 0.25rem;
        }

        .timeline-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .timeline-time {
            font-size: 0.75rem;
            color: #adb5bd;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Media Query for Responsive Sidebar */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.pushed {
                margin-left: 250px;
            }
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
                    <h4 class="mb-1">Welcome back, <?php echo $user_name; ?>!</h4>
                    <p class="text-muted">Here's what's happening with your projects today.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <button class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Project
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="stats-card">
                <div class="stats-icon primary">
                    <i class="bi bi-folder"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Projects</div>
                    <div class="stats-value"><?php echo $total_projects; ?></div>
                </div>
                <div class="stats-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $total_projects_percentage; ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <i class="bi bi-arrow-up"></i> <?php echo $total_projects_growth; ?> from last period
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="stats-card">
                <div class="stats-icon success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Approved Projects</div>
                    <div class="stats-value"><?php echo $approved_projects; ?></div>
                </div>
                <div class="stats-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $approved_projects_percentage; ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <i class="bi bi-arrow-up"></i> <?php echo $approved_projects_growth; ?> from last period
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="stats-card">
                <div class="stats-icon warning">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Pending Projects</div>
                    <div class="stats-value"><?php echo $pending_projects; ?></div>
                </div>
                <div class="stats-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pending_projects_percentage; ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <i class="bi bi-arrow-up"></i> <?php echo $pending_projects_growth; ?> from last period
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon danger">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Rejected Projects</div>
                    <div class="stats-value"><?php echo $rejected_projects; ?></div>
                </div>
                <div class="stats-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $rejected_projects_percentage; ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <i class="bi bi-arrow-up"></i> <?php echo $rejected_projects_growth; ?> from last period
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Project Submissions</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $selected_time_range; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($time_ranges as $range): ?>
                                <li><a class="dropdown-item" href="#"><?php echo $range; ?></a></li>
                            <?php endforeach; ?>
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
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Project Categories</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $selected_category_range; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($category_ranges as $range): ?>
                                <li><a class="dropdown-item" href="#"><?php echo $range; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities and Pending Projects -->
    <div class="row">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activities</h5>
                </div>
                <div class="card-body p-0">
                    <div class="timeline p-3">
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon <?php echo $activity['type']; ?>">
                                    <?php if ($activity['type'] === 'primary'): ?>
                                        <i class="bi bi-plus text-primary small"></i>
                                    <?php elseif ($activity['type'] === 'success'): ?>
                                        <i class="bi bi-check text-success small"></i>
                                    <?php elseif ($activity['type'] === 'danger'): ?>
                                        <i class="bi bi-x text-danger small"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title"><?php echo $activity['title']; ?></h6>
                                    <p class="timeline-text"><?php echo $activity['description']; ?></p>
                                    <span class="timeline-time"><?php echo $activity['time_ago']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="#" class="btn btn-sm btn-outline-primary">View All Activities</a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pending Projects</h5>
                    <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                            <tr>
                                <th>Project</th>
                                <th>Type</th>
                                <th>Submitted By</th>
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
                                                <i class="bi bi-<?php echo $project['icon']; ?>"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo $project['name']; ?></h6>
                                                <small class="text-muted"><?php echo $project['technologies']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $project['type']; ?></td>
                                    <td><?php echo $project['submitted_by']; ?></td>
                                    <td>
                                            <span class="badge bg-<?php echo $project['status_class']; ?>">
                                                <?php echo $project['status']; ?>
                                            </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#"><i class="bi bi-eye me-2"></i>View</a></li>
                                                <li><a class="dropdown-item" href="#"><i class="bi bi-check-circle me-2"></i>Approve</a></li>
                                                <li><a class="dropdown-item" href="#"><i class="bi bi-x-circle me-2"></i>Reject</a></li>
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

</div>

<!-- Bootstrap JS and Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Sidebar Toggle
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show');
        document.querySelector('.main-content').classList.toggle('pushed');
    });

    // Projects Chart
    const projectsChartCtx = document.getElementById('projectsChart').getContext('2d');
    const projectsChart = new Chart(projectsChartCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($project_chart_labels); ?>,
            datasets: [
                {
                    label: 'Submitted',
                    data: <?php echo json_encode($submitted_projects_data); ?>,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Approved',
                    data: <?php echo json_encode($approved_projects_data); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Rejected',
                    data: <?php echo json_encode($rejected_projects_data); ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Categories Chart
    const categoriesChartCtx = document.getElementById('categoriesChart').getContext('2d');
    const categoriesChart = new Chart(categoriesChartCtx, {
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
            },
            cutout: '70%'
        }
    });
</script>
</body>
</html>