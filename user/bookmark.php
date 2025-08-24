<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$session_id = session_id();
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die('Query failed: ' . $conn->error);
}
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Bookmarks - IdeaNest</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="../assets/css/bookmark.css">
        <style>
            /* Enhanced Modal Styles */
            .modal-backdrop {
                backdrop-filter: blur(10px);
                background-color: rgba(102, 126, 234, 0.3);
            }

            .modal-content {
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 24px;
                box-shadow: 0 25px 50px rgba(102, 126, 234, 0.2);
                overflow: hidden;
            }

            .modal-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            }

            .btn-close {
                background: rgba(255, 255, 255, 0.2);
                border-radius: 50%;
                width: 40px;
                height: 40px;
                opacity: 1;
                filter: none;
            }

            .btn-close:hover {
                background: rgba(255, 255, 255, 0.3);
                transform: scale(1.1);
            }

            .modal-body {
                padding: 2rem;
                max-height: 70vh;
                overflow-y: auto;
            }

            .detail-section {
                background: rgba(102, 126, 234, 0.05);
                border-radius: 16px;
                padding: 1.5rem;
                margin-bottom: 1.5rem;
                border: 1px solid rgba(102, 126, 234, 0.1);
            }

            .detail-section h6 {
                color: #667eea;
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
                color: #374151;
                min-width: 120px;
                margin-right: 1rem;
            }

            .detail-value {
                color: #6b7280;
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
            }

            .status-approved {
                background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
                color: white;
            }

            .status-pending {
                background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
                color: white;
            }

            .status-rejected {
                background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
                color: white;
            }

            .file-link {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                color: #667eea;
                text-decoration: none;
                padding: 0.5rem 1rem;
                background: rgba(102, 126, 234, 0.1);
                border-radius: 12px;
                transition: all 0.3s ease;
                margin-right: 0.5rem;
                margin-bottom: 0.5rem;
            }

            .file-link:hover {
                background: rgba(102, 126, 234, 0.2);
                color: #5b21b6;
                transform: translateY(-2px);
            }

            .description-text {
                line-height: 1.7;
                color: #374151;
                text-align: justify;
            }

            .project-image {
                width: 100%;
                max-width: 400px;
                height: auto;
                border-radius: 12px;
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                margin: 0 auto;
                display: block;
            }

            .modal-footer {
                background: rgba(102, 126, 234, 0.05);
                border-top: 1px solid rgba(102, 126, 234, 0.1);
                padding: 1.5rem 2rem;
            }

            .btn-modal-action {
                padding: 0.75rem 2rem;
                border-radius: 12px;
                font-weight: 600;
                transition: all 0.3s ease;
                border: none;
            }

            .btn-modal-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }

            .btn-modal-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
                color: white;
            }

            .btn-modal-secondary {
                background: rgba(102, 126, 234, 0.1);
                color: #667eea;
                border: 1px solid rgba(102, 126, 234, 0.2);
            }

            .btn-modal-secondary:hover {
                background: rgba(102, 126, 234, 0.2);
                color: #5b21b6;
            }

            /* Scrollbar Styling */
            .modal-body::-webkit-scrollbar {
                width: 6px;
            }

            .modal-body::-webkit-scrollbar-track {
                background: rgba(102, 126, 234, 0.1);
                border-radius: 3px;
            }

            .modal-body::-webkit-scrollbar-thumb {
                background: rgba(102, 126, 234, 0.4);
                border-radius: 3px;
            }

            .modal-body::-webkit-scrollbar-thumb:hover {
                background: rgba(102, 126, 234, 0.6);
            }

            /* Animation */
            .modal.fade .modal-dialog {
                transform: translate(0, -50px) scale(0.9);
            }

            .modal.show .modal-dialog {
                transform: translate(0, 0) scale(1);
            }

            .loading-spinner {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid rgba(102, 126, 234, 0.3);
                border-radius: 50%;
                border-top-color: #667eea;
                animation: spin 1s ease-in-out infinite;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        </style>
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
                        <span class="stat-number"><?php echo $result ? $result->num_rows : 0; ?></span>
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
            <?php if (isset($bookmark_message)): ?>
                <div class="alert alert-modern">
                    <?php echo $bookmark_message; ?>
                </div>
            <?php endif; ?>

            <!-- Bookmarked Projects -->
            <div class="row g-4">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
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
                <?php else: ?>
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
                    ${project.video_path ? `<a href="${project.video_path}" class="file-link" target="_blank"><i class="fas fa-video"></i>Video</a>` : ''}
                    ${project.code_file_path ? `<a href="${project.code_file_path}" class="file-link" target="_blank"><i class="fas fa-code"></i>Source Code</a>` : ''}
                    ${project.instruction_file_path ? `<a href="${project.instruction_file_path}" class="file-link" target="_blank"><i class="fas fa-file-pdf"></i>Instructions</a>` : ''}
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