<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include_once "../../Login/Login/db.php";
// PHPMailer
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // Generate random password
        $plain_password = bin2hex(random_bytes(4)); // 8-char random password
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO subadmins (email, password) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $hashed_password);
            if ($stmt->execute()) {
                // Send email
                $mail = new PHPMailer(true);
                try {
                    // Gmail SMTP settings (hardcoded)
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'ideanest.ict@gmail.com';
                    $mail->Password = 'luou xlhs ojuw auvx'; // Use your Gmail App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('ideanest.ict@gmail.com', 'IdeaNest Admin');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Welcome to IdeaNest - Subadmin Access';
                    $mail->Body = "<h3>Welcome to IdeaNest!</h3><p>Your subadmin account has been created.</p><ul><li><b>Login ID:</b> $email</li><li><b>Password:</b> $plain_password</li></ul><p>Please log in and change your password after first login.</p>";
                    $mail->AltBody = "Your subadmin account has been created.\nLogin ID: $email\nPassword: $plain_password\nPlease log in and change your password after first login.";
                    $mail->send();
                    $message = "Subadmin added and credentials sent to $email.";
                } catch (Exception $e) {
                    $error = "Subadmin added, but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $error = "Failed to add subadmin. Email may already exist.";
            }
            $stmt->close();
        } else {
            $error = "Database error.";
        }
    }
}

// Handle classification change request approval/rejection
if (isset($_POST['request_action']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['request_action'];
    $admin_comment = isset($_POST['admin_comment']) ? trim($_POST['admin_comment']) : '';
    // Fetch request details
    $stmt = $conn->prepare("SELECT subadmin_id, requested_software_classification, requested_hardware_classification FROM subadmin_classification_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($subadmin_id, $req_software, $req_hardware);
    if ($stmt->fetch()) {
        $stmt->close();
        if ($action === 'approve') {
            // Update subadmin classification
            $stmt2 = $conn->prepare("UPDATE subadmins SET software_classification=?, hardware_classification=? WHERE id=?");
            $stmt2->bind_param("ssi", $req_software, $req_hardware, $subadmin_id);
            $stmt2->execute();
            $stmt2->close();
            // Mark request as approved
            $stmt3 = $conn->prepare("UPDATE subadmin_classification_requests SET status='approved', decision_date=NOW(), admin_comment=? WHERE id=?");
            $stmt3->bind_param("si", $admin_comment, $request_id);
            $stmt3->execute();
            $stmt3->close();
        } elseif ($action === 'reject') {
            // Mark request as rejected
            $stmt3 = $conn->prepare("UPDATE subadmin_classification_requests SET status='rejected', decision_date=NOW(), admin_comment=? WHERE id=?");
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
// Fetch all pending classification change requests
$pending_requests = [];
$result = $conn->query("SELECT r.id, r.subadmin_id, r.requested_software_classification, r.requested_hardware_classification, s.email, s.software_classification AS current_software, s.hardware_classification AS current_hardware FROM subadmin_classification_requests r JOIN subadmins s ON r.subadmin_id = s.id WHERE r.status = 'pending' ORDER BY r.request_date ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}
// Fetch subadmin count and details
$subadmin_count = 0;
$subadmin_list = [];
$result = $conn->query("SELECT id, name, email, domain, software_classification, hardware_classification FROM subadmins ORDER BY id DESC");
if ($result) {
    $subadmin_count = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $subadmin_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
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
                    <span>Add Subadmin</span>
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
            <h1 class="page-title">Add Subadmin</h1>
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
        <?php if($message): ?>
            <div class="alert alert-success alert-banner alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger alert-banner alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <!-- Subadmin Form -->
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <h3 class="mb-4">Add Subadmin</h3>
                            <form method="post" autocomplete="off">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Subadmin Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Add & Send Credentials</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Classification Change Requests -->
            <div class="row justify-content-center mt-5">
                <div class="col-md-10">
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <h4 class="mb-4">Pending Classification Change Requests</h4>
                            <?php if (count($pending_requests) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Subadmin Email</th>
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
                                                    <td><?php echo htmlspecialchars($req['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['current_software']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['current_hardware']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['requested_software_classification']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['requested_hardware_classification']); ?></td>
                                                    <td>
                                                        <form method="post" style="display:inline-block;">
                                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                            <button type="submit" name="request_action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                                        </form>
                                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $req['id']; ?>">Reject</button>
                                                        <!-- Reject Modal -->
                                                        <div class="modal fade" id="rejectModal<?php echo $req['id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?php echo $req['id']; ?>" aria-hidden="true">
                                                          <div class="modal-dialog">
                                                            <div class="modal-content">
                                                              <div class="modal-header">
                                                                <h5 class="modal-title" id="rejectModalLabel<?php echo $req['id']; ?>">Reject Classification Change</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                              </div>
                                                              <form method="post">
                                                                <div class="modal-body">
                                                                  <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                                  <div class="mb-3">
                                                                    <label for="admin_comment<?php echo $req['id']; ?>" class="form-label">Reason for rejection</label>
                                                                    <textarea class="form-control" name="admin_comment" id="admin_comment<?php echo $req['id']; ?>" required></textarea>
                                                                  </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                  <button type="submit" name="request_action" value="reject" class="btn btn-danger">Reject</button>
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
                                <div class="alert alert-info mb-0">No pending requests.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Subadmin Overview Section -->
            <div class="row justify-content-center mt-5">
                <div class="col-md-12">
                    <div class="glass-card card p-4 w-100">
                        <h4 class="mb-3"><i class="bi bi-people me-2"></i>Subadmin Overview</h4>
                        <div class="d-flex align-items-center mb-3">
                            <span class="fs-5 fw-bold me-2">Total Subadmins:</span>
                            <span class="badge bg-primary fs-6"> <?php echo $subadmin_count; ?> </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Software Classification</th>
                                        <th>Hardware Classification</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subadmin_list as $sub): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sub['name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($sub['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($sub['domain'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($sub['software_classification'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($sub['hardware_classification'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-content').classList.toggle('pushed');
        });
    </script>
</body>
</html> 