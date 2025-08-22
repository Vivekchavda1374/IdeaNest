<?php
// user/all_projects.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$basePath = './';
include '../Login/Login/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle bookmark toggle
if (isset($_POST['toggle_bookmark']) && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
    $session_id = session_id();
    // Check if bookmark already exists
    $check_sql = "SELECT * FROM bookmark WHERE project_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $project_id, $session_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        // Remove bookmark
        $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("is", $project_id, $session_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        $bookmark_message = '<div class="alert alert-info">Bookmark removed!</div>';
    } else {
        // Add bookmark
        $idea_id = 0;
        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isi", $project_id, $session_id, $idea_id);
        $insert_stmt->execute();
        $insert_stmt->close();
        $bookmark_message = '<div class="alert alert-success">Project bookmarked!</div>';
    }
    $check_stmt->close();
}

// Get session-based ownership tracking
$session_id = session_id();

// Create a temporary ownership table for session-based tracking if it doesn't exist
$create_temp_ownership = "CREATE TABLE IF NOT EXISTS temp_project_ownership (
    project_id INT NOT NULL,
    user_session VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, user_session),
    INDEX idx_session (user_session)
)";
$conn->query($create_temp_ownership);

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_classification = isset($_GET['classification']) ? trim($_GET['classification']) : '';
$filter_type = isset($_GET['type']) ? trim($_GET['type']) : '';

// NEW: Handle view filters
$view_filter = isset($_GET['view']) ? trim($_GET['view']) : 'all';
$show_only_owned = ($view_filter === 'owned');
$show_only_bookmarked = ($view_filter === 'bookmarked');

// Pagination settings
$projects_per_page = 9;
$current_page_num = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page_num - 1) * $projects_per_page;

// Modified count query to handle view filters
$count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects ap";
$count_joins = "";
$count_conditions = " WHERE 1=1";
$count_params = [];
$count_types = "";

// Add joins and conditions based on view filter
if ($show_only_owned) {
    $count_joins .= " INNER JOIN temp_project_ownership tpo ON ap.id = tpo.project_id AND tpo.user_session = ?";
    $count_params[] = $session_id;
    $count_types .= "s";
} elseif ($show_only_bookmarked) {
    $count_joins .= " INNER JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?";
    $count_params[] = $session_id;
    $count_types .= "s";
}

$count_sql .= $count_joins . $count_conditions;

if ($search !== '') {
    $count_sql .= " AND (ap.project_name LIKE ? OR ap.description LIKE ? OR ap.classification LIKE ? OR ap.project_type LIKE ? OR ap.language LIKE ? )";
    $search_param = "%$search%";
    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $count_types .= "sssss";
}
if ($filter_classification !== '') {
    $count_sql .= " AND ap.classification = ?";
    $count_params[] = $filter_classification;
    $count_types .= "s";
}
if ($filter_type !== '') {
    $count_sql .= " AND ap.project_type = ?";
    $count_params[] = $filter_type;
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_projects = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_projects / $projects_per_page);

// Modified main query to handle view filters
$sql = "SELECT ap.*, 
               CASE WHEN b.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked,
               CASE WHEN tpo.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_owner
        FROM admin_approved_projects ap
        LEFT JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?
        LEFT JOIN temp_project_ownership tpo ON ap.id = tpo.project_id AND tpo.user_session = ?";

$main_conditions = " WHERE 1=1";
$params = [$session_id, $session_id];
$types = "ss";

// Add view filter conditions
if ($show_only_owned) {
    $main_conditions .= " AND tpo.project_id IS NOT NULL";
} elseif ($show_only_bookmarked) {
    $main_conditions .= " AND b.project_id IS NOT NULL";
}

$sql .= $main_conditions;

if ($search !== '') {
    $sql .= " AND (ap.project_name LIKE ? OR ap.description LIKE ? OR ap.classification LIKE ? OR ap.project_type LIKE ? OR ap.language LIKE ? )";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $types .= "sssss";
}
if ($filter_classification !== '') {
    $sql .= " AND ap.classification = ?";
    $params[] = $filter_classification;
    $types .= "s";
}
if ($filter_type !== '') {
    $sql .= " AND ap.project_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}
$sql .= " ORDER BY ap.submission_date DESC LIMIT ? OFFSET ?";
$params[] = $projects_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}
$stmt->close();

