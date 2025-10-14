<?php
session_start();
if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}

// Use local XAMPP database connection
$conn = new mysqli("localhost", "root", "", "ictmu6ya_ideanest", 3306, "/opt/lampp/var/mysql/mysql.sock");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
include_once "sidebar_subadmin.php";

$subadmin_id = $_SESSION['subadmin_id'];

// First, check if domains column exists, if not add it
$check_column = $conn->query("SHOW COLUMNS FROM subadmins LIKE 'domains'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE subadmins ADD COLUMN domains TEXT NULL AFTER domain");
}

// Fetch current profile
$stmt = $conn->prepare("SELECT email, name, domain, domains FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

// Set default values if empty
$email = $profile['email'] ?? '';
$name = $profile['name'] ?? '';
$department = $profile['domain'] ?? '';
$domains = $profile['domains'] ?? '';

$available_domains = [
    'Embedded Systems', 'IoT Projects', 'Robotics', 'Automation',
    'Sensor-Based Projects', 'Communication Systems', 'Power Electronics',
    'Wearable Technology', 'Mechatronics', 'Renewable Energy',
    'Web Application', 'Mobile Application', 'AI & Machine Learning',
    'Desktop Application', 'System Software', 'Embedded Systems / IoT',
    'Cybersecurity', 'Game Development', 'Data Science & Analytics'
];
$selected_domains = $domains ? explode(',', $domains) : [];

$success = $error = '';

// Fetch classification change request
$request_stmt = $conn->prepare("SELECT id, status, requested_domains, admin_comment FROM subadmin_classification_requests WHERE subadmin_id = ? ORDER BY id DESC LIMIT 1");
$request_stmt->bind_param("i", $subadmin_id);
$request_stmt->execute();
$request_stmt->bind_result($req_id, $req_status, $req_domains, $admin_comment);
$has_request = $request_stmt->fetch();
$request_stmt->close();

// Check if there is a pending request
$pending_stmt = $conn->prepare("SELECT COUNT(*) FROM subadmin_classification_requests WHERE subadmin_id = ? AND status = 'pending'");
$pending_stmt->bind_param("i", $subadmin_id);
$pending_stmt->execute();
$pending_stmt->bind_result($pending_count);
$pending_stmt->fetch();
$pending_stmt->close();
$can_request = ($pending_count == 0);

// If the latest request is approved, update the displayed classification
if ($has_request && $req_status === 'approved') {
    $stmt = $conn->prepare("SELECT domains FROM subadmins WHERE id = ?");
    $stmt->bind_param("i", $subadmin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $domains = $row['domains'] ?? '';
    $selected_domains = $domains ? explode(',', $domains) : [];
}

// Handle classification change request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_classification_change'])) {
    if (!empty($_POST['requested_domains'])) {
        $requested_domains = implode(',', $_POST['requested_domains']);
        
        // Insert new classification request
        $insert_stmt = $conn->prepare("INSERT INTO subadmin_classification_requests (subadmin_id, requested_domains, status) VALUES (?, ?, 'pending')");
        $insert_stmt->bind_param("is", $subadmin_id, $requested_domains);
        
        if ($insert_stmt->execute()) {
            $success = "Domain change request submitted successfully! Please wait for admin approval.";
            $can_request = false;
        } else {
            $error = "Failed to submit request. Please try again.";
        }
        $insert_stmt->close();
    } else {
        $error = "Please select at least one domain.";
    }
}

// Handle update for name, department, and password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['request_classification_change']) && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']);
    $new_department = trim($_POST['department']);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_name === '' || $new_department === '') {
        $error = "Name and department are required fields.";
    } elseif ($new_password !== '' || $confirm_password !== '') {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match. Please try again.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE subadmins SET name=?, domain=?, password=? WHERE id=?");
            $stmt->bind_param("sssi", $new_name, $new_department, $hashed_password, $subadmin_id);
            if ($stmt->execute()) {
                $success = "Profile and password updated successfully.";
                $name = $new_name;
                $department = $new_department;
            } else {
                $error = "Failed to update profile and password. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("UPDATE subadmins SET name=?, domain=? WHERE id=?");
        $stmt->bind_param("ssi", $new_name, $new_department, $subadmin_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $name = $new_name;
            $department = $new_department;
        } else {
            $error = "Failed to update profile. Please try again.";
        }
        $stmt->close();
    }
}

ob_start();
?>

