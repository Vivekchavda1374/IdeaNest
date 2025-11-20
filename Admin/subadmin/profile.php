<?php
// Configure session settings
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_path', '/');
session_start();

if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}

include_once "../../Login/Login/db.php";
include_once "sidebar_subadmin.php"; // Include the layout file

$subadmin_id = $_SESSION['subadmin_id'];

// Fetch current profile
$stmt = $conn->prepare("SELECT email, first_name, last_name, phone, department, position, specialization, domains, experience_years, bio FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($email, $first_name, $last_name, $phone, $department, $position, $specialization, $domains, $experience_years, $bio);
$stmt->fetch();
$stmt->close();
$name = $first_name . ' ' . $last_name;
$domain = $department;

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
    $new_first_name = trim($_POST['first_name']);
    $new_last_name = trim($_POST['last_name']);
    $new_phone = trim($_POST['phone'] ?? '');
    $new_department = trim($_POST['department']);
    $new_position = trim($_POST['position'] ?? '');
    $new_specialization = trim($_POST['specialization'] ?? '');
    $new_domains = isset($_POST['domains']) ? implode(',', $_POST['domains']) : '';
    $new_experience_years = intval($_POST['experience_years'] ?? 0);
    $new_bio = trim($_POST['bio'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_first_name === '' || $new_last_name === '' || $new_department === '') {
        $error = "First name, last name, and department are required fields.";
    } elseif ($new_password !== '' || $confirm_password !== '') {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match. Please try again.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE subadmins SET first_name=?, last_name=?, phone=?, department=?, position=?, specialization=?, domains=?, experience_years=?, bio=?, password=? WHERE id=?");
            $stmt->bind_param("sssssssissi", $new_first_name, $new_last_name, $new_phone, $new_department, $new_position, $new_specialization, $new_domains, $new_experience_years, $new_bio, $hashed_password, $subadmin_id);
            if ($stmt->execute()) {
                $success = "Profile and password updated successfully.";
                $first_name = $new_first_name;
                $last_name = $new_last_name;
                $phone = $new_phone;
                $department = $new_department;
                $position = $new_position;
                $specialization = $new_specialization;
                $experience_years = $new_experience_years;
                $bio = $new_bio;
                $name = $first_name . ' ' . $last_name;
                $domain = $department;
                $selected_domains = $new_domains ? explode(',', $new_domains) : [];
            } else {
                $error = "Failed to update profile and password. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("UPDATE subadmins SET first_name=?, last_name=?, phone=?, department=?, position=?, specialization=?, domains=?, experience_years=?, bio=? WHERE id=?");
        $stmt->bind_param("sssssssisi", $new_first_name, $new_last_name, $new_phone, $new_department, $new_position, $new_specialization, $new_domains, $new_experience_years, $new_bio, $subadmin_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $first_name = $new_first_name;
            $last_name = $new_last_name;
            $phone = $new_phone;
            $department = $new_department;
            $position = $new_position;
            $specialization = $new_specialization;
            $experience_years = $new_experience_years;
            $bio = $new_bio;
            $name = $first_name . ' ' . $last_name;
            $domain = $department;
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

    <link rel="stylesheet" href="../../assets/css/profile_subadmin.css">

    <div class="row g-4">
        <!-- Profile Information Card -->
        <div class="col-lg-12">
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
                                <i class="bi bi-person me-2"></i>First Name
                            </label>
                            <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-person me-2"></i>Last Name
                            </label>
                            <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-telephone me-2"></i>Phone Number
                            </label>
                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-building me-2"></i>Department
                            </label>
                            <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($department ?? ''); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-briefcase me-2"></i>Position
                            </label>
                            <select class="form-control" name="position">
                                <option value="">Select Position</option>
                                <option value="Faculty" <?php echo ($position ?? '') === 'Faculty' ? 'selected' : ''; ?>>Faculty</option>
                                <option value="Mentor" <?php echo ($position ?? '') === 'Mentor' ? 'selected' : ''; ?>>Mentor</option>
                                <option value="Working Professional" <?php echo ($position ?? '') === 'Working Professional' ? 'selected' : ''; ?>>Working Professional</option>
                                <option value="HOD" <?php echo ($position ?? '') === 'HOD' ? 'selected' : ''; ?>>HOD</option>
                                <option value="Student" <?php echo ($position ?? '') === 'Student' ? 'selected' : ''; ?>>Student</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-award me-2"></i>Experience (Years)
                            </label>
                            <input type="number" class="form-control" name="experience_years" value="<?php echo htmlspecialchars($experience_years ?? 0); ?>" min="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                <i class="bi bi-star me-2"></i>Specialization
                            </label>
                            <input type="text" class="form-control" name="specialization" value="<?php echo htmlspecialchars($specialization ?? ''); ?>" placeholder="e.g., Web Development, AI/ML">
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                <i class="bi bi-file-text me-2"></i>Bio
                            </label>
                            <textarea class="form-control" name="bio" rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($bio ?? ''); ?></textarea>
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


    </div>



<?php
// Get the content
$content = ob_get_clean();

// Render the layout with content
renderLayout('Profile', $content, 'profile');
?>