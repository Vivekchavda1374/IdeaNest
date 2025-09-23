<?php


// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include_once "../../Login/Login/db.php";

// PHPMailer dependencies
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables
$message = '';
$error = '';

/**
 * Handle Subadmin Creation
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['request_action']) && !isset($_POST['remove_subadmin']) && !isset($_POST['ticket_action'])) {
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

        if ($result->num_rows !== false) {
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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_subadmin']) && isset($_POST['subadmin_id'])) {
    $subadmin_id = intval($_POST['subadmin_id']);
    $removal_reason = trim($_POST['removal_reason'] ?? '');

    if ($subadmin_id !== false) {
        // Get subadmin details before deletion for logging
        $stmt = $conn->prepare("SELECT email, name FROM subadmins WHERE id = ?");
        $stmt->bind_param("i", $subadmin_id);
        $stmt->execute();
        $stmt->bind_result($subadmin_email, $subadmin_name);

        if ($stmt->fetch()) {
            $stmt->close();

            // Start transaction for safe deletion
            $conn->autocommit(false);

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
                $conn->autocommit(true);

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
                $conn->autocommit(true);
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
if (isset($_POST['request_action']) && isset($_POST['request_id']) && !isset($_POST['ticket_action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['request_action'];
    $admin_comment = isset($_POST['admin_comment']) ? trim($_POST['admin_comment']) : '';

    // Fetch request details
    $stmt = $conn->prepare("
        SELECT subadmin_id, requested_domains 
        FROM subadmin_classification_requests 
        WHERE id = ?
    ");

    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($subadmin_id, $req_domains);

    if ($stmt->fetch()) {
        $stmt->close();

        if ($action === 'approve') {
            // Update subadmin domains
            $stmt2 = $conn->prepare("
                UPDATE subadmins 
                SET domains = ? 
                WHERE id = ?
            ");
            $stmt2->bind_param("si", $req_domains, $subadmin_id);
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
 * Handle Support Ticket Actions
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_action'])) {
    $ticket_action = $_POST['ticket_action'];
    $ticket_id = intval($_POST['ticket_id']);

    if ($ticket_action === 'respond' && isset($_POST['admin_response'])) {
        $admin_response = trim($_POST['admin_response']);
        $new_status = $_POST['new_status'] ?? 'in_progress';

        if (!empty($admin_response)) {
            // Update ticket with admin response
            $stmt = $conn->prepare("
                UPDATE support_tickets 
                SET admin_response = ?, status = ?, updated_at = NOW(),
                    admin_id = 1, admin_name = 'Admin'
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $admin_response, $new_status, $ticket_id);

            if ($stmt->execute()) {
                // Add reply to conversation
                $reply_stmt = $conn->prepare("
                    INSERT INTO support_ticket_replies (ticket_id, sender_type, sender_name, sender_email, message)
                    VALUES (?, 'admin', 'Admin', 'ideanest.ict@gmail.com', ?)
                ");
                $reply_stmt->bind_param("is", $ticket_id, $admin_response);
                $reply_stmt->execute();
                $reply_stmt->close();

                // Set resolved_at if status is resolved
                if ($new_status === 'resolved') {
                    $resolve_stmt = $conn->prepare("UPDATE support_tickets SET resolved_at = NOW() WHERE id = ?");
                    $resolve_stmt->bind_param("i", $ticket_id);
                    $resolve_stmt->execute();
                    $resolve_stmt->close();
                }

                $message = "Ticket response sent successfully.";
            } else {
                $error = "Failed to send response.";
            }
            $stmt->close();
        }
    } elseif ($ticket_action === 'update_status') {
        $new_status = $_POST['new_status'];
        $stmt = $conn->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $new_status, $ticket_id);

        if ($stmt->execute()) {
            if ($new_status === 'resolved') {
                $resolve_stmt = $conn->prepare("UPDATE support_tickets SET resolved_at = NOW() WHERE id = ?");
                $resolve_stmt->bind_param("i", $ticket_id);
                $resolve_stmt->execute();
                $resolve_stmt->close();
            } elseif ($new_status === 'closed') {
                $close_stmt = $conn->prepare("UPDATE support_tickets SET closed_at = NOW() WHERE id = ?");
                $close_stmt->bind_param("i", $ticket_id);
                $close_stmt->execute();
                $close_stmt->close();
            }
            $message = "Ticket status updated successfully.";
        } else {
            $error = "Failed to update ticket status.";
        }
        $stmt->close();
    }

    // Redirect to avoid resubmission
    header("Location: add_subadmin.php?tab=tickets");
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
        r.requested_domains, 
        r.request_date,
        s.email
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
 * Fetch Support Tickets
 */
