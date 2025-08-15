<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include "../Login/Login/db.php";

// Start session and check if admin is logged in
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to admin login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}

// Set default active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_term = "%{$search}%";

// Handle user block action
if (isset($_POST['block_user'])) {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $enrollment_number = $_POST['enrollment_number'];
    $gr_number = $_POST['gr_number'];

    // Begin transaction
    $conn->begin_transaction();

    try {
        // First, insert into removed_user table WITH THE SAME ID from register
        $stmt = $conn->prepare("INSERT INTO removed_user (id, name, email, enrollment_number, gr_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $name, $email, $enrollment_number, $gr_number);
        $stmt->execute();

        // Then delete from register table
        $stmt = $conn->prepare("DELETE FROM register WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Set success message
        $success_message = "User access removed successfully!";
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
        // First, insert back into register table WITH THE SAME ID
        $stmt = $conn->prepare("INSERT INTO register (id, name, email, enrollment_number, gr_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $name, $email, $enrollment_number, $gr_number);
        $stmt->execute();

        // Then delete from removed_user table
        $stmt = $conn->prepare("DELETE FROM removed_user WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Set success message
        $success_message = "User access restored successfully!";
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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>


        /* Tab Navigation */
        .nav-tabs {
            border-bottom: 1px solid #f1f1f1;
            margin-bottom: 1.5rem;
        }

        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            padding: 0.75rem 1rem;
            color: #6c757d;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            border-color: #4361ee;
            color: #4361ee;
            background-color: transparent;
        }

        .nav-tabs .nav-link:hover:not(.active) {
            border-color: #f8f9fa;
            color: #4361ee;
        }

        /* Card Styles */
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f1f1f1;
            padding: 1.25rem 1.5rem;
        }

        .card-title {
            margin-bottom: 0;
            font-weight: 600;
            color: #495057;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            border-top: none;
            padding: 1rem;
            color: #495057;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        /* Search Box */
        .search-box {
            margin-bottom: 1.5rem;
        }

        .search-box .input-group {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .search-box .input-group-text {
            background-color: #fff;
            border: none;
            color: #6c757d;
        }

        .search-box .form-control {
            border: none;
            box-shadow: none;
            padding: 0.75rem 1rem;
        }

        .search-box .form-control:focus {
            box-shadow: none;
        }

        /* Button Styles */
        .btn {
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 0.25rem;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: #4361ee;
            border-color: #4361ee;
        }

        .btn-primary:hover {
            background-color: #2a4ade;
            border-color: #2a4ade;
        }

        .btn-danger {
            background-color: #ef4444;
            border-color: #ef4444;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            border-color: #dc2626;
        }

        .btn-success {
            background-color: #10b981;
            border-color: #10b981;
        }

        .btn-success:hover {
            background-color: #059669;
            border-color: #059669;
        }

        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        /* Media Queries */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.pushed {
                margin-left: 250px;
            }
        }
    </style>
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
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

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
                        <?php if(!empty($search)): ?>
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
                        <?php if ($active_users_result->num_rows > 0): ?>
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
                        <?php if ($blocked_users_result->num_rows > 0): ?>
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
                                while($row = $active_users_result->fetch_assoc()) {
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
                                                        <button type='submit' name='block_user' class='btn btn-danger btn-sm'>
                                                            <i class='bi bi-slash-circle me-1'></i> Remove Access
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
                                while($row = $blocked_users_result->fetch_assoc()) {
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
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JavaScript to maintain the active tab and search parameters after form submission
    document.addEventListener('DOMContentLoaded', function() {
        // Get the stored tab from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');

        if (tabParam) {
            // If tab parameter exists in URL, activate that tab
            const tabToActivate = document.querySelector('#userTabs button[data-bs-target="#' + tabParam + '-users"]');
            if (tabToActivate) {
                const tab = new bootstrap.Tab(tabToActivate);
                tab.show();
            }
        }

        // Add event listeners to tabs to store the active tab
        const tabs = document.querySelectorAll('#userTabs button');
        tabs.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(event) {
                const targetId = event.target.getAttribute('data-bs-target').replace('#', '').replace('-users', '');
                // Update URL without refreshing page, preserving search term
                const searchParam = urlParams.get('search') ? '&search=' + urlParams.get('search') : '';
                history.replaceState(null, null, '?tab=' + targetId + searchParam);
            });
        });

        // Sidebar toggle functionality for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                mainContent.classList.toggle('pushed');
            });
        }
    });
</script>
</body>

</html>