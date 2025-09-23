<?php
session_start();
if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}

include_once "../../Login/Login/db.php";
include_once "sidebar_subadmin.php"; // Include the layout file

$subadmin_id = $_SESSION['subadmin_id'];

// Fetch current profile
$stmt = $conn->prepare("SELECT email, name, domain, domains FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($email, $name, $domain, $domains);
$stmt->fetch();
$stmt->close();

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

// Classification options
$software_options = [
        'Web',
        'Mobile',
        'Artificial Intelligence & Machine Learning',
        'Desktop',
        'System Software',
        'Embedded/IoT Software',
        'Cybersecurity',
        'Game Development',
        'Data Science & Analytics',
        'Cloud-Based Applications'
];

$hardware_options = [
        'Embedded Systems',
        'Internet of Things (IoT)',
        'Robotics',
        'Automation',
        'Sensor-Based Systems',
        'Communication Systems',
        'Power Electronics',
        'Wearable Technology',
        'Mechatronics',
        'Renewable Energy Systems'
];

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
    $stmt->bind_result($domains);
    $stmt->fetch();
    $stmt->close();
}

// Handle new classification change request
if (isset($_POST['request_classification_change'])) {
    $new_domains = isset($_POST['requested_domains']) ? implode(',', $_POST['requested_domains']) : '';

    if ($new_domains === '') {
        $error = "Please select at least one domain for your request.";
    } elseif (!$can_request) {
        $error = "You already have a pending classification change request. Please wait for the admin to approve or reject it.";
    } else {
        $stmt = $conn->prepare("INSERT INTO subadmin_classification_requests (subadmin_id, requested_domains) VALUES (?, ?)");
        $stmt->bind_param("is", $subadmin_id, $new_domains);
        if ($stmt->execute()) {
            $success = "Domain change request sent to admin successfully.";
        } else {
            $error = "Failed to send request. Please try again.";
        }
        $stmt->close();
        header("Location: profile.php");
        exit();
    }
}

// Handle update for name, domain, domains, and password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['request_classification_change'])) {
    $new_name = trim($_POST['name']);
    $new_domain = trim($_POST['domain']);
    $new_domains = isset($_POST['domains']) ? implode(',', $_POST['domains']) : '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_name === '' || $new_domain === '') {
        $error = "Name and department are required fields.";
    } elseif ($new_password !== '' || $confirm_password !== '') {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match. Please try again.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE subadmins SET name=?, domain=?, domains=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $new_name, $new_domain, $new_domains, $hashed_password, $subadmin_id);
            if ($stmt->execute()) {
                $success = "Profile and password updated successfully.";
                $name = $new_name;
                $domain = $new_domain;
                $selected_domains = $new_domains ? explode(',', $new_domains) : [];
            } else {
                $error = "Failed to update profile and password. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("UPDATE subadmins SET name=?, domain=?, domains=? WHERE id=?");
        $stmt->bind_param("sssi", $new_name, $new_domain, $new_domains, $subadmin_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $name = $new_name;
            $domain = $new_domain;
            $selected_domains = $new_domains ? explode(',', $new_domains) : [];
        } else {
            $error = "Failed to update profile. Please try again.";
        }
        $stmt->close();
    }
}

