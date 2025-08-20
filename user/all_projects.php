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

        /* Project Cards */
        .project-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            position: relative;
            cursor: pointer;
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

        .project-card .card-body {
            padding: var(--spacing-xl);
            position: relative;
        }

        .project-card .card-title {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: var(--spacing-md);
            line-height: 1.3;
        }

        .project-card .card-text {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: var(--spacing-lg);
            font-size: var(--font-size-base);
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

        /* Bookmark Buttons */
        .bookmark-float {
            position: absolute;
            top: var(--spacing-lg);
            right: var(--spacing-lg);
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
            justify-content: between;
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

            .project-card:hover {
                transform: translateY(-4px);
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
            .bookmark-inline {
                display: none;
            }
        }

        /* Focus States for Accessibility */
        .btn:focus,
        .form-control:focus,
        .form-select:focus,
        .page-link:focus {
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
    </style>
</head>
<body>
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<?php include "layout.php"; ?>

<!-- Main Content -->
<main class="main-content">
    <!-- Mobile Header -->


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
<script src="../assets/js/all_projects.js"></script>
<script src = "../assets/js/layout_user.js"></script>
</body
</html>