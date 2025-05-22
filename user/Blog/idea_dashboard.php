<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idea Management Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #4361ee;
        --primary-light: rgba(67, 97, 238, 0.1);
        --primary-dark: #fbfbff;
        --secondary-color: #f50057;
        --success-color: #10b981;
        --success-light: rgba(16, 185, 129, 0.1);
        --warning-color: #f59e0b;
        --warning-light: rgba(245, 158, 11, 0.1);
        --danger-color: #ef4444;
        --danger-light: rgba(239, 68, 68, 0.1);
        --info-color: #0ea5e9;
        --info-light: rgba(14, 165, 233, 0.1);
        --light-bg: #f8fafc;
        --dark-text: #1e293b;
        --light-text: #64748b;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-300: #cbd5e1;
        --gray-400: #94a3b8;
        --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        --card-hover-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
        --border-radius: 0.75rem;
        --border-radius-sm: 0.5rem;
        --font-primary: 'Inter', sans-serif;
    }

    body {
        background-color: var(--light-bg);
        font-family: var(--font-primary);
        color: var(--dark-text);
        line-height: 1.6;
    }

    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.5rem;
    }

    .page-header {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .page-header h1 {
        margin-bottom: 0.5rem;
        color: var(--dark-text);
        font-weight: 700;
    }

    .stats-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.25rem;
        box-shadow: var(--card-shadow);
        transition: var(--transition);
        height: 100%;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-hover-shadow);
    }

    .stats-card .category-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        color: var(--primary-color);
    }

    .stats-card h3 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .stats-card p {
        color: var(--light-text);
        font-size: 0.9rem;
    }

    .filter-container {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
    }

    .category-tab {
        border-radius: var(--border-radius-sm);
        padding: 0.75rem 1.25rem;
        margin: 0 0.5rem;
        font-weight: 600;
        transition: var(--transition);
        border: 1px solid var(--gray-200);
        color: var(--light-text);
    }

    .category-tab.active {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .project-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        transition: var(--transition);
        height: 100%;
        border: 1px solid var(--gray-200);
    }

    .project-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-hover-shadow);
    }

    .project-card .card-header {
        background: white;
        border-bottom: 1px solid var(--gray-200);
        padding: 1rem;
    }

    .project-card .card-header h5 {
        margin-bottom: 0;
        font-weight: 600;
    }

    .project-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .meta-item {
        background-color: var(--gray-100);
        padding: 0.5rem 0.75rem;
        border-radius: var(--border-radius-sm);
        font-size: 0.875rem;
        color: var(--light-text);
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: var(--border-radius-sm);
        font-size: 0.8rem;
        font-weight: 500;
    }

    .empty-state {
        background: white;
        border-radius: var(--border-radius);
        padding: 3rem;
        text-align: center;
        box-shadow: var(--card-shadow);
    }

    .empty-state-icon {
        font-size: 4rem;
        color: var(--gray-300);
        margin-bottom: 1.5rem;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .action-btn {
        border-radius: var(--border-radius-sm);
        padding: 0.5rem 1rem;
    }

    .priority1-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        border-radius: var(--border-radius-sm);
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .priority1-high {
        background-color: var(--danger-light);
        color: var(--danger-color);
    }

    .priority1-medium {
        background-color: var(--warning-light);
        color: var(--warning-color);
    }

    .priority1-low {
        background-color: var(--success-light);
        color: var(--success-color);
    }

    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }

        .page-header {
            flex-direction: column;
            text-align: center;
        }

        .category-tab {
            margin: 0.25rem;
            padding: 0.5rem 1rem;
        }
    }
    </style>
</head>

<body>
    <?php
function createDBConnection() {
$servername = "localhost"; // Change if needed
$username = "root"; // Database username
$password = ""; // Database password
$dbname = "ideanest"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
return false; // Return false if connection fails
}
return $conn;
}


