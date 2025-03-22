<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get projects with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$itemsPerPage = 6; // Number of projects per page
$offset = ($page - 1) * $itemsPerPage;

// Get filter parameters
$filterType = isset($_GET['type']) ? $_GET['type'] : '';

// Build query with possible filter
$whereClause = "";
if (!empty($filterType)) {
    $whereClause = " WHERE project_type = '" . $conn->real_escape_string($filterType) . "'";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM projects" . $whereClause;
$countResult = $conn->query($countSql);
$totalProjects = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalProjects / $itemsPerPage);

// Get projects for current page
$sql = "SELECT * FROM projects" . $whereClause . " ORDER BY submission_date DESC LIMIT $offset, $itemsPerPage";
$result = $conn->query($sql);

// Get project categories for filter
$categorySql = "SELECT DISTINCT project_type FROM projects ORDER BY project_type";
$categoryResult = $conn->query($categorySql);
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Discover Amazing Projects | IdeaNest</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="style.css">
        <style>
            :root {
                --primary-color: #3a86ff;
                --secondary-color: #8338ec;
                --success-color: #06d6a0;
                --warning-color: #ffbe0b;
                --danger-color: #ef476f;
                --light-bg: #f8f9fa;
                --dark-text: #2b2d42;
                --card-shadow: 0 6px 12px rgba(0,0,0,0.08);
                --hover-shadow: 0 12px 20px rgba(0,0,0,0.12);
            }

            body {
                background-color: var(--light-bg);
                color: var(--dark-text);
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .hero-section {
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                color: white;
                padding: 3rem 0;
                margin-bottom: 2rem;
                border-radius: 0 0 20px 20px;
                box-shadow: var(--card-shadow);
            }

            .card {
                border-radius: 12px;
                overflow: hidden;
                box-shadow: var(--card-shadow);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                margin-bottom: 25px;
                height: 100%;
                border: none;
            }

            .card:hover {
                transform: translateY(-5px);
                box-shadow: var(--hover-shadow);
            }

            .card-header {
                background-color: #f8f9fa;
                border-bottom: 1px solid rgba(0,0,0,0.05);
                padding: 1rem;
            }

            .card-title {
                font-weight: 700;
                color: var(--dark-text);
                font-size: 1.25rem;
                margin-bottom: 0;
            }

            .card-body {
                padding: 1.25rem;
            }

            .card-footer {
                background-color: white;
                border-top: 1px solid rgba(0,0,0,0.05);
                padding: 1rem;
            }

            .badge {
                padding: 0.5rem 0.75rem;
                border-radius: 50px;
                font-weight: 600;
                font-size: 0.75rem;
            }

            .badge-project-type {
                background-color: var(--primary-color);
                color: white;
            }

            .badge-language {
                background-color: var(--secondary-color);
                color: white;
            }

            .status-badge {
                display: inline-block;
                padding: 0.35rem 0.75rem;
                border-radius: 50px;
                font-size: 0.75rem;
                font-weight: 600;
            }

            .status-pending {
                background-color: var(--warning-color);
                color: #856404;
            }

            .status-approved {
                background-color: var(--success-color);
                color: #155724;
            }

            .status-rejected {
                background-color: var(--danger-color);
                color: white;
            }

            .filter-container {
                background-color: white;
                border-radius: 10px;
                padding: 1.25rem;
                margin-bottom: 2rem;
                box-shadow: var(--card-shadow);
            }

            .btn-download, .btn-code {
                border-radius: 50px;
                padding: 0.375rem 1rem;
                font-weight: 600;
                font-size: 0.85rem;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }

            .btn-download {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
                color: white;
            }

            .btn-code {
                background-color: var(--success-color);
                border-color: var(--success-color);
                color: white;
            }

            .btn-view {
                border-radius: 50px;
                padding: 0.375rem 1rem;
                font-weight: 600;
                font-size: 0.85rem;
                background-color: transparent;
                border: 1px solid var(--primary-color);
                color: var(--primary-color);
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }

            .btn-view:hover {
                background-color: var(--primary-color);
                color: white;
            }

            .video-container {
                border-radius: 10px;
                overflow: hidden;
                margin-top: 15px;
                box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            }

            .empty-result {
                text-align: center;
                padding: 50px 0;
            }

            .pagination {
                justify-content: center;
                margin: 2rem 0;
            }

            .pagination .page-link {
                border-radius: 50px;
                margin: 0 3px;
                color: var(--primary-color);
                border: 1px solid var(--primary-color);
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .pagination .page-item.active .page-link {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
            }

            .text-truncate-2 {
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .meta-info {
                color: #6c757d;
                font-size: 0.875rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .meta-icon {
                color: var(--primary-color);
            }

            .filter-btn {
                border-radius: 50px;
                font-weight: 600;
                font-size: 0.85rem;
                padding: 0.375rem 1rem;
                transition: all 0.2s;
            }

            .filter-btn.active {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
                color: white;
            }

            .filter-btn:not(.active) {
                background-color: transparent;
                border: 1px solid var(--primary-color);
                color: var(--primary-color);
            }

            .filter-btn:not(.active):hover {
                background-color: rgba(58, 134, 255, 0.1);
            }
        </style>
    </head>

    <body>

    <!-- Hero Section -->
    <div class="hero-section">
        <a href="index.php" class="btn btn-primary btn-lg  align-items-center gap-2" style="margin-left: 20px">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="container">
            <h1 class="display-4 text-center fw-bold">Discover Amazing Projects</h1>
            <p class="lead text-center">Explore innovative ideas and inspiring projects from our community</p>
        </div>
    </div>

    <div class="container">
        <!-- Filter Section -->
        <div class="filter-container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-3"><i class="fas fa-filter me-2 meta-icon"></i>Filter Projects</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="projects_view.php" class="btn filter-btn <?php echo empty($filterType) ? 'active' : ''; ?>">
                            All Projects
                        </a>
                        <?php while ($category = $categoryResult->fetch_assoc()): ?>
                            <a href="?type=<?php echo urlencode($category['project_type']); ?>"
                               class="btn filter-btn <?php echo $filterType === $category['project_type'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['project_type']); ?>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="col-md-4 text-end align-self-center">
                    <div class="meta-info justify-content-end">
                        <i class="fas fa-project-diagram meta-icon"></i>
                        <span class="fw-bold"><?php echo $totalProjects; ?></span> projects found
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['project_name']); ?></h5>
<!--                                --><?php
//                                $statusClass = 'status-pending';
//                                if ($row['project_status'] == 'Approved') {
//                                    $statusClass = 'status-approved';
//                                } elseif ($row['project_status'] == 'Rejected') {
//                                    $statusClass = 'status-rejected';
//                                }
//                                ?>
<!--                                <span class="status-badge --><?php //echo $statusClass; ?><!--">-->
<!--                                    --><?php //echo htmlspecialchars($row['project_status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="d-flex gap-2 mb-3">
                                    <span class="badge badge-project-type">
                                        <?php echo htmlspecialchars($row['project_type']); ?>
                                    </span>
                                    <span class="badge badge-language">
                                        <?php echo htmlspecialchars($row['language']); ?>
                                    </span>
                                </div>

                                <?php if (!empty($row['classification'])): ?>
                                    <div class="meta-info mb-2">
                                        <i class="fas fa-tag meta-icon"></i>
                                        <span><?php echo htmlspecialchars($row['classification']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <p class="card-text text-truncate-2">
                                    <?php
                                    // Limit description length
                                    $desc = htmlspecialchars($row['description']);
                                    echo (strlen($desc) > 150) ? nl2br(substr($desc, 0, 150)) . '...' : nl2br($desc);
                                    ?>
                                </p>

                                <?php if (!empty($row['video_path']) && file_exists('../uploads/' . $row['video_path'])): ?>
                                    <div class="video-container mt-3">
                                        <video width="100%" height="150" controls>
                                            <source src="../uploads/<?php echo htmlspecialchars($row['video_path']); ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex gap-2">
                                        <?php if (!empty($row['instruction_file_path']) && file_exists('../uploads/' . $row['instruction_file_path'])): ?>
                                            <a href="../uploads/<?php echo htmlspecialchars($row['instruction_file_path']); ?>"
                                               class="btn btn-download" download>
                                                <i class="fas fa-download"></i> Instructions
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($row['code_file_path']) && file_exists('../uploads/' . $row['code_file_path'])): ?>
                                            <a href="../uploads/<?php echo htmlspecialchars($row['code_file_path']); ?>"
                                               class="btn btn-code" download>
                                                <i class="fas fa-code"></i> Code
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <a href="project_details.php?id=<?php echo $row['id']; ?>" class="btn btn-view">
                                        <i class="fas fa-eye"></i> Details
                                    </a>
                                </div>
                                <div class="meta-info mt-3">
                                    <i class="far fa-calendar-alt meta-icon"></i>
                                    <?php echo date('M d, Y', strtotime($row['submission_date'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 empty-result">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No projects found. Try a different filter or check back later!
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Project pagination" class="my-4">
                <ul class="pagination">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($filterType) ? '&type=' . urlencode($filterType) : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($filterType) ? '&type=' . urlencode($filterType) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($filterType) ? '&type=' . urlencode($filterType) : ''; ?>" aria-label="Next">
                            <span aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>

    </html>

<?php $conn->close(); ?>