$ticket_filter = $_GET['ticket_status'] ?? 'all';
$ticket_priority = $_GET['ticket_priority'] ?? 'all';
$ticket_category = $_GET['ticket_category'] ?? 'all';

$ticket_where = [];
$ticket_params = [];
$ticket_types = '';

if ($ticket_filter !== 'all') {
    $ticket_where[] = "status = ?";
    $ticket_params[] = $ticket_filter;
    $ticket_types .= 's';
}
if ($ticket_priority !== 'all') {
    $ticket_where[] = "priority = ?";
    $ticket_params[] = $ticket_priority;
    $ticket_types .= 's';
}
if ($ticket_category !== 'all') {
    $ticket_where[] = "category = ?";
    $ticket_params[] = $ticket_category;
    $ticket_types .= 's';
}

$ticket_sql = "SELECT * FROM support_tickets";
if ($ticket_where) {
    $ticket_sql .= " WHERE " . implode(" AND ", $ticket_where);
}
$ticket_sql .= " ORDER BY 
    CASE status 
        WHEN 'open' THEN 1 
        WHEN 'in_progress' THEN 2 
        WHEN 'resolved' THEN 3 
        WHEN 'closed' THEN 4 
    END,
    CASE priority 
        WHEN 'urgent' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        WHEN 'low' THEN 4 
    END,
    created_at DESC";

$ticket_stmt = $conn->prepare($ticket_sql);
if ($ticket_params) {
    $ticket_stmt->bind_param($ticket_types, ...$ticket_params);
}
$ticket_stmt->execute();
$ticket_result = $ticket_stmt->get_result();

$support_tickets = [];
while ($row = $ticket_result->fetch_assoc()) {
    $support_tickets[] = $row;
}
$ticket_stmt->close();

// Get ticket statistics
$stats_result = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
        SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count
    FROM support_tickets
");
$ticket_stats = $stats_result->fetch_assoc();

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

