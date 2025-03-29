<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle bookmark toggle - Instagram style (allow multiple bookmarks)
if (isset($_POST['toggle_bookmark'])) {
    $project_id = $_POST['project_id'];
    $session_id = session_id();

    // Check if bookmark already exists for this project
    $check_sql = "SELECT * FROM bookmark WHERE project_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $project_id, $session_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Bookmark exists, so remove it
        $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("is", $project_id, $session_id);

        if ($delete_stmt->execute()) {
            echo "<div class='alert alert-info shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-bookmark me-2'></i>
                        <strong>Success!</strong> Project removed from bookmarks!
                    </div>
                  </div>";
        } else {
            echo "<div class='alert alert-danger shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Error!</strong> " . $conn->error . "
                    </div>
                  </div>";
        }
        $delete_stmt->close();
    } else {
        // Add new bookmark
        $idea_id = 0; // Default value for idea_id or you could make this field nullable

        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isi", $project_id, $session_id, $idea_id);

        if ($insert_stmt->execute()) {
            echo "<div class='alert alert-success shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-bookmark-fill me-2'></i>
                        <strong>Success!</strong> Project added to bookmarks!
                    </div>
                  </div>";
        } else {
            echo "<div class='alert alert-danger shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Error!</strong> " . $conn->error . "
                    </div>
                  </div>";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// Get approved projects with bookmark status for current user
$sql = "SELECT admin_approved_projects.*, 
        CASE WHEN bookmark.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
        FROM admin_approved_projects 
        LEFT JOIN bookmark ON admin_approved_projects.id = bookmark.project_id AND bookmark.user_id = ? 
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
    <title>Approved Projects | IdeaNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Add Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3a86ff;
            --secondary-color: #4361ee;
            --tertiary-color: #4895ef;
            --success-color: #2ec4b6;
            --info-color: #4cc9f0;
            --warning-color: #ff9f1c;
            --danger-color: #e71d36;
            --light-color: #f8f9fa;
            --dark-color: #1f2937;
            --border-radius: 12px;
            --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            --hover-transform: translateY(-5px);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: #374151;
            line-height: 1.6;
        }

        /* Modern navbar with gradient */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
        }

        .navbar-brand i {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2.5rem 0 3.5rem;
            position: relative;
            margin-bottom: 4rem;
            border-radius: 0 0 30px 30px;
            box-shadow: var(--box-shadow);
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            border-radius: 0 0 30px 30px;
            z-index: 0;
        }

        .dashboard-title {
            position: relative;
            z-index: 1;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            font-size: 2.2rem;
        }

        .dashboard-title i {
            margin-right: 15px;
            font-size: 2.4rem;
        }

        /* Toast container */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            border-radius: var(--border-radius);
        }

        /* Project cards */
        .project-card {
            margin-bottom: 1.8rem;
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
            background: white;
        }

        .project-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--tertiary-color));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .project-card:hover {
            transform: var(--hover-transform);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }

        .project-card:hover::after {
            opacity: 1;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .card-body {
            padding: 1.75rem;
        }

        /* Search and filter section */
        .search-filter-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }

        .search-input {
            border-radius: 50px;
            padding-left: 1rem;
            padding-right: 1rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            border-color: var(--primary-color);
        }

        /* Badges */
        .badge {
            padding: 0.6rem 1rem;
            font-weight: 600;
            font-size: 0.75rem;
            border-radius: 50px;
        }

        .badge-approved {
            background-color: var(--success-color);
            color: white;
        }

        /* File links */
        .files-container {
            background: #f9fafb;
            border-radius: var(--border-radius);
            padding: 1.25rem;
            height: 100%;
        }

        .file-link {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px 16px;
            border-radius: var(--border-radius);
            background-color: white;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #374151;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .file-link:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .file-link:hover i {
            color: white;
        }

        .file-link i {
            margin-right: 12px;
            font-size: 1.25rem;
            color: var(--primary-color);
            transition: color 0.2s ease;
        }

        /* Project details */
        .project-detail {
            margin-bottom: 15px;
            padding-bottom: 10px;
        }

        .project-detail:not(:last-child) {
            border-bottom: 1px dashed #e5e7eb;
        }

        .project-detail strong {
            color: var(--secondary-color);
            font-weight: 600;
            display: inline-block;
            margin-bottom: 5px;
        }

        .project-detail p {
            margin-bottom: 0;
        }

        /* Bookmark button */
        .bookmark-btn {
            background: none;
            border: none;
            color: var(--warning-color);
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bookmark-btn:hover {
            background-color: rgba(255, 159, 28, 0.1);
            transform: scale(1.15);
        }

        .bookmark-btn.active {
            color: var(--warning-color);
        }

        .bookmark-btn.active i {
            animation: bookmark-added 0.6s ease;
        }

        @keyframes bookmark-added {
            0% { transform: scale(1); }
            50% { transform: scale(1.4); }
            100% { transform: scale(1); }
        }

        /* Section title with icon */
        .section-title {
            position: relative;
            margin: 2rem 0 1.5rem;
            padding-bottom: 0.75rem;
            color: var(--secondary-color);
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--tertiary-color));
            border-radius: 2px;
        }

        .section-title i {
            margin-right: 12px;
            font-size: 1.5rem;
        }

        /* Empty state */
        .empty-projects {
            text-align: center;
            padding: 4rem 2rem;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-projects i {
            font-size: 5rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .empty-projects h3 {
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        /* Category pills */
        .category-pill {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 50px;
            background-color: #e5e7eb;
            color: #4b5563;
            font-size: 0.75rem;
            font-weight: 500;
            margin-right: 5px;
            margin-bottom: 5px;
            transition: all 0.2s ease;
        }

        .category-pill:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Description with gradient border */
        .description-container {
            border-left: 4px solid;
            border-image: linear-gradient(to bottom, var(--primary-color), var(--tertiary-color)) 1;
            padding-left: 15px;
            margin-top: 15px;
        }

        /* Alert styling */
        .alert {
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
        }

        .alert-info {
            background-color: #eff6ff;
            color: #1e40af;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #b91c1c;
        }

        /* Project stats */
        .project-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: #f9fafb;
            border-radius: var(--border-radius);
            padding: 12px 15px;
            flex: 1;
            min-width: 120px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }

        .stat-item:hover {
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .dashboard-title {
                font-size: 1.8rem;
            }

            .dashboard-title i {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 2rem 0 2.5rem;
            }

            .dashboard-title {
                font-size: 1.5rem;
            }

            .dashboard-title i {
                font-size: 1.7rem;
            }

            .project-stats {
                flex-direction: column;
                gap: 10px;
            }

            .stat-item {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<!-- Toast Container for notifications -->
<div class="toast-container"></div>

<!-- Modern Navbar -->
<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="bi bi-lightning-charge-fill"></i>
            IdeaNest
        </a>
    </div>
</nav>

<!-- Dashboard Header -->
<div class="dashboard-header">
    <div class="container">
        <h1 class="dashboard-title">
            <i class="bi bi-kanban"></i>
            Approved Projects
        </h1>
    </div>
</div>

<div class="container">
    <!-- Search and Filter Section -->
    <div class="search-filter-container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                    <input type="text" class="form-control search-input border-start-0" id="searchProjects"
                           placeholder="Search projects...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-md-end mt-3 mt-md-0">
                    <select class="form-select me-2" style="max-width: 150px;">
                        <option selected>All Types</option>
                        <option>Hardware</option>
                        <option>Software</option>
                        <option>Research</option>
                    </select>
                    <select class="form-select" style="max-width: 150px;">
                        <option selected>Sort By</option>
                        <option>Newest</option>
                        <option>Oldest</option>
                        <option>A-Z</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Stats -->
    <div class="project-stats">
        <div class="stat-item">
            <div class="stat-value"><?php echo $result ? $result->num_rows : 0; ?></div>
            <div class="stat-label">Total Projects</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">
                <?php
                $bookmarked = 0;
                if ($result) {
                    mysqli_data_seek($result, 0);
                    while($row = $result->fetch_assoc()) {
                        if ($row["is_bookmarked"]) $bookmarked++;
                    }
                    mysqli_data_seek($result, 0);
                }
                echo $bookmarked;
                ?>
            </div>
            <div class="stat-label">Bookmarked</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">
                <?php
                $recent = 0;
                if ($result) {
                    mysqli_data_seek($result, 0);
                    $now = time();
                    $week_ago = $now - (7 * 24 * 60 * 60);
                    while($row = $result->fetch_assoc()) {
                        $submit_time = strtotime($row["submission_date"]);
                        if ($submit_time > $week_ago) $recent++;
                    }
                    mysqli_data_seek($result, 0);
                }
                echo $recent;
                ?>
            </div>
            <div class="stat-label">Recent (7d)</div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer"></div>


    <!-- Approved Projects Section -->
    <h2 class="section-title">
        <i class="bi bi-check-circle"></i>
        Admin Approved Projects
    </h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="row" id="projectContainer">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="col-lg-6 project-item">
                    <div class="project-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($row["project_name"]); ?></h5>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-approved">Approved</span>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                                    <button type="submit" name="toggle_bookmark" class="bookmark-btn <?php echo $row["is_bookmarked"] ? 'active' : ''; ?>">
                                        <i class="bi <?php echo $row["is_bookmarked"] ? 'bi-bookmark-fill' : 'bi-bookmark'; ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="description-container mb-3">
                                <p><?php echo htmlspecialchars($row["description"]); ?></p>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="project-detail">
                                        <strong><i class="bi bi-person-fill me-1"></i> Project Lead</strong>
                                        <p><?php echo htmlspecialchars($row["id"]); ?></p>
                                    </div>
                                    <div class="project-detail">
                                        <strong><i class="bi bi-calendar-event me-1"></i> Submission Date</strong>
                                        <p><?php echo date('F j, Y', strtotime($row["submission_date"])); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="project-detail">
                                        <strong><i class="bi bi-tag-fill me-1"></i> Category</strong>
                                        <p><?php echo htmlspecialchars($row["classification"]); ?></p>
                                    </div>

                                </div>
                            </div>

                            <?php if (!empty($row["project_file_path"])): ?>
                                <div class="mt-3">
                                    <div class="files-container">
                                        <h6 class="mb-3"><i class="bi bi-file-earmark me-2"></i>Project Files</h6>
                                        <a href="<?php echo htmlspecialchars($row["project_file_path"]); ?>" class="file-link" target="_blank">
                                            <i class="bi bi-file-pdf"></i>
                                            Download Project Documentation
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mt-3">
                                <h6 class="mb-2"><i class="bi bi-tags me-2"></i>Keywords</h6>
                                <?php
                                $keywords = explode(',', $row["language"]);
                                foreach($keywords as $keyword):
                                    if(trim($keyword) != ""): ?>
                                        <span class="category-pill"><?php echo trim(htmlspecialchars($keyword)); ?></span>
                                    <?php
                                    endif;
                                endforeach; ?>
                            </div>


                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-projects">
            <i class="bi bi-folder-x"></i>
            <h3>No Approved Projects</h3>
            <p class="text-muted mb-4">There are currently no approved projects available.</p>
            <a href="submit_project.php" class="btn btn-primary">Submit Your Idea</a>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($result && $result->num_rows > 6): ?>
        <div class="d-flex justify-content-center mt-4 mb-5">
            <nav aria-label="Project pagination">
                <ul class="pagination">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-3"><i class="bi bi-lightning-charge-fill me-2"></i>IdeaNest</h5>
                <p>A platform to share and discover innovative project ideas within our community.</p>
            </div>
            <div class="col-md-3">
                <h5 class="mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white">Home</a></li>
                    <li><a href="submit_project.php" class="text-white">Submit Project</a></li>
                    <li><a href="my_bookmarks.php" class="text-white">My Bookmarks</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5 class="mb-3">Contact</h5>
                <ul class="list-unstyled">
                    <li><i class="bi bi-envelope me-2"></i>support@ideanest.com</li>
                    <li><i class="bi bi-telephone me-2"></i>(123) 456-7890</li>
                </ul>
            </div>
        </div>
        <hr class="my-3 bg-light">
        <div class="text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> IdeaNest. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap and custom JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Search functionality
    document.getElementById('searchProjects').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const projects = document.querySelectorAll('.project-item');

        projects.forEach(project => {
            const projectTitle = project.querySelector('.card-header h5').textContent.toLowerCase();
            const projectDesc = project.querySelector('.description-container p').textContent.toLowerCase();
            const projectLead = project.querySelector('.project-detail:nth-child(1) p').textContent.toLowerCase();

            if (projectTitle.includes(searchText) || projectDesc.includes(searchText) || projectLead.includes(searchText)) {
                project.style.display = '';
            } else {
                project.style.display = 'none';
            }
        });
    });

    // Show alert messages in the toast container
    function showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = `toast show bg-${type} text-white`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
                <div class="toast-header bg-${type} text-white">
                    <strong class="me-auto">IdeaNest</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;

        toastContainer.appendChild(toast);

        // Remove the toast after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    // Move the alerts to the toast container
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            // Extract message and determine type
            const message = alert.textContent.trim();
            let type = 'info';
            if (alert.classList.contains('alert-success')) type = 'success';
            if (alert.classList.contains('alert-danger')) type = 'danger';

            // Show as toast
            showToast(message, type);

            // Remove original alert
            alert.remove();
        });
    });

    // Bookmark animation
    const bookmarkBtns = document.querySelectorAll('.bookmark-btn');
    bookmarkBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // The actual toggle happens through the form submit,
            // this is just for immediate visual feedback
            const icon = this.querySelector('i');
            if (icon.classList.contains('bi-bookmark')) {
                icon.classList.remove('bi-bookmark');
                icon.classList.add('bi-bookmark-fill');
                this.classList.add('active');
            } else {
                icon.classList.remove('bi-bookmark-fill');
                icon.classList.add('bi-bookmark');
                this.classList.remove('active');
            }
        });
    });
</script>
</body>

</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>