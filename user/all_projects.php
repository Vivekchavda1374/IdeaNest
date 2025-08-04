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
$sql = "SELECT ap.*, CASE WHEN b.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
        FROM admin_approved_projects ap
        LEFT JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?
        ORDER BY ap.submission_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $session_id);
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
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-500: #64748b;
            --gray-700: #334155;
            --danger-color: #ef4444;
            --border-radius: 12px;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--white);
            border-right: 1px solid var(--gray-200);
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
            box-shadow: var(--shadow-lg);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .sidebar-logo-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .sidebar-logo-text {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-user-avatar {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .sidebar-user-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .sidebar-user-info p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin: 0;
        }

        .sidebar-nav {
            padding: 1.5rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
            padding: 0 1.5rem;
        }

        .nav-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
            margin-bottom: 1rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            color: var(--gray-700);
            text-decoration: none;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
            position: relative;
        }

        .nav-item:hover {
            background: var(--gray-100);
            color: var(--primary-color);
            transform: translateX(4px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .nav-text {
            flex: 1;
            font-weight: 500;
        }

        /* Main content area */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 2rem;
        }

        /* Mobile header for menu toggle */
        .mobile-header {
            display: none;
            background: var(--white);
            padding: 1rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
        }

        .mobile-menu-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        /* Project cards */
        .projects-header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .projects-header h2 {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .projects-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-700);
            font-weight: 500;
        }

        .stat-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }

        .project-card {
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .project-card:hover {
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            transform: translateY(-8px) scale(1.02);
        }

        .project-card:hover::before {
            opacity: 1;
        }

        .bookmark-float {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 2;
        }

        .bookmark-float button {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .bookmark-float button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .project-card .card-body {
            padding: 2rem;
            padding-top: 3rem;
        }

        .project-card .card-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .project-card .card-text {
            color: #64748b;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .project-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .project-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
        }

        .badge-classification {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .badge-type {
            background: rgba(103, 102, 234, 0.1);
            color: #6766ea;
            border: 1px solid rgba(103, 102, 234, 0.2);
        }

        .project-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--gray-500);
            margin-bottom: 1rem;
        }

        .bookmark-inline {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            background: none;
            border: 2px solid #e2e8f0;
            color: #64748b;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .bookmark-inline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .bookmark-inline.bookmarked {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-color: transparent;
            color: white;
        }

        /* Modal styles */
        .project-modal-glass .modal-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .project-modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border: none;
        }

        .project-modal-header .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .project-modal-desc {
            background: rgba(103, 102, 234, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            font-size: 1.1rem;
            line-height: 1.7;
            color: #2d3748;
            border: 1px solid rgba(103, 102, 234, 0.1);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        /* Mobile responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .mobile-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .projects-header h2 {
                font-size: 2rem;
            }

            .projects-stats {
                flex-direction: column;
                gap: 1rem;
            }
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .overlay.active {
            display: block;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="sidebar-logo-text">IdeaNest</div>
            </div>
            <div class="sidebar-user">
                <div class="sidebar-user-avatar"><?php echo htmlspecialchars($user_initial); ?></div>
                <div class="sidebar-user-info">
                    <h4><?php echo htmlspecialchars($user_name); ?></h4>
                    <p>Innovator</p>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="<?php echo $basePath; ?>index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <i class="fas fa-home nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="<?php echo $basePath; ?>all_projects.php" class="nav-item <?php echo ($current_page == 'all_projects.php') ? 'active' : ''; ?>">
                    <i class="fas fa-project-diagram nav-icon"></i>
                    <span class="nav-text">All Projects</span>
                </a>
                <a href="<?php echo $basePath; ?>Blog/form.php" class="nav-item <?php echo ($current_page == 'form.php') ? 'active' : ''; ?>">
                    <i class="fas fa-lightbulb nav-icon"></i>
                    <span class="nav-text">Ideas</span>
                </a>
                <a href="<?php echo $basePath; ?>bookmark.php" class="nav-item <?php echo ($current_page == 'bookmark.php') ? 'active' : ''; ?>">
                    <i class="fas fa-bookmark nav-icon"></i>
                    <span class="nav-text">Bookmarks</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Create</div>
                <a href="<?php echo $basePath; ?>forms/new_project_add.php" class="nav-item <?php echo ($current_page == 'new_project_add.php') ? 'active' : ''; ?>">
                    <i class="fas fa-plus nav-icon"></i>
                    <span class="nav-text">New Project</span>
                </a>
                <a href="<?php echo $basePath; ?>Blog/form.php" class="nav-item <?php echo ($current_page == 'form.php') ? 'active' : ''; ?>">
                    <i class="fas fa-edit nav-icon"></i>
                    <span class="nav-text">New Idea</span>
                </a>
                <a href="<?php echo $basePath; ?>search.php" class="nav-item <?php echo ($current_page == 'search.php') ? 'active' : ''; ?>">
                    <i class="fas fa-search nav-icon"></i>
                    <span class="nav-text">Search</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Community</div>
                <a href="<?php echo $basePath; ?>all_projects.php" class="nav-item <?php echo ($current_page == 'all_projects.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users nav-icon"></i>
                    <span class="nav-text">Explore Projects</span>
                </a>
                <a href="<?php echo $basePath; ?>Blog/list-project.php" class="nav-item <?php echo ($current_page == 'list-project.php') ? 'active' : ''; ?>">
                    <i class="fas fa-list nav-icon"></i>
                    <span class="nav-text">All Ideas</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Account</div>
                <a href="<?php echo $basePath; ?>user_profile_setting.php" class="nav-item <?php echo ($current_page == 'user_profile_setting.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user nav-icon"></i>
                    <span class="nav-text">Profile</span>
                </a>
                <a href="<?php echo $basePath; ?>user_profile_setting.php" class="nav-item <?php echo ($current_page == 'user_profile_setting.php') ? 'active' : ''; ?>">
                    <i class="fas fa-cog nav-icon"></i>
                    <span class="nav-text">Settings</span>
                </a>
                <a href="<?php echo $basePath; ?>../Login/Login/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt nav-icon"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Mobile Header -->
        <div class="mobile-header">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="mb-0">All Projects</h5>
        </div>

        <!-- Projects Header -->
        <div class="projects-header">
            <h2><i class="fas fa-project-diagram me-3"></i>All Approved Projects</h2>
            <p class="text-muted mb-0">Discover innovative projects from our community of creators</p>
            <div class="projects-stats">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <span><?php echo count($projects); ?> Projects</span>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <span>Community Driven</span>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <span>Curated Content</span>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($bookmark_message)) echo $bookmark_message; ?>

        <!-- Projects Grid -->
        <div class="row g-4">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $project): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card project-card h-100">
                            <form method="post" class="bookmark-float">
                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                <button type="submit" name="toggle_bookmark" title="<?php echo $project['is_bookmarked'] ? 'Remove from bookmarks' : 'Add to bookmarks'; ?>">
                                    <i class="fas fa-bookmark<?php echo $project['is_bookmarked'] ? '' : '-o'; ?>" style="color:<?php echo $project['is_bookmarked'] ? '#f72585' : '#aaa'; ?>;"></i>
                                </button>
                            </form>
                            
                            <div class="card-body" data-bs-toggle="modal" data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                <h5 class="card-title"><?php echo htmlspecialchars($project['project_name']); ?></h5>
                                <p class="card-text">
                                    <?php echo htmlspecialchars(mb_strimwidth($project['description'], 0, 120, '...')); ?>
                                </p>
                                
                                <div class="project-badges">
                                    <span class="project-badge badge-classification"><?php echo htmlspecialchars($project['classification']); ?></span>
                                    <?php if (!empty($project['project_type'])): ?>
                                        <span class="project-badge badge-type"><?php echo htmlspecialchars($project['project_type']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="project-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo isset($project['submission_date']) ? htmlspecialchars($project['submission_date']) : (isset($project['created_at']) ? htmlspecialchars($project['created_at']) : ''); ?></span>
                                </div>
                                
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                    <button type="submit" name="toggle_bookmark" class="bookmark-inline<?php echo $project['is_bookmarked'] ? ' bookmarked' : ''; ?>">
                                        <i class="fas fa-bookmark<?php echo $project['is_bookmarked'] ? '' : '-o'; ?>"></i>
                                        <span><?php echo $project['is_bookmarked'] ? 'Bookmarked' : 'Add Bookmark'; ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for project details -->
                    <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="projectModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg project-modal-glass">
                            <div class="modal-content">
                                <div class="modal-header project-modal-header">
                                    <h5 class="modal-title" id="projectModalLabel<?php echo $project['id']; ?>">
                                        <i class="fas fa-project-diagram me-2"></i><?php echo htmlspecialchars($project['project_name']); ?>
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3"><strong class="text-secondary">Classification:</strong> <?php echo htmlspecialchars($project['classification']); ?></div>
                                            <div class="mb-3"><strong class="text-secondary">Type:</strong> <?php echo htmlspecialchars($project['project_type'] ?? ''); ?></div>
                                            <div class="mb-3"><strong class="text-secondary">Submitted:</strong> <?php echo isset($project['submission_date']) ? htmlspecialchars($project['submission_date']) : (isset($project['created_at']) ? htmlspecialchars($project['created_at']) : ''); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3"><strong class="text-secondary">ID:</strong> <?php echo $project['id']; ?></div>
                                            <?php if (!empty($project['language'])): ?>
                                                <div class="mb-3"><strong class="text-secondary">Language:</strong> <?php echo htmlspecialchars($project['language']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <h6 class="fw-bold mb-3">Description</h6>
                                        <div class="project-modal-desc">
                                            <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($project['project_file_path'])): ?>
                                        <div class="mb-3">
                                            <a href="<?php echo htmlspecialchars($project['project_file_path']); ?>" class="btn btn-outline-primary" target="_blank">
                                                <i class="fas fa-download me-2"></i> Download Project File
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <h4 class="mb-3">No Projects Found</h4>
                        <p class="text-muted mb-4">There are currently no approved projects to display. Check back later for new submissions!</p>
                        <a href="<?php echo $basePath; ?>forms/new_project_add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Submit Your Project
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar functionality
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

            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    // Fade out
                    alert.style.transition = "opacity 0.5s, transform 0.5s";
                    alert.style.opacity = 0;
                    alert.style.transform = "translateY(-20px)";
                    // Remove from DOM after fade
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 3000); // 3 seconds
            });

            // Add smooth scroll behavior for better UX
            document.documentElement.style.scrollBehavior = 'smooth';

            // Add loading state for bookmark buttons
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && submitBtn.name === 'toggle_bookmark') {
                        submitBtn.style.opacity = '0.6';
                        submitBtn.style.pointerEvents = 'none';
                    }
                });
            });
        });

        // Add intersection observer for cards animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Apply animation to project cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.project-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });
        });
    </script>
</body>
</html>