<div class="row g-4">
    <!-- Profile Information Card -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="me-3">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 60px; height: 60px;">
                            <i class="bi bi-person-fill text-white" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-1 fw-bold">Profile Information</h4>
                        <p class="text-muted mb-0">Update your personal details and account settings</p>
                    </div>
                </div>

                <?php if ($success) : ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error) : ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-envelope me-2"></i>Email Address
                        </label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled>
                        <div class="form-text">Email address cannot be changed</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person me-2"></i>Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($name); ?>" required placeholder="Enter your full name">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-building me-2"></i>Department <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($department); ?>" required placeholder="e.g., Computer Science, Electronics, etc.">
                        <div class="form-text">Specify your academic department or area of expertise</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-tags me-2"></i>Current Assigned Domains
                        </label>
                        <div class="p-3 bg-light rounded">
                            <?php if (!empty($domains)): ?>
                                <?php foreach (explode(',', $domains) as $domain): ?>
                                    <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars(trim($domain)); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">No domains assigned yet</span>
                            <?php endif; ?>
                        </div>
                        <div class="form-text">To change your domains, use the "Request Domain Change" button in the sidebar</div>
                    </div>

                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-shield-lock me-2"></i>Change Password (Optional)
                        </h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" class="form-control" name="new_password" minlength="6" autocomplete="new-password" placeholder="Leave blank to keep current">
                        <div class="form-text">Minimum 6 characters</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" minlength="6" autocomplete="new-password" placeholder="Confirm new password">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-4" name="update_profile">
                            <i class="bi bi-check-lg me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Classification Request Card -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">
                    <i class="bi bi-tags-fill me-2"></i>Domain Management
                </h5>

                <!-- Current Request Status -->
                <?php if ($has_request) : ?>
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3">Current Request Status</h6>

                        <?php if ($req_status === 'pending') : ?>
                            <div class="alert alert-warning mb-0">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-clock-fill me-2"></i>
                                    <strong>Status: Pending Review</strong>
                                </div>
                                <div class="small">
                                    <div class="mb-1"><strong>Requested Domains:</strong></div>
                                    <div class="ms-2">
                                        <?php 
                                        $req_domain_list = $req_domains ? explode(',', $req_domains) : [];
                                        foreach ($req_domain_list as $rd) {
                                            echo '<span class="badge bg-warning text-dark me-1 mb-1">' . htmlspecialchars(trim($rd)) . '</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($req_status === 'approved') : ?>
                            <div class="alert alert-success mb-0">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <strong>Status: Approved</strong>
                                </div>
                                <div class="small">Your domain assignment has been updated successfully!</div>
                            </div>
                        <?php elseif ($req_status === 'rejected') : ?>
                            <div class="alert alert-danger mb-0">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-x-circle-fill me-2"></i>
                                    <strong>Status: Rejected</strong>
                                </div>
                                <div class="small mb-2">
                                    <div class="mb-1"><strong>Requested Domains:</strong></div>
                                    <div class="ms-2">
                                        <?php 
                                        $req_domain_list = $req_domains ? explode(',', $req_domains) : [];
                                        foreach ($req_domain_list as $rd) {
                                            echo '<span class="badge bg-danger me-1 mb-1">' . htmlspecialchars(trim($rd)) . '</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php if ($admin_comment) : ?>
                                    <div class="small mt-2">
                                        <strong>Admin Comment:</strong><br>
                                        <em><?php echo nl2br(htmlspecialchars($admin_comment)); ?></em>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Request Button -->
                <button class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center"
                        data-bs-toggle="modal"
                        data-bs-target="#classificationRequestModal"
                        <?php echo !$can_request ? 'disabled' : ''; ?>>
                    <i class="bi bi-send-fill me-2"></i>
                    Request Domain Change
                </button>

                <?php if (!$can_request) : ?>
                    <div class="small text-muted text-center mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        You have a pending request. Please wait for admin response.
                    </div>
                <?php endif; ?>

                <!-- Quick Info -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-semibold mb-3">Quick Information</h6>
                    <div class="small text-muted">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Account Status:</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Assigned Domains:</span>
                            <span class="badge bg-info"><?php echo count($selected_domains); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Profile Status:</span>
                            <span class="badge bg-<?php echo ($name && $department) ? 'success' : 'warning'; ?>">
                                <?php echo ($name && $department) ? 'Complete' : 'Incomplete'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Classification Request Modal -->
<div class="modal fade" id="classificationRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-tags-fill me-2"></i>Request Domain Change
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Note:</strong> Select the domains you want to be assigned to review projects. Your request will be reviewed by an administrator.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-tags me-2"></i>Select Domains <span class="text-danger">*</span>
                        </label>
                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($available_domains as $dom): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="requested_domains[]" value="<?php echo htmlspecialchars($dom); ?>" id="domain_<?php echo md5($dom); ?>">
                                    <label class="form-check-label" for="domain_<?php echo md5($dom); ?>">
                                        <?php echo htmlspecialchars($dom); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">Select multiple domains based on your expertise</div>
                    </div>

                    <div class="mt-4">
                        <h6 class="fw-semibold mb-2">Current Assigned Domains:</h6>
                        <div class="bg-light p-3 rounded">
                            <?php if (!empty($domains)): ?>
                                <?php foreach (explode(',', $domains) as $d): ?>
                                    <span class="badge bg-secondary me-1 mb-1"><?php echo htmlspecialchars(trim($d)); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">No domains currently assigned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>Cancel
                    </button>
                    <button type="submit" name="request_classification_change" class="btn btn-primary">
                        <i class="bi bi-send-fill me-2"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('Profile', $content, 'profile');