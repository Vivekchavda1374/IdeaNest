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
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $github_username = trim($_POST['github_username'] ?? '');
    $enrollment_number = trim($_POST['enrollment_number'] ?? '');
    $gr_number = trim($_POST['gr_number'] ?? '');

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $update_fields = [];
    $types = "";
    $params = [];
    $errors = [];

    // Skip validation if only updating GitHub username
    $github_only = !empty($github_username) && empty($name) && empty($email);

    if (!$github_only) {
        // Basic validation
        if (empty($name)) {
            $errors[] = "Name is required";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        }
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
        // Always update basic info
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

        if (!empty($enrollment_number)) {
            $update_fields[] = "enrollment_number = ?";
            $types .= "s";
            $params[] = $enrollment_number;
        }

        if (!empty($gr_number)) {
            $update_fields[] = "gr_number = ?";
            $types .= "s";
            $params[] = $gr_number;
        }

        $update_fields[] = "email_notifications = ?";
        $types .= "i";
        $params[] = $email_notifications;

        // GitHub username update
        if (!empty($github_username)) {
            // Validate GitHub username format
            if (preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]){0,38}$/', $github_username)) {
                if (file_exists('github_service.php')) {
                    require_once 'github_service.php';
                    $github_profile = fetchGitHubProfile($github_username);
                    if ($github_profile) {
                        $update_fields[] = "github_username = ?";
                        $types .= "s";
                        $params[] = $github_username;

                        $update_fields[] = "github_profile_url = ?";
                        $types .= "s";
                        $params[] = $github_profile['html_url'];

                        $update_fields[] = "github_repos_count = ?";
                        $types .= "i";
                        $params[] = $github_profile['public_repos'];

                        $update_fields[] = "github_last_sync = NOW()";
                    } else {
                        // Save username anyway, validation can happen later
                        $update_fields[] = "github_username = ?";
                        $types .= "s";
                        $params[] = $github_username;
                    }
                } else {
                    // Save username if service file doesn't exist
                    $update_fields[] = "github_username = ?";
                    $types .= "s";
                    $params[] = $github_username;
                }
            }
        } elseif (isset($_POST['github_username']) && empty($github_username)) {
            // Clear GitHub data if username is explicitly emptied
            $update_fields[] = "github_username = NULL";
            $update_fields[] = "github_profile_url = NULL";
            $update_fields[] = "github_repos_count = 0";
            $update_fields[] = "github_last_sync = NULL";
        }

        // Password update (skip for Google users without password)
        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    // Check if user has a password (not Google-only user)
                    if (!empty($user['password']) && password_verify($current_password, $user['password'])) {
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



        // Update database - ensure we have fields to update
        if (!empty($update_fields) && empty($errors)) {
            error_log("Updating profile for user $user_id with fields: " . implode(', ', $update_fields));
            $params[] = $user_id;
            $types .= "i";

            $sql = "UPDATE register SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt->bind_param($types, ...$params)) {
                if ($stmt->execute()) {
                    // Mark profile as complete for Google users
                    if (isset($_GET['google_setup'])) {
                        $complete_stmt = $conn->prepare("UPDATE register SET profile_complete = 1 WHERE id = ?");
                        $complete_stmt->bind_param("i", $user_id);
                        $complete_stmt->execute();
                        unset($_SESSION['google_new_user']);
                        
                        // Refresh user data
                        $query = "SELECT * FROM register WHERE id = ?";
                        $refresh_stmt = $conn->prepare($query);
                        $refresh_stmt->bind_param("i", $user_id);
                        $refresh_stmt->execute();
                        $result = $refresh_stmt->get_result();
                        $user = $result->fetch_assoc();
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['er_number'] = $user['enrollment_number'];
                        
                        // Redirect immediately
                        header("Location: index.php");
                        exit();
                    }
                    
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
            } else {
                $errors[] = "Error preparing statement";
            }
        }
    }
}

// Get user statistics
$project_count_query = "SELECT COUNT(*) as count FROM admin_approved_projects WHERE user_id = ?";
$project_stmt = $conn->prepare($project_count_query);
$project_stmt->bind_param("i", $user_id);
$project_stmt->execute();
$project_count = $project_stmt->get_result()->fetch_assoc()['count'];

$idea_count_query = "SELECT COUNT(*) as count FROM blog WHERE user_id = ?";
$idea_stmt = $conn->prepare($idea_count_query);
$idea_stmt->bind_param("i", $user_id);
$idea_stmt->execute();
$idea_count = $idea_stmt->get_result()->fetch_assoc()['count'];

// Calculate current academic year
$current_year = date('Y');
$current_month = date('n');
$academic_year = ($current_month >= 6) ? $current_year + 1 : $current_year;
$year_in_college = $user['passout_year'] ? ($academic_year - $user['passout_year'] + 4) : 'N/A';


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/css/user_profile.css">
    <style>
        .notification-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
            flex-shrink: 0;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #6366f1;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        .toggle-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
        }
        .toggle-info p {
            margin: 0;
            font-size: 0.875rem;
            color: #6b7280;
        }

    </style>
    <link rel="stylesheet" href="assets/css/loader.css">
    <link rel="stylesheet" href="assets/css/loading.css">
