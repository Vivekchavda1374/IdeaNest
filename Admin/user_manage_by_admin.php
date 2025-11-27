<?php
require_once __DIR__ . '/includes/security_init.php';
require_once '../config/config.php';
// Production-safe error reporting
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

// Database connection
include "../Login/Login/db.php";

// Start session and check if admin is logged in
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to admin login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}

// Check if viewing specific user profile
$view_user_id = isset($_GET['view']) ? (int)$_GET['view'] : null;

// Set default active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_term = "%{$search}%";

// Get user profile data if viewing specific user
if ($view_user_id) {
    // Get user details
    $user_query = "SELECT * FROM register WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $view_user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();

    if (!$user_data) {
        $error_message = "User not found.";
        $view_user_id = null;
    } else {
        // Get user's projects
        $projects_query = "SELECT * FROM projects WHERE user_id = ? ORDER BY submission_date DESC";
        $stmt = $conn->prepare($projects_query);
        $stmt->bind_param("i", $view_user_id);
        $stmt->execute();
        $user_projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get user's approved projects
        $approved_query = "SELECT * FROM admin_approved_projects WHERE user_id = ? ORDER BY submission_date DESC";
        $stmt = $conn->prepare($approved_query);
        $stmt->bind_param("s", $view_user_id);
        $stmt->execute();
        $approved_projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get user's ideas/blog posts
        $ideas_query = "SELECT * FROM blog WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($ideas_query);
        $stmt->bind_param("i", $view_user_id);
        $stmt->execute();
        $user_ideas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get user's bookmarks
        $bookmarks_query = "SELECT b.*, ap.project_name FROM bookmark b 
                           LEFT JOIN admin_approved_projects ap ON b.project_id = ap.id 
                           WHERE b.user_id = ? ORDER BY b.bookmarked_at DESC";
        $stmt = $conn->prepare($bookmarks_query);
        $stmt->bind_param("s", $view_user_id);
        $stmt->execute();
        $user_bookmarks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle user block action
if (isset($_POST['block_user'])) {
    $user_id = $_POST['user_id'];

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Get all user data from register table
        $stmt = $conn->prepare("SELECT * FROM register WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        
        if ($user_data) {
            // Build dynamic INSERT query with all columns
            $columns = array_keys($user_data);
            $placeholders = array_fill(0, count($columns), '?');
            $types = str_repeat('s', count($columns));
            
            $insert_query = "INSERT INTO removed_user (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param($types, ...array_values($user_data));
            $stmt->execute();

            // Then delete from register table
            $stmt = $conn->prepare("DELETE FROM register WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            // Set success message
            $success_message = "User access removed successfully!";
        } else {
            throw new Exception("User not found in register table");
        }
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle restore access action
if (isset($_POST['restore_access'])) {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $enrollment_number = $_POST['enrollment_number'];
    $gr_number = $_POST['gr_number'];

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Get all user data from removed_user table
        $stmt = $conn->prepare("SELECT * FROM removed_user WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        
        if ($user_data) {
            // Build dynamic INSERT query with all columns
            $columns = array_keys($user_data);
            $placeholders = array_fill(0, count($columns), '?');
            $types = str_repeat('s', count($columns));
            
            $insert_query = "INSERT INTO register (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param($types, ...array_values($user_data));
            $stmt->execute();

            // Then delete from removed_user table
            $stmt = $conn->prepare("DELETE FROM removed_user WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            // Set success message
            $success_message = "User access restored successfully!";
        } else {
            throw new Exception("User not found in removed_user table");
        }
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all active users with search functionality
if (!empty($search)) {
    $active_users_sql = "SELECT id, name, email, enrollment_number, gr_number FROM register 
                         WHERE name LIKE ? OR gr_number LIKE ? OR enrollment_number LIKE ?";
    $stmt = $conn->prepare($active_users_sql);
    if (!$stmt) {
        die("Error preparing active users query: " . $conn->error);
    }
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $active_users_result = $stmt->get_result();
} else {
    $active_users_sql = "SELECT id, name, email, enrollment_number, gr_number FROM register";
    $active_users_result = $conn->query($active_users_sql);
    if (!$active_users_result) {
        die("Error in active users query: " . $conn->error);
    }
}

// Get all blocked users with search functionality
if (!empty($search)) {
    $blocked_users_sql = "SELECT id, name, email, enrollment_number, gr_number FROM removed_user 
                          WHERE name LIKE ? OR gr_number LIKE ? OR enrollment_number LIKE ?";
    $stmt = $conn->prepare($blocked_users_sql);
    if (!$stmt) {
        die("Error preparing blocked users query: " . $conn->error);
    }
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $blocked_users_result = $stmt->get_result();
} else {
    $blocked_users_sql = "SELECT id, name, email, enrollment_number, gr_number FROM removed_user";
    $blocked_users_result = $conn->query($blocked_users_sql);
    if (!$blocked_users_result) {
        die("Error in blocked users query: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management System - <?php echo $site_name; ?></title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/user_manage_by_admin.css">
    <link rel="stylesheet" href="assets/css/loader.css">
    <link rel="stylesheet" href="assets/css/loading.css">
</head>

<body>
<!-- Sidebar -->

<?php include 'sidebar_admin.php'?>

<!-- Main Content -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title" >User Management</h1>
        <div class="topbar-actions">
            <div class="dropdown">
                <a href="#" class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($success_message)) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- User Profile View -->
    <?php if ($view_user_id && $user_data) : ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-person"></i> User Profile: <?php echo htmlspecialchars($user_data['name']); ?></h5>
            <a href="user_manage_by_admin.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Users
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center mb-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                            <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
                        </div>
                        <h4 class="mt-2"><?php echo htmlspecialchars($user_data['name']); ?></h4>
                        <span class="badge bg-<?php echo $user_data['role'] === 'mentor' ? 'success' : 'primary'; ?>">
                            <?php echo ucfirst($user_data['role']); ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-8">
                    <h6>Contact Information</h6>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                    <p><strong>Enrollment:</strong> <?php echo htmlspecialchars($user_data['enrollment_number']); ?></p>
                    <p><strong>GR Number:</strong> <?php echo htmlspecialchars($user_data['gr_number']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($user_data['department'] ?? 'N/A'); ?></p>
                    <p><strong>About:</strong> <?php echo htmlspecialchars($user_data['about'] ?? 'No description'); ?></p>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary"><?php echo count($user_projects); ?></h3>
                            <small>Projects Submitted</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?php echo count($approved_projects); ?></h3>
                            <small>Approved Projects</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info"><?php echo count($user_ideas); ?></h3>
                            <small>Ideas Posted</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-warning"><?php echo count($user_bookmarks); ?></h3>
                            <small>Bookmarks</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Projects Tab -->
            <div class="mt-4">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#projects">Projects (<?php echo count($user_projects); ?>)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#approved">Approved (<?php echo count($approved_projects); ?>)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#ideas">Ideas (<?php echo count($user_ideas); ?>)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#bookmarks">Bookmarks (<?php echo count($user_bookmarks); ?>)</a>
                    </li>
                </ul>
                
                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="projects">
                        <?php if (count($user_projects) > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($user_projects as $project) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($project['project_type']); ?></td>
                                        <td><span class="badge bg-<?php echo $project['status'] === 'approved' ? 'success' : ($project['status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($project['status']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($project['submission_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else : ?>
                        <p class="text-muted">No projects submitted yet.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane fade" id="approved">
                        <?php if (count($approved_projects) > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Title</th><th>Type</th><th>Language</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($approved_projects as $project) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($project['project_type']); ?></td>
                                        <td><?php echo htmlspecialchars($project['language']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($project['submission_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else : ?>
                        <p class="text-muted">No approved projects yet.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane fade" id="ideas">
                        <?php if (count($user_ideas) > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($user_ideas as $idea) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($idea['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($idea['project_type']); ?></td>
                                        <td><span class="badge bg-<?php echo $idea['status'] === 'completed' ? 'success' : ($idea['status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($idea['status']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($idea['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else : ?>
                        <p class="text-muted">No ideas posted yet.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane fade" id="bookmarks">
                        <?php if (count($user_bookmarks) > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Project</th><th>Bookmarked</th></tr></thead>
                                <tbody>
                                    <?php foreach ($user_bookmarks as $bookmark) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($bookmark['project_name'] ?? 'Unknown Project'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($bookmark['bookmarked_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else : ?>
                        <p class="text-muted">No bookmarks yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else : ?>
    <!-- Main Content Area -->
    <div class="card">

        <div class="card-body">
            <!-- Search Box -->
            <div class="search-box">
                <form class="row g-3" method="get">
                    <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="search"
                                   placeholder="Search by name, GR number or enrollment number"
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i> Search
                        </button>
                        <?php if (!empty($search)) : ?>
                            <a href="?tab=<?php echo $active_tab; ?>"
                               class="btn btn-outline-secondary w-100 mt-2">
                                <i class="bi bi-x-circle me-2"></i> Clear Search
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Nav tabs -->
            <ul class="nav nav-tabs" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $active_tab == 'active' ? 'active' : ''; ?>"
                            id="active-users-tab" data-bs-toggle="tab" data-bs-target="#active-users"
                            type="button" role="tab" aria-controls="active-users"
                            aria-selected="<?php echo $active_tab == 'active' ? 'true' : 'false'; ?>">
                        <i class="bi bi-person-check me-2"></i> Active Users
                        <?php if ($active_users_result->num_rows > 0) : ?>
                            <span class="badge bg-primary ms-1"><?php echo $active_users_result->num_rows; ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $active_tab == 'blocked' ? 'active' : ''; ?>"
                            id="blocked-users-tab" data-bs-toggle="tab" data-bs-target="#blocked-users"
                            type="button" role="tab" aria-controls="blocked-users"
                            aria-selected="<?php echo $active_tab == 'blocked' ? 'true' : 'false'; ?>">
                        <i class="bi bi-person-x me-2"></i> Blocked Users
                        <?php if ($blocked_users_result->num_rows > 0) : ?>
                            <span class="badge bg-danger ms-1"><?php echo $blocked_users_result->num_rows; ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <!-- Active Users Tab -->
                <div class="tab-pane fade <?php echo $active_tab == 'active' ? 'show active' : ''; ?>"
                     id="active-users" role="tabpanel" aria-labelledby="active-users-tab">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Enrollment Number</th>
                                <th>GR Number</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($active_users_result->num_rows > 0) {
                                while ($row = $active_users_result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["enrollment_number"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["gr_number"]) . "</td>";
                                    echo "<td>
                                                    <a href='?view=" . $row["id"] . "' class='btn btn-primary btn-sm me-1'>
                                                        <i class='bi bi-eye'></i> View
                                                    </a>
                                                    <form method='post' action='' class='d-inline'>
                                                        <input type='hidden' name='user_id' value='" . $row["id"] . "'>
                                                        <input type='hidden' name='name' value='" . htmlspecialchars($row["name"]) . "'>
                                                        <input type='hidden' name='email' value='" . htmlspecialchars($row["email"]) . "'>
                                                        <input type='hidden' name='enrollment_number' value='" . htmlspecialchars($row["enrollment_number"]) . "'>
                                                        <input type='hidden' name='gr_number' value='" . htmlspecialchars($row["gr_number"]) . "'>
                                                        <button type='submit' name='block_user' class='btn btn-danger btn-sm'>
                                                            <i class='bi bi-slash-circle me-1'></i> Remove
                                                        </button>
                                                    </form>
                                                </td>";
                                    echo "</tr>";
                                }
                            } else {
                                if (!empty($search)) {
                                    echo "<tr><td colspan='6' class='text-center'>No active users found matching: '" . htmlspecialchars($search) . "'</td></tr>";
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>No active users found</td></tr>";
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Blocked Users Tab -->
                <div class="tab-pane fade <?php echo $active_tab == 'blocked' ? 'show active' : ''; ?>"
                     id="blocked-users" role="tabpanel" aria-labelledby="blocked-users-tab">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Enrollment Number</th>
                                <th>GR Number</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($blocked_users_result->num_rows > 0) {
                                while ($row = $blocked_users_result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["enrollment_number"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["gr_number"]) . "</td>";
                                    echo "<td>
                                                    <form method='post' action=''>
                                                        <input type='hidden' name='user_id' value='" . $row["id"] . "'>
                                                        <input type='hidden' name='name' value='" . htmlspecialchars($row["name"]) . "'>
                                                        <input type='hidden' name='email' value='" . htmlspecialchars($row["email"]) . "'>
                                                        <input type='hidden' name='enrollment_number' value='" . htmlspecialchars($row["enrollment_number"]) . "'>
                                                        <input type='hidden' name='gr_number' value='" . htmlspecialchars($row["gr_number"]) . "'>
                                                        <button type='submit' name='restore_access' class='btn btn-success btn-sm'>
                                                            <i class='bi bi-person-check me-1'></i> Restore Access
                                                        </button>
                                                    </form>
                                                </td>";
                                    echo "</tr>";
                                }
                            } else {
                                if (!empty($search)) {
                                    echo "<tr><td colspan='6' class='text-center'>No blocked users found matching: '" . htmlspecialchars($search) . "'</td></tr>";
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>No blocked users found</td></tr>";
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/user_manage_by_admin.js"></script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="assets/js/loader.js"></script>
<script src="assets/js/loading.js"></script>
</body>

</html>