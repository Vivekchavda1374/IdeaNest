<?php
session_start();
include 'layout.php';
include '../Login/Login/db.php';


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
        $phone_no = trim($_POST['phone_no']);
        $department = trim($_POST['department']);
        $passout_year = trim($_POST['passout_year']);

        // Validate required fields
        if (empty($name) || empty($email)) {
            $alert = "Name and email are required fields.";
            $alertType = "danger";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alert = "Please enter a valid email address.";
            $alertType = "danger";
        } else {
            // Update user data
            $updateSql = "UPDATE register SET name = ?, email = ?, enrollment_number = ?, gr_number = ?, about = ?, phone_no = ?, department = ?, passout_year = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssssssssi", $name, $email, $enrollment_number, $gr_number, $about, $phone_no, $department, $passout_year, $user_id);

            if ($updateStmt->execute()) {
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

<div class="container-fluid py-4">
    <?php if (!empty($alert)): ?>
        <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
            <?php echo $alert; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-4 mb-4">
            <div class="card shadow border-0">
                <div class="card-body text-center">
                    <div class="user-avatar rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                        <span class="text-white"><?php echo htmlspecialchars($user_initial); ?></span>
                    </div>
                    <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($user_name); ?></h5>
                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($userData['email']); ?></p>
                   
                </div>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action active" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab"><i class="fas fa-user me-2"></i>Profile</a>
                    <a class="list-group-item list-group-item-action" id="security-tab" data-bs-toggle="tab" href="#security" role="tab"><i class="fas fa-lock me-2"></i>Security</a>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <div class="tab-content">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <h4 class="fw-bold mb-4">Profile Information</h4>
                            <form method="POST" action="">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="enrollment_number" class="form-label">Enrollment Number</label>
                                        <input type="text" class="form-control" id="enrollment_number" name="enrollment_number" value="<?php echo htmlspecialchars($userData['enrollment_number']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gr_number" class="form-label">GR Number</label>
                                        <input type="text" class="form-control" id="gr_number" name="gr_number" value="<?php echo htmlspecialchars($userData['gr_number']); ?>">
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="phone_no" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="phone_no" name="phone_no" value="<?php echo htmlspecialchars($userData['phone_no']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($userData['department']); ?>">
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="passout_year" class="form-label">Passout Year</label>
                                        <input type="number" class="form-control" id="passout_year" name="passout_year" min="1900" max="2099" value="<?php echo htmlspecialchars($userData['passout_year']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="about" class="form-label">About Me</label>
                                        <input type="text" class="form-control" id="about" name="about" maxlength="500" value="<?php echo htmlspecialchars($userData['about']); ?>">
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary px-4">Save Changes</button>
                                </div>
                            </form>
                        </div>
                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <h4 class="fw-bold mb-4">Security Settings</h4>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                <div class="form-text mb-3">
                                    <ul class="mb-0 ps-3">
                                        <li>Minimum 8 characters</li>
                                        <li>At least one uppercase letter</li>
                                        <li>At least one number</li>
                                        <li>At least one special character</li>
                                    </ul>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="change_password" class="btn btn-primary px-4">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab switching script (Bootstrap 5) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var triggerTabList = [].slice.call(document.querySelectorAll('.list-group-item'));
        triggerTabList.forEach(function(triggerEl) {
            triggerEl.addEventListener('click', function (e) {
                e.preventDefault();
                triggerTabList.forEach(function(el) { el.classList.remove('active'); });
                this.classList.add('active');
                var tabPanes = document.querySelectorAll('.tab-pane');
                tabPanes.forEach(function(pane) { pane.classList.remove('show', 'active'); });
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.classList.add('show', 'active');
                }
            });
        });
    });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>