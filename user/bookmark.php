<?php
include "../Login/Login/db.php";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set default notification values
$notification = [
    'show' => false,
    'type' => '',
    'icon' => '',
    'message' => ''
];

// Handle bookmark removal if requested
if (isset($_POST['remove_bookmark']) && isset($_POST['project_id'])) {
    $project_id = filter_var($_POST['project_id'], FILTER_SANITIZE_NUMBER_INT);
    $session_id = session_id();

    $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("is", $project_id, $session_id);

    if ($stmt->execute()) {
        $notification = [
            'show' => true,
            'type' => 'success',
            'icon' => 'bi-check-circle-fill',
            'message' => 'Bookmark successfully removed!'
        ];
    } else {
        $notification = [
            'show' => true,
            'type' => 'danger',
            'icon' => 'bi-exclamation-triangle-fill',
            'message' => 'Error removing bookmark: ' . $conn->error
        ];
    }
    $stmt->close();
}

// Fetch all bookmarked projects for current session
$sql = "SELECT admin_approved_projects.* 
        FROM bookmark
        JOIN admin_approved_projects ON bookmark.project_id = admin_approved_projects.id
        WHERE bookmark.user_id = ?
        ORDER BY admin_approved_projects.submission_date DESC";

$stmt = $conn->prepare($sql);
$session_id = session_id();
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Bookmarked Projects</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            /* Modern Color Palette */
            :root {
                --primary-color: #4361ee;
                --primary-light: #4895ef;
                --primary-dark: #3a0ca3;
                --accent-color: #f72585;
                --accent-light: #f94144;
                --success-color: #10b981;
                --success-light: #d1fae5;
                --danger-color: #ef4444;
                --danger-light: #fee2e2;
                --warning-color: #f59e0b;
                --warning-light: #fef3c7;
                --info-color: #3b82f6;
                --info-light: #dbeafe;
                --background-color: #f8fafc;
                --card-bg: #ffffff;
                --text-primary: #1e293b;
                --text-secondary: #64748b;
                --border-color: #e2e8f0;
                --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
                --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
                --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
                --transition-speed: 0.3s;
            }

            /* Base Styles */
            body {
                font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
                background-color: var(--background-color);
                color: var(--text-primary);
                line-height: 1.6;
                position: relative;
                min-height: 100vh;
                padding-bottom: 5rem;
            }

            /* Dashboard Header */
            .dashboard-header {
                background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
                color: white;
                padding: 2.5rem 0;
                margin-bottom: 2.5rem;
                border-radius: 0 0 30px 30px;
                box-shadow: var(--shadow-lg);
                position: relative;
                overflow: hidden;
            }

            .dashboard-header::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -10%;
                width: 300px;
                height: 300px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
            }

            .dashboard-header::after {
                content: '';
                position: absolute;
                bottom: -30%;
                left: -5%;
                width: 200px;
                height: 200px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 50%;
            }

            .dashboard-title {
                font-weight: 700;
                margin: 0;
                display: flex;
                align-items: center;
                font-size: 2rem;
                position: relative;
                z-index: 2;
            }

            .dashboard-title i {
                margin-right: 15px;
                font-size: 2.2rem;
                color: rgba(255, 255, 255, 0.9);
            }

            .dashboard-subtitle {
                color: rgba(255, 255, 255, 0.8);
                font-weight: 400;
                margin-top: 0.5rem;
                position: relative;
                z-index: 2;
            }

            /* Section Styling */
            .section-title {
                display: flex;
                align-items: center;
                margin: 2rem 0 1.5rem;
                padding-bottom: 0.75rem;
                border-bottom: 2px solid var(--primary-light);
                color: var(--primary-dark);
                font-weight: 700;
                font-size: 1.3rem;
            }

            .section-title i {
                margin-right: 10px;
                color: var(--primary-color);
            }

            /* Card Styling */
            .project-card {
                margin-bottom: 2rem;
                border-radius: 16px;
                border: none;
                box-shadow: var(--shadow-md);
                transition: all var(--transition-speed) ease;
                overflow: hidden;
                background-color: var(--card-bg);
                position: relative;
            }

            .project-card:hover {
                transform: translateY(-6px);
                box-shadow: var(--shadow-lg);
            }

            .card-header {
                background-color: var(--card-bg);
                border-bottom: 1px solid var(--border-color);
                padding: 1.5rem;
                position: relative;
            }

            .card-body {
                padding: 1.5rem;
            }

            /* Badge Styling */
            .badge {
                padding: 0.5rem 1rem;
                font-weight: 600;
                font-size: 0.75rem;
                border-radius: 50px;
                box-shadow: var(--shadow-sm);
            }

            .badge-approved {
                background: linear-gradient(45deg, var(--success-color), #34d399);
                color: white;
            }

            /* Project Details */
            .project-detail {
                margin-bottom: 1rem;
                font-size: 0.95rem;
            }

            .project-detail strong {
                color: var(--primary-dark);
                font-weight: 600;
                display: inline-block;
                margin-bottom: 0.25rem;
            }

            .project-detail p {
                color: var(--text-secondary);
                line-height: 1.8;
            }

            /* File Links */
            .file-link {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
                padding: 10px 15px;
                border-radius: 10px;
                background-color: #f1f5f9;
                transition: all 0.2s ease;
                text-decoration: none;
                color: var(--text-primary);
                border-left: 3px solid transparent;
            }

            .file-link:hover {
                background-color: #e2e8f0;
                border-left: 3px solid var(--primary-color);
                transform: translateX(3px);
                color: var(--primary-dark);
            }

            .file-link i {
                margin-right: 10px;
                font-size: 1.2rem;
                color: var(--primary-color);
            }

            /* Button Styling */
            .btn-action {
                border-radius: 50px;
                padding: 10px 24px;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.85rem;
                letter-spacing: 0.5px;
                transition: all 0.3s ease;
                box-shadow: var(--shadow-sm);
            }

            .btn-primary {
                background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
                border: none;
            }

            .btn-primary:hover {
                background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
                transform: translateY(-2px);
                box-shadow: var(--shadow-md);
            }

            /* Bookmark Button */
            .bookmark-btn {
                background: none;
                border: none;
                color: var(--accent-color);
                font-size: 1.3rem;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-left: 12px;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                position: relative;
                z-index: 5;
            }

            .bookmark-btn:hover {
                background-color: rgba(247, 37, 133, 0.1);
                transform: scale(1.15);
            }

            .bookmark-btn i {
                transition: all 0.3s ease;
            }

            .bookmark-btn:hover i {
                color: var(--accent-light);
            }

            /* Empty State */
            .empty-projects {
                text-align: center;
                padding: 4rem 2rem;
                background-color: var(--card-bg);
                border-radius: 16px;
                box-shadow: var(--shadow-md);
                transition: all var(--transition-speed) ease;
            }

            .empty-projects:hover {
                transform: translateY(-3px);
                box-shadow: var(--shadow-lg);
            }

            .empty-projects i {
                font-size: 5rem;
                color: #cbd5e1;
                margin-bottom: 1.5rem;
                opacity: 0.7;
            }

            .empty-projects h3 {
                color: var(--text-primary);
                font-weight: 600;
                margin-bottom: 1rem;
            }

            .empty-projects p {
                color: var(--text-secondary);
                max-width: 500px;
                margin: 0 auto 1.5rem;
            }

            .browse-link {
                display: inline-flex;
                align-items: center;
                background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
                color: white;
                text-decoration: none;
                padding: 12px 25px;
                border-radius: 50px;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: var(--shadow-sm);
            }

            .browse-link:hover {
                transform: translateY(-2px);
                box-shadow: var(--shadow-md);
                color: white;
            }

            .browse-link i {
                margin-right: 8px;
                color: white;
                opacity: 1;
            }

            /* Alert Styling */
            .toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
            }

            .toast {
                border-radius: 12px;
                box-shadow: var(--shadow-md);
                border: none;
                min-width: 300px;
                background-color: white;
                overflow: hidden;
            }

            .toast-header {
                border-bottom: none;
                padding: 0.75rem 1rem;
                background-color: transparent;
            }

            .toast-body {
                padding: 0.75rem 1rem 1rem;
            }

            .toast.success .toast-header {
                color: var(--success-color);
                background-color: var(--success-light);
            }

            .toast.danger .toast-header {
                color: var(--danger-color);
                background-color: var(--danger-light);
            }

            .alert {
                border-radius: 12px;
                padding: 1rem 1.5rem;
                margin-bottom: 1.5rem;
                border: none;
                box-shadow: var(--shadow-sm);
            }

            .alert-success {
                background-color: var(--success-light);
                color: var(--success-color);
                border-left: 4px solid var(--success-color);
            }

            .alert-danger {
                background-color: var(--danger-light);
                color: var(--danger-color);
                border-left: 4px solid var(--danger-color);
            }

            /* Responsive Adjustments */
            @media (max-width: 992px) {
                .dashboard-header {
                    padding: 1.5rem 0;
                    margin-bottom: 2rem;
                }

                .dashboard-title {
                    font-size: 1.5rem;
                }

                .card-header {
                    padding: 1.25rem;
                }

                .card-body {
                    padding: 1.25rem;
                }
            }

            @media (max-width: 768px) {
                .action-buttons {
                    flex-direction: column;
                    width: 100%;
                }

                .btn-action {
                    width: 100%;
                    margin-bottom: 10px;
                }

                .project-card {
                    margin-bottom: 1.5rem;
                }

                .section-title {
                    font-size: 1.2rem;
                }

                .project-detail {
                    margin-bottom: 0.75rem;
                }
            }

            @media (max-width: 576px) {
                .dashboard-header {
                    border-radius: 0 0 15px 15px;
                    padding: 1.25rem 0;
                }

                .empty-projects {
                    padding: 3rem 1.5rem;
                }

                .card-header {
                    flex-direction: column;
                    align-items: flex-start !important;
                }

                .card-header .badge {
                    margin-top: 0.75rem;
                }

                .bookmark-btn {
                    position: absolute;
                    top: 1rem;
                    right: 1rem;
                }
            }

            /* Animation Effects */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .project-card {
                animation: fadeIn 0.5s ease-out forwards;
                animation-delay: calc(var(--animation-order) * 0.1s);
                opacity: 0;
            }

            .toast {
                animation: slideIn 0.5s ease-out forwards;
            }

            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }

            /* Footer */
            .page-footer {
                background-color: #f1f5f9;
                padding: 1.5rem 0;
                position: absolute;
                bottom: 0;
                width: 100%;
                text-align: center;
                color: var(--text-secondary);
                font-size: 0.9rem;
            }

            /* Truncate text */
            .truncate-3-lines {
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Tags */
            .tag {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 50px;
                font-size: 0.8rem;
                font-weight: 500;
                margin-right: 8px;
                margin-bottom: 8px;
                background-color: #f1f5f9;
                color: var(--primary-dark);
                transition: all 0.2s ease;
            }

            .tag:hover {
                background-color: var(--primary-light);
                color: white;
                transform: translateY(-2px);
            }

            .tag i {
                margin-right: 4px;
                font-size: 0.7rem;
            }
        </style>
    </head>

    <body>
    <div class="dashboard-header">
        <div class="container">
            <h1 class="dashboard-title">
                <i class="bi bi-bookmark-heart-fill"></i>
                My Bookmarked Projects
            </h1>
            <p class="dashboard-subtitle">Access your saved projects for quick reference</p>
        </div>
    </div>

    <div class="container">
        <?php if ($notification['show']): ?>
            <div class="alert alert-<?php echo $notification['type']; ?> shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="bi <?php echo $notification['icon']; ?> me-2"></i>
                    <strong><?php echo $notification['type'] === 'success' ? 'Success!' : 'Alert!'; ?></strong>
                    <?php echo $notification['message']; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bookmarked Projects Section -->
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="section-title">
                <i class="bi bi-bookmark-fill me-2"></i>My Bookmarks
            </h2>
            <a href="user_project_search.php" class="btn btn-sm btn-primary d-none d-md-flex align-items-center">
                <i class="bi bi-search me-1"></i> Browse Projects
            </a>
        </div>

        <?php
        if ($result && $result->num_rows > 0) {
            $counter = 0;
            while($row = $result->fetch_assoc()) {
                $counter++;
                ?>
                <div class="project-card card" style="--animation-order: <?php echo $counter; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($row["project_name"]); ?></h5>
                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this bookmark?');">
                                <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                                <button type="submit" name="remove_bookmark" class="bookmark-btn" title="Remove bookmark">
                                    <i class="bi bi-bookmark-fill"></i>
                                </button>
                            </form>
                        </div>
                        <span class="badge badge-approved">
                    <i class="bi bi-check-circle me-1"></i>
                    Approved
                </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="project-detail mt-2">
                                    <strong><i class="bi bi-text-paragraph me-1"></i> Description:</strong>
                                    <p class="mt-2 truncate-3-lines"><?php echo nl2br(htmlspecialchars($row["description"])); ?></p>
                                    <button class="btn btn-link btn-sm p-0 mt-1 show-more">Show more</button>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="project-detail">
                                            <strong><i class="bi bi-tag me-1"></i> Type:</strong>
                                            <span class="tag"><i class="bi bi-bookmark"></i><?php echo htmlspecialchars($row["project_type"]); ?></span>
                                        </div>
                                        <div class="project-detail">
                                            <strong><i class="bi bi-bookmark me-1"></i> Classification:</strong>
                                            <span class="tag"><i class="bi bi-layers"></i><?php echo htmlspecialchars($row["classification"]); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="project-detail">
                                            <strong><i class="bi bi-code-slash me-1"></i> Language:</strong>
                                            <span class="tag"><i class="bi bi-code"></i><?php echo htmlspecialchars($row["language"]); ?></span>
                                        </div>
                                        <div class="project-detail">
                                            <strong><i class="bi bi-calendar-date me-1"></i> Submitted:</strong>
                                            <span class="text-secondary"><?php echo date("F j, Y", strtotime($row["submission_date"])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title fw-bold mb-3"><i class="bi bi-file-earmark me-1"></i> Project Files</h6>

                                        <?php if(!empty($row["image_path"])): ?>
                                            <a href="<?php echo htmlspecialchars($row["image_path"]); ?>" target="_blank"
                                               class="file-link">
                                                <i class="bi bi-file-earmark-image"></i> View Image
                                            </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["video_path"])): ?>
                                            <a href="<?php echo htmlspecialchars($row["video_path"]); ?>" target="_blank"
                                               class="file-link">
                                                <i class="bi bi-file-earmark-play"></i> View Video
                                            </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["code_file_path"])): ?>
                                            <a href="<?php echo htmlspecialchars($row["code_file_path"]); ?>" target="_blank"
                                               class="file-link">
                                                <i class="bi bi-file-earmark-code"></i> View Code
                                            </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["instruction_file_path"])): ?>
                                            <a href="<?php echo htmlspecialchars($row["instruction_file_path"]); ?>" target="_blank"
                                               class="file-link">
                                                <i class="bi bi-file-earmark-text"></i> View Instructions
                                            </a>
                                        <?php endif; ?>

                                        <a href="user_project_search.php?id=<?php echo $row["id"]; ?>" class="btn btn-primary btn-sm d-block mt-3">
                                            <i class="bi bi-eye me-1"></i> View Full Project
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="empty-projects">
                <i class="bi bi-bookmark"></i>
                <h3>No Bookmarked Projects</h3>
                <p class="text-muted">You haven't bookmarked any projects yet. Browse approved projects and click the
                    bookmark icon to add them here.</p>
                <a href="user_project_search.php" class="browse-link">
                    <i class="bi bi-search"></i> Browse Projects
                </a>
            </div>
            <?php
        }
        ?>

        <div class="d-block d-md-none text-center mt-3 mb-5">
            <a href="user_project_search.php" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Browse Projects
            </a>
        </div>
    </div>

    <footer class="page-footer">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date("Y"); ?> IdeaNest. All rights reserved.</p>
        </div>
    </footer>

    <!-- Toast notification container -->
    <div class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide full description
        document.addEventListener('DOMContentLoaded', function() {
            const showMoreButtons = document.querySelectorAll('.show-more');

            showMoreButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const description = this.previousElementSibling;

                    if (description.classList.contains('truncate-3-lines')) {
                        description.classList.remove('truncate-3-lines');
                        this.textContent = 'Show less';
                    } else {
                        description.classList.add('truncate-3-lines');
                        this.textContent = 'Show more';
                    }
                });
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                });
            }, 5000);
        });
    </script>
    </body>

    </html>

<?php
// Close connection
$stmt->close();
$conn->close();
?>