// For demo purposes, let's mark some projects as owned by current session
// In a real application, this would be handled during project creation
if (!empty($projects)) {
    $demo_ownership_sql = "INSERT IGNORE INTO temp_project_ownership (project_id, user_session) VALUES ";
    $demo_values = [];
    $demo_params = [];
    $demo_types = "";

    // Mark every 3rd project as owned by current user for demonstration
    foreach ($projects as $index => $project) {
        if (($index + 1) % 3 == 0) { // Every 3rd project
            $demo_values[] = "(?, ?)";
            $demo_params[] = $project['id'];
            $demo_params[] = $session_id;
            $demo_types .= "is";
        }
    }

    if (!empty($demo_values)) {
        $demo_ownership_sql .= implode(", ", $demo_values);
        $demo_stmt = $conn->prepare($demo_ownership_sql);
        $demo_stmt->bind_param($demo_types, ...$demo_params);
        $demo_stmt->execute();
        $demo_stmt->close();

        // Re-fetch projects with updated ownership
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $projects = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
        }
        $stmt->close();
    }
}

// Get counts for filter buttons
$owned_count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects ap 
                    INNER JOIN temp_project_ownership tpo ON ap.id = tpo.project_id AND tpo.user_session = ?";
$owned_count_stmt = $conn->prepare($owned_count_sql);
$owned_count_stmt->bind_param("s", $session_id);
$owned_count_stmt->execute();
$owned_count = $owned_count_stmt->get_result()->fetch_assoc()['total'];
$owned_count_stmt->close();

$bookmarked_count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects ap 
                         INNER JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?";
$bookmarked_count_stmt = $conn->prepare($bookmarked_count_sql);
$bookmarked_count_stmt->bind_param("s", $session_id);
$bookmarked_count_stmt->execute();
$bookmarked_count = $bookmarked_count_stmt->get_result()->fetch_assoc()['total'];
$bookmarked_count_stmt->close();

$all_count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects";
$all_count_stmt = $conn->prepare($all_count_sql);
$all_count_stmt->execute();
$all_count = $all_count_stmt->get_result()->fetch_assoc()['total'];
$all_count_stmt->close();

$conn->close();

// Get user info from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "vivek";
$user_initial = !empty($user_name) ? strtoupper(substr($user_name, 0, 1)) : "V";

