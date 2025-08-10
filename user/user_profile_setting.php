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
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --border-radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 80px;
            }
        }

        .settings-container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
        }

        .settings-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 2rem;
            text-align: center;
        }

        .settings-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .settings-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .profile-section {
            padding: 2rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--white);
            box-shadow: var(--shadow-lg);
            transition: transform 0.3s ease;
        }

        .profile-picture:hover {
            transform: scale(1.05);
        }

        .profile-info h2 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--gray-800);
            font-weight: 700;
        }

        .profile-info p {
            margin: 0.5rem 0 0;
            color: var(--gray-600);
            font-size: 1.1rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            background: var(--white);
            padding: 1rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--shadow-sm);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .settings-form {
            padding: 2rem;
        }

        .form-section {
            margin-bottom: 2.5rem;
        }

        .section-title {
            font-size: 1.4rem;
            color: var(--gray-800);
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            transform: translateY(-1px);
        }

        .form-control:hover {
            border-color: var(--gray-400);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .upload-section {
            border: 2px dashed var(--gray-300);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-section:hover {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.05);
        }

        .upload-icon {
            font-size: 2rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }

        .upload-text {
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        .upload-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .upload-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .upload-btn input[type="file"] {
            display: none;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--gray-600);
            color: var(--white);
        }

        .btn-secondary:hover {
            background: var(--gray-700);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        .strength-bar {
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            margin-top: 0.25rem;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .strength-weak { background: var(--danger-color); width: 25%; }
        .strength-fair { background: var(--warning-color); width: 50%; }
        .strength-good { background: var(--info-color); width: 75%; }
        .strength-strong { background: var(--success-color); width: 100%; }

        .input-group {
            position: relative;
        }

        .input-group-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            pointer-events: none;
        }

        .input-group .form-control {
            padding-left: 2.75rem;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            font-size: 1.1rem;
        }

        .toggle-password:hover {
            color: var(--gray-600);
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .settings-header {
                padding: 1.5rem;
            }

            .settings-form {
                padding: 1.5rem;
            }

            .profile-section {
                padding: 1.5rem;
            }
        }
    </style>
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

<script>
    // Profile picture preview
    document.getElementById('user_image').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePreview').src = e.target.result;
            }
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    // Password toggle functionality
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                targetInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Password strength checker
    document.getElementById('new_password').addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('passwordStrength');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        if (password.length === 0) {
            strengthDiv.style.display = 'none';
            return;
        }

        strengthDiv.style.display = 'block';

        let strength = 0;
        let feedback = '';

        if (password.length >= 8) strength++;
        else feedback = 'At least 8 characters required';

        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;

        strengthFill.className = 'strength-fill';

        if (strength <= 2) {
            strengthFill.classList.add('strength-weak');
            strengthText.textContent = feedback || 'Weak password';
            strengthText.style.color = 'var(--danger-color)';
        } else if (strength === 3) {
            strengthFill.classList.add('strength-fair');
            strengthText.textContent = 'Fair password';
            strengthText.style.color = 'var(--warning-color)';
        } else if (strength === 4) {
            strengthFill.classList.add('strength-good');
            strengthText.textContent = 'Good password';
            strengthText.style.color = 'var(--info-color)';
        } else {
            strengthFill.classList.add('strength-strong');
            strengthText.textContent = 'Strong password';
            strengthText.style.color = 'var(--success-color)';
        }
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const currentPassword = document.getElementById('current_password').value;

        // Password validation
        if (newPassword || confirmPassword || currentPassword) {
            if (!currentPassword) {
                e.preventDefault();
                alert('Please enter your current password to change it.');
                document.getElementById('current_password').focus();
                return;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                document.getElementById('confirm_password').focus();
                return;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                alert('New password must be at least 6 characters long!');
                document.getElementById('new_password').focus();
                return;
            }
        }

        // Phone number validation
        const phoneNo = document.getElementById('phone_no').value;
        if (phoneNo && !/^\d{10}$/.test(phoneNo)) {
            e.preventDefault();
            alert('Phone number must be exactly 10 digits!');
            document.getElementById('phone_no').focus();
            return;
        }

        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitBtn.disabled = true;

        // Re-enable button after a delay in case of validation errors
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 5000);
    });

    // File size validation
    document.getElementById('user_image').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                alert('File size must be less than 5MB');
                this.value = '';
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only JPG, PNG, and GIF files are allowed');
                this.value = '';
                return;
            }
        }
    });
</script>
</body>
</html>