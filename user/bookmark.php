<?php
require_once __DIR__ . '/../includes/security_init.php';
// Production-safe error reporting
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Start session before any output or includes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = './';
include '../Login/Login/db.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : session_id();

// Handle bookmark toggle
if (isset($_POST['toggle_bookmark'])) {
    $project_id = $_POST['project_id'];
    $session_id = session_id();

    // Check if bookmark already exists for this project
    $check_sql = "SELECT id FROM bookmark WHERE project_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $project_id, $session_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Bookmark exists, so remove it
        $bookmark_data = $check_result->fetch_assoc();
        $delete_sql = "DELETE FROM bookmark WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $bookmark_data['id']);

        if ($delete_stmt->execute()) {
            $bookmark_message = "<div class='alert alert-info shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-bookmark me-2'></i>
                        <strong>Success!</strong> Project removed from bookmarks!
                    </div>
                  </div>";
        } else {
            $bookmark_message = "<div class='alert alert-danger shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Error!</strong> Failed to remove bookmark: " . $conn->error . "
                    </div>
                  </div>";
        }
        $delete_stmt->close();
    } else {
        // Add new bookmark
        $idea_id = 0; // Default value for idea_id
        $current_timestamp = date('Y-m-d H:i:s');

        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id, bookmarked_at) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isis", $project_id, $session_id, $idea_id, $current_timestamp);

        if ($insert_stmt->execute()) {
            $bookmark_message = "<div class='alert alert-success shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-bookmark-fill me-2'></i>
                        <strong>Success!</strong> Project added to bookmarks!
                    </div>
                  </div>";
        } else {
            $bookmark_message = "<div class='alert alert-danger shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Error!</strong> Failed to add bookmark: " . $conn->error . "
                    </div>
                  </div>";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// Get bookmarked projects for current user
$sql = "SELECT admin_approved_projects.*, 
        bookmark.bookmarked_at,
        bookmark.id as bookmark_id
        FROM admin_approved_projects 
        INNER JOIN bookmark ON admin_approved_projects.id = bookmark.project_id 
        WHERE bookmark.user_id = ? 
        ORDER BY bookmark.bookmarked_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Database query preparation failed. Please try again later.");
}
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
    die("User not authenticated");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die('Query failed: ' . $conn->error);
}
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../assets/js/anti_injection.js"></script>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Bookmarks - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
    <link rel="stylesheet" href="../assets/css/index.css">
       <style>
           :root {
               --primary-color: #6366f1;
               --secondary-color: #8b5cf6;
               --accent-color: #10b981;
               --warning-color: #f59e0b;
               --danger-color: #ef4444;
               --info-color: #06b6d4;
               --success-color: #10b981;
               --dark-color: #1e293b;
               --light-color: #f8fafc;
               --border-color: #e2e8f0;
               --text-primary: #1e293b;
               --text-secondary: #64748b;
               --text-muted: #94a3b8;
               --bg-primary: #ffffff;
               --bg-secondary: #f8fafc;
               --bg-tertiary: #f1f5f9;
               --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
               --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
               --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
               --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
               --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
               --gradient-accent: linear-gradient(135deg, var(--accent-color), #34d399);
               --gradient-warm: linear-gradient(135deg, var(--warning-color), #fbbf24);
           }

           body {
               font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
               background: var(--bg-secondary);
               color: var(--text-primary);
               line-height: 1.6;
               font-size: 14px;
               overflow-x: hidden;
           }

           .main-content {
               margin-left: 280px;
               padding: 2rem;
               min-height: 100vh;
               background: var(--bg-secondary);
               transition: margin-left 0.3s ease;
           }

           .bookmark-container {
               max-width: 1200px;
               margin: 0 auto;
           }

           .bookmark-header {
               background: var(--gradient-primary);
               color: white;
               padding: 3rem 2rem;
               border-radius: 1.5rem;
               margin-bottom: 2rem;
               text-align: center;
               position: relative;
               overflow: hidden;
               box-shadow: var(--shadow-xl);
           }

           .bookmark-header::before {
               content: '';
               position: absolute;
               top: 0;
               left: 0;
               right: 0;
               bottom: 0;
               background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 25%, transparent 25%),
               linear-gradient(-45deg, rgba(255, 255, 255, 0.1) 25%, transparent 25%);
               background-size: 20px 20px;
               background-position: 0 0, 0 10px;
               opacity: 0.3;
           }

           .bookmark-header h1 {
               font-size: 2.5rem;
               font-weight: 700;
               margin-bottom: 1rem;
               position: relative;
               z-index: 2;
               text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
           }

           .bookmark-header p {
               font-size: 1.1rem;
               opacity: 0.9;
               margin-bottom: 2rem;
               position: relative;
               z-index: 2;
           }

           /* Bookmark Stats */
           .bookmark-stats {
               display: flex;
               justify-content: center;
               gap: 3rem;
               position: relative;
               z-index: 2;
           }

           .stat-item {
               text-align: center;
           }

           .stat-number {
               display: block;
               font-size: 2rem;
               font-weight: 700;
               color: white;
               text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
           }

           .stat-label {
               font-size: 0.9rem;
               opacity: 0.8;
               text-transform: uppercase;
               letter-spacing: 0.5px;
           }

           .project-card {
               background: var(--bg-primary);
               border-radius: 1.25rem;
               box-shadow: var(--shadow-md);
               transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
               position: relative;
               overflow: hidden;
               border: 1px solid var(--border-color);
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

           .project-card:hover {
               transform: translateY(-8px);
               box-shadow: var(--shadow-xl);
           }

           .project-card:hover::before {
               opacity: 1;
           }

           .bookmark-icon {
               position: absolute;
               top: 1rem;
               right: 1rem;
               background: var(--gradient-primary);
               color: white;
               width: 45px;
               height: 45px;
               border-radius: 50%;
               display: flex;
               align-items: center;
               justify-content: center;
               font-size: 1.1rem;
               z-index: 10;
               box-shadow: var(--shadow-md);
           }

           .project-type-badge {
               background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
               color: var(--primary-color);
               padding: 0.4rem 0.8rem;
               border-radius: 20px;
               font-size: 0.75rem;
               font-weight: 600;
               text-transform: uppercase;
               letter-spacing: 0.5px;
               border: 1px solid rgba(99, 102, 241, 0.2);
           }

           .project-title {
               color: var(--text-primary);
               font-weight: 700;
               font-size: 1.2rem;
               margin-bottom: 1rem;
               line-height: 1.4;
           }

           .project-description {
               color: var(--text-secondary);
               line-height: 1.6;
               margin-bottom: 1.5rem;
               font-size: 0.95rem;
           }

           /* Project Meta */
           .project-meta {
               margin-bottom: 1.5rem;
           }

           .meta-item {
               display: flex;
               align-items: center;
               margin-bottom: 0.5rem;
               font-size: 0.85rem;
           }

           .meta-icon {
               color: var(--primary-color);
               width: 16px;
               margin-right: 0.5rem;
           }

           .meta-label {
               font-weight: 600;
               color: var(--text-primary);
               margin-right: 0.5rem;
           }

           .meta-value {
               color: var(--text-secondary);
           }

           /* Card Actions */
           .card-actions {
               display: flex;
               gap: 0.75rem;
               align-items: center;
           }

           .btn-modern {
               padding: 0.6rem 1.2rem;
               border-radius: 8px;
               font-weight: 600;
               font-size: 0.85rem;
               transition: all 0.3s ease;
               border: none;
               display: inline-flex;
               align-items: center;
               gap: 0.5rem;
               text-decoration: none;
           }

           .btn-primary-modern {
               background: var(--gradient-primary);
               color: white;
               flex: 1;
               justify-content: center;
           }

           .btn-primary-modern:hover {
               background: linear-gradient(135deg, #5b21b6, #7c3aed);
               transform: translateY(-2px);
               box-shadow: var(--shadow-md);
               color: white;
           }

           .btn-danger-modern {
               background: white;
               color: var(--danger-color);
               border: 2px solid var(--danger-color);
               width: 40px;
               height: 40px;
               padding: 0;
               justify-content: center;
               border-radius: 8px;
           }

           .btn-danger-modern:hover {
               background: var(--danger-color);
               color: white;
               transform: translateY(-2px);
           }

           .empty-state {
               text-align: center;
               padding: 5rem 2rem;
               background: var(--bg-primary);
               border-radius: 1.5rem;
               box-shadow: var(--shadow-lg);
               border: 1px solid var(--border-color);
           }

           .empty-state-icon {
               font-size: 4rem;
               color: var(--primary-color);
               margin-bottom: 1.5rem;
               opacity: 0.6;
           }

           .empty-state h3 {
               color: var(--text-primary);
               font-weight: 700;
               margin-bottom: 1rem;
           }

           .empty-state p {
               color: var(--text-secondary);
               font-size: 1.1rem;
               line-height: 1.6;
               margin-bottom: 2rem;
               max-width: 500px;
               margin-left: auto;
               margin-right: auto;
           }

           .btn-explore {
               background: var(--gradient-primary);
               color: white;
               padding: 1rem 2rem;
               border-radius: 0.75rem;
               text-decoration: none;
               font-weight: 600;
               display: inline-flex;
               align-items: center;
               gap: 0.75rem;
               transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
               box-shadow: var(--shadow-md);
           }

           .btn-explore:hover {
               background: linear-gradient(135deg, #5b21b6, #7c3aed);
               transform: translateY(-3px);
               box-shadow: var(--shadow-lg);
               color: white;
           }

           /* Alerts */
           .alert-modern {
               border: none;
               border-radius: 12px;
               margin-bottom: 2rem;
               box-shadow: var(--shadow-light);
           }

           .alert-info {
               background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
               color: var(--primary-color);
               border-left: 4px solid var(--primary-color);
           }

           .alert-success {
               background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
               color: var(--success-color);
               border-left: 4px solid var(--success-color);
           }

           .alert-danger {
               background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
               color: var(--danger-color);
               border-left: 4px solid var(--danger-color);
           }

           /* Modal Styles - Purple & White Theme */
           .modal-backdrop {
               backdrop-filter: blur(10px);
               background-color: rgba(99, 102, 241, 0.3);
           }

           .modal-content {
               background: white;
               backdrop-filter: blur(20px);
               border: 1px solid var(--border-color);
               border-radius: 16px;
               box-shadow: var(--shadow-heavy);
               overflow: hidden;
           }

           .modal-header {
               background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
               color: white;
               border-bottom: none;
               padding: 2rem;
               position: relative;
           }

           .modal-header::before {
               content: '';
               position: absolute;
               bottom: 0;
               left: 0;
               right: 0;
               height: 3px;
               background: linear-gradient(90deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0.8) 50%, rgba(255,255,255,0.3) 100%);
           }

           .modal-title {
               font-size: 1.8rem;
               font-weight: 700;
               text-shadow: 0 2px 4px rgba(0,0,0,0.1);
               color: white;
           }

           .btn-close {
               background: rgba(255, 255, 255, 0.2);
               border-radius: 50%;
               width: 40px;
               height: 40px;
               opacity: 1;
               filter: none;
               border: none;
           }

           .btn-close:hover {
               background: rgba(255, 255, 255, 0.3);
               transform: scale(1.1);
           }

           .modal-body {
               padding: 2rem;
               max-height: 70vh;
               overflow-y: auto;
               background: white;
           }

           .detail-section {
               background: rgba(99, 102, 241, 0.05);
               border-radius: 12px;
               padding: 1.5rem;
               margin-bottom: 1.5rem;
               border: 1px solid rgba(99, 102, 241, 0.1);
           }

           .detail-section h6 {
               color: var(--primary-color);
               font-weight: 700;
               font-size: 1.1rem;
               margin-bottom: 1rem;
               display: flex;
               align-items: center;
               gap: 0.5rem;
           }

           .detail-row {
               display: flex;
               margin-bottom: 0.75rem;
               align-items: flex-start;
           }

           .detail-row:last-child {
               margin-bottom: 0;
           }

           .detail-label {
               font-weight: 600;
               color: var(--text-primary);
               min-width: 120px;
               margin-right: 1rem;
           }

           .detail-value {
               color: var(--text-secondary);
               flex: 1;
               word-break: break-word;
           }

           .status-badge {
               padding: 0.5rem 1rem;
               border-radius: 20px;
               font-size: 0.85rem;
               font-weight: 600;
               text-transform: uppercase;
               letter-spacing: 0.5px;
               border: 2px solid;
           }

           .status-approved {
               background: white;
               color: var(--success-color);
               border-color: var(--success-color);
           }

           .status-pending {
               background: white;
               color: var(--warning-color);
               border-color: var(--warning-color);
           }

           .status-rejected {
               background: white;
               color: var(--danger-color);
               border-color: var(--danger-color);
           }

           .file-link {
               display: inline-flex;
               align-items: center;
               gap: 0.5rem;
               color: var(--primary-color);
               text-decoration: none;
               padding: 0.5rem 1rem;
               background: white;
               border: 2px solid var(--primary-color);
               border-radius: 8px;
               transition: all 0.3s ease;
               margin-right: 0.5rem;
               margin-bottom: 0.5rem;
               font-weight: 500;
           }

           .file-link:hover {
               background: var(--primary-color);
               color: white;
               transform: translateY(-2px);
               box-shadow: var(--shadow-light);
           }

           .description-text {
               line-height: 1.7;
               color: var(--text-primary);
               text-align: justify;
               background: white;
               padding: 1rem;
               border-radius: 8px;
               border: 1px solid var(--border-color);
           }

           .project-image {
               width: 100%;
               max-width: 400px;
               height: auto;
               border-radius: 12px;
               box-shadow: var(--shadow-light);
               margin: 0 auto;
               display: block;
               border: 2px solid var(--border-color);
           }

           .modal-footer {
               background: rgba(99, 102, 241, 0.05);
               border-top: 1px solid var(--border-color);
               padding: 1.5rem 2rem;
           }

           .btn-modal-action {
               padding: 0.75rem 2rem;
               border-radius: 8px;
               font-weight: 600;
               transition: all 0.3s ease;
               border: none;
           }

           .btn-modal-primary {
               background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
               color: white;
           }

           .btn-modal-primary:hover {
               background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
               transform: translateY(-2px);
               box-shadow: var(--shadow-medium);
               color: white;
           }

           .btn-modal-secondary {
               background: white;
               color: var(--primary-color);
               border: 2px solid var(--primary-color);
           }

           .btn-modal-secondary:hover {
               background: var(--primary-color);
               color: white;
           }

           /* Loading Spinner */
           .loading-spinner {
               display: inline-block;
               width: 20px;
               height: 20px;
               border: 3px solid rgba(99, 102, 241, 0.3);
               border-radius: 50%;
               border-top-color: var(--primary-color);
               animation: spin 1s ease-in-out infinite;
           }

           @keyframes spin {
               to { transform: rotate(360deg); }
           }

           /* Scrollbar Styling */
           .modal-body::-webkit-scrollbar {
               width: 6px;
           }

           .modal-body::-webkit-scrollbar-track {
               background: rgba(99, 102, 241, 0.1);
               border-radius: 3px;
           }

           .modal-body::-webkit-scrollbar-thumb {
               background: var(--primary-color);
               border-radius: 3px;
           }

           .modal-body::-webkit-scrollbar-thumb:hover {
               background: var(--secondary-color);
           }

           /* Animation */
           .modal.fade .modal-dialog {
               transform: translate(0, -50px) scale(0.9);
           }

           .modal.show .modal-dialog {
               transform: translate(0, 0) scale(1);
           }

           /* Card Body and Footer */
           .card-body {
               background: white;
               padding: 1.5rem;
           }

           .card-footer {
               background: white;
               border-top: 1px solid var(--border-color);
               padding: 1.5rem;
           }

           /* Text Colors */
           .text-muted {
               color: var(--text-secondary) !important;
           }

           /* Responsive Design */
           @media (max-width: 1024px) {
               .main-content {
                   margin-left: 0;
                   padding: 1.5rem;
               }
           }

           @media (max-width: 768px) {
               .bookmark-header {
                   padding: 2rem 1rem;
               }

               .bookmark-header h1 {
                   font-size: 2rem;
               }

               .bookmark-stats {
                   flex-direction: column;
                   gap: 1.5rem;
               }

               .stat-item {
                   display: flex;
                   justify-content: space-between;
                   align-items: center;
                   background: rgba(255, 255, 255, 0.1);
                   padding: 1rem;
                   border-radius: 8px;
               }

               .stat-number {
                   font-size: 1.5rem;
               }

               .modal-header {
                   padding: 1.5rem;
               }

               .modal-body {
                   padding: 1.5rem;
               }

               .modal-footer {
                   padding: 1rem 1.5rem;
               }

               .card-actions {
                   flex-direction: column;
                   align-items: stretch;
               }

               .btn-danger-modern {
                   width: 100%;
                   height: auto;
                   padding: 0.6rem;
               }
           }

           @media (max-width: 480px) {
               .main-content {
                   padding: 0.5rem;
               }

               .bookmark-header {
                   padding: 1.5rem 1rem;
                   margin-bottom: 1rem;
               }

               .bookmark-header h1 {
                   font-size: 1.5rem;
               }

               .project-card {
                   margin-bottom: 1rem;
               }

               .detail-row {
                   flex-direction: column;
                   gap: 0.25rem;
               }

               .detail-label {
                   min-width: auto;
                   margin-right: 0;
                   margin-bottom: 0.25rem;
               }
           }

           /* Additional Purple & White Enhancements */
           .card {
               background: white;
               border: 1px solid var(--border-color);
           }

           .bg-transparent {
               background: transparent !important;
           }

           .border-0 {
               border: none !important;
           }

           /* Ensure all backgrounds are white or purple gradients */
           * {
               scrollbar-width: thin;
               scrollbar-color: var(--primary-color) rgba(99, 102, 241, 0.1);
           }

           /* Focus states for accessibility */
           .btn:focus,
           button:focus {
               outline: 2px solid var(--primary-color);
               outline-offset: 2px;
           }

           /* Hover states for better interactivity */
           .project-card .card-body:hover .project-title {
               color: var(--primary-color);
               transition: color 0.3s ease;
           }

           /* Date formatting enhancement */
           .meta-value:has(.fas.fa-calendar-alt) {
               font-weight: 500;
           }
       </style>
        <link rel="stylesheet" href="../assets/css/loader.css">
</head>
    <body>

    <?php include 'layout.php'  ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="bookmark-container">
            <!-- Bookmark Header -->
            <div class="bookmark-header">
                <h1><i class="fas fa-bookmark me-3"></i>My Bookmarks</h1>
                <p>Your curated collection of favorite projects and ideas</p>

                <div class="bookmark-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo true; ?></span>
                        <span class="stat-label">Bookmarked Projects</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo date('M'); ?></span>
                        <span class="stat-label">This Month</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo date('Y'); ?></span>
                        <span class="stat-label">This Year</span>
                    </div>
                </div>
            </div>

            <!-- Display bookmark message if set -->
            <?php if (isset($bookmark_message)) : ?>
                <div class="alert alert-modern">
                    <?php echo $bookmark_message; ?>
                </div>
            <?php endif; ?>

            <!-- Bookmarked Projects -->
            <div class="row g-4">
                <?php if ($result && $result->num_rows > 0) : ?>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card project-card h-100 border-0">
                                <div class="bookmark-icon">
                                    <i class="fas fa-bookmark"></i>
                                </div>

                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="project-type-badge">
                                        <?php echo htmlspecialchars($row['project_type']); ?>
                                    </span>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo date('M d, Y', strtotime($row['bookmarked_at'])); ?>
                                        </small>
                                    </div>

                                    <h5 class="project-title">
                                        <?php echo htmlspecialchars($row['project_name']); ?>
                                    </h5>

                                    <p class="project-description">
                                        <?php echo htmlspecialchars(mb_strimwidth($row['description'], 0, 120, '...')); ?>
                                    </p>

                                    <div class="project-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-tags meta-icon"></i>
                                            <span class="meta-label">Classification:</span>
                                            <span class="meta-value"><?php echo htmlspecialchars($row['classification']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-code meta-icon"></i>
                                            <span class="meta-label">Language:</span>
                                            <span class="meta-value"><?php echo htmlspecialchars($row['language']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-calendar-alt meta-icon"></i>
                                            <span class="meta-label">Submitted:</span>
                                            <span class="meta-value"><?php echo date('M d, Y', strtotime($row['submission_date'])); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer bg-transparent border-0 p-4 pt-0">
                                    <div class="card-actions">
                                        <button type="button" class="btn btn-modern btn-primary-modern"
                                                data-bs-toggle="modal"
                                                data-bs-target="#projectModal"
                                                onclick="loadProjectDetails(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </button>
                                        <form method="post" style="display:inline; flex: 0 0 auto;">
                                            <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="toggle_bookmark" class="btn btn-modern btn-danger-modern" title="Remove Bookmark">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-bookmark empty-state-icon"></i>
                            <h3>No Bookmarks Yet</h3>
                            <p>Start exploring amazing projects and save your favorites to this collection. Your bookmarks will appear here for easy access.</p>
                            <a href="all_projects.php" class="btn-explore">
                                <i class="fas fa-search"></i>
                                Explore Projects
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Project Details Modal -->
    <div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="projectModalLabel">
                        <i class="fas fa-project-diagram me-2"></i>
                        <span id="modalProjectName">Project Details</span>
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modalContent">
                        <div class="text-center">
                            <div class="loading-spinner"></div>
                            <p class="mt-3 text-muted">Loading project details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                    <button type="button" class="btn btn-modal-primary" onclick="bookmarkProject()">
                        <i class="fas fa-bookmark me-2"></i>Toggle Bookmark
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentProject = null;

        function loadProjectDetails(project) {
            currentProject = project;

            // Update modal title
            document.getElementById('modalProjectName').textContent = project.project_name;

            // Show loading state
            document.getElementById('modalContent').innerHTML = `
        <div class="text-center">
            <div class="loading-spinner"></div>
            <p class="mt-3 text-muted">Loading project details...</p>
        </div>
    `;

            // Simulate loading delay for better UX
            setTimeout(() => {
                const modalContent = `
            <!-- Project Image Section -->
            ${project.image_path ? `
            <div class="detail-section">
                <h6><i class="fas fa-image"></i>Project Image</h6>
                <img src="${project.image_path}" alt="${project.project_name}" class="project-image"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display: none;" class="text-center text-muted py-4">
                    <i class="fas fa-image fa-3x mb-3 opacity-50"></i>
                    <p>Image not available</p>
                </div>
            </div>
            ` : ''}

            <!-- Basic Information -->
            <div class="detail-section">
                <h6><i class="fas fa-info-circle"></i>Basic Information</h6>
                <div class="detail-row">
                    <span class="detail-label">Project Name:</span>
                    <span class="detail-value">${escapeHtml(project.project_name)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type:</span>
                    <span class="detail-value">
                        <span class="project-type-badge">${escapeHtml(project.project_type)}</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Classification:</span>
                    <span class="detail-value">${escapeHtml(project.classification)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Language:</span>
                    <span class="detail-value">${escapeHtml(project.language)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge status-${project.status}">
                            ${escapeHtml(project.status)}
                        </span>
                    </span>
                </div>
            </div>

            <!-- Project Description -->
            <div class="detail-section">
                <h6><i class="fas fa-align-left"></i>Project Description</h6>
                <div class="description-text">
                    ${escapeHtml(project.description)}
                </div>
            </div>

            <!-- Timeline Information -->
            <div class="detail-section">
                <h6><i class="fas fa-clock"></i>Timeline</h6>
                <div class="detail-row">
                    <span class="detail-label">Submitted:</span>
                    <span class="detail-value">${formatDate(project.submission_date)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Bookmarked:</span>
                    <span class="detail-value">${formatDate(project.bookmarked_at)}</span>
                </div>
            </div>

            <!-- Files Section -->
            ${(project.video_path || project.code_file_path || project.instruction_file_path) ? `
            <div class="detail-section">
                <h6><i class="fas fa-files"></i>Project Files</h6>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    ${project.video_path ? `<a href="download.php?file=${encodeURIComponent(project.video_path.split('/').pop())}" class="file-link" target="_blank"><i class="fas fa-video"></i>Video</a>` : ''}
                    ${project.code_file_path ? `<a href="download.php?file=${encodeURIComponent(project.code_file_path.split('/').pop())}" class="file-link" target="_blank"><i class="fas fa-code"></i>Source Code</a>` : ''}
                    ${project.instruction_file_path ? `<a href="download.php?file=${encodeURIComponent(project.instruction_file_path.split('/').pop())}" class="file-link" target="_blank"><i class="fas fa-file-pdf"></i>Instructions</a>` : ''}
                </div>
            </div>
            ` : ''}

            <!-- Additional Information -->
            <div class="detail-section">
                <h6><i class="fas fa-database"></i>System Information</h6>
                <div class="detail-row">
                    <span class="detail-label">Project ID:</span>
                    <span class="detail-value">#${project.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Bookmark ID:</span>
                    <span class="detail-value">#${project.bookmark_id}</span>
                </div>
            </div>
        `;

                document.getElementById('modalContent').innerHTML = modalContent;
            }, 500);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || 'Not specified';
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        function bookmarkProject() {
            if (currentProject) {
                // Create a form to toggle bookmark
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'project_id';
                input.value = currentProject.id;

                const button = document.createElement('button');
                button.type = 'submit';
                button.name = 'toggle_bookmark';

                form.appendChild(input);
                form.appendChild(button);
                document.body.appendChild(form);

                form.submit();
            }
        }

        // Add smooth scroll to top when modal closes
        document.getElementById('projectModal').addEventListener('hidden.bs.modal', function () {
            currentProject = null;
        });
    </script>

    
<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../assets/js/loader.js"></script>
</body>
    </html>

<?php
// Close the database statement and connection
if (isset($stmt)) {
    $stmt->close();
}
if (isset($conn)) {
    $conn->close();
}

include $basePath . 'layout_footer.php';
?>