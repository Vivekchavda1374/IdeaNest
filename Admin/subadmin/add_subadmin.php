<?php


// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include_once "../../Login/Login/db.php";

// PHPMailer dependencies
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables
$message = '';
$error = '';

/**
 * Handle Subadmin Creation
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['request_action']) && !isset($_POST['remove_subadmin'])) {
    $email = trim($_POST['email']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // Check if subadmin already exists
        $check_stmt = $conn->prepare("SELECT id FROM subadmins WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "A subadmin with this email already exists.";
            $check_stmt->close();
        } else {
            $check_stmt->close();

            // Generate random password
            $plain_password = bin2hex(random_bytes(4)); // 8-character random password
            $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

            // Insert into database
            $stmt = $conn->prepare("INSERT INTO subadmins (email, password, created_at) VALUES (?, ?, NOW())");

            if ($stmt) {
                $stmt->bind_param("ss", $email, $hashed_password);

                if ($stmt->execute()) {
                    // Send email with credentials
                    $mail = new PHPMailer(true);

                    try {
                        // Gmail SMTP settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'ideanest.ict@gmail.com';
                        $mail->Password = 'luou xlhs ojuw auvx'; // Use your Gmail App Password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        // Email content
                        $mail->setFrom('ideanest.ict@gmail.com', 'IdeaNest Admin');
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Welcome to IdeaNest - Subadmin Access';

                        $mail->Body = "
                            <h3>Welcome to IdeaNest!</h3>
                            <p>Your subadmin account has been created.</p>
                            <ul>
                                <li><b>Login ID:</b> $email</li>
                                <li><b>Password:</b> $plain_password</li>
                            </ul>
                            <p>Please log in and change your password after first login.</p>
                            <p><a href='../../Login/Login/login.php' style='background-color: #4361ee; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login Now</a></p>
                        ";

                        $mail->AltBody = "
                            Your subadmin account has been created.
                            Login ID: $email
                            Password: $plain_password
                            Please log in and change your password after first login.
                        ";

                        $mail->send();
                        $message = "Subadmin added successfully and credentials sent to $email.";

                    } catch (Exception $e) {
                        $error = "Subadmin added, but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $error = "Failed to add subadmin. Please try again.";
                }

                $stmt->close();
            } else {
                $error = "Database error occurred.";
            }
        }
    }
}

/**
 * Handle Subadmin Removal
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_subadmin']) && isset($_POST['subadmin_id'])) {
    $subadmin_id = intval($_POST['subadmin_id']);
    $removal_reason = trim($_POST['removal_reason'] ?? '');

    if ($subadmin_id > 0) {
        // Get subadmin details before deletion for logging
        $stmt = $conn->prepare("SELECT email, name FROM subadmins WHERE id = ?");
        $stmt->bind_param("i", $subadmin_id);
        $stmt->execute();
        $stmt->bind_result($subadmin_email, $subadmin_name);

        if ($stmt->fetch()) {
            $stmt->close();

            // Start transaction for safe deletion
            $conn->autocommit(FALSE);

            try {
                // Delete related classification requests first
                $stmt1 = $conn->prepare("DELETE FROM subadmin_classification_requests WHERE subadmin_id = ?");
                $stmt1->bind_param("i", $subadmin_id);
                $stmt1->execute();
                $stmt1->close();

                // Delete subadmin
                $stmt2 = $conn->prepare("DELETE FROM subadmins WHERE id = ?");
                $stmt2->bind_param("i", $subadmin_id);
                $stmt2->execute();
                $stmt2->close();

                // Log the removal (optional - create admin_logs table if needed)
                $log_stmt = $conn->prepare("INSERT INTO admin_logs (action, details, timestamp) VALUES ('subadmin_removed', ?, NOW())");
                $log_details = "Removed subadmin: $subadmin_email ($subadmin_name). Reason: $removal_reason";
                $log_stmt->bind_param("s", $log_details);
                $log_stmt->execute();
                $log_stmt->close();

                // Commit transaction
                $conn->commit();
                $conn->autocommit(TRUE);

                $message = "Subadmin removed successfully.";

                // Send notification email to removed subadmin
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'ideanest.ict@gmail.com';
                    $mail->Password = 'luou xlhs ojuw auvx';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('ideanest.ict@gmail.com', 'IdeaNest Admin');
                    $mail->addAddress($subadmin_email);
                    $mail->isHTML(true);
                    $mail->Subject = 'IdeaNest - Subadmin Access Revoked';

                    $mail->Body = "
                        <h3>Access Revoked</h3>
                        <p>Your subadmin access to IdeaNest has been revoked.</p>
                        <p><strong>Reason:</strong> " . htmlspecialchars($removal_reason) . "</p>
                        <p>If you have any questions, please contact the administrator.</p>
                    ";

                    $mail->send();
                } catch (Exception $e) {
                    // Silently fail email notification
                }

            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $conn->autocommit(TRUE);
                $error = "Failed to remove subadmin. Please try again.";
            }
        } else {
            $stmt->close();
            $error = "Subadmin not found.";
        }
    } else {
        $error = "Invalid subadmin ID.";
    }

    // Refresh to avoid resubmission
    header("Location: add_subadmin.php?tab=manage");
    exit();
}

/**
 * Handle Classification Change Request Approval/Rejection
 */
