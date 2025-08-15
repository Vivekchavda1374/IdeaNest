<?php
session_start();
include 'layout.php';
include '../Login/Login/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM register WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone_no = trim($_POST['phone_no']);
    $about = trim($_POST['about']);
    $department = trim($_POST['department']);
    $passout_year = trim($_POST['passout_year']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $update_fields = [];
    $types = "";
    $params = [];
    $errors = [];

    // Basic validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    if (!empty($phone_no) && !preg_match('/^[0-9]{10}$/', $phone_no)) {
        $errors[] = "Phone number must be 10 digits";
    }

    // Check if email is already taken by another user
    if (!empty($email) && $email !== $user['email']) {
        $email_check = "SELECT id FROM register WHERE email = ? AND id != ?";
        $email_stmt = $conn->prepare($email_check);
        $email_stmt->bind_param("si", $email, $user_id);
        $email_stmt->execute();
        if ($email_stmt->get_result()->num_rows > 0) {
            $errors[] = "Email is already registered";
        }
    }

    if (empty($errors)) {
        // Basic info updates
        $update_fields[] = "name = ?";
        $types .= "s";
        $params[] = $name;

        $update_fields[] = "email = ?";
        $types .= "s";
        $params[] = $email;

        if (!empty($phone_no)) {
            $update_fields[] = "phone_no = ?";
            $types .= "s";
            $params[] = $phone_no;
        }

        if (!empty($about)) {
            $update_fields[] = "about = ?";
            $types .= "s";
            $params[] = $about;
        }

        if (!empty($department)) {
            $update_fields[] = "department = ?";
            $types .= "s";
            $params[] = $department;
        }

        if (!empty($passout_year)) {
            $update_fields[] = "passout_year = ?";
            $types .= "s";
            $params[] = $passout_year;
        }

        // Password update
        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    if (password_verify($current_password, $user['password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_fields[] = "password = ?";
                        $types .= "s";
                        $params[] = $hashed_password;
                    } else {
                        $errors[] = "Current password is incorrect";
                    }
                } else {
                    $errors[] = "New password must be at least 6 characters long";
                }
            } else {
                $errors[] = "New passwords do not match";
            }
        }

        // Profile picture update
        if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['user_image']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($filetype, $allowed)) {
                $temp_name = $_FILES['user_image']['tmp_name'];
                $new_filename = uniqid('profile_') . '.' . $filetype;
                $upload_dir = 'forms/uploads/images/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($temp_name, $upload_path)) {
                    // Delete old image if exists
                    if (!empty($user['user_image']) && file_exists($upload_dir . $user['user_image'])) {
                        unlink($upload_dir . $user['user_image']);
                    }
                    $update_fields[] = "user_image = ?";
                    $types .= "s";
                    $params[] = $new_filename;
                } else {
                    $errors[] = "Failed to upload image";
                }
            } else {
                $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed";
            }
        }

        // Update database
        if (!empty($update_fields) && empty($errors)) {
            $params[] = $user_id;
            $types .= "i";

            $sql = "UPDATE register SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $success = "Profile updated successfully";

                // Refresh user data
                $query = "SELECT * FROM register WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                $_SESSION['user_name'] = $user['name'];
            } else {
                $errors[] = "Error updating profile";
            }
        }
    }
}

// Get user statistics
$project_count_query = "SELECT COUNT(*) as count FROM projects WHERE user_id = ?";
$project_stmt = $conn->prepare($project_count_query);
$project_stmt->bind_param("i", $user_id);
$project_stmt->execute();
$project_count = $project_stmt->get_result()->fetch_assoc()['count'];