// Initialize variables
$projects = [];
$error_message = null;
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_priority1 = isset($_GET['priority1']) ? $_GET['priority1'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Get database connection
    $conn = createDBConnection();

    // CountYour Ideas by type
    $stats = [
        'total' => 0,
        'software' => 0,
        'hardware' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'high_priority1' => 0
    ];

    $stats_query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN project_type = 'software' THEN 1 ELSE 0 END) as software,
            SUM(CASE WHEN project_type = 'hardware' THEN 1 ELSE 0 END) as hardware,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN priority1 = 'high' THEN 1 ELSE 0 END) as high_priority1  FROM blog";

    $stats_result = $conn->query($stats_query);
    if ($stats_result) {
        $stats = $stats_result->fetch_assoc();
    }

    // Build SQL query with filters
    $sql = "SELECT * FROM blog WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($filter_type)) {
        $sql .= " AND project_type = ?";
        $params[] = $filter_type;
        $types .= "s";
    }

    if (!empty($filter_status)) {
        $sql .= " AND status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }

    if (!empty($filter_priority1)) {
        $sql .= " AND priority1 = ?";
        $params[] = $filter_priority1;
        $types .= "s";
    }

    if (!empty($search_term)) {
        $sql .= " AND (project_name LIKE ? OR description LIKE ? OR er_number LIKE ?)";
        $search_pattern = "%{$search_term}%";
        $params[] = $search_pattern;
        $params[] = $search_pattern;
        $params[] = $search_pattern;
        $types .= "sss";
    }

    $sql .= " ORDER BY priority1 = 'high' DESC, submission_datetime DESC";

    // Prepare statement
    $stmt = $conn->prepare($sql);

    // Bind parameters if needed
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all projects
    $projects = $result->fetch_all(MYSQLI_ASSOC);

    // GroupYour Ideas by type for category view
    $projects_by_type = [
        'software' => [],
        'hardware' => []
    ];

    foreach ($projects as $project) {
        $projects_by_type[$project['project_type']][] = $project;
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Helper function to get status class
function getStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'in_progress':
            return 'bg-info text-dark';
        case 'completed':
            return 'bg-success text-white';
        case 'rejected':
            return 'bg-danger text-white';
        default:
            return 'bg-secondary text-white';
    }
}