if (isset($_POST['request_action']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['request_action'];
    $admin_comment = isset($_POST['admin_comment']) ? trim($_POST['admin_comment']) : '';

    // Fetch request details
    $stmt = $conn->prepare("
        SELECT subadmin_id, requested_software_classification, requested_hardware_classification 
        FROM subadmin_classification_requests 
        WHERE id = ?
    ");

    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($subadmin_id, $req_software, $req_hardware);

    if ($stmt->fetch()) {
        $stmt->close();

        if ($action === 'approve') {
            // Update subadmin classification
            $stmt2 = $conn->prepare("
                UPDATE subadmins 
                SET software_classification = ?, hardware_classification = ? 
                WHERE id = ?
            ");
            $stmt2->bind_param("ssi", $req_software, $req_hardware, $subadmin_id);
            $stmt2->execute();
            $stmt2->close();

            // Mark request as approved
            $stmt3 = $conn->prepare("
                UPDATE subadmin_classification_requests 
                SET status = 'approved', decision_date = NOW(), admin_comment = ? 
                WHERE id = ?
            ");
            $stmt3->bind_param("si", $admin_comment, $request_id);
            $stmt3->execute();
            $stmt3->close();

        } elseif ($action === 'reject') {
            // Mark request as rejected
            $stmt3 = $conn->prepare("
                UPDATE subadmin_classification_requests 
                SET status = 'rejected', decision_date = NOW(), admin_comment = ? 
                WHERE id = ?
            ");
            $stmt3->bind_param("si", $admin_comment, $request_id);
            $stmt3->execute();
            $stmt3->close();
        }
    } else {
        $stmt->close();
    }

    // Refresh to avoid resubmission
    header("Location: add_subadmin.php");
    exit();
}

/**
 * Fetch Pending Classification Change Requests
 */
$pending_requests = [];
$result = $conn->query("
    SELECT 
        r.id, 
        r.subadmin_id, 
        r.requested_software_classification, 
        r.requested_hardware_classification, 
        r.request_date,
        s.email, 
        s.software_classification AS current_software, 
        s.hardware_classification AS current_hardware 
    FROM subadmin_classification_requests r 
    JOIN subadmins s ON r.subadmin_id = s.id 
    WHERE r.status = 'pending' 
    ORDER BY r.request_date ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}

/**
 * Fetch Subadmin Count and Details
 */
// 1. Read filter values from GET
$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$software = trim($_GET['software_classification'] ?? '');
$hardware = trim($_GET['hardware_classification'] ?? '');
$status = trim($_GET['status'] ?? '');

// 2. Build dynamic SQL query
$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}
if ($department !== '') {
    $where[] = "domain = ?";
    $params[] = $department;
    $types .= 's';
}
if ($software !== '') {
    $where[] = "software_classification = ?";
    $params[] = $software;
    $types .= 's';
}
if ($hardware !== '') {
    $where[] = "hardware_classification = ?";
    $params[] = $hardware;
    $types .= 's';
}
if ($status !== '') {
    if ($status === 'active') {
        $where[] = "status = 'active'";
    } elseif ($status === 'inactive') {
        $where[] = "status = 'inactive'";
    }
}

$sql = "SELECT id, name, email, domain, software_classification, hardware_classification, status, created_at, last_login FROM subadmins";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$subadmin_count = $result->num_rows;
$subadmin_list = [];
while ($row = $result->fetch_assoc()) {
    $subadmin_list[] = $row;
}
$stmt->close();

// Get active tab from URL parameter
$active_tab = $_GET['tab'] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subadmin Management</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <style>
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background-color: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
            padding: 1rem;
        }

        .sidebar-header {
            padding: 1rem 0;
            text-align: center;
            border-bottom: 1px solid #f1f1f1;
            margin-bottom: 1rem;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4361ee;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .sidebar-brand i {
            margin-right: 0.5rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-item {
            margin-bottom: 0.5rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #6c757d;
            text-decoration: none;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }

        .sidebar-link i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        .sidebar-link.active {
            background-color: #4361ee;
            color: #fff;
        }

        .sidebar-link:hover:not(.active) {
            background-color: #f8f9fa;
            color: #4361ee;
        }

        .sidebar-divider {
            margin: 1rem 0;
            border-top: 1px solid #f1f1f1;
        }

        .sidebar-footer {
            padding: 1rem 0;
            border-top: 1px solid #f1f1f1;
            margin-top: 1rem;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 1rem;
            transition: all 0.3s;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4361ee;
            margin-left: 1rem;
        }

        /* Status badges */
        .status-active {
            background-color: #d1edff;
            color: #0969da;
        }

        .status-inactive {
            background-color: #ffebe9;
            color: #d1242f;
        }

        /* Action buttons */
        .btn-remove {
            background-color: #d73a49;
            border-color: #d73a49;
            color: #fff;
        }

        .btn-remove:hover {
            background-color: #cb2431;
            border-color: #b93129;
            color: #fff;
        }

        /* Responsive Design */
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

<body class="bg-light">
<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            <i class="bi bi-lightbulb"></i>
            <span>IdeaNest Admin</span>
        </a>
    </div>

    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="../admin.php" class="sidebar-link">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../admin_view_project.php" class="sidebar-link">
                <i class="bi bi-kanban"></i>
                <span>Projects</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../user_manage_by_admin.php" class="sidebar-link">
                <i class="bi bi-people"></i>
                <span>Users Management</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="add_subadmin.php" class="sidebar-link active">
                <i class="bi bi-person-plus"></i>
                <span>Subadmin Management</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../notifications.php" class="sidebar-link">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="../settings.php" class="sidebar-link">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="../../Login/Login/logout.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title">Subadmin Management</h1>
        <div class="topbar-actions">
            <div class="dropdown">
                <a href="#" class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="../settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../../Login/Login/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success alert-banner alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-banner alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Main Container -->
    <div class="container-fluid p-0" style="min-height:100vh;">
        <!-- Tab Navigation Bar -->
        <ul class="nav nav-tabs mt-4" id="subadminTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab === 'overview' ? 'active' : ''; ?>" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="<?php echo $active_tab === 'overview' ? 'true' : 'false'; ?>">
                    <i class="bi bi-people me-1"></i> Subadmin Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab === 'add' ? 'active' : ''; ?>" id="addsubadmin-tab" data-bs-toggle="tab" data-bs-target="#addsubadmin" type="button" role="tab" aria-controls="addsubadmin" aria-selected="<?php echo $active_tab === 'add' ? 'true' : 'false'; ?>">
                    <i class="bi bi-person-plus me-1"></i> Add Subadmin
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab === 'manage' ? 'active' : ''; ?>" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage" type="button" role="tab" aria-controls="manage" aria-selected="<?php echo $active_tab === 'manage' ? 'true' : 'false'; ?>">
                    <i class="bi bi-gear me-1"></i> Manage Subadmins
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab === 'pending' ? 'active' : ''; ?>" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="<?php echo $active_tab === 'pending' ? 'true' : 'false'; ?>">
                    <i class="bi bi-hourglass-split me-1"></i> Pending Requests
                    <?php if (count($pending_requests) > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?php echo count($pending_requests); ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="subadminTabContent" style="padding:0;">
            <!-- Subadmin Overview tab pane -->
            <div class="tab-pane fade <?php echo $active_tab === 'overview' ? 'show active' : ''; ?>" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                <div class="glass-card card p-4 w-100 mt-4">
                    <h4 class="mb-3">
                        <i class="bi bi-people me-2"></i>Subadmin Overview
                    </h4>
                    <!-- Filter/Search Bar UI -->
                    <form class="row g-3 align-items-end mb-4" id="subadminFilterBar" autocomplete="off" method="get">
                        <input type="hidden" name="tab" value="overview">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search name or email..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department">
                                <option value="">All Departments</option>
                                <option value="ICT" <?php if($department=='ICT') echo 'selected'; ?>>ICT</option>
                                <option value="CSE" <?php if($department=='CSE') echo 'selected'; ?>>CSE</option>
                                <option value="ECE" <?php if($department=='ECE') echo 'selected'; ?>>ECE</option>
                                <option value="EEE" <?php if($department=='EEE') echo 'selected'; ?>>EEE</option>
                                <option value="ME" <?php if($department=='ME') echo 'selected'; ?>>ME</option>
                                <option value="CE" <?php if($department=='CE') echo 'selected'; ?>>CE</option>
                                <option value="Other" <?php if($department=='Other') echo 'selected'; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php if($status=='active') echo 'selected'; ?>>Active</option>
                                <option value="inactive" <?php if($status=='inactive') echo 'selected'; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Software Classification</label>
                            <select class="form-select" name="software_classification">
                                <option value="">All Software</option>
                                <option value="Web" <?php if($software=='Web') echo 'selected'; ?>>Web</option>
                                <option value="Mobile" <?php if($software=='Mobile') echo 'selected'; ?>>Mobile</option>
                                <option value="Artificial Intelligence & Machine Learning" <?php if($software=='Artificial Intelligence & Machine Learning') echo 'selected'; ?>>AI & ML</option>
                                <option value="Desktop" <?php if($software=='Desktop') echo 'selected'; ?>>Desktop</option>
                                <option value="System Software" <?php if($software=='System Software') echo 'selected'; ?>>System Software</option>
                                <option value="Embedded/IoT Software" <?php if($software=='Embedded/IoT Software') echo 'selected'; ?>>Embedded/IoT</option>
                                <option value="Cybersecurity" <?php if($software=='Cybersecurity') echo 'selected'; ?>>Cybersecurity</option>
                                <option value="Game Development" <?php if($software=='Game Development') echo 'selected'; ?>>Game Development</option>
                                <option value="Data Science & Analytics" <?php if($software=='Data Science & Analytics') echo 'selected'; ?>>Data Science</option>
                                <option value="Cloud-Based Applications" <?php if($software=='Cloud-Based Applications') echo 'selected'; ?>>Cloud Applications</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="reset" class="btn btn-outline-secondary w-100" onclick="window.location.href='?tab=overview'"><i class="bi bi-x-circle"></i> Clear</button>
                        </div>
                    </form>
                    <div class="d-flex align-items-center mb-3">
                        <span class="fs-5 fw-bold me-2">Total Subadmins:</span>
                        <span class="badge bg-primary fs-6"><?php echo $subadmin_count; ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Software</th>
                                <th>Hardware</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Last Login</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($subadmin_list as $sub): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sub['name'] ?? 'Not Set'); ?></td>
                                    <td><?php echo htmlspecialchars($sub['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($sub['domain'] ?? 'Not Set'); ?></td>
                                    <td><?php echo htmlspecialchars($sub['software_classification'] ?? 'Not Set'); ?></td>
                                    <td><?php echo htmlspecialchars($sub['hardware_classification'] ?? 'Not Set'); ?></td>
                                    <td>
                                                <span class="badge <?php echo ($sub['status'] ?? 'active') === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo ucfirst($sub['status'] ?? 'active'); ?>
                                                </span>
                                    </td>
                                    <td><?php echo $sub['created_at'] ? date('M d, Y', strtotime($sub['created_at'])) : 'N/A'; ?></td>
                                    <td><?php echo $sub['last_login'] ? date('M d, Y', strtotime($sub['last_login'])) : 'Never'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Subadmin tab pane -->
            <div class="tab-pane fade <?php echo $active_tab === 'add' ? 'show active' : ''; ?>" id="addsubadmin" role="tabpanel" aria-labelledby="addsubadmin-tab">
                <div class="row justify-content-center align-items-start w-100">
                    <div class="col-md-6">
                        <div class="card shadow-lg mt-4">
                            <div class="card-body">
                                <h3 class="mb-4">
                                    <i class="bi bi-person-plus me-2"></i>Add New Subadmin
                                </h3>
                                <form method="post" autocomplete="off">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Subadmin Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" required>
                                        <div class="form-text">A random password will be generated and emailed to this address.</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-plus-circle me-2"></i>Add Subadmin & Send Credentials
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manage Subadmins Tab -->
            <div class="tab-pane fade <?php echo $active_tab === 'manage' ? 'show active' : ''; ?>" id="manage" role="tabpanel" aria-labelledby="manage-tab">
                <div class="row justify-content-center w-100">
                    <div class="col-md-12">
                        <div class="card shadow-lg mt-4">
                            <div class="card-body">
                                <h4 class="mb-4">
                                    <i class="bi bi-gear me-2"></i>Manage Subadmins
                                </h4>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($subadmin_list as $sub): ?>
                                            <tr>
                                                <td><?php echo $sub['id']; ?></td>
                                                <td><?php echo htmlspecialchars($sub['name'] ?? 'Not Set'); ?></td>
                                                <td><?php echo htmlspecialchars($sub['email'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($sub['domain'] ?? 'Not Set'); ?></td>
                                                <td>
                                                            <span class="badge <?php echo ($sub['status'] ?? 'active') === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                                <?php echo ucfirst($sub['status'] ?? 'active'); ?>
                                                            </span>
                                                </td>
                                                <td><?php echo $sub['created_at'] ? date('M d, Y', strtotime($sub['created_at'])) : 'N/A'; ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editModal<?php echo $sub['id']; ?>"
                                                                title="Edit Subadmin">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-remove"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#removeModal<?php echo $sub['id']; ?>"
                                                                title="Remove Subadmin">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Edit Modal -->
                                                    <div class="modal fade" id="editModal<?php echo $sub['id']; ?>"
                                                         tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Edit Subadmin</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($sub['email']); ?></p>
                                                                    <p><strong>Current Status:</strong>
                                                                        <span class="badge <?php echo ($sub['status'] ?? 'active') === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                                                    <?php echo ucfirst($sub['status'] ?? 'active'); ?>
                                                                                </span>
                                                                    </p>
                                                                    <div class="alert alert-info">
                                                                        <i class="bi bi-info-circle me-2"></i>
                                                                        Advanced subadmin editing features will be available in future updates.
                                                                        For now, you can remove and re-add subadmins as needed.
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Remove Modal -->
                                                    <div class="modal fade" id="removeModal<?php echo $sub['id']; ?>"
                                                         tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title">
                                                                        <i class="bi bi-exclamation-triangle me-2"></i>Remove Subadmin
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="post">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="subadmin_id" value="<?php echo $sub['id']; ?>">
                                                                        <div class="alert alert-warning">
                                                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                                                            <strong>Warning!</strong> This action cannot be undone.
                                                                        </div>
                                                                        <p><strong>Are you sure you want to remove this subadmin?</strong></p>
                                                                        <ul class="list-unstyled">
                                                                            <li><strong>Name:</strong> <?php echo htmlspecialchars($sub['name'] ?? 'Not Set'); ?></li>
                                                                            <li><strong>Email:</strong> <?php echo htmlspecialchars($sub['email']); ?></li>
                                                                            <li><strong>Department:</strong> <?php echo htmlspecialchars($sub['domain'] ?? 'Not Set'); ?></li>
                                                                        </ul>
                                                                        <div class="mb-3">
                                                                            <label for="removal_reason<?php echo $sub['id']; ?>" class="form-label">
                                                                                <strong>Reason for removal (required):</strong>
                                                                            </label>
                                                                            <textarea class="form-control"
                                                                                      name="removal_reason"
                                                                                      id="removal_reason<?php echo $sub['id']; ?>"
                                                                                      rows="3"
                                                                                      placeholder="Please provide a reason for removing this subadmin..."
                                                                                      required></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                            Cancel
                                                                        </button>
                                                                        <button type="submit" name="remove_subadmin" value="1" class="btn btn-danger">
                                                                            <i class="bi bi-trash me-2"></i>Remove Subadmin
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if (empty($subadmin_list)): ?>
                                    <div class="alert alert-info text-center">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No subadmins found. <a href="#" onclick="document.getElementById('addsubadmin-tab').click()">Add your first subadmin</a>.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Requests Tab -->
            <div class="tab-pane fade <?php echo $active_tab === 'pending' ? 'show active' : ''; ?>" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                <div class="row justify-content-center align-items-start w-100">
                    <div class="col-md-12">
                        <div class="card shadow-lg mt-4">
                            <div class="card-body">
                                <h4 class="mb-4">
                                    <i class="bi bi-hourglass-split me-2"></i>Pending Classification Change Requests
                                    <?php if (count($pending_requests) > 0): ?>
                                        <span class="badge bg-warning text-dark ms-2"><?php echo count($pending_requests); ?> Pending</span>
                                    <?php endif; ?>
                                </h4>
                                <?php if (count($pending_requests) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                            <tr>
                                                <th>Subadmin Email</th>
                                                <th>Request Date</th>
                                                <th>Current Software</th>
                                                <th>Current Hardware</th>
                                                <th>Requested Software</th>
                                                <th>Requested Hardware</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($pending_requests as $req): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($req['email']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, Y H:i', strtotime($req['request_date'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                                <span class="badge bg-light text-dark">
                                                                    <?php echo htmlspecialchars($req['current_software'] ?: 'None'); ?>
                                                                </span>
                                                    </td>
                                                    <td>
                                                                <span class="badge bg-light text-dark">
                                                                    <?php echo htmlspecialchars($req['current_hardware'] ?: 'None'); ?>
                                                                </span>
                                                    </td>
                                                    <td>
                                                                <span class="badge bg-primary">
                                                                    <?php echo htmlspecialchars($req['requested_software_classification']); ?>
                                                                </span>
                                                    </td>
                                                    <td>
                                                                <span class="badge bg-secondary">
                                                                    <?php echo htmlspecialchars($req['requested_hardware_classification']); ?>
                                                                </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <!-- Approve Button -->
                                                            <form method="post" style="display:inline-block;">
                                                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                                <input type="hidden" name="admin_comment" value="Request approved by admin">
                                                                <button type="submit" name="request_action" value="approve"
                                                                        class="btn btn-success btn-sm"
                                                                        onclick="return confirm('Are you sure you want to approve this classification change?')">
                                                                    <i class="bi bi-check-circle me-1"></i>Approve
                                                                </button>
                                                            </form>
                                                            <!-- Reject Button -->
                                                            <button class="btn btn-danger btn-sm"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#rejectModal<?php echo $req['id']; ?>">
                                                                <i class="bi bi-x-circle me-1"></i>Reject
                                                            </button>
                                                        </div>

                                                        <!-- Reject Modal -->
                                                        <div class="modal fade" id="rejectModal<?php echo $req['id']; ?>"
                                                             tabindex="-1"
                                                             aria-labelledby="rejectModalLabel<?php echo $req['id']; ?>"
                                                             aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-danger text-white">
                                                                        <h5 class="modal-title" id="rejectModalLabel<?php echo $req['id']; ?>">
                                                                            <i class="bi bi-x-circle me-2"></i>Reject Classification Change
                                                                        </h5>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <form method="post">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                                            <div class="mb-3">
                                                                                <p><strong>Subadmin:</strong> <?php echo htmlspecialchars($req['email']); ?></p>
                                                                                <p><strong>Requested Changes:</strong></p>
                                                                                <ul>
                                                                                    <li>Software: <?php echo htmlspecialchars($req['requested_software_classification']); ?></li>
                                                                                    <li>Hardware: <?php echo htmlspecialchars($req['requested_hardware_classification']); ?></li>
                                                                                </ul>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label for="admin_comment<?php echo $req['id']; ?>" class="form-label">
                                                                                    <strong>Reason for rejection (required):</strong>
                                                                                </label>
                                                                                <textarea class="form-control"
                                                                                          name="admin_comment"
                                                                                          id="admin_comment<?php echo $req['id']; ?>"
                                                                                          rows="3"
                                                                                          placeholder="Please provide a detailed reason for rejecting this request..."
                                                                                          required></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                                Cancel
                                                                            </button>
                                                                            <button type="submit" name="request_action" value="reject" class="btn btn-danger">
                                                                                <i class="bi bi-x-circle me-2"></i>Reject Request
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info text-center mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No pending classification change requests at this time.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Sidebar toggle for mobile
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show');
        document.querySelector('.main-content').classList.toggle('pushed');
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Confirm removal action
    function confirmRemoval() {
        return confirm('Are you sure you want to remove this subadmin? This action cannot be undone.');
    }

    // Tab persistence
    document.addEventListener('DOMContentLoaded', function() {
        // Get active tab from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');

        if (activeTab) {
            const tabButton = document.getElementById(activeTab + '-tab');
            if (tabButton) {
                const tab = new bootstrap.Tab(tabButton);
                tab.show();
            }
        }

        // Update URL when tab changes
        const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabButtons.forEach(function(button) {
            button.addEventListener('shown.bs.tab', function(e) {
                const tabId = e.target.getAttribute('data-bs-target').substring(1);
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId);
                window.history.replaceState({}, '', url);
            });
        });
    });
</script>
</body>
</html>