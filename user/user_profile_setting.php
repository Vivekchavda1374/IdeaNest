<?php
include '../Login/Login/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "User";
$user_initial = !empty($user_name) ? substr($user_name, 0, 1) : "U";

// Initialize message variables
$alert = "";
$alertType = "";

// Fetch current user data
$sql = "SELECT * FROM register WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Get form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $enrollment_number = trim($_POST['enrollment_number']);
        $gr_number = trim($_POST['gr_number']);
        $about = trim($_POST['about']);

        // Validate required fields
        if (empty($name) || empty($email)) {
            $alert = "Name and email are required fields.";
            $alertType = "danger";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alert = "Please enter a valid email address.";
            $alertType = "danger";
        } else {
            // Update user data
            $updateSql = "UPDATE register SET name = ?, email = ?, enrollment_number = ?, gr_number = ?, about = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("sssssi", $name, $email, $enrollment_number, $gr_number, $about, $user_id);

            if ($updateStmt->execute()) {
                // Update session data
                $_SESSION['user_name'] = $name;
                $_SESSION['email'] = $email;

                $alert = "Profile updated successfully!";
                $alertType = "success";

                // Refresh user data
                $stmt->execute();
                $result = $stmt->get_result();
                $userData = $result->fetch_assoc();
            } else {
                $alert = "Error updating profile: " . $conn->error;
                $alertType = "danger";
            }
            $updateStmt->close();
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        if (password_verify($current_password, $userData['password'])) {
            // Check if new passwords match
            if ($new_password === $confirm_password) {
                // Check password strength
                if (strlen($new_password) < 8) {
                    $alert = "Password must be at least 8 characters long.";
                    $alertType = "danger";
                } else {
                    // Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update password
                    $passwordSql = "UPDATE register SET password = ? WHERE id = ?";
                    $passwordStmt = $conn->prepare($passwordSql);
                    $passwordStmt->bind_param("si", $hashed_password, $user_id);

                    if ($passwordStmt->execute()) {
                        $alert = "Password changed successfully!";
                        $alertType = "success";
                    } else {
                        $alert = "Error changing password: " . $conn->error;
                        $alertType = "danger";
                    }
                    $passwordStmt->close();
                }
            } else {
                $alert = "New password and confirm password do not match.";
                $alertType = "danger";
            }
        } else {
            $alert = "Current password is incorrect.";
            $alertType = "danger";
        }
    }
}

// Close statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .settings-card {
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s;
        }
        .settings-card:hover {
            transform: translateY(-5px);
        }
        .nav-pills .nav-link.active {
            background-color: #4361ee;
        }
        .nav-pills .nav-link {
            color: #333;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 8px;
        }
        .btn-primary {
            background-color: #4361ee;
            border-color: #4361ee;
        }
        .btn-outline-primary {
            color: #4361ee;
            border-color: #4361ee;
        }
        .btn-outline-primary:hover {
            background-color: #4361ee;
            border-color: #4361ee;
        }
        .avatar-upload {
            position: relative;
            width: 120px;
            margin: 0 auto 20px;
        }
        .avatar-upload img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #4361ee;
        }
        .avatar-upload .avatar-edit {
            position: absolute;
            right: 5px;
            bottom: 5px;
            z-index: 1;
            width: 34px;
            height: 34px;
        }
        .avatar-upload .avatar-edit input {
            display: none;
        }
        .avatar-upload .avatar-edit label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #4361ee;
            border: 1px solid transparent;
            color: white;
            cursor: pointer;
        }
    </style>
</head>