// Helper function to get priority1 class
function getpriority1Class($priority1) {
    switch ($priority1) {
        case 'high':
            return 'priority1-high';
        case 'medium':
            return 'priority1-medium';
        case 'low':
            return 'priority1-low';
        default:
            return 'priority1-medium';
    }
}
?>

    <div class="dashboard-container">
        <div class="mb-3">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1><i class="fas fa-lightbulb me-2"></i>Idea Management Dashboard</h1>
                <p class="text-muted mb-0">Track, manage, and organize all your ideas in one place</p>
            </div>
            <div>
                <a href="form.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>New Idea
                </a>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="category-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Ideas</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="category-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3><?php echo $stats['software']; ?></h3>
                    <p>Software Ideas</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="category-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3><?php echo $stats['hardware']; ?></h3>
                    <p>Hardware Ideas</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="category-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3><?php echo $stats['high_priority1']; ?></h3>
                    <p>High Priority Ideas</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" placeholder="Search ideas..."
                            name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="software" <?php echo ($filter_type == 'software') ? 'selected' : ''; ?>>
                            Software
                        </option>
                        <option value="hardware" <?php echo ($filter_type == 'hardware') ? 'selected' : ''; ?>>
                            Hardware
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>
                            Pending
                        </option>
                        <option value="in_progress" <?php echo ($filter_status == 'in_progress') ? 'selected' : ''; ?>>
                            In Progress
                        </option>
                        <option value="completed" <?php echo ($filter_status == 'completed') ? 'selected' : ''; ?>>
                            Completed
                        </option>
                        <option value="rejected" <?php echo ($filter_status == 'rejected') ? 'selected' : ''; ?>>
                            Rejected
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="priority1">
                        <option value="">All Priorities</option>
                        <option value="high" <?php echo ($filter_priority1 == 'high') ? 'selected' : ''; ?>>
                            High
                        </option>
                        <option value="medium" <?php echo ($filter_priority1 == 'medium') ? 'selected' : ''; ?>>
                            Medium
                        </option>
                        <option value="low" <?php echo ($filter_priority1 == 'low') ? 'selected' : ''; ?>>
                            Low
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Empty State -->
        <?php if (empty($projects)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-clipboard"></i>
            </div>
            <h3>No Ideas Found</h3>
            <p class="text-muted">Try changing your search criteria or add a new idea</p>
            <a href="form.php" class="btn btn-primary mt-3">
                <i class="fas fa-plus me-2"></i>Add New Idea
            </a>
        </div>
        <?php endif; ?>

        <!-- Category Tabs -->
        <ul class="nav nav-pills mb-4" id="projectTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link category-tab active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all"
                    type="button" role="tab">
                    <i class="fas fa-th-large me-2"></i>All Your Ideas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link category-tab" id="software-tab" data-bs-toggle="pill" data-bs-target="#software"
                    type="button" role="tab">
                    <i class="fas fa-laptop-code me-2"></i>Software
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link category-tab" id="hardware-tab" data-bs-toggle="pill" data-bs-target="#hardware"
                    type="button" role="tab">
                    <i class="fas fa-microchip me-2"></i>Hardware
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link category-tab" id="status-tab" data-bs-toggle="pill"
                    data-bs-target="#status-view" type="button" role="tab">
                    <i class="fas fa-tasks me-2"></i>By Status
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="projectTabsContent">
            <!-- AllYour Ideas Tab -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <?php if (empty($projects)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-clipboard"></i>
                    </div>
                    <h3>No Ideas found</h3>
                    <p class="text-muted">Try changing your search criteria or add a Idea</p>
                    <a href="form.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i>Add Your Idea
                    </a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($projects as $project): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="project-card card h-100 position-relative">
                            <span class="priority1-badge <?php echo getpriority1Class($project['priority1']); ?>">
                                <?php echo ucfirst($project['priority1']); ?> Priority
                            </span>
                            <div class="card-header">
                                <h5><?php echo htmlspecialchars($project['project_name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="project-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-hashtag me-1"></i>
                                        <?php echo htmlspecialchars($project['er_number']); ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="<?php echo ($project['project_type'] == 'software') ? 'fas fa-laptop-code' : 'fas fa-microchip'; ?> me-1"></i>
                                        <?php echo ucfirst($project['project_type']); ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $project['classification'])); ?>
                                    </div>
                                </div>

                                <span class="status-badge <?php echo getStatusClass($project['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                </span>

                                <div class="mt-3">
                                    <p class="text-muted small">
                                        <?php
                                        $desc = htmlspecialchars($project['description']);
                                        echo (strlen($desc) > 100) ? substr($desc, 0, 100) . '...' : $desc;
                                        ?>
                                    </p>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small class="text-muted">
                                        Submitted: <?php echo date('M d, Y', strtotime($project['submission_datetime'])); ?>
                                    </small>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="edit.php?id=<?php echo $project['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- SoftwareYour Ideas Tab -->
            <div class="tab-pane fade" id="software" role="tabpanel">
                <?php if (empty($projects_by_type['software'])): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3>No software Ideas found</h3>
                    <p class="text-muted">Try changing your search criteria or add a new software Idea</p>
                    <a href="form.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i>Add Software Your Idea
                    </a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($projects_by_type['software'] as $project): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="project-card card h-100 position-relative">
                            <span class="priority1-badge <?php echo getpriority1Class($project['priority1']); ?>">
                                <?php echo ucfirst($project['priority1']); ?> Priority
                            </span>
                            <div class="card-header">
                                <h5><?php echo htmlspecialchars($project['project_name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="project-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-hashtag me-1"></i>
                                        <?php echo htmlspecialchars($project['er_number']); ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-laptop-code me-1"></i>
                                        Software
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $project['classification'])); ?>
                                    </div>
                                </div>

                                <span class="status-badge <?php echo getStatusClass($project['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                </span>

                                <div class="mt-3">
                                    <p class="text-muted small">
                                        <?php
                                        $desc = htmlspecialchars($project['description']);
                                        echo (strlen($desc) > 100) ? substr($desc, 0, 100) . '...' : $desc;
                                        ?>
                                    </p>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small class="text-muted">
                                        Submitted: <?php echo date('M d, Y', strtotime($project['submission_datetime'])); ?>
                                    </small>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="edit.php?id=<?php echo $project['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- HardwareYour Ideas Tab -->
            <div class="tab-pane fade" id="hardware" role="tabpanel">
                <?php if (empty($projects_by_type['hardware'])): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3>No hardware Your Ideas found</h3>
                    <p class="text-muted">Try changing your search criteria or add a new hardware Your Idea</p>
                    <a href="form.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i>Add Hardware Your Idea
                    </a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($projects_by_type['hardware'] as $project): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="project-card card h-100 position-relative">
                            <span class="priority1-badge <?php echo getpriority1Class($project['priority1']); ?>">
                                <?php echo ucfirst($project['priority1']); ?> Priority
                            </span>
                            <div class="card-header">
                                <h5><?php echo htmlspecialchars($project['project_name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="project-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-hashtag me-1"></i>
                                        <?php echo htmlspecialchars($project['er_number']); ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-microchip me-1"></i>
                                        Hardware
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $project['classification'])); ?>
                                    </div>
                                </div>

                                <span class="status-badge <?php echo getStatusClass($project['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                </span>

                                <div class="mt-3">
                                    <p class="text-muted small">
                                        <?php
                                        $desc = htmlspecialchars($project['description']);
                                        echo (strlen($desc) > 100) ? substr($desc, 0, 100) . '...' : $desc;
                                        ?>
                                    </p>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small class="text-muted">
                                        Submitted: <?php echo date('M d, Y', strtotime($project['submission_datetime'])); ?>
                                    </small>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="edit.php?id=<?php echo $project['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- By Status Tab -->
            <div class="tab-pane fade" id="status-view" role="tabpanel">
                <div class="row">
                    <!-- PendingYour Ideas -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending</h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php
                                $pending_count = 0;
                                foreach ($projects as $project):
                                    if ($project['status'] == 'pending'):
                                        $pending_count++;
                                        ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">
                                                    <?php echo htmlspecialchars($project['project_name']); ?></h6>
                                                <small
                                                    class="text-muted"><?php echo htmlspecialchars($project['er_number']); ?></small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </li>
                                    <?php
                                    endif;
                                endforeach;

                                if ($pending_count == 0):
                                    ?>
                                    <li class="list-group-item text-center py-4">
                                        <i class="fas fa-check-circle text-muted mb-2" style="font-size: 2rem;"></i>
                                        <p class="mb-0">No pending Your Ideas</p>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- In ProgressYour Ideas -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-dark">
                                <h5 class="mb-0"><i class="fas fa-spinner me-2"></i>In Progress</h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php
                                $in_progress_count = 0;
                                foreach ($projects as $project):
                                    if ($project['status'] == 'in_progress'):
                                        $in_progress_count++;
                                        ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">
                                                    <?php echo htmlspecialchars($project['project_name']); ?></h6>
                                                <small
                                                    class="text-muted"><?php echo htmlspecialchars($project['er_number']); ?></small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </li>
                                    <?php
                                    endif;
                                endforeach;

                                if ($in_progress_count == 0):
                                    ?>
                                    <li class="list-group-item text-center py-4">
                                        <i class="fas fa-clipboard-list text-muted mb-2" style="font-size: 2rem;"></i>
                                        <p class="mb-0">No Your Ideas in progress</p>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- CompletedYour Ideas -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Completed</h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php
                                $completed_count = 0;
                                foreach ($projects as $project):
                                    if ($project['status'] == 'completed'):
                                        $completed_count++;
                                        ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">
                                                    <?php echo htmlspecialchars($project['project_name']); ?></h6>
                                                <small
                                                    class="text-muted"><?php echo htmlspecialchars($project['er_number']); ?></small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </li>
                                    <?php
                                    endif;
                                endforeach;

                                if ($completed_count == 0):
                                    ?>
                                    <li class="list-group-item text-center py-4">
                                        <i class="fas fa-clipboard-check text-muted mb-2" style="font-size: 2rem;"></i>
                                        <p class="mb-0">No completed Your Ideas</p>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- RejectedYour Ideas -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Rejected</h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php
                                $rejected_count = 0;
                                foreach ($projects as $project):
                                    if ($project['status'] == 'rejected'):
                                        $rejected_count++;
                                        ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">
                                                    <?php echo htmlspecialchars($project['project_name']); ?></h6>
                                                <small
                                                    class="text-muted"><?php echo htmlspecialchars($project['er_number']); ?></small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#projectModal<?php echo $project['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </li>
                                    <?php
                                    endif;
                                endforeach;

                                if ($rejected_count == 0):
                                    ?>
                                    <li class="list-group-item text-center py-4">
                                        <i class="fas fa-ban text-muted mb-2" style="font-size: 2rem;"></i>
                                        <p class="mb-0">No rejected Your Ideas</p>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->

    </div>
    <!--Your Idea Detail Modals -->
    <?php foreach ($projects as $project): ?>
    <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1"
        aria-labelledby="projectModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectModalLabel<?php echo $project['id']; ?>">
                        <?php echo htmlspecialchars($project['project_name']); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>ER Number:</strong>
                                <?php echo htmlspecialchars($project['er_number']); ?></p>
                            <p class="mb-1"><strong>Type:</strong> <?php echo ucfirst($project['project_type']); ?></p>
                            <p class="mb-1"><strong>Classification:</strong>
                                <?php echo ucfirst(str_replace('_', ' ', $project['classification'])); ?></p>
                            <p class="mb-1"><strong>Priority:</strong> <?php echo ucfirst($project['priority1']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Status:</strong>
                                <span class="status-badge <?php echo getStatusClass($project['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                </span>
                            </p>
                            <p class="mb-1"><strong>Submitted:</strong>
                                <?php echo date('F d, Y', strtotime($project['submission_datetime'])); ?></p>
                            <p class="mb-1"><strong>Assigned To:</strong>
                                <?php echo htmlspecialchars(isset($project['assigned_to']) ? $project['assigned_to'] : 'Not Assigned'); ?>
                            </p>
                            <p class="mb-1"><strong>Completion Date:</strong>
                                <?php echo $project['completion_date'] ? date('F d, Y', strtotime($project['completion_date'])) : 'N/A'; ?>
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6>Description</h6>
                        <div class="p-3 bg-light rounded">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Your Idea
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Set tab state from localStorage if available
    document.addEventListener('DOMContentLoaded', function() {
        var activeTab = localStorage.getItem('activeProjectTab');
        if (activeTab) {
            var triggerEl = document.querySelector('#projectTabs button[data-bs-target="' + activeTab + '"]');
            if (triggerEl) {
                var tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }
    });

    // Store active tab in localStorage
    var tabElList = document.querySelectorAll('#projectTabs button[data-bs-toggle="pill"]');
    tabElList.forEach(function(tabEl) {
        tabEl.addEventListener('shown.bs.tab', function(event) {
            localStorage.setItem('activeProjectTab', event.target.getAttribute('data-bs-target'));
        });
    });
    </script>
</body>

</html>