// Start output buffering to capture content
ob_start();
?>

    <div class="row g-4">
        <!-- Profile Information Card -->
        <div class="col-lg-8">
            <div class="glass-card">
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
                        <div class="alert alert-success d-flex align-items-center mb-4">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error) : ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off" class="row g-3" data-loading-message="Updating profile...">
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-envelope me-2"></i>Email Address
                            </label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled>
                            <div class="form-text">Email address cannot be changed</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-person me-2"></i>Full Name
                            </label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-building me-2"></i>Department
                            </label>
                            <input type="text" class="form-control" name="domain" value="<?php echo htmlspecialchars($domain); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-tags me-2"></i>Expertise Domains
                            </label>
                            <div class="border rounded p-2" style="max-height: 120px; overflow-y: auto;">
                                <?php foreach ($available_domains as $dom): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="domains[]" value="<?php echo $dom; ?>" 
                                               <?php echo in_array($dom, $selected_domains) ? 'checked' : ''; ?>>
                                        <label class="form-check-label small"><?php echo $dom; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">Select multiple domains you can review projects for</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                <i class="bi bi-tags me-2"></i>Current Assigned Domains
                            </label>
                            <div class="p-3 bg-light rounded">
                                <?php if (!empty($domains)): ?>
                                    <?php foreach (explode(',', $domains) as $domain): ?>
                                        <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars(trim($domain)); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">No domains assigned</span>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">Domain assignments can only be changed through admin approval</div>
                        </div>

                        <div class="col-12">
                            <hr class="my-4">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-shield-lock me-2"></i>Change Password
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" minlength="6" autocomplete="new-password">
                            <div class="form-text">Leave blank to keep current password</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" minlength="6" autocomplete="new-password">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Classification Request Card -->
        <div class="col-lg-4">
            <div class="glass-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-tags-fill me-2"></i>Domain Management
                    </h5>

                    <!-- Current Request Status -->
                    <?php if ($has_request) : ?>
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3">Current Request Status</h6>

                            <?php if ($req_status === 'pending') : ?>
                                <div class="alert alert-warning">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-clock-fill me-2"></i>
                                        <strong>Status: Pending</strong>
                                    </div>
                                    <div class="small">
                                        <div><strong>Requested Domains:</strong> <?php echo htmlspecialchars($req_domains ?: 'No domains'); ?></div>
                                    </div>
                                </div>
                            <?php elseif ($req_status === 'approved') : ?>
                                <div class="alert alert-success">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <strong>Status: Approved</strong>
                                    </div>
                                    <div class="small">Your classification has been updated successfully.</div>
                                </div>
                            <?php elseif ($req_status === 'rejected') : ?>
                                <div class="alert alert-danger">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-x-circle-fill me-2"></i>
                                        <strong>Status: Rejected</strong>
                                    </div>
                                    <div class="small mb-2">
                                        <div><strong>Requested Domains:</strong> <?php echo htmlspecialchars($req_domains ?: 'No domains'); ?></div>
                                    </div>
                                    <?php if ($admin_comment) : ?>
                                        <div class="small">
                                            <strong>Admin Comment:</strong><br>
                                            <?php echo htmlspecialchars($admin_comment); ?>
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
                            <?php if (!$can_request) {
                                echo 'disabled';
                            } ?>>
                        <i class="bi bi-send-fill me-2"></i>
                        Request Domain Change
                    </button>

                    <?php if (!$can_request) : ?>
                        <div class="small text-muted text-center mt-2">
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
                                <span>Last Login:</span>
                                <span><?php echo date('M d, Y'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Member Since:</span>
                                <span>2024</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Classification Request Modal -->
    <div class="modal fade" id="classificationRequestModal" tabindex="-1" aria-labelledby="classificationRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" data-loading-message="Sending domain change request...">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="classificationRequestModalLabel">
                            <i class="bi bi-tags-fill me-2"></i>Request Domain Change
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Note:</strong> Your request will be reviewed by an administrator. Select the domains you want to be assigned to.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-tags me-2"></i>Request New Domains
                            </label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($available_domains as $dom): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="requested_domains[]" value="<?php echo htmlspecialchars($dom); ?>">
                                        <label class="form-check-label"><?php echo htmlspecialchars($dom); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6 class="fw-semibold mb-2">Current Domains:</h6>
                            <div class="bg-light p-3 rounded">
                                <div class="small">
                                    <?php echo htmlspecialchars($domains ?: 'No domains assigned'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i>Cancel
                        </button>
                        <button type="submit" name="request_classification_change" class="btn btn-primary">
                            <i class="bi bi-send-fill me-2"></i>Send Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
// Get the content
$content = ob_get_clean();

// Render the layout with content
renderLayout('Profile', $content, 'profile');
?>