<body>
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-cubes"></i>
        <div class="logo">Dashboard</div>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="projects_view.php">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
        </li>
        <li>
            <a href="Blog/list-project.php">
                <i class="fas fa-file-alt"></i>
                <span>Idea Posts</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-user-graduate"></i>
                <span>Mentorship</span>
            </a>
        </li>
        <li>
            <a href="../bksony/bookmark/bookmark.php">
                <i class="fas fa-bookmark"></i>
                <span>Bookmarks</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </a>
        </li>
        <li>
            <a href="account_settings.php" class="active">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container-fluid">
            <!-- Sidebar Toggle Button -->
            <button id="sidebarToggle" class="btn btn-light d-lg-none me-3">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Page Title -->
            <h5 class="mb-0 text-primary fw-bold">Account Settings</h5>

            <!-- Right-side menu items -->
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown ms-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                            <span class="text-white fw-medium"><?php echo htmlspecialchars($user_initial); ?></span>
                        </div>
                        <span class="d-none d-lg-inline"><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                        <li>
                            <div class="dropdown-item text-center">
                                <div class="user-avatar rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 60px; height: 60px;">
                                    <span class="text-white fw-bold"><?php echo htmlspecialchars($user_initial); ?></span>
                                </div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($user_name); ?></h6>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($userData['email']); ?></p>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item active" href="account_settings.php"><i class="fas fa-cog me-2"></i> Account Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../Login/Login/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <!-- Alert for messages -->
        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                <?php echo $alert; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Settings Navigation -->
            <div class="col-lg-3 mb-4">
                <div class="card settings-card">
                    <div class="card-body p-0">
                        <div class="text-center p-4 bg-light">
                            <div class="avatar-upload">
                                <div class="user-avatar rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                    <span class="text-white" style="font-size: 48px;"><?php echo htmlspecialchars($user_initial); ?></span>
                                </div>
                                <div class="avatar-edit">
                                    <input type='file' id="imageUpload" accept=".png, .jpg, .jpeg" />
                                    <label for="imageUpload"><i class="fas fa-pencil-alt"></i></label>
                                </div>
                            </div>
                            <h5 class="mt-2 mb-1"><?php echo htmlspecialchars($user_name); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars($userData['email']); ?></p>
                        </div>

                        <div class="p-3">
                            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                                    <i class="fas fa-user me-2"></i> Profile Information
                                </button>
                                <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                                    <i class="fas fa-lock me-2"></i> Security
                                </button>
                              </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="col-lg-9">
                <div class="card settings-card">
                    <div class="card-body p-4">
                        <div class="tab-content" id="v-pills-tabContent">
                            <!-- Profile Information Tab -->
                            <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                <h4 class="card-title mb-4">Profile Information</h4>
                                <form method="POST" action="">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="enrollment_number" class="form-label">Enrollment Number</label>
                                            <input type="text" class="form-control" id="enrollment_number" name="enrollment_number" value="<?php echo htmlspecialchars($userData['enrollment_number']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="gr_number" class="form-label">GR Number</label>
                                            <input type="text" class="form-control" id="gr_number" name="gr_number" value="<?php echo htmlspecialchars($userData['gr_number']); ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="about" class="form-label">About Me</label>
                                        <textarea class="form-control" id="about" name="about" rows="4"><?php echo htmlspecialchars($userData['about']); ?></textarea>
                                        <div class="form-text">Tell us about yourself, your skills, interests, and goals.</div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" name="update_profile" class="btn btn-primary px-4">Save Changes</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                                <h4 class="card-title mb-4">Security Settings</h4>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-text">
                                            <p class="mb-2">Password requirements:</p>
                                            <ul class="ps-3 mb-0">
                                                <li>Minimum 8 characters</li>
                                                <li>Include at least one uppercase letter</li>
                                                <li>Include at least one number</li>
                                                <li>Include at least one special character</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" name="change_password" class="btn btn-primary px-4">Update Password</button>
                                    </div>
                                </form>

                                <hr class="my-4">

                                <h5 class="mb-3">Account Security</h5>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6 class="mb-1">Two-Factor Authentication</h6>
                                            <p class="text-muted mb-0 small">Add an extra layer of security to your account</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="twoFactorSwitch">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6 class="mb-1">Login Activity</h6>
                                            <p class="text-muted mb-0 small">View your recent login sessions</p>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm">View Activity</button>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('overlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                overlay.classList.toggle('active');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                overlay.classList.remove('active');
            });
        }

        // Password strength indicator
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        if (newPassword && confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Passwords do not match");
                } else {
                    confirmPassword.setCustomValidity("");
                }
            });
        }

        // Auto dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>
</body>
</html>