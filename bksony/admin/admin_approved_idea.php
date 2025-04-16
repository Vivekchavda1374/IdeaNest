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

// Handle approval action
if(isset($_POST['approve']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First, get the idea details from blog table
        $stmt = $conn->prepare("SELECT * FROM blog WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($row = $result->fetch_assoc()) {
            // Insert into admin_approved_idea table
            $insertStmt = $conn->prepare("INSERT INTO admin_approved_idea 
                (er_number, project_name, project_type, classification, description, 
                submission_datetime, status, priority1, assigned_to, completion_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $insertStmt->bind_param(
                "ssssssssss",
                $row['er_number'],
                $row['project_name'],
                $row['project_type'],
                $row['classification'],
                $row['description'],
                $row['submission_datetime'],
                $row['status'],
                $row['priority1'],
                $row['assigned_to'],
                $row['completion_date']
            );
            
            $insertStmt->execute();
            
            // Delete from blog table
            $deleteStmt = $conn->prepare("DELETE FROM blog WHERE id = ?");
            $deleteStmt->bind_param("i", $id);
            $deleteStmt->execute();
            
            // Commit the transaction
            $conn->commit();
            
            $message = "Idea successfully approved and moved!";
            $alertType = "success";
        } else {
            throw new Exception("Idea not found!");
        }
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Handle reject action
if(isset($_POST['reject']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    try {
        // Simply delete the idea from blog table
        $deleteStmt = $conn->prepare("DELETE FROM blog WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        
        if($deleteStmt->affected_rows > 0) {
            $message = "Idea has been rejected and removed from the system.";
            $alertType = "warning";
        } else {
            throw new Exception("Idea not found or already processed!");
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Fetch all ideas from blog table
$sql = "SELECT * FROM blog ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Idea Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Custom styles -->
    <style>
    :root {
        --primary-color: #3f51b5;
        --secondary-color: #f50057;
        --success-color: #43a047;
        --light-bg: #f5f7ff;
    }

    body {
        background-color: var(--light-bg);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .navbar-brand {
        font-weight: 700;
        letter-spacing: 1px;
    }

    .card {
        border: none;
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
        margin-bottom: 20px;
        height: 100%;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .status-badge {
        font-size: 0.8rem;
        font-weight: 500;
    }

    .priority-high {
        color: var(--secondary-color);
    }

    .priority-medium {
        color: #ff9800;
    }

    .priority-low {
        color: #2196f3;
    }

    .action-buttons .btn {
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
        margin-right: 5px;
    }

    .idea-title {
        font-weight: 600;
        color: var(--primary-color);
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }

    .badge {
        font-weight: 500;
    }

    .modal-content {
        border: none;
        border-radius: 0.75rem;
    }

    .modal-header {
        background-color: var(--primary-color);
        color: white;
        border-radius: 0.75rem 0.75rem 0 0;
    }

    .dashboard-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #5c6bc0 100%);
        color: white;
        padding: 1.5rem 0;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .stats-card {
        border-left: 4px solid var(--primary-color);
        background-color: white;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 0;
    }

    .empty-state i {
        font-size: 3rem;
        color: #ccc;
    }

    .idea-card {
        position: relative;
    }

    .idea-description {
        height: 80px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }

    .idea-meta {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .card-footer {
        background-color: transparent;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .card-badge {
        position: absolute;
        top: 10px;
        right: 10px;
    }

    .card-header {
        background-color: transparent;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1rem;
    }

    .search-box {
        max-width: 500px;
        margin: 0 auto 20px;
    }

    .dropdown-menu {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border: none;
    }

    .card-icon {
        font-size: 1.5rem;
        margin-right: 10px;
        color: var(--primary-color);
    }

    .no-results {
        display: none;
        text-align: center;
        padding: 2rem;
        background-color: white;
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    /* Animation for cards */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .idea-item {
        animation: fadeIn 0.5s ease-out forwards;
    }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-lightning-charge-fill me-2"></i>
                Idea Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Dashboard Header -->
        <div class="row dashboard-header align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Ideas </h1>
                <p class="lead mb-0">Manage and approve submitted project ideas</p>
            </div>
            <div class="col-md-6 text-md-end">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel me-2"></i>Filter Ideas
                </button>
                <button class="btn btn-outline-light ms-2">
                    <i class="bi bi-download me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <h6 class="text-muted">Pending Ideas</h6>
                        <div class="d-flex align-items-center">
                            <h2 class="mb-0"><?php echo $result->num_rows; ?></h2>
                            <i class="bi bi-hourglass-split ms-auto text-primary fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: var(--success-color);">
                    <div class="card-body">
                        <h6 class="text-muted">High Priority</h6>
                        <div class="d-flex align-items-center">
                            <?php
                            $highPriorityCount = 0;
                            if($result->num_rows > 0) {
                                $result->data_seek(0); // Reset result pointer
                                while($row = $result->fetch_assoc()) {
                                    if(strtolower($row['priority1']) == 'high') {
                                        $highPriorityCount++;
                                    }
                                }
                                $result->data_seek(0); // Reset result pointer again
                            }
                            ?>
                            <h2 class="mb-0"><?php echo $highPriorityCount; ?></h2>
                            <i class="bi bi-exclamation-triangle ms-auto text-warning fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card h-100" style="border-left-color: #9c27b0;">
                    <div class="card-body">
                        <h6 class="text-muted">Today's Submissions</h6>
                        <div class="d-flex align-items-center">
                            <?php
                            $todayCount = 0;
                            if($result->num_rows > 0) {
                                $result->data_seek(0); // Reset result pointer
                                $today = date('Y-m-d');
                                while($row = $result->fetch_assoc()) {
                                    if(date('Y-m-d', strtotime($row['created_at'])) == $today) {
                                        $todayCount++;
                                    }
                                }
                                $result->data_seek(0); // Reset result pointer again
                            }
                            ?>
                            <h2 class="mb-0"><?php echo $todayCount; ?></h2>
                            <i class="bi bi-calendar-check ms-auto text-purple fs-1" style="color: #9c27b0;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if(isset($message) || isset($error)): ?>
        <div class="alert alert-<?php echo isset($message) ? $alertType : 'danger'; ?> alert-dismissible fade show"
            role="alert">
            <?php echo isset($message) ? $message : $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Search Box -->
        <div class="search-box mb-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control border-start-0" id="searchInput"
                    placeholder="Search ideas by name, type, or assigned person...">
            </div>
        </div>

        <?php if($result->num_rows > 0): ?>
        <div class="row" id="ideasContainer">
            <?php while($row = $result->fetch_assoc()): ?>
            <?php
                $statusClass = 'secondary';
                switch(strtolower($row['status'])) {
                    case 'active':
                        $statusClass = 'success';
                        break;
                    case 'pending':
                        $statusClass = 'warning';
                        break;
                    case 'blocked':
                        $statusClass = 'danger';
                        break;
                }
                
                $priorityClass = 'primary';
                $priorityIcon = 'arrow-down';
                switch(strtolower($row['priority1'])) {
                    case 'high':
                        $priorityClass = 'danger';
                        $priorityIcon = 'arrow-up';
                        break;
                    case 'medium':
                        $priorityClass = 'warning';
                        $priorityIcon = 'arrow-right';
                        break;
                }
            ?>
            <div class="col-lg-4 col-md-6 mb-4 idea-item"
                data-type="<?php echo strtolower(htmlspecialchars($row['project_type'])); ?>"
                data-priority="<?php echo strtolower(htmlspecialchars($row['priority1'])); ?>"
                data-status="<?php echo strtolower(htmlspecialchars($row['status'])); ?>"
                data-date="<?php echo date('Y-m-d', strtotime($row['submission_datetime'])); ?>">
                <div class="card idea-card h-100">
                    <div class="card-header d-flex align-items-center">
                        <i class="bi bi-lightbulb-fill card-icon"></i>
                        <div>
                            <h5 class="idea-title mb-0"><?php echo htmlspecialchars($row['project_name']); ?></h5>
                            <small class="text-muted">ER: <?php echo htmlspecialchars($row['er_number']); ?></small>
                        </div>
                        <span class="badge bg-<?php echo $priorityClass; ?> ms-auto">
                            <i class="bi bi-<?php echo $priorityIcon; ?>"></i>
                            <?php echo htmlspecialchars($row['priority1']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span
                                class="badge rounded-pill bg-info"><?php echo htmlspecialchars($row['classification']); ?></span>
                            <span
                                class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                        </div>

                        <p class="idea-description"><?php echo htmlspecialchars($row['description']); ?></p>

                        <div class="idea-meta mt-3">
                            <div class="row">
                                <div class="col-6">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($row['assigned_to']); ?>
                                </div>
                                <div class="col-6 text-end">
                                    <i class="bi bi-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($row['submission_datetime'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-primary view-details"
                                data-id="<?php echo $row['id']; ?>"
                                data-project="<?php echo htmlspecialchars($row['project_name']); ?>"
                                data-er="<?php echo htmlspecialchars($row['er_number']); ?>"
                                data-type="<?php echo htmlspecialchars($row['project_type']); ?>"
                                data-classification="<?php echo htmlspecialchars($row['classification']); ?>"
                                data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                data-priority="<?php echo htmlspecialchars($row['priority1']); ?>"
                                data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                data-assigned="<?php echo htmlspecialchars($row['assigned_to']); ?>"
                                data-submitted="<?php echo date('Y-m-d H:i', strtotime($row['submission_datetime'])); ?>"
                                data-completion="<?php echo $row['completion_date']; ?>" data-bs-toggle="modal"
                                data-bs-target="#detailsModal">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                            <div class="btn-group">
                                <form method="post" class="d-inline-block"
                                    onsubmit="return confirm('Are you sure you want to approve this idea?');">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="approve" class="btn btn-sm btn-success">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>
                                <form method="post" class="d-inline-block ms-1"
                                    onsubmit="return confirm('Are you sure you want to reject this idea? This will permanently delete it.');">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="reject" class="btn btn-sm btn-danger">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="no-results" id="noResults">
            <div class="card">
                <div class="card-body empty-state">
                    <i class="bi bi-search mb-3"></i>
                    <h4>No Matching Ideas</h4>
                    <p class="text-muted">No ideas match your current search or filter criteria.</p>
                    <button class="btn btn-outline-primary mt-2" id="clearFilters">
                        <i class="bi bi-arrow-repeat me-2"></i>Clear All Filters
                    </button>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="card">
            <div class="card-body empty-state">
                <i class="bi bi-inbox mb-3"></i>
                <h4>No Ideas Found</h4>
                <p class="text-muted">There are currently no pending ideas to review.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Idea Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <h3 id="modalProjectTitle"></h3>
                            <p class="text-muted">ER Number: <span id="modalErNumber"></span></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-info fs-6" id="modalClassification"></span>
                            <span class="badge bg-secondary fs-6 ms-2" id="modalProjectType"></span>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h6><i class="bi bi-person text-primary"></i> Assigned To</h6>
                                    <p id="modalAssignedTo" class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h6><i class="bi bi-calendar-event text-primary"></i> Submission Date</h6>
                                    <p id="modalSubmissionDate" class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h6><i class="bi bi-calendar-check text-primary"></i> Expected Completion</h6>
                                    <p id="modalCompletionDate" class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6><i class="bi bi-flag text-primary"></i> Status</h6>
                            <span id="modalStatus" class="badge"></span>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-arrow-up-circle text-primary"></i> Priority</h6>
                            <span id="modalPriority" class="badge"></span>
                        </div>
                    </div>

                    <h5 class="border-bottom pb-2 mb-3">Full Description</h5>
                    <div class="card">
                        <div class="card-body bg-light">
                            <p id="modalDescription" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <form method="post" class="d-inline-block">
                        <input type="hidden" name="id" id="modalIdApprove" value="">
                        <button type="submit" name="approve" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Approve Idea
                        </button>
                    </form>
                    <form method="post" class="d-inline-block">
                        <input type="hidden" name="id" id="modalIdReject" value="">
                        <button type="submit" name="reject" class="btn btn-danger">
                            <i class="bi bi-x-lg"></i> Reject Idea
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Ideas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="mb-3">
                            <label class="form-label">Project Type</label>
                            <select class="form-select" id="filterType">
                                <option value="">All Types</option>
                                <option value="development">Development</option>
                                <option value="research">Research</option>
                                <option value="innovation">Innovation</option>
                                <option value="improvement">Improvement</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <div class="form-check">
                                <input class="form-check-input filter-priority" type="checkbox" value="high"
                                    id="priorityHigh">
                                <label class="form-check-label" for="priorityHigh">High</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input filter-priority" type="checkbox" value="medium"
                                    id="priorityMedium">
                                <label class="form-check-label" for="priorityMedium">Medium</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input filter-priority" type="checkbox" value="low"
                                    id="priorityLow">
                                <label class="form-check-label" for="priorityLow">Low</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="filterStatus">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col">
                                    <input type="date" class="form-control" id="filterDateFrom" placeholder="From">
                                </div>
                                <div class="col">
                                    <input type="date" class="form-control" id="filterDateTo" placeholder="To">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="resetFilters">Reset</button>
                    <button type="button" class="btn btn-primary" id="applyFilters" data-bs-dismiss="modal">Apply
                        Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let searchTerm = this.value.toLowerCase();
        let cards = document.querySelectorAll('.idea-item');
        let visibleCount = 0;

        cards.forEach(function(card) {
            let title = card.querySelector('.idea-title').textContent.toLowerCase();
            let type = card.querySelector('.badge.rounded-pill').textContent.toLowerCase();
            let assigned = card.querySelector('.idea-meta .col-6').textContent.toLowerCase();
            let description = card.querySelector('.idea-description').textContent.toLowerCase();

            if (title.includes(searchTerm) || type.includes(searchTerm) ||
                assigned.includes(searchTerm) || description.includes(searchTerm)) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Show or hide no results message
        document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
    });

    // Modal content population
    document.querySelectorAll('.view-details').forEach(function(button) {
        button.addEventListener('click', function() {
            let id = this.getAttribute('data-id');
            let project = this.getAttribute('data-project');
            let erNumber = this.getAttribute('data-er');
            let projectType = this.getAttribute('data-type');
            let classification = this.getAttribute('data-classification');
            let status = this.getAttribute('data-status');
            let priority = this.getAttribute('data-priority');
            let description = this.getAttribute('data-description');
            let assignedTo = this.getAttribute('data-assigned');
            let submissionDate = this.getAttribute('data-submitted');
            let completionDate = this.getAttribute('data-completion');



            document.getElementById('modalProjectTitle').textContent = project;
            document.getElementById('modalErNumber').textContent = erNumber;
            document.getElementById('modalProjectType').textContent = projectType;
            document.getElementById('modalClassification').textContent = classification;
            document.getElementById('modalAssignedTo').textContent = assignedTo;
            document.getElementById('modalSubmissionDate').textContent = submissionDate;
            document.getElementById('modalCompletionDate').textContent = completionDate ||
                'Not specified';
            document.getElementById('modalDescription').textContent = description;

            // Set status badge color
            let statusBadge = document.getElementById('modalStatus');
            statusBadge.textContent = status;
            statusBadge.className = 'badge';

            switch (status.toLowerCase()) {
                case 'active':
                    statusBadge.classList.add('bg-success');
                    break;
                case 'pending':
                    statusBadge.classList.add('bg-warning');
                    break;
                case 'blocked':
                    statusBadge.classList.add('bg-danger');
                    break;
                default:
                    statusBadge.classList.add('bg-secondary');
            }

            // Set priority badge color
            let priorityBadge = document.getElementById('modalPriority');
            priorityBadge.textContent = priority;
            priorityBadge.className = 'badge';

            switch (priority.toLowerCase()) {
                case 'high':
                    priorityBadge.classList.add('bg-danger');
                    break;
                case 'medium':
                    priorityBadge.classList.add('bg-warning');
                    break;
                case 'low':
                    priorityBadge.classList.add('bg-primary');
                    break;
                default:
                    priorityBadge.classList.add('bg-secondary');
            }

            // Set form ids for approve/reject actions
            document.getElementById('modalIdApprove').value = id;
            document.getElementById('modalIdReject').value = id;
        });
    });

    // Filter functionality
    document.getElementById('applyFilters').addEventListener('click', function() {
        let filterType = document.getElementById('filterType').value.toLowerCase();
        let filterStatus = document.getElementById('filterStatus').value.toLowerCase();
        let filterDateFrom = document.getElementById('filterDateFrom').value;
        let filterDateTo = document.getElementById('filterDateTo').value;
        let selectedPriorities = Array.from(document.querySelectorAll('.filter-priority:checked')).map(el => el
            .value);

        let cards = document.querySelectorAll('.idea-item');
        let visibleCount = 0;

        cards.forEach(function(card) {
            let cardType = card.getAttribute('data-type');
            let cardStatus = card.getAttribute('data-status');
            let cardPriority = card.getAttribute('data-priority');
            let cardDate = card.getAttribute('data-date');

            let typeMatch = filterType === '' || cardType === filterType;
            let statusMatch = filterStatus === '' || cardStatus === filterStatus;
            let priorityMatch = selectedPriorities.length === 0 || selectedPriorities.includes(
                cardPriority);

            let dateMatch = true;
            if (filterDateFrom && cardDate < filterDateFrom) {
                dateMatch = false;
            }
            if (filterDateTo && cardDate > filterDateTo) {
                dateMatch = false;
            }

            if (typeMatch && statusMatch && priorityMatch && dateMatch) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Show or hide no results message
        document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
    });

    // Reset filters
    document.getElementById('resetFilters').addEventListener('click', function() {
        document.getElementById('filterType').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';

        document.querySelectorAll('.filter-priority').forEach(function(checkbox) {
            checkbox.checked = false;
        });
    });

    // Clear all filters button
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('filterType').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.getElementById('searchInput').value = '';

        document.querySelectorAll('.filter-priority').forEach(function(checkbox) {
            checkbox.checked = false;
        });

        // Show all cards
        document.querySelectorAll('.idea-item').forEach(function(card) {
            card.style.display = '';
        });

        // Hide no results message
        document.getElementById('noResults').style.display = 'none';
    });
    </script>
</body>

</html>