$sql = "SELECT id, name, email, domain, domains, status, created_at, last_login FROM subadmins";
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

        /* Ticket status badges */
        .status-open { background-color: #fff3cd; color: #856404; }
        .status-in_progress { background-color: #d1ecf1; color: #0c5460; }
        .status-resolved { background-color: #d4edda; color: #155724; }
        .status-closed { background-color: #f8d7da; color: #721c24; }

        /* Priority badges */
        .priority-low { background-color: #e2e3e5; color: #383d41; }
        .priority-medium { background-color: #fff3cd; color: #856404; }
        .priority-high { background-color: #f8d7da; color: #721c24; }
        .priority-urgent { background-color: #d73a49; color: #fff; }

        /* Category badges */
        .category-technical { background-color: #d1ecf1; color: #0c5460; }
        .category-account { background-color: #d4edda; color: #155724; }
        .category-project { background-color: #e2e3e5; color: #383d41; }
        .category-bug_report { background-color: #f8d7da; color: #721c24; }
        .category-feature_request { background-color: #d1edff; color: #0969da; }
        .category-other { background-color: #f3e2f3; color: #6f42c1; }

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

        /* Stats cards */
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stats-card.urgent {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        }

        .stats-card.high {
            background: linear-gradient(135deg, #feca57 0%, #ff9ff3 100%);
        }

        .stats-card.medium {
            background: linear-gradient(135deg, #48dbfb 0%, #0abde3 100%);
        }

        .stats-card.resolved {
            background: linear-gradient(135deg, #1dd1a1 0%, #10ac84 100%);
        }

        /* Ticket conversation */
        .ticket-conversation {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
        }

        .message-bubble {
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            max-width: 80%;
        }

        .message-bubble.admin {
            background-color: #4361ee;
            color: white;
            margin-left: auto;
            text-align: right;
        }

        .message-bubble.subadmin {
            background-color: #f8f9fa;
            color: #333;
            margin-right: auto;
        }

        .message-meta {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 0.25rem;
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
<?php include '../sidebar_admin.php'; ?>

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
    <?php if ($message) : ?>
        <div class="alert alert-success alert-banner alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error) : ?>
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
                    <?php if (count($pending_requests) !== false) : ?>
                        <span class="badge bg-warning text-dark ms-1"><?php echo count($pending_requests); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab === 'tickets' ? 'active' : ''; ?>" id="tickets-tab" data-bs-toggle="tab" data-bs-target="#tickets" type="button" role="tab" aria-controls="tickets" aria-selected="<?php echo $active_tab === 'tickets' ? 'true' : 'false'; ?>">
                    <i class="bi bi-headset me-1"></i> Support Tickets
                    <?php if ($ticket_stats['open_count'] + $ticket_stats['in_progress_count'] !== false) : ?>
                        <span class="badge bg-danger ms-1"><?php echo $ticket_stats['open_count'] + $ticket_stats['in_progress_count']; ?></span>
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
                                <option value="ICT" <?php if ($department == 'ICT') {
                                    echo 'selected';
                                                    } ?>>ICT</option>
                                <option value="CSE" <?php if ($department == 'CSE') {
                                    echo 'selected';
                                                    } ?>>CSE</option>
                                <option value="ECE" <?php if ($department == 'ECE') {
                                    echo 'selected';
                                                    } ?>>ECE</option>
                                <option value="EEE" <?php if ($department == 'EEE') {
                                    echo 'selected';
                                                    } ?>>EEE</option>
                                <option value="ME" <?php if ($department == 'ME') {
                                    echo 'selected';
                                                   } ?>>ME</option>
                                <option value="CE" <?php if ($department == 'CE') {
                                    echo 'selected';
                                                   } ?>>CE</option>
                                <option value="Other" <?php if ($department == 'Other') {
                                    echo 'selected';
                                                      } ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php if ($status == 'active') {
                                    echo 'selected';
                                                       } ?>>Active</option>
                                <option value="inactive" <?php if ($status == 'inactive') {
                                    echo 'selected';
                                                         } ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Software Classification</label>
                            <select class="form-select" name="software_classification">
                                <option value="">All Software</option>
                                <option value="Web" <?php if ($software == 'Web') {
                                    echo 'selected';
                                                    } ?>>Web</option>
                                <option value="Mobile" <?php if ($software == 'Mobile') {
                                    echo 'selected';
                                                       } ?>>Mobile</option>
                                <option value="Artificial Intelligence & Machine Learning" <?php if ($software == 'Artificial Intelligence & Machine Learning') {
                                    echo 'selected';
                                                                                           } ?>>AI & ML</option>
                                <option value="Desktop" <?php if ($software == 'Desktop') {
                                    echo 'selected';
                                                        } ?>>Desktop</option>
                                <option value="System Software" <?php if ($software == 'System Software') {
                                    echo 'selected';
                                                                } ?>>System Software</option>
                                <option value="Embedded/IoT Software" <?php if ($software == 'Embedded/IoT Software') {
                                    echo 'selected';
                                                                      } ?>>Embedded/IoT</option>
                                <option value="Cybersecurity" <?php if ($software == 'Cybersecurity') {
                                    echo 'selected';
                                                              } ?>>Cybersecurity</option>
                                <option value="Game Development" <?php if ($software == 'Game Development') {
                                    echo 'selected';
                                                                 } ?>>Game Development</option>
                                <option value="Data Science & Analytics" <?php if ($software == 'Data Science & Analytics') {
                                    echo 'selected';
                                                                         } ?>>Data Science</option>
                                <option value="Cloud-Based Applications" <?php if ($software == 'Cloud-Based Applications') {
                                    echo 'selected';
                                                                         } ?>>Cloud Applications</option>
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
                                <th>Domains</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Last Login</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($subadmin_list as $sub) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sub['name'] ?: 'Not Set'); ?></td>
                                    <td><?php echo htmlspecialchars($sub['email'] ?: ''); ?></td>
                                    <td><?php echo htmlspecialchars($sub['domain'] ?: 'Not Set'); ?></td>
                                    <td><?php echo htmlspecialchars($sub['domains'] ?: 'No domains'); ?></td>
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
                                        <?php foreach ($subadmin_list as $sub) : ?>
                                            <tr>
                                                <td><?php echo $sub['id']; ?></td>
                                                <td><?php echo htmlspecialchars($sub['name'] ?: 'Not Set'); ?></td>
                                                <td><?php echo htmlspecialchars($sub['email'] ?: ''); ?></td>
                                                <td><?php echo htmlspecialchars($sub['domain'] ?: 'Not Set'); ?></td>
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

                                <?php if (empty($subadmin_list)) : ?>
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
                                    <?php if (count($pending_requests) !== false) : ?>
                                        <span class="badge bg-warning text-dark ms-2"><?php echo count($pending_requests); ?> Pending</span>
                                    <?php endif; ?>
                                </h4>
                                <?php if (count($pending_requests) !== false) : ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                            <tr>
                                                <th>Subadmin Email</th>
                                                <th>Request Date</th>
                                                <th>Requested Domains</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($pending_requests as $req) : ?>
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
                                                        <span class="badge bg-primary">
                                                            <?php echo htmlspecialchars($req['requested_domains'] ?: 'No domains requested'); ?>
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
                                                                                <p><strong>Requested Domains:</strong> <?php echo htmlspecialchars($req['requested_domains'] ?: 'No domains'); ?></p>
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
                                <?php else : ?>
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

            <!-- Support Tickets Tab -->
            <div class="tab-pane fade <?php echo $active_tab === 'tickets' ? 'show active' : ''; ?>" id="tickets" role="tabpanel" aria-labelledby="tickets-tab">
                <div class="row w-100">
                    <div class="col-12">
                        <div class="card shadow-lg mt-4">
                            <div class="card-body">
                                <h4 class="mb-4">
                                    <i class="bi bi-headset me-2"></i>Support Tickets Management
                                </h4>

                                <!-- Ticket Statistics -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="stats-card">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-1">Total Tickets</h5>
                                                    <h3 class="mb-0"><?php echo $ticket_stats['total']; ?></h3>
                                                </div>
                                                <i class="bi bi-ticket-perforated" style="font-size: 2rem; opacity: 0.7;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stats-card urgent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-1">Open Tickets</h5>
                                                    <h3 class="mb-0"><?php echo $ticket_stats['open_count']; ?></h3>
                                                </div>
                                                <i class="bi bi-exclamation-circle" style="font-size: 2rem; opacity: 0.7;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stats-card high">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-1">In Progress</h5>
                                                    <h3 class="mb-0"><?php echo $ticket_stats['in_progress_count']; ?></h3>
                                                </div>
                                                <i class="bi bi-clock-history" style="font-size: 2rem; opacity: 0.7;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stats-card resolved">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-1">Resolved</h5>
                                                    <h3 class="mb-0"><?php echo $ticket_stats['resolved_count']; ?></h3>
                                                </div>
                                                <i class="bi bi-check-circle" style="font-size: 2rem; opacity: 0.7;"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ticket Filters -->
                                <form class="row g-3 align-items-end mb-4" method="get">
                                    <input type="hidden" name="tab" value="tickets">
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="ticket_status">
                                            <option value="all" <?php if ($ticket_filter == 'all') {
                                                echo 'selected';
                                                                } ?>>All Status</option>
                                            <option value="open" <?php if ($ticket_filter == 'open') {
                                                echo 'selected';
                                                                 } ?>>Open</option>
                                            <option value="in_progress" <?php if ($ticket_filter == 'in_progress') {
                                                echo 'selected';
                                                                        } ?>>In Progress</option>
                                            <option value="resolved" <?php if ($ticket_filter == 'resolved') {
                                                echo 'selected';
                                                                     } ?>>Resolved</option>
                                            <option value="closed" <?php if ($ticket_filter == 'closed') {
                                                echo 'selected';
                                                                   } ?>>Closed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Priority</label>
                                        <select class="form-select" name="ticket_priority">
                                            <option value="all" <?php if ($ticket_priority == 'all') {
                                                echo 'selected';
                                                                } ?>>All Priority</option>
                                            <option value="urgent" <?php if ($ticket_priority == 'urgent') {
                                                echo 'selected';
                                                                   } ?>>Urgent</option>
                                            <option value="high" <?php if ($ticket_priority == 'high') {
                                                echo 'selected';
                                                                 } ?>>High</option>
                                            <option value="medium" <?php if ($ticket_priority == 'medium') {
                                                echo 'selected';
                                                                   } ?>>Medium</option>
                                            <option value="low" <?php if ($ticket_priority == 'low') {
                                                echo 'selected';
                                                                } ?>>Low</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="ticket_category">
                                            <option value="all" <?php if ($ticket_category == 'all') {
                                                echo 'selected';
                                                                } ?>>All Categories</option>
                                            <option value="technical" <?php if ($ticket_category == 'technical') {
                                                echo 'selected';
                                                                      } ?>>Technical</option>
                                            <option value="account" <?php if ($ticket_category == 'account') {
                                                echo 'selected';
                                                                    } ?>>Account</option>
                                            <option value="project" <?php if ($ticket_category == 'project') {
                                                echo 'selected';
                                                                    } ?>>Project</option>
                                            <option value="bug_report" <?php if ($ticket_category == 'bug_report') {
                                                echo 'selected';
                                                                       } ?>>Bug Report</option>
                                            <option value="feature_request" <?php if ($ticket_category == 'feature_request') {
                                                echo 'selected';
                                                                            } ?>>Feature Request</option>
                                            <option value="other" <?php if ($ticket_category == 'other') {
                                                echo 'selected';
                                                                  } ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-funnel me-2"></i>Filter Tickets
                                        </button>
                                    </div>
                                </form>

                                <!-- Tickets Table -->
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Subadmin</th>
                                            <th>Subject</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (empty($support_tickets)) : ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <div class="alert alert-info mb-0">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        No support tickets found.
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else : ?>
                                            <?php foreach ($support_tickets as $ticket) : ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($ticket['ticket_number']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($ticket['subadmin_name']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($ticket['subadmin_email']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($ticket['subject']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge category-<?php echo $ticket['category']; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['category'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge priority-<?php echo $ticket['priority']; ?>">
                                                            <?php echo ucfirst($ticket['priority']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge status-<?php echo $ticket['status']; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-primary"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#ticketModal<?php echo $ticket['id']; ?>"
                                                                    title="View Ticket">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-success"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#respondModal<?php echo $ticket['id']; ?>"
                                                                    title="Respond to Ticket">
                                                                <i class="bi bi-reply"></i>
                                                            </button>
                                                        </div>

                                                        <!-- Ticket View Modal -->
                                                        <div class="modal fade" id="ticketModal<?php echo $ticket['id']; ?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">
                                                                            <i class="bi bi-ticket-perforated me-2"></i>
                                                                            Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                                                        </h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="row mb-3">
                                                                            <div class="col-md-6">
                                                                                <strong>Subadmin:</strong> <?php echo htmlspecialchars($ticket['subadmin_name']); ?><br>
                                                                                <strong>Email:</strong> <?php echo htmlspecialchars($ticket['subadmin_email']); ?>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?><br>
                                                                                <strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($ticket['updated_at'])); ?>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row mb-3">
                                                                            <div class="col-md-4">
                                                                                <strong>Category:</strong>
                                                                                <span class="badge category-<?php echo $ticket['category']; ?>">
                                                                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['category'])); ?>
                                                                                </span>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                <strong>Priority:</strong>
                                                                                <span class="badge priority-<?php echo $ticket['priority']; ?>">
                                                                                    <?php echo ucfirst($ticket['priority']); ?>
                                                                                </span>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                <strong>Status:</strong>
                                                                                <span class="badge status-<?php echo $ticket['status']; ?>">
                                                                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                                                                </span>
                                                                            </div>
                                                                        </div>

                                                                        <hr>

                                                                        <h6><strong>Subject:</strong> <?php echo htmlspecialchars($ticket['subject']); ?></h6>

                                                                        <div class="mb-3">
                                                                            <strong>Message:</strong>
                                                                            <div class="p-3 bg-light rounded">
                                                                                <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                                                                            </div>
                                                                        </div>

                                                                        <?php if ($ticket['admin_response']) : ?>
                                                                            <div class="mb-3">
                                                                                <strong>Admin Response:</strong>
                                                                                <div class="p-3 bg-primary bg-opacity-10 rounded">
                                                                                    <?php echo nl2br(htmlspecialchars($ticket['admin_response'])); ?>
                                                                                </div>
                                                                            </div>
                                                                        <?php endif; ?>

                                                                        <!-- Conversation History -->
                                                                        <?php
                                                                        $replies_result = $conn->query("SELECT * FROM support_ticket_replies WHERE ticket_id = {$ticket['id']} ORDER BY created_at ASC");
                                                                        $replies = [];
                                                                        while ($reply = $replies_result->fetch_assoc()) {
                                                                            $replies[] = $reply;
                                                                        }
                                                                        ?>

                                                                        <?php if (!empty($replies)) : ?>
                                                                            <hr>
                                                                            <h6><strong>Conversation History:</strong></h6>
                                                                            <div class="ticket-conversation">
                                                                                <?php foreach ($replies as $reply) : ?>
                                                                                    <div class="message-bubble <?php echo $reply['sender_type']; ?>">
                                                                                        <div class="message-content">
                                                                                            <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                                                                                        </div>
                                                                                        <div class="message-meta">
                                                                                            <strong><?php echo htmlspecialchars($reply['sender_name']); ?></strong> -
                                                                                            <?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <button type="button" class="btn btn-success"
                                                                                data-bs-dismiss="modal"
                                                                                onclick="$('#respondModal<?php echo $ticket['id']; ?>').modal('show')">
                                                                            <i class="bi bi-reply me-2"></i>Respond to Ticket
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Respond Modal -->
                                                        <div class="modal fade" id="respondModal<?php echo $ticket['id']; ?>" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">
                                                                            <i class="bi bi-reply me-2"></i>
                                                                            Respond to Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                                                        </h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="post">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="ticket_action" value="respond">
                                                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">

                                                                            <div class="alert alert-info">
                                                                                <strong>Subject:</strong> <?php echo htmlspecialchars($ticket['subject']); ?><br>
                                                                                <strong>From:</strong> <?php echo htmlspecialchars($ticket['subadmin_name']); ?> (<?php echo htmlspecialchars($ticket['subadmin_email']); ?>)
                                                                            </div>

                                                                            <div class="mb-3">
                                                                                <label for="admin_response<?php echo $ticket['id']; ?>" class="form-label">
                                                                                    <strong>Your Response:</strong>
                                                                                </label>
                                                                                <textarea class="form-control" name="admin_response"
                                                                                          id="admin_response<?php echo $ticket['id']; ?>"
                                                                                          rows="6"
                                                                                          placeholder="Type your response to the subadmin..."
                                                                                          required></textarea>
                                                                            </div>

                                                                            <div class="mb-3">
                                                                                <label for="new_status<?php echo $ticket['id']; ?>" class="form-label">
                                                                                    <strong>Update Status:</strong>
                                                                                </label>
                                                                                <select class="form-select" name="new_status" id="new_status<?php echo $ticket['id']; ?>">
                                                                                    <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                                                    <option value="resolved" <?php echo $ticket['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                                                    <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="submit" class="btn btn-success">
                                                                                <i class="bi bi-send me-2"></i>Send Response
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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

    // Auto-refresh ticket stats every 30 seconds
    setInterval(function() {
        if (document.querySelector('#tickets.active')) {
            location.reload();
        }
    }, 30000);

    // Form validation for ticket responses
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const textarea = form.querySelector('textarea[name="admin_response"]');
            if (textarea && textarea.value.trim().length < 10) {
                e.preventDefault();
                alert('Please provide a more detailed response (at least 10 characters).');
                textarea.focus();
            }
        });
    });
</script>
</body>
</html>