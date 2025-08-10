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

// Fetch all approved projects with bookmark status
$session_id = session_id();

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_classification = isset($_GET['classification']) ? trim($_GET['classification']) : '';
$filter_type = isset($_GET['type']) ? trim($_GET['type']) : '';

// Pagination settings
$projects_per_page = 9;
$current_page_num = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page_num - 1) * $projects_per_page;

// First, get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects ap WHERE 1=1";
$count_params = [];
$count_types = "";

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

// Main query with pagination
$sql = "SELECT ap.*, CASE WHEN b.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
        FROM admin_approved_projects ap
        LEFT JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?
        WHERE 1=1";
$params = [$session_id];
$types = "s";

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
        :root {
            --sidebar-width: 280px;
            /* Purple Theme Colors */
            --primary-purple: #8B5CF6;
            --secondary-purple: #A78BFA;
            --dark-purple: #7C3AED;
            --light-purple: #C4B5FD;
            --extra-light-purple: #EDE9FE;
            --purple-gradient: linear-gradient(135deg, #8B5CF6 0%, #A78BFA 100%);
            --purple-gradient-dark: linear-gradient(135deg, #7C3AED 0%, #8B5CF6 100%);
            --purple-gradient-light: linear-gradient(135deg, #C4B5FD 0%, #DDD6FE 100%);

            /* Supporting Colors */
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;

            /* Status Colors */
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;

            /* Design System */
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --border-radius-lg: 20px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            --shadow-purple: 0 20px 40px rgba(139, 92, 246, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--gray-800);
            line-height: 1.6;
        }

        /* Main content area */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 2rem;
            position: relative;
        }

        /* Mobile header for menu toggle */
        .mobile-header {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
        }

        .mobile-menu-toggle {
            background: var(--purple-gradient);
            border: none;
            padding: 0.75rem;
            border-radius: var(--border-radius-sm);
            color: white;
            font-size: 1.2rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mobile-menu-toggle:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        /* Projects Header */
        .projects-header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius-lg);
            padding: 3rem 2.5rem;
            margin-bottom: 2.5rem;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-xl);
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
            background: var(--purple-gradient);
        }

        .projects-header h2 {
            background: var(--purple-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 3rem;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .projects-header p {
            color: var(--gray-600);
            font-size: 1.125rem;
            font-weight: 400;
            margin-bottom: 2rem;
        }

        .projects-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: rgba(139, 92, 246, 0.05);
            border-radius: var(--border-radius);
            border: 1px solid rgba(139, 92, 246, 0.1);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background: rgba(139, 92, 246, 0.1);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: var(--purple-gradient);
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            box-shadow: var(--shadow-purple);
        }

        .stat-text {
            color: var(--gray-700);
            font-weight: 600;
            font-size: 1rem;
        }

        /* Search and Filter Form */
        .filter-form {
            background: rgba(255, 255, 255, 0.8);
            padding: 2rem;
            border-radius: var(--border-radius);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow-sm);
        }

        .filter-form .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .filter-form .form-control,
        .filter-form .form-select {
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius-sm);
            padding: 0.875rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .filter-form .form-control:focus,
        .filter-form .form-select:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            background: white;
        }

        .btn-primary {
            background: var(--purple-gradient);
            border: none;
            padding: 0.875rem 2rem;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            letter-spacing: 0.025em;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: var(--purple-gradient-dark);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Project Cards */
        .project-card {
            border-radius: var(--border-radius-lg);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-lg);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            height: 100%;
        }

        .project-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--purple-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .project-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-2xl), var(--shadow-purple);
        }

        .project-card:hover::before {
            transform: scaleX(1);
        }

        .bookmark-float {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 2;
        }

        .bookmark-float button {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius-sm);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .bookmark-float button:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-lg);
            background: white;
        }

        .bookmark-float button i {
            font-size: 1.25rem;
            transition: all 0.2s ease;
        }

        .project-card .card-body {
            padding: 2.5rem 2rem;
            padding-top: 3.5rem;
        }

        .project-card .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 1rem;
            line-height: 1.3;
            letter-spacing: -0.01em;
        }

        .project-card .card-text {
            color: var(--gray-600);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .project-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .project-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            letter-spacing: 0.025em;
        }

        .badge-classification {
            background: var(--purple-gradient);
            color: white;
            box-shadow: var(--shadow-purple);
        }

        .badge-type {
            background: var(--extra-light-purple);
            color: var(--dark-purple);
            border: 1px solid var(--light-purple);
        }

        .project-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--gray-500);
            margin-bottom: 1.5rem;
        }

        .bookmark-inline {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            border-radius: var(--border-radius-sm);
            background: none;
            border: 2px solid var(--gray-300);
            color: var(--gray-600);
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
        }

        .bookmark-inline:hover {
            border-color: var(--primary-purple);
            color: var(--primary-purple);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .bookmark-inline.bookmarked {
            background: var(--purple-gradient);
            border-color: var(--primary-purple);
            color: white;
            box-shadow: var(--shadow-purple);
        }

        .bookmark-inline i {
            font-size: 1rem;
        }

        /* Modal Enhancements */
        .project-modal-glass .modal-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-2xl);
            overflow: hidden;
        }

        .project-modal-header {
            background: var(--purple-gradient);
            color: white;
            padding: 2.5rem;
            border: none;
        }

        .project-modal-header .modal-title {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .project-modal-desc {
            background: rgba(139, 92, 246, 0.05);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(139, 92, 246, 0.1);
            margin-top: 1rem;
        }

        /* Enhanced Pagination */
        .pagination-container {
            margin-top: 4rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            padding: 3rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .pagination-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--purple-gradient);
        }

        .pagination-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
            padding: 0;
            list-style: none;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination .page-item {
            margin: 0;
        }

        .pagination .page-item .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 48px;
            height: 48px;
            padding: 0 0.75rem;
            border-radius: var(--border-radius-sm);
            border: 2px solid var(--gray-200);
            background: white;
            color: var(--gray-700);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }

        .pagination .page-item .page-link:hover {
            background: var(--purple-gradient);
            color: white;
            border-color: var(--primary-purple);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg), var(--shadow-purple);
        }

        .pagination .page-item.active .page-link {
            background: var(--purple-gradient);
            color: white;
            border-color: var(--primary-purple);
            box-shadow: var(--shadow-md), var(--shadow-purple);
            transform: scale(1.05);
        }

        .pagination .page-item.disabled .page-link {
            background: var(--gray-100);
            color: var(--gray-400);
            border-color: var(--gray-200);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .pagination .page-item.disabled .page-link:hover {
            background: var(--gray-100);
            color: var(--gray-400);
            border-color: var(--gray-200);
            transform: none;
            box-shadow: none;
        }

        .pagination-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            text-align: center;
        }

        .pagination-stats {
            color: var(--gray-600);
            font-weight: 500;
            font-size: 1rem;
        }

        .pagination-stats strong {
            color: var(--primary-purple);
            font-weight: 700;
        }

        .pagination-summary {
            color: var(--gray-500);
            font-size: 0.875rem;
            font-style: italic;
        }

        /* Pagination Navigation Controls */
        .pagination-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .pagination-nav-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--gray-300);
            background: white;
            color: var(--gray-600);
            text-decoration: none;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .pagination-nav-btn:hover:not(.disabled) {
            border-color: var(--primary-purple);
            color: var(--primary-purple);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .pagination-nav-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Alerts */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            padding: 1.25rem 1.5rem;
            font-weight: 500;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-xl);
        }

        .empty-state-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 2rem;
            background: var(--purple-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            box-shadow: var(--shadow-purple);
        }

        .empty-state h4 {
            color: var(--gray-800);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--gray-600);
            font-size: 1.125rem;
        }

        /* Mobile responsive */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .mobile-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .projects-header {
                padding: 2rem 1.5rem;
            }

            .projects-header h2 {
                font-size: 2.25rem;
            }

            .projects-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .pagination {
                gap: 0.25rem;
            }

            .pagination .page-item .page-link {
                min-width: 40px;
                height: 40px;
                font-size: 0.85rem;
            }

            .pagination-container {
                padding: 2rem 1rem;
            }

            .pagination-nav {
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        @media (max-width: 640px) {
            .projects-header h2 {
                font-size: 1.875rem;
            }

            .filter-form {
                padding: 1.5rem;
            }

            .stat-item {
                padding: 1rem;
            }

            .project-card .card-body {
                padding: 2rem 1.5rem;
            }
        }

        /* Loading Animation */
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

        /* Hover Effects */
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-lift:hover {
            transform: translateY(-2px);
        }

        /* Purple Glow Effect */
        .purple-glow {
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
        }
    </style>
</head>
<body>
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<?php include "layout.php"; ?>

<!-- Main Content -->
<main class="main-content">
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h5 class="mb-0 fw-bold">All Projects</h5>
    </div>

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
                    <div class="stat-text"><?php echo $total_projects; ?> Total Projects</div>
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

        <!-- Search and Filters -->
        <form method="get" class="filter-form row g-3 align-items-end">
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

    <!-- Projects Grid -->
    <div class="row g-4 mb-4">
        <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $index => $project): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card project-card h-100 fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <form method="post" class="bookmark-float">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <button type="submit" name="toggle_bookmark"
                                    title="<?php echo $project['is_bookmarked'] ? 'Remove from bookmarks' : 'Add to bookmarks'; ?>">
                                <i class="fas fa-bookmark"
                                   style="color: <?php echo $project['is_bookmarked'] ? '#8B5CF6' : '#cbd5e1'; ?>;
                                           opacity: <?php echo $project['is_bookmarked'] ? '1' : '0.6'; ?>;"></i>
                            </button>
                        </form>

                        <div class="card-body" data-bs-toggle="modal" data-bs-target="#projectModal<?php echo $project['id']; ?>">
                            <h5 class="card-title"><?php echo htmlspecialchars($project['project_name']); ?></h5>
                            <p class="card-text">
                                <?php echo htmlspecialchars(mb_strimwidth($project['description'], 0, 120, '...')); ?>
                            </p>

                            <div class="project-badges">
                                    <span class="project-badge badge-classification">
                                        <?php echo htmlspecialchars($project['classification']); ?>
                                    </span>
                                <?php if (!empty($project['project_type'])): ?>
                                    <span class="project-badge badge-type">
                                            <?php echo htmlspecialchars($project['project_type']); ?>
                                        </span>
                                <?php endif; ?>
                            </div>

                            <div class="project-date">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo isset($project['submission_date']) ? htmlspecialchars($project['submission_date']) : (isset($project['created_at']) ? htmlspecialchars($project['created_at']) : ''); ?></span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                    <button type="submit" name="toggle_bookmark"
                                            class="bookmark-inline<?php echo $project['is_bookmarked'] ? ' bookmarked' : ''; ?>">
                                        <i class="fas fa-bookmark"></i>
                                        <span><?php echo $project['is_bookmarked'] ? 'Bookmarked' : 'Bookmark'; ?></span>
                                    </button>
                                </form>
                                <small class="text-muted">
                                    <i class="fas fa-eye me-1"></i>Click to view details
                                </small>
                            </div>
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
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong class="text-secondary d-block mb-1">Classification:</strong>
                                            <span class="badge badge-classification"><?php echo htmlspecialchars($project['classification']); ?></span>
                                        </div>
                                        <div class="mb-3">
                                            <strong class="text-secondary d-block mb-1">Type:</strong>
                                            <span class="badge badge-type"><?php echo htmlspecialchars($project['project_type'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="mb-3">
                                            <strong class="text-secondary d-block mb-1">Submitted:</strong>
                                            <?php echo isset($project['submission_date']) ? htmlspecialchars($project['submission_date']) : (isset($project['created_at']) ? htmlspecialchars($project['created_at']) : 'N/A'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong class="text-secondary d-block mb-1">Project ID:</strong>
                                            #<?php echo $project['id']; ?>
                                        </div>
                                        <?php if (!empty($project['language'])): ?>
                                            <div class="mb-3">
                                                <strong class="text-secondary d-block mb-1">Language:</strong>
                                                <?php echo htmlspecialchars($project['language']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="mb-3">
                                            <strong class="text-secondary d-block mb-1">Status:</strong>
                                            <span class="badge" style="background: var(--success-color); color: white;">
                                                    <i class="fas fa-check-circle me-1"></i>Approved
                                                </span>
                                        </div>
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
                                    <button type="submit" name="toggle_bookmark" class="btn btn-primary">
                                        <i class="fas fa-bookmark me-2"></i>
                                        <?php echo $project['is_bookmarked'] ? 'Remove Bookmark' : 'Add Bookmark'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state fade-in-up">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4>No projects found</h4>
                    <p>We couldn't find any projects matching your search criteria. Try adjusting your filters or search terms.</p>
                    <a href="?<?php echo http_build_query(array_filter($_GET, function($key) { return !in_array($key, ['search', 'classification', 'type']); }, ARRAY_FILTER_USE_KEY)); ?>"
                       class="btn btn-primary mt-3 hover-lift">
                        <i class="fas fa-refresh me-2"></i>Clear Filters
                    </a>
                </div>
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
                        <strong><?php echo $total_projects; ?></strong> projects
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

<script>
    // Enhanced JavaScript functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu toggle functionality
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            });
        }

        // Close sidebar when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 1024) {
                if (sidebar && !sidebar.contains(event.target) &&
                    mobileMenuToggle && !mobileMenuToggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                }
            }
        });

        // Navigation item click handlers - only for mobile sidebar closing
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Close sidebar on mobile after clicking (but allow navigation to proceed)
                if (window.innerWidth <= 1024) {
                    setTimeout(() => {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('active');
                    }, 100); // Small delay to allow navigation to start
                }
            });
        });

        // Responsive sidebar handling
        function handleResize() {
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        }

        window.addEventListener('resize', handleResize);

        // Auto-hide alerts with enhanced animation
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            // Add initial animation
            alert.style.animation = 'fadeInUp 0.5s ease-out';

            setTimeout(function() {
                // Fade out with slide up effect
                alert.style.transition = "all 0.5s cubic-bezier(0.4, 0, 0.2, 1)";
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-30px) scale(0.95)';
                alert.style.maxHeight = '0';
                alert.style.padding = '0';
                alert.style.margin = '0';

                // Remove from DOM after animation
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            }, 4000); // Show for 4 seconds
        });

        // Enhanced loading states for bookmark buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && submitBtn.name === 'toggle_bookmark') {
                    // Add loading state
                    const originalHTML = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    submitBtn.style.opacity = '0.7';
                    submitBtn.style.pointerEvents = 'none';

                    // If form submission fails, restore button
                    setTimeout(() => {
                        if (submitBtn) {
                            submitBtn.innerHTML = originalHTML;
                            submitBtn.style.opacity = '1';
                            submitBtn.style.pointerEvents = 'auto';
                        }
                    }, 3000);
                }
            });
        });

        // Smooth scroll for pagination
        document.querySelectorAll('.pagination .page-link, .pagination-nav-btn').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href') !== '#' && !this.closest('.disabled')) {
                    // Add loading effect
                    this.style.opacity = '0.6';
                    this.style.pointerEvents = 'none';

                    // Smooth scroll to top
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Enhanced intersection observer for staggered animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Apply staggered animation to project cards
        const cards = document.querySelectorAll('.project-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';
            card.style.transition = `all 0.6s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.1}s`;
            observer.observe(card);
        });

        // Add purple glow effect on card hover
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('purple-glow');
            });

            card.addEventListener('mouseleave', function() {
                this.classList.remove('purple-glow');
            });
        });

        // Enhanced modal animations
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('show.bs.modal', function() {
                this.querySelector('.modal-content').style.animation = 'fadeInUp 0.4s ease-out';
            });
        });

        // Add ripple effect to buttons
        document.querySelectorAll('.btn, .page-link').forEach(button => {
            button.addEventListener('click', function(e) {
                if (this.classList.contains('disabled') || this.getAttribute('href') === '#') {
                    return;
                }

                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255, 255, 255, 0.3);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        pointer-events: none;
                    `;

                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
        document.head.appendChild(style);

        // Enhanced form validation and UX
        const searchInput = document.getElementById('search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    // Add visual feedback for search
                    if (this.value.length > 0) {
                        this.style.borderColor = 'var(--primary-purple)';
                        this.style.boxShadow = '0 0 0 3px rgba(139, 92, 246, 0.1)';
                    } else {
                        this.style.borderColor = '';
                        this.style.boxShadow = '';
                    }
                }, 300);
            });
        }
    });
</script>
</body
</html>