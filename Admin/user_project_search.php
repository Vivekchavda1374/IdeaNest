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
            /* Modern color palette with better contrast */
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --secondary-color: #7209b7;
            --tertiary-color: #5a189a;
            --success-color: #2dc653;
            --info-color: #4cc9f0;
            --warning-color: #faa307;
            --danger-color: #e63946;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;

            /* Enhanced visual properties */
            --border-radius-sm: 6px;
            --border-radius: 12px;
            --border-radius-lg: 20px;
            --box-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
            --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 15px 40px rgba(0, 0, 0, 0.12);
            --transition-speed: 0.3s;
            --hover-transform: translateY(-5px);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: var(--gray-800);
            line-height: 1.6;
        }

        /* Improved container with subtle gradient background */
        .container {
            position: relative;
            padding-top: 2rem;
            padding-bottom: 3rem;
        }

        .container::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.03) 0%, rgba(114, 9, 183, 0.03) 100%);
            z-index: -1;
            pointer-events: none;
        }

        /* Enhanced header styling */
        .section-title {
            position: relative;
            margin: 2rem 0 1.5rem;
            padding-bottom: 0.75rem;
            color: var(--primary-dark);
            font-weight: 700;
            display: flex;
            align-items: center;
            letter-spacing: -0.5px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        .section-title i {
            margin-right: 12px;
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        /* Improved project stats with animations */
        .project-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-item {
            background: white;
            border-radius: var(--border-radius);
            padding: 18px 15px;
            flex: 1;
            min-width: 140px;
            text-align: center;
            box-shadow: var(--box-shadow-sm);
            transition: all var(--transition-speed) ease;
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: opacity var(--transition-speed) ease;
        }

        .stat-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--box-shadow);
        }

        .stat-item:hover::before {
            opacity: 1;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
            animation: countUp 2s ease-out forwards;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Enhanced search and filter container */
        .search-filter-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--gray-200);
        }

        .search-input {
            border-radius: 50px;
            padding-left: 1rem;
            padding-right: 1rem;
            border: 1px solid var(--gray-300);
            transition: all 0.3s ease;
            height: 48px;
            font-size: 1rem;
        }

        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
            border-color: var(--primary-color);
        }

        .form-select {
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--gray-300);
            height: 48px;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--gray-700);
            transition: all 0.3s ease;
            cursor: pointer;
            background-position: right 0.75rem center;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
        }

        .input-group-text {
            border-radius: 50px 0 0 50px;
            background-color: white;
            border-right: none;
            padding-left: 1.25rem;
            color: var(--gray-500);
        }

        /* Refined project cards with enhanced hover effects */
        .project-card {
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--box-shadow-sm);
            transition: all var(--transition-speed) ease;
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
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: opacity var(--transition-speed) ease;
        }

        .project-card:hover {
            transform: var(--hover-transform);
            box-shadow: var(--box-shadow-lg);
            border-color: var(--gray-300);
        }

        .project-card:hover::after {
            opacity: 1;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 1.5rem;
        }

        .card-header h5 {
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            font-size: 1.25rem;
            line-height: 1.4;
        }

        .card-body {
            padding: 1.75rem;
        }

        /* Badge styling */
        .badge {
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.75rem;
            border-radius: 50px;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .badge-approved {
            background: linear-gradient(135deg, var(--success-color), #20ab8e);
            color: white;
        }

        /* Improved bookmark button with better animation */
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
            position: relative;
            outline: none;
        }

        .bookmark-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: rgba(250, 163, 7, 0.1);
            transform: scale(0);
            transition: transform 0.3s ease;
        }

        .bookmark-btn:hover::before {
            transform: scale(1);
        }

        .bookmark-btn:hover {
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

        /* Prettier description container */
        .description-container {
            border-left: 4px solid;
            border-image: linear-gradient(to bottom, var(--primary-color), var(--secondary-color)) 1;
            padding-left: 18px;
            margin-top: 15px;
            margin-bottom: 20px;
        }

        .description-container p {
            color: var(--gray-700);
            line-height: 1.7;
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        /* Improved project details */
        .project-detail {
            margin-bottom: 15px;
            padding-bottom: 12px;
        }

        .project-detail:not(:last-child) {
            border-bottom: 1px dashed var(--gray-300);
        }

        .project-detail strong {
            color: var(--primary-dark);
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            font-size: 0.875rem;
        }

        .project-detail strong i {
            color: var(--primary-color);
        }

        .project-detail p {
            margin-bottom: 0;
            color: var(--gray-700);
            font-weight: 500;
        }

        /* Enhanced file links */
        .files-container {
            background: var(--gray-100);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            height: 100%;
            border: 1px solid var(--gray-200);
        }

        .files-container h6 {
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .files-container h6 i {
            color: var(--primary-color);
        }

        .file-link {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 14px 16px;
            border-radius: var(--border-radius-sm);
            background-color: white;
            transition: all 0.2s ease;
            text-decoration: none;
            color: var(--gray-700);
            box-shadow: var(--box-shadow-sm);
            border: 1px solid var(--gray-200);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .file-link:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.25);
            border-color: var(--primary-color);
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

        /* Improved category pills */
        .category-pill {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            background-color: var(--gray-200);
            color: var(--gray-700);
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 8px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .category-pill:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(67, 97, 238, 0.2);
        }

        /* Enhanced empty state */
        .empty-projects {
            text-align: center;
            padding: 5rem 2rem;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--gray-200);
        }

        .empty-projects i {
            font-size: 6rem;
            color: var(--gray-300);
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .empty-projects h3 {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .empty-projects p {
            max-width: 500px;
            margin: 0 auto 1.5rem;
            color: var(--gray-600);
        }

        .empty-projects .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            padding: 0.7rem 1.75rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            transition: all 0.3s ease;
        }

        .empty-projects .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
        }

        /* Better alerts styling */
        .alert {
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
        }

        .alert i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border-left: 4px solid var(--success-color);
        }

        .alert-info {
            background-color: #eff6ff;
            color: #1e40af;
            border-left: 4px solid var(--primary-color);
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #b91c1c;
            border-left: 4px solid var(--danger-color);
        }

        /* Improved pagination */
        .pagination {
            gap: 5px;
        }

        .page-item:first-child .page-link {
            border-top-left-radius: var(--border-radius-sm);
            border-bottom-left-radius: var(--border-radius-sm);
        }

        .page-item:last-child .page-link {
            border-top-right-radius: var(--border-radius-sm);
            border-bottom-right-radius: var(--border-radius-sm);
        }

        .page-link {
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
            padding: 0.5rem 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .page-link:hover {
            background-color: var(--gray-100);
            color: var(--primary-dark);
            border-color: var(--gray-400);
            z-index: 0;
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(67, 97, 238, 0.3);
            font-weight: 600;
            z-index: 0;
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            border-radius: var(--border-radius);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin-bottom: 10px;
            opacity: 0;
            transform: translateX(30px);
            animation: toastIn 0.3s ease forwards;
        }

        @keyframes toastIn {
            to { opacity: 1; transform: translateX(0); }
        }

        .toast-header {
            padding: 0.75rem 1rem;
            border-bottom: none;
        }

        .toast-body {
            padding: 1rem;
            font-weight: 500;
        }

        /* Media queries for better responsiveness */
        @media (max-width: 992px) {
            .section-title {
                font-size: 1.75rem;
            }

            .container {
                padding-top: 1.5rem;
            }

            .stat-value {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 768px) {
            .section-title {
                font-size: 1.5rem;
            }

            .search-filter-container {
                padding: 1.25rem;
            }

            .project-stats {
                flex-direction: column;
                gap: 10px;
            }

            .stat-item {
                width: 100%;
                padding: 15px;
            }

            .card-header {
                padding: 1.25rem;
            }

            .card-body {
                padding: 1.25rem;
            }

            .form-select {
                margin-top: 10px;
            }
        }

        @media (max-width: 576px) {
            .section-title {
                font-size: 1.4rem;
            }

            .card-header h5 {
                font-size: 1.1rem;
            }

            .badge {
                padding: 0.4rem 0.8rem;
                font-size: 0.7rem;
            }

            .bookmark-btn {
                width: 36px;
                height: 36px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>


<div class="container">
    <!-- Search and Filter Section -->

    <h2 class="section-title">
        <i class="bi bi-check-circle"></i>
        Projects
    </h2>
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
            <!-- Change these lines in the search-filter-container section -->
            <select class="form-select me-2" style="max-width: 150px;" id="projectTypeFilter">
                <option selected>All Types</option>
                <option>Hardware</option>
                <option>Software</option>

            </select>
            <select class="form-select" style="max-width: 150px;" id="sortByFilter">
                <option selected>Sort By</option>
                <option>Newest</option>
                <option>Oldest</option>
                <option>A-Z</option>
                <option>Z-A</option>
            </select>
        </div>
    </div>

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
                                    </div>projects_view.php
                                    <div class="project-detail">
                                        <strong><i class="bi bi-calendar-event me-1"></i> Submission Date</strong>
                                        <p><?php echo date('F j, Y', strtotime($row["submission_date"])); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="project-detail">
                                        <strong><i class="bi bi-tag-fill me-1"></i> Category</strong>
                                        <p><?php echo htmlspecialchars($row["project_type"]); ?></p>
                                    </div>
                                    <div class="project-detail">
                                        <strong><i class="bi bi-tag-fill me-1"></i> Classification</strong>
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
    // Enhanced search and filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchProjects');
        const typeFilter = document.getElementById('projectTypeFilter');
        const sortFilter = document.getElementById('sortByFilter');
        const projectContainer = document.getElementById('projectContainer');
        const projects = document.querySelectorAll('.project-item');

        // Combined filter function that handles search, type filtering, and sorting
        // Function to filter projects
        function filterProjects() {
            const searchText = searchInput.value.toLowerCase();
            const selectedType = typeFilter.value;

            // First pass: Filter by search text and type
            projects.forEach(project => {
                const projectTitle = project.querySelector('.card-header h5').textContent.toLowerCase();
                const projectDesc = project.querySelector('.description-container p').textContent.toLowerCase();
                const projectLead = project.querySelector('.project-detail:nth-child(1) p').textContent.toLowerCase();

                // Find the category element specifically (with the Category label)
                const categoryElement = Array.from(project.querySelectorAll('.project-detail')).find(
                    detail => detail.querySelector('strong').textContent.includes('Category')
                );
                const projectCategory = categoryElement ? categoryElement.querySelector('p').textContent.toLowerCase() : '';

                // Check if project matches search text
                const matchesSearch = projectTitle.includes(searchText) ||
                    projectDesc.includes(searchText) ||
                    projectLead.includes(searchText);

                // Check if project matches the type filter
                const matchesType = selectedType === 'All Types' ||
                    projectCategory.includes(selectedType.toLowerCase());

                // Show project only if it matches both criteria
                project.style.display = (matchesSearch && matchesType) ? '' : 'none';
            });

            // Second pass: Sorting
            sortProjects();
        }

        // Function to sort projects based on selected sort option
        function sortProjects() {
            const sortBy = sortFilter.value;
            const projectsArray = Array.from(projects).filter(p => p.style.display !== 'none');

            projectsArray.sort((a, b) => {
                switch(sortBy) {
                    case 'Newest':
                        const dateA = new Date(a.querySelector('.project-detail:nth-child(2) p').textContent);
                        const dateB = new Date(b.querySelector('.project-detail:nth-child(2) p').textContent);
                        return dateB - dateA;
                    case 'Oldest':
                        const dateC = new Date(a.querySelector('.project-detail:nth-child(2) p').textContent);
                        const dateD = new Date(b.querySelector('.project-detail:nth-child(2) p').textContent);
                        return dateC - dateD;
                    case 'A-Z':
                        const titleA = a.querySelector('.card-header h5').textContent.toLowerCase();
                        const titleB = b.querySelector('.card-header h5').textContent.toLowerCase();
                        return titleA.localeCompare(titleB);
                    case 'Z-A':
                        const titleC = a.querySelector('.card-header h5').textContent.toLowerCase();
                        const titleD = b.querySelector('.card-header h5').textContent.toLowerCase();
                        return titleD.localeCompare(titleC);
                    default:
                        return 0;
                }
            });

            // Remove and reappend projects in sorted order
            projectsArray.forEach(project => {
                projectContainer.appendChild(project);
            });
        }

        // Event listeners for search input and filter dropdowns
        searchInput.addEventListener('keyup', filterProjects);
        typeFilter.addEventListener('change', filterProjects);
        sortFilter.addEventListener('change', filterProjects);

        // Initial filtering on page load
        filterProjects();
    });
</script>
</body>

</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>