</head>
<body>
<div class="main-content">
    <div class="settings-container">
        <div class="settings-header">
            <h1><i class="fas fa-user-cog"></i> Profile Settings</h1>
            <p>Manage your account information and preferences</p>
            <?php if (isset($_GET['google_setup'])) : ?>
                <div class="alert alert-info">
                    <i class="fas fa-google"></i>
                    Welcome! Please complete your profile to access all features.
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($errors)) : ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <ul style="margin: 0; padding-left: 1rem;">
                    <?php foreach ($errors as $error) : ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($success)) : ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-picture-wrapper">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=6366f1&color=fff&size=240" alt="Profile Picture" class="profile-picture">
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
                    <div class="stat-number"><?php echo $year_in_college; ?></div>
                    <div class="stat-label">Month</div>
                </div>
            </div>
        </div>

        <form action="" method="POST" class="settings-form">

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
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-group-icon"></i>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone_no" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <i class="fas fa-phone input-group-icon"></i>
                            <input type="tel" id="phone_no" name="phone_no" class="form-control" value="<?php echo htmlspecialchars($user['phone_no'] ?? ''); ?>" placeholder="10-digit mobile number">
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
                        <textarea id="about" name="about" class="form-control" rows="4" placeholder="Tell us about yourself, your interests, and goals..."><?php echo htmlspecialchars($user['about'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-id-card"></i>
                    Academic Information
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="enrollment_number" class="form-label">Enrollment Number</label>
                        <div class="input-group">
                            <i class="fas fa-id-badge input-group-icon"></i>
                            <input type="text" id="enrollment_number" name="enrollment_number" class="form-control" value="<?php echo htmlspecialchars($user['enrollment_number']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="gr_number" class="form-label">GR Number</label>
                        <div class="input-group">
                            <i class="fas fa-hashtag input-group-icon"></i>
                            <input type="text" id="gr_number" name="gr_number" class="form-control" value="<?php echo htmlspecialchars($user['gr_number']); ?>">
                        </div>
                    </div>
                </div>
            </div>



            <!-- GitHub Integration -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fab fa-github"></i>
                    GitHub Integration
                </h3>
                <div class="form-group">
                    <label for="github_username" class="form-label">GitHub Username</label>
                    <div class="input-group">
                        <i class="fab fa-github input-group-icon"></i>
                        <input type="text" id="github_username" name="github_username" class="form-control" 
                               value="<?php echo htmlspecialchars($user['github_username'] ?? ''); ?>" 
                               placeholder="Enter your GitHub username">
                    </div>
                    <small class="form-text">Connect your GitHub profile to showcase your repositories and contributions</small>
                    <?php if (!empty($user['github_username'])) : ?>
                        <div class="github-info" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fab fa-github" style="color: #333;"></i>
                                <div>
                                    <strong>Connected:</strong> 
                                    <a href="<?php echo htmlspecialchars($user['github_profile_url'] ?? ''); ?>" target="_blank" style="color: #0366d6;">
                                        <?php echo htmlspecialchars($user['github_username']); ?>
                                    </a>
                                    <br>
                                    <small style="color: #666;">
                                        <?php echo $user['github_repos_count'] ?? 0; ?> public repositories
                                        <?php if (!empty($user['github_last_sync'])) : ?>
                                            • Last synced: <?php echo date('M j, Y', strtotime($user['github_last_sync'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-bell"></i>
                    Notification Settings
                </h3>
                <div class="form-group">
                    <div class="notification-toggle">
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_notifications" <?php echo ($user['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <div class="toggle-info">
                            <h4>Weekly Email Notifications</h4>
                            <p>Receive weekly updates about new projects and ideas from other students</p>
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
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-group-icon"></i>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <i class="fas fa-check-circle input-group-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
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
<script>
// GitHub sync functionality
document.addEventListener('DOMContentLoaded', function() {
    const githubInput = document.getElementById('github_username');
    const githubInfo = document.querySelector('.github-info');
    
    if (githubInput) {
        // Add sync button
        const syncButton = document.createElement('button');
        syncButton.type = 'button';
        syncButton.className = 'btn btn-outline-primary btn-sm mt-2';
        syncButton.innerHTML = '<i class="fab fa-github"></i> Sync Now';
        syncButton.style.display = githubInput.value ? 'inline-block' : 'none';
        
        githubInput.parentNode.appendChild(syncButton);
        
        // Show/hide sync button based on input
        githubInput.addEventListener('input', function() {
            syncButton.style.display = this.value.trim() ? 'inline-block' : 'none';
        });
        
        // Sync functionality
        syncButton.addEventListener('click', function() {
            const username = githubInput.value.trim();
            if (!username) return;
            
            // Show loading state
            syncButton.disabled = true;
            syncButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            
            // Make API call
            fetch('sync_github.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username: username })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update GitHub info display
                    if (githubInfo) {
                        githubInfo.innerHTML = `
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fab fa-github" style="color: #333;"></i>
                                <div>
                                    <strong>Connected:</strong> 
                                    <a href="${data.data.profile_url}" target="_blank" style="color: #0366d6;">
                                        ${data.data.username}
                                    </a>
                                    <br>
                                    <small style="color: #666;">
                                        ${data.data.repos_count} public repositories
                                        • Last synced: Just now
                                    </small>
                                </div>
                            </div>
                        `;
                        githubInfo.style.display = 'block';
                    }
                    
                    // Show success message
                    showNotification('GitHub profile synced successfully!', 'success');
                } else {
                    showNotification(data.message || 'Failed to sync GitHub profile', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
            })
            .finally(() => {
                // Reset button state
                syncButton.disabled = false;
                syncButton.innerHTML = '<i class="fab fa-github"></i> Sync Now';
            });
        });
    }
});

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
</script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="assets/js/loader.js"></script>
<script src="assets/js/loading.js"></script>
</body>
</html>