// Get current page to set active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Approved Projects - IdeaNest</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Modern Purple & White Theme for All Projects */
        :root {
            /* Your Custom Color Palette */
            --primary-color: #6366f1;
            --primary-hover: #5855eb;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark: #1e293b;
            --light: #f8fafc;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --sidebar-width: 280px;

            /* Derived Colors and Gradients */
            --primary-light: #a5b4fc;
            --primary-dark: #4f46e5;
            --accent-color: #ddd6fe;

            /* Gradients using your colors */
            --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --gradient-light: linear-gradient(135deg, var(--light) 0%, var(--gray-100) 100%);
            --gradient-card: linear-gradient(135deg, var(--white) 0%, var(--light) 100%);
            --gradient-hover: linear-gradient(135deg, var(--primary-hover) 0%, var(--secondary-color) 100%);

            /* Spacing */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;

            /* Typography */
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;
            --font-size-4xl: 2.25rem;

            /* Border Radius using your values */
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: var(--border-radius);
            --radius-xl: var(--border-radius-lg);
            --radius-2xl: 24px;
            --radius-full: 9999px;
        }

        /* Base Styles */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--white);
            color: var(--gray-700);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* Main Content Layout */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: var(--spacing-xl);
            transition: all 0.3s ease;
            background: var(--white);
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 80px var(--spacing-md) var(--spacing-xl);
                background: var(--white);
            }
        }

        /* Projects Header */
        .projects-header {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: var(--spacing-2xl);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }

        .projects-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .projects-header h2 {
            font-size: var(--font-size-3xl);
            font-weight: 800;
            color: var(--gray-800);
            margin-bottom: var(--spacing-sm);
            display: flex;
            align-items: center;
        }

        .projects-header h2 i {
            color: var(--primary-color);
            margin-right: var(--spacing-md);
            font-size: var(--font-size-2xl);
        }

        .projects-header p {
            font-size: var(--font-size-lg);
            color: var(--gray-600);
            margin-bottom: var(--spacing-xl);
            font-weight: 400;
        }

        /* View Filter Buttons */
        .view-filter-buttons {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }

        .view-filter-buttons h5 {
            font-size: var(--font-size-lg);
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
        }

        .view-filter-buttons h5 i {
            color: var(--primary-color);
            margin-right: var(--spacing-sm);
        }

        .filter-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .filter-btn {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: 12px 24px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-full);
            background: var(--white);
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 600;
            font-size: var(--font-size-base);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .filter-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transition: left 0.3s ease;
            z-index: 0;
        }

        .filter-btn span {
            position: relative;
            z-index: 1;
        }

        .filter-btn i {
            position: relative;
            z-index: 1;
        }

        .filter-btn:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .filter-btn:hover::before {
            left: 0;
        }

        .filter-btn:hover,
        .filter-btn:hover span {
            color: var(--white);
        }

        .filter-btn.active {
            background: var(--gradient-primary);
            border-color: var(--primary-color);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .filter-btn.active::before {
            left: 0;
        }

        .filter-btn-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 8px;
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 700;
            margin-left: var(--spacing-xs);
        }

        .filter-btn:not(.active) .filter-btn-count {
            background: var(--gray-100);
            color: var(--gray-600);
        }

        /* Projects Stats */
        .projects-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .stat-item {
            background: var(--white);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .stat-item:hover::before {
            transform: translateX(0);
        }

        .stat-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: var(--gradient-primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: var(--font-size-xl);
        }

        .stat-text {
            font-weight: 600;
            color: var(--gray-700);
            font-size: var(--font-size-lg);
        }

        .pagination-summary {
            font-size: var(--font-size-sm);
            color: var(--gray-500);
            margin-top: var(--spacing-xs);
        }

        /* Filter Form */
        .filter-form {
            background: var(--white);
            padding: var(--spacing-xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            margin-top: var(--spacing-xl);
        }

        .filter-form .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--spacing-sm);
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-form .form-control,
        .filter-form .form-select {
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            font-size: var(--font-size-base);
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--gray-700);
        }

        .filter-form .form-control:focus,
        .filter-form .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            outline: none;
        }

        .input-group-text {
            background: var(--white);
            border: 2px solid var(--gray-200);
            border-right: none;
            color: var(--gray-500);
        }

        .form-control.border-start-0 {
            border-left: none;
        }

        /* Buttons */
        .btn {
            font-weight: 600;
            border-radius: var(--radius-md);
            padding: 12px 24px;
            font-size: var(--font-size-base);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            background: var(--gradient-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
            color: var(--gray-800);
        }

        .btn-outline-success {
            background: transparent;
            color: var(--success-color);
            border: 2px solid var(--success-color);
        }

        .btn-outline-success:hover {
            background: var(--success-color);
            color: var(--white);
        }

        .btn-warning {
            background: var(--warning-color);
            color: var(--white);
        }

        .btn-warning:hover {
            background: #d97706;
            color: var(--white);
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert {
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            border: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .alert::before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-success::before {
            content: "\f058";
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
            color: var(--info-color);
            border-left: 4px solid var(--info-color);
        }

        .alert-info::before {
            content: "\f05a";
        }

        /* ROW-WISE Project Cards - FIXED LAYOUT */
        .projects-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xl);
        }

        .project-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            position: relative;
            cursor: pointer;
            width: 100%;
            display: flex;
            flex-direction: row;
            min-height: 200px;
        }

        .project-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .project-card:hover::before {
            opacity: 1;
        }

        .project-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-light);
        }

        .project-card-content {
            flex: 1;
            padding: var(--spacing-xl);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .project-card-header {
            margin-bottom: var(--spacing-lg);
        }

        .project-card .card-title {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: var(--spacing-md);
            line-height: 1.3;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .project-card .card-text {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: var(--spacing-lg);
            font-size: var(--font-size-base);
        }

        /* Project Ownership and Lock Styles */
        .project-ownership {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .ownership-indicator {
            width: 24px;
            height: 24px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-xs);
        }

        .owner-indicator {
            background: var(--success-color);
            color: var(--white);
        }

        .locked-indicator {
            background: var(--gray-400);
            color: var(--white);
        }

        /* Edit Actions */
        .edit-actions {
            position: absolute;
            top: var(--spacing-lg);
            right: var(--spacing-lg);
            z-index: 10;
        }

        .edit-btn {
            background: var(--warning-color);
            border: 2px solid var(--warning-color);
            border-radius: var(--radius-full);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            color: var(--white);
            text-decoration: none;
        }

        .edit-btn:hover {
            background: #d97706;
            color: var(--white);
            transform: scale(1.1);
            box-shadow: var(--shadow-md);
        }

        .locked-btn {
            background: var(--gray-400);
            border-color: var(--gray-400);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .locked-btn:hover {
            background: var(--gray-400);
            color: var(--white);
            transform: none;
            cursor: not-allowed;
        }

        /* Project Badges */
        .project-badges {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-lg);
        }

        .project-badge {
            padding: 6px 12px;
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        .badge-classification {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
        }

        .badge-type {
            background: var(--accent-color);
            color: var(--primary-dark);
        }

        .badge-owner {
            background: var(--success-color);
            color: var(--white);
        }

        /* Project Date */
        .project-date {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: var(--gray-500);
            font-size: var(--font-size-sm);
            margin-bottom: var(--spacing-lg);
        }

        .project-date i {
            color: var(--primary-color);
        }

        /* Bookmark Buttons - FIXED */
        .bookmark-float {
            position: absolute;
            top: var(--spacing-lg);
            right: calc(var(--spacing-lg) + 60px); /* Position next to edit button */
            z-index: 10;
        }

        .bookmark-float button {
            background: var(--white);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-full);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .bookmark-float button:hover {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: var(--white);
            transform: scale(1.1);
        }

        .bookmark-inline {
            background: transparent;
            border: 2px solid var(--gray-200);
            color: var(--gray-500);
            padding: 8px 16px;
            border-radius: var(--radius-full);
            font-size: var(--font-size-sm);
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .bookmark-inline:hover,
        .bookmark-inline.bookmarked {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: var(--white);
        }

        /* Card Footer */
        .card-footer-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            margin-top: auto;
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--gray-200);
        }

        /* Project Side Panel */
        .project-side-panel {
            width: 250px;
            background: var(--gradient-light);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-xl);
            border-left: 1px solid var(--gray-200);
            position: relative;
        }

        .project-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: var(--radius-2xl);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: var(--font-size-3xl);
            margin-bottom: var(--spacing-lg);
        }

        .project-status {
            padding: 8px 16px;
            background: var(--success-color);
            color: var(--white);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Modals */
        .project-modal-glass .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
        }

        .project-modal-header {
            background: var(--gradient-primary);
            color: var(--white);
            border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
            padding: var(--spacing-xl);
            border-bottom: none;
        }

        .project-modal-header .modal-title {
            font-size: var(--font-size-2xl);
            font-weight: 700;
        }

        .project-modal-desc {
            background: var(--gray-50);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary-color);
            line-height: 1.6;
            color: var(--gray-700);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
            background: var(--white);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-lg);
            color: var(--white);
            font-size: var(--font-size-3xl);
        }

        .empty-state h4 {
            color: var(--gray-800);
            font-size: var(--font-size-2xl);
            font-weight: 700;
            margin-bottom: var(--spacing-md);
        }

        .empty-state p {
            color: var(--gray-600);
            font-size: var(--font-size-lg);
            max-width: 500px;
            margin: 0 auto var(--spacing-lg);
        }

        /* Pagination */
        .pagination-container {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            margin-top: var(--spacing-xl);
        }

        .pagination-wrapper {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }

        .pagination-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .pagination-stats {
            color: var(--gray-600);
            font-size: var(--font-size-base);
        }

        .pagination-stats strong {
            color: var(--primary-color);
            font-weight: 700;
        }

        .pagination {
            justify-content: center;
            margin: 0;
        }

        .pagination .page-item {
            margin: 0 2px;
        }

        .pagination .page-link {
            border: 2px solid var(--gray-200);
            color: var(--gray-600);
            padding: 12px 16px;
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .pagination .page-link:hover {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
        }

        .pagination .page-item.active .page-link {
            background: var(--gradient-primary);
            border-color: var(--primary-color);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .pagination .page-item.disabled .page-link {
            background: var(--gray-100);
            border-color: var(--gray-200);
            color: var(--gray-400);
            cursor: not-allowed;
        }

        .pagination-nav {
            display: flex;
            justify-content: space-between;
            gap: var(--spacing-md);
        }

        .pagination-nav-btn {
            background: var(--gradient-primary);
            color: var(--white);
            padding: 12px 20px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .pagination-nav-btn:hover {
            background: var(--gradient-hover);
            color: var(--white);
            transform: translateY(-2px);
        }

        .pagination-nav-btn.disabled {
            background: var(--gray-300);
            color: var(--gray-500);
            pointer-events: none;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .fade-in-hidden {
            opacity: 0;
            transform: translateY(30px);
        }

        .fade-in-visible {
            opacity: 1;
            transform: translateY(0);
            transition: all 0.6s ease-out;
        }

        /* Hover Effects */
        .hover-lift {
            transition: all 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-4px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 100px var(--spacing-md) var(--spacing-lg);
                background: var(--white);
            }

            .projects-header {
                padding: var(--spacing-lg);
                margin-bottom: var(--spacing-lg);
            }

            .projects-header h2 {
                font-size: var(--font-size-2xl);
            }

            .projects-stats {
                grid-template-columns: 1fr;
                gap: var(--spacing-md);
            }

            .filter-form {
                padding: var(--spacing-lg);
            }

            .pagination-info {
                flex-direction: column;
                text-align: center;
            }

            .pagination-nav {
                flex-direction: column;
            }

            .project-card {
                flex-direction: column;
                min-height: auto;
            }

            .project-side-panel {
                width: 100%;
                border-left: none;
                border-top: 1px solid var(--gray-200);
                padding: var(--spacing-lg);
            }

            .project-icon {
                width: 60px;
                height: 60px;
                font-size: var(--font-size-2xl);
                margin-bottom: var(--spacing-md);
            }

            .bookmark-float {
                right: var(--spacing-lg);
                top: auto;
                bottom: var(--spacing-lg);
            }

            .edit-actions {
                top: auto;
                bottom: var(--spacing-lg);
                right: calc(var(--spacing-lg) + 60px);
            }

            .card-footer-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-btn-group {
                flex-direction: column;
            }

            .filter-btn {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .projects-header h2 {
                font-size: var(--font-size-xl);
                flex-direction: column;
                gap: var(--spacing-sm);
                text-align: center;
            }

            .stat-item {
                flex-direction: column;
                text-align: center;
                gap: var(--spacing-sm);
            }

            .pagination .page-link {
                padding: 8px 12px;
                font-size: var(--font-size-sm);
            }

            .project-card .card-title {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-sm);
            }
        }

        /* Print Styles */
        @media print {
            .main-content {
                margin-left: 0;
                padding: var(--spacing-md);
            }

            .project-card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid var(--gray-300);
            }

            .pagination-container,
            .filter-form,
            .bookmark-float,
            .bookmark-inline,
            .edit-actions,
            .view-filter-buttons {
                display: none;
            }
        }

        /* Focus States for Accessibility */
        .btn:focus,
        .form-control:focus,
        .form-select:focus,
        .page-link:focus,
        .filter-btn:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Loading States */
        .btn-clicked {
            transform: scale(0.98);
        }

        .search-active {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: var(--radius-full);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--gradient-hover);
        }

        /* Tooltip for locked projects */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 140px;
            background-color: var(--gray-800);
            color: var(--white);
            text-align: center;
            border-radius: var(--radius-md);
            padding: 8px;
            font-size: var(--font-size-xs);
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -70px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: var(--gray-800) transparent transparent transparent;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Multi-column Projects Layout */
        .projects-columns-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            padding: 1rem;
        }

        .projects-column {
            flex: 1;
            min-width: 300px; /* Minimum column width */
            max-width: 400px; /* Maximum column width */
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .project-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        /* Responsive adjustments */
        @media (max-width: 1400px) {
            .projects-column {
                min-width: 280px;
            }
        }

        @media (max-width: 1200px) {
            .projects-column {
                min-width: 260px;
            }
        }

        @media (max-width: 992px) {
            .projects-columns-wrapper {
                gap: 1.5rem;
            }
            .projects-column {
                min-width: 240px;
            }
        }

        @media (max-width: 768px) {
            .projects-columns-wrapper {
                gap: 1rem;
            }
            .projects-column {
                min-width: 100%;
    </style>
</head>
<body>
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<?php include "layout.php"; ?>

<!-- Main Content -->
<main class="main-content">
    <!-- Projects Header -->
    <div class="projects-header fade-in-up">
        <h2><i class="fas fa-project-diagram me-3"></i>All Approved Projects</h2>
        <p class="mb-0">Discover innovative projects from our community of creators and innovators</p>

        <div class="projects-stats">
            <div class="stat-item hover-lift">
                <div class="stat-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div>
                    <div class="stat-text"><?php echo $total_projects; ?>
                        <?php
                        if ($view_filter === 'owned') echo 'Your Projects';
                        elseif ($view_filter === 'bookmarked') echo 'Bookmarked Projects';
                        else echo 'Total Projects';
                        ?>
                    </div>
                    <div class="pagination-summary">Showing page <?php echo $current_page_num; ?> of <?php echo $total_pages; ?></div>
                </div>
            </div>
            <div class="stat-item hover-lift">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-text">Community Driven</div>
            </div>
            <div class="stat-item hover-lift">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-text">Curated Content</div>
            </div>
        </div>
    </div>

    <!-- View Filter Buttons -->
    <div class="view-filter-buttons fade-in-up">
        <h5><i class="fas fa-filter"></i>View Options</h5>
        <div class="filter-btn-group">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'all', 'page' => 1])); ?>"
               class="filter-btn <?php echo $view_filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>All Projects</span>
                <span class="filter-btn-count"><?php echo $all_count; ?></span>
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'owned', 'page' => 1])); ?>"
               class="filter-btn <?php echo $view_filter === 'owned' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>My Projects</span>
                <span class="filter-btn-count"><?php echo $owned_count; ?></span>
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'bookmarked', 'page' => 1])); ?>"
               class="filter-btn <?php echo $view_filter === 'bookmarked' ? 'active' : ''; ?>">
                <i class="fas fa-bookmark"></i>
                <span>Bookmarked</span>
                <span class="filter-btn-count"><?php echo $bookmarked_count; ?></span>
            </a>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="filter-form fade-in-up">
        <form method="get" class="row g-3 align-items-end">
            <!-- Preserve view filter -->
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">

            <div class="col-12 col-md-4">
                <label for="search" class="form-label">Search Projects</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-color: var(--gray-200);">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="search" name="search"
                           placeholder="Search by name, description, type..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label for="classification" class="form-label">Classification</label>
                <select class="form-select" id="classification" name="classification">
                    <option value="">All Classifications</option>
                    <?php
                    // Get all unique classifications from database for filter dropdown
                    $classifications = array_unique(array_filter(array_map(function($p){ return $p['classification'] ?? ''; }, $projects)));
                    foreach ($classifications as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>" <?php if ($filter_classification === $c) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($c); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label for="type" class="form-label">Project Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <?php
                    $types_arr = array_unique(array_filter(array_map(function($p){ return $p['project_type'] ?? ''; }, $projects)));
                    foreach ($types_arr as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php if ($filter_type === $t) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-primary hover-lift">
                    <i class="fas fa-search me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($bookmark_message)) echo $bookmark_message; ?>

    <!-- Projects List - ROW-WISE LAYOUT -->
    <div class="projects-list">
        <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $index => $project): ?>
                <div class="project-card fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <!-- Fixed Bookmark Float Form -->
                    <form method="post" class="bookmark-float">
                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                        <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="classification" value="<?php echo htmlspecialchars($filter_classification); ?>">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($filter_type); ?>">
                        <button type="submit" name="toggle_bookmark"
                                title="<?php echo $project['is_bookmarked'] ? 'Remove from bookmarks' : 'Add to bookmarks'; ?>">
                            <i class="fas fa-bookmark"
                               style="color: <?php echo $project['is_bookmarked'] ? '#8B5CF6' : '#cbd5e1'; ?>;
                                       opacity: <?php echo $project['is_bookmarked'] ? '1' : '0.6'; ?>;"></i>
                        </button>
                    </form>

                    <!-- Edit Action Float -->
                    <div class="edit-actions">
                        <?php if ($project['is_owner']): ?>
                            <a href="edit_project.php?id=<?php echo $project['id']; ?>"
                               class="edit-btn tooltip"
                               title="Edit your project">
                                <i class="fas fa-edit"></i>
                                <span class="tooltip-text">Edit Project</span>
                            </a>
                        <?php else: ?>
                            <div class="edit-btn locked-btn tooltip">
                                <i class="fas fa-lock"></i>
                                <span class="tooltip-text">Can't edit others' projects</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="project-card-content" onclick="openProjectModal(<?php echo $project['id']; ?>)">
                        <div class="project-card-header">
                            <h5 class="card-title">
                                <span><?php echo htmlspecialchars($project['project_name']); ?></span>
                                <div class="project-ownership">
                                    <?php if ($project['is_owner']): ?>
                                        <div class="ownership-indicator owner-indicator tooltip">
                                            <i class="fas fa-user"></i>
                                            <span class="tooltip-text">Your Project</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="ownership-indicator locked-indicator tooltip">
                                            <i class="fas fa-lock"></i>
                                            <span class="tooltip-text">Others' Project</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </h5>

                            <div class="project-badges">
                                <span class="project-badge badge-classification">
                                    <?php echo htmlspecialchars($project['classification']); ?>
                                </span>
                                <?php if (!empty($project['project_type'])): ?>
                                    <span class="project-badge badge-type">
                                        <?php echo htmlspecialchars($project['project_type']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($project['is_owner']): ?>
                                    <span class="project-badge badge-owner">
                                        <i class="fas fa-crown me-1"></i>Your Project
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="project-date">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo isset($project['submission_date']) ? htmlspecialchars($project['submission_date']) : (isset($project['created_at']) ? htmlspecialchars($project['created_at']) : ''); ?></span>
                            </div>
                        </div>

                        <p class="card-text">
                            <?php echo htmlspecialchars(mb_strimwidth($project['description'], 0, 200, '...')); ?>
                        </p>

                        <div class="card-footer-actions">
                            <!-- Fixed Bookmark Inline Form -->
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <input type="hidden" name="classification" value="<?php echo htmlspecialchars($filter_classification); ?>">
                                <input type="hidden" name="type" value="<?php echo htmlspecialchars($filter_type); ?>">
                                <button type="submit" name="toggle_bookmark"
                                        class="bookmark-inline<?php echo $project['is_bookmarked'] ? ' bookmarked' : ''; ?>">
                                    <i class="fas fa-bookmark"></i>
                                    <span><?php echo $project['is_bookmarked'] ? 'Bookmarked' : 'Bookmark'; ?></span>
                                </button>
                            </form>

                            <?php if ($project['is_owner']): ?>
                                <a href="edit_project.php?id=<?php echo $project['id']; ?>"
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                            <?php else: ?>
                                <small class="text-muted d-flex align-items-center">
                                    <i class="fas fa-lock me-1"></i>Read Only
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="project-side-panel">
                        <div class="project-icon">
                            <i class="fas fa-<?php echo $project['is_owner'] ? 'user-crown' : 'project-diagram'; ?>"></i>
                        </div>
                        <div class="project-status">
                            <i class="fas fa-check-circle me-1"></i>Approved
                        </div>
                    </div>
                </div>

                <!-- Modal for project details -->
                <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1"
                     aria-labelledby="projectModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg project-modal-glass">
                        <div class="modal-content">
                            <div class="modal-header project-modal-header">
                                <h5 class="modal-title" id="projectModalLabel<?php echo $project['id']; ?>">
                                    <i class="fas fa-project-diagram me-2"></i>
                                    <?php echo htmlspecialchars($project['project_name']); ?>
                                    <?php if ($project['is_owner']): ?>
                                        <span class="badge ms-2" style="background: rgba(255,255,255,0.2);">
                                            <i class="fas fa-crown me-1"></i>Your Project
                                        </span>
                                    <?php endif; ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <strong class="text-secondary d-block mb-1">Submitted:</strong>
                                        <?php echo isset($project['submission_date']) ? htmlspecialchars($project['submission_date']) : (isset($project['created_at']) ? htmlspecialchars($project['created_at']) : 'N/A'); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong class="text-secondary d-block mb-1">Status:</strong>
                                        <span class="badge" style="background: var(--success-color); color: white;">
                                            <i class="fas fa-check-circle me-1"></i>Approved
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                                        <i class="fas fa-file-text me-2 text-primary"></i>Description
                                    </h6>
                                    <div class="project-modal-desc">
                                        <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                                    </div>
                                </div>

                                <?php if (!empty($project['project_file_path'])): ?>
                                    <div class="mb-3">
                                        <h6 class="fw-bold mb-3 d-flex align-items-center">
                                            <i class="fas fa-download me-2 text-success"></i>Project Files
                                        </h6>
                                        <a href="<?php echo htmlspecialchars($project['project_file_path']); ?>"
                                           class="btn btn-outline-success hover-lift" target="_blank">
                                            <i class="fas fa-download me-2"></i>Download Project Files
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Close
                                </button>

                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                    <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                    <input type="hidden" name="classification" value="<?php echo htmlspecialchars($filter_classification); ?>">
                                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($filter_type); ?>">
                                    <button type="submit" name="toggle_bookmark" class="btn btn-primary">
                                        <i class="fas fa-bookmark me-2"></i>
                                        <?php echo $project['is_bookmarked'] ? 'Remove Bookmark' : 'Add Bookmark'; ?>
                                    </button>
                                </form>

                                <?php if ($project['is_owner']): ?>
                                    <a href="edit_project.php?id=<?php echo $project['id']; ?>"
                                       class="btn btn-warning">
                                        <i class="fas fa-edit me-2"></i>Edit Project
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary" disabled>
                                        <i class="fas fa-lock me-2"></i>Can't Edit
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state fade-in-up">
                <div class="empty-state-icon">
                    <?php if ($view_filter === 'owned'): ?>
                        <i class="fas fa-user-plus"></i>
                    <?php elseif ($view_filter === 'bookmarked'): ?>
                        <i class="fas fa-bookmark"></i>
                    <?php else: ?>
                        <i class="fas fa-search"></i>
                    <?php endif; ?>
                </div>
                <h4>
                    <?php
                    if ($view_filter === 'owned') echo 'No projects found in your collection';
                    elseif ($view_filter === 'bookmarked') echo 'No bookmarked projects found';
                    else echo 'No projects found';
                    ?>
                </h4>
                <p>
                    <?php
                    if ($view_filter === 'owned') {
                        echo 'You haven\'t created any projects yet. Start by submitting your first project to the community!';
                    } elseif ($view_filter === 'bookmarked') {
                        echo 'You haven\'t bookmarked any projects yet. Browse through all projects and bookmark the ones that interest you.';
                    } else {
                        echo 'We couldn\'t find any projects matching your search criteria. Try adjusting your filters or search terms.';
                    }
                    ?>
                </p>
                <?php if ($view_filter === 'owned'): ?>
                    <a href="submit_project.php" class="btn btn-primary mt-3 hover-lift">
                        <i class="fas fa-plus me-2"></i>Submit Your First Project
                    </a>
                <?php elseif ($view_filter === 'bookmarked'): ?>
                    <a href="?view=all" class="btn btn-primary mt-3 hover-lift">
                        <i class="fas fa-th-large me-2"></i>Browse All Projects
                    </a>
                <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, array_filter(['view' => $view_filter], function($v) { return !empty($v) && $v !== 'all'; }))); ?>"
                       class="btn btn-primary mt-3 hover-lift">
                        <i class="fas fa-refresh me-2"></i>Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Enhanced Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-container fade-in-up">
            <div class="pagination-wrapper">
                <!-- Pagination Info -->
                <div class="pagination-info">
                    <div class="pagination-stats">
                        Showing <strong><?php echo (($current_page_num - 1) * $projects_per_page) + 1; ?></strong> to
                        <strong><?php echo min($current_page_num * $projects_per_page, $total_projects); ?></strong> of
                        <strong><?php echo $total_projects; ?></strong>
                        <?php
                        if ($view_filter === 'owned') echo 'your projects';
                        elseif ($view_filter === 'bookmarked') echo 'bookmarked projects';
                        else echo 'projects';
                        ?>
                    </div>
                    <div class="pagination-summary">
                        Page <?php echo $current_page_num; ?> of <?php echo $total_pages; ?> pages
                    </div>
                </div>

                <!-- Main Pagination -->
                <nav aria-label="Project pagination">
                    <ul class="pagination">
                        <!-- First Page -->
                        <?php if ($current_page_num > 3): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"
                                   title="First Page">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Previous Page -->
                        <li class="page-item <?php echo ($current_page_num <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link"
                               href="<?php echo ($current_page_num <= 1) ? '#' : '?' . http_build_query(array_merge($_GET, ['page' => $current_page_num - 1])); ?>"
                                    <?php echo ($current_page_num <= 1) ? 'tabindex="-1" aria-disabled="true"' : ''; ?>
                               title="Previous Page">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $current_page_num - 2);
                        $end_page = min($total_pages, $current_page_num + 2);

                        // Ensure we show at least 5 pages when possible
                        if ($end_page - $start_page < 4) {
                            if ($start_page == 1) {
                                $end_page = min($total_pages, $start_page + 4);
                            } else {
                                $start_page = max(1, $end_page - 4);
                            }
                        }

                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo ($i == $current_page_num) ? 'active' : ''; ?>">
                                <a class="page-link"
                                   href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                    <?php echo $total_pages; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Next Page -->
                        <li class="page-item <?php echo ($current_page_num >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link"
                               href="<?php echo ($current_page_num >= $total_pages) ? '#' : '?' . http_build_query(array_merge($_GET, ['page' => $current_page_num + 1])); ?>"
                                    <?php echo ($current_page_num >= $total_pages) ? 'tabindex="-1" aria-disabled="true"' : ''; ?>
                               title="Next Page">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>

                        <!-- Last Page -->
                        <?php if ($current_page_num < $total_pages - 2): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"
                                   title="Last Page">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <!-- Quick Navigation -->
                <div class="pagination-nav">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"
                       class="pagination-nav-btn <?php echo ($current_page_num <= 1) ? 'disabled' : ''; ?>">
                        <i class="fas fa-fast-backward me-2"></i>First
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"
                       class="pagination-nav-btn <?php echo ($current_page_num >= $total_pages) ? 'disabled' : ''; ?>">
                        Last<i class="fas fa-fast-forward ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/all_projects.js"></script>
<script src="../assets/js/layout_user.js"></script>

</body>
</html>