$idea_count_query = "SELECT COUNT(*) as count FROM blog WHERE er_number = ?";
$idea_stmt = $conn->prepare($idea_count_query);
$idea_stmt->bind_param("s", $user['enrollment_number']);
$idea_stmt->execute();
$idea_count = $idea_stmt->get_result()->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - IdeaNest</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/user_profile.css">
</head>
<body>
<div class="main-content">
    <div class="settings-container">
        <div class="settings-header">
            <h1><i class="fas fa-user-cog"></i> Profile Settings</h1>
            <p>Manage your account information and preferences</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <ul style="margin: 0; padding-left: 1rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-picture-wrapper">
                    <img src="<?php echo !empty($user['user_image']) ? 'forms/uploads/images/' . htmlspecialchars($user['user_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=6366f1&color=fff&size=240'; ?>"
                         alt="Profile Picture"
                         class="profile-picture"
                         id="profilePreview">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?> - Class of <?php echo htmlspecialchars($user['passout_year'] ?? 'N/A'); ?></p>
                    <p><i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($user['enrollment_number']); ?></p>
                </div>
            </div>

            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $project_count; ?></div>
                    <div class="stat-label">Projects</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $idea_count; ?></div>
                    <div class="stat-label">Ideas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo !empty($user['passout_year']) ? date('Y') - $user['passout_year'] + 4 : 'N/A'; ?></div>
                    <div class="stat-label">Year</div>
                </div>
            </div>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="settings-form">
            <!-- Profile Picture Upload -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-camera"></i>
                    Profile Picture
                </h3>
                <div class="upload-section" onclick="document.getElementById('user_image').click()">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">
                        Click to upload a new profile picture
                    </div>
                    <label class="upload-btn">
                        <i class="fas fa-upload"></i> Choose Image
                        <input type="file" name="user_image" id="user_image" accept="image/*">
                    </label>
                    <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--gray-500);">
                        Maximum file size: 5MB. Supported formats: JPG, PNG, GIF
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-user"></i>
                    Personal Information
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name *</label>
                        <div class="input-group">
                            <i class="fas fa-user input-group-icon"></i>
                            <input type="text" id="name" name="name" class="form-control"
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-group-icon"></i>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone_no" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <i class="fas fa-phone input-group-icon"></i>
                            <input type="tel" id="phone_no" name="phone_no" class="form-control"
                                   value="<?php echo htmlspecialchars($user['phone_no'] ?? ''); ?>"
                                   placeholder="10-digit mobile number">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="department" class="form-label">Department</label>
                        <div class="input-group">
                            <i class="fas fa-building input-group-icon"></i>
                            <select id="department" name="department" class="form-control">
                                <option value="">Select Department</option>
                                <option value="ict" <?php echo ($user['department'] ?? '') === 'ict' ? 'selected' : ''; ?>>ICT</option>
                                <option value="cse" <?php echo ($user['department'] ?? '') === 'cse' ? 'selected' : ''; ?>>CSE</option>
                                <option value="ece" <?php echo ($user['department'] ?? '') === 'ece' ? 'selected' : ''; ?>>ECE</option>
                                <option value="mechanical" <?php echo ($user['department'] ?? '') === 'mechanical' ? 'selected' : ''; ?>>Mechanical</option>
                                <option value="civil" <?php echo ($user['department'] ?? '') === 'civil' ? 'selected' : ''; ?>>Civil</option>
                                <option value="electrical" <?php echo ($user['department'] ?? '') === 'electrical' ? 'selected' : ''; ?>>Electrical</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="passout_year" class="form-label">Passout Year</label>
                        <div class="input-group">
                            <i class="fas fa-graduation-cap input-group-icon"></i>
                            <select id="passout_year" name="passout_year" class="form-control">
                                <option value="">Select Year</option>
                                <?php
                                $current_year = date('Y');
                                for ($year = $current_year; $year <= $current_year + 6; $year++) {
                                    $selected = ($user['passout_year'] ?? '') == $year ? 'selected' : '';
                                    echo "<option value='$year' $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="about" class="form-label">About Me</label>
                        <textarea id="about" name="about" class="form-control" rows="4"
                                  placeholder="Tell us about yourself, your interests, and goals..."><?php echo htmlspecialchars($user['about'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Academic Information (Read Only) -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-id-card"></i>
                    Academic Information (Read Only)
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="enrollment_number" class="form-label">Enrollment Number</label>
                        <div class="input-group">
                            <i class="fas fa-id-badge input-group-icon"></i>
                            <input type="text" id="enrollment_number" class="form-control"
                                   value="<?php echo htmlspecialchars($user['enrollment_number']); ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="gr_number" class="form-label">GR Number</label>
                        <div class="input-group">
                            <i class="fas fa-hashtag input-group-icon"></i>
                            <input type="text" id="gr_number" class="form-control"
                                   value="<?php echo htmlspecialchars($user['gr_number']); ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-lock"></i>
                    Change Password
                </h3>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Leave password fields empty if you don't want to change your password
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <div class="input-group">
                            <i class="fas fa-key input-group-icon"></i>
                            <input type="password" id="current_password" name="current_password" class="form-control">
                            <button type="button" class="toggle-password" data-target="current_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-group-icon"></i>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                            <button type="button" class="toggle-password" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength" style="display: none;">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <span id="strengthText"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <i class="fas fa-check-circle input-group-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                            <button type="button" class="toggle-password" data-target="confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.location.reload()">
                    <i class="fas fa-undo"></i> Reset Changes
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
<script src="../assets/js/user_profile.js"></script>
</body>
</html>