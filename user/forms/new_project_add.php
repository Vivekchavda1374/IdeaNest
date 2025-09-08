<?php
// Start session at the beginning of the script
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../../Login/Login/db.php';

// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set character set to prevent encoding issues
if (isset($conn)) {
    $conn->set_charset("utf8mb4");
}

$message = "";
$messageType = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get user_id from session - Keep as string to match database expectations
        $user_id = $_SESSION['user_id'];

        // Sanitize and validate inputs
        $project_name = htmlspecialchars(trim($_POST['project_name'] ?? ''));
        $project_type = in_array($_POST['project_type'] ?? '', ['software', 'hardware']) ? $_POST['project_type'] : '';
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $language = htmlspecialchars(trim($_POST['language'] ?? ''));

        // Validate required fields
        if (empty($project_name)) {
            throw new Exception("Project name is required.");
        }
        if (empty($project_type)) {
            throw new Exception("Project type is required.");
        }
        if (empty($description)) {
            throw new Exception("Project description is required.");
        }

        // New fields with proper validation
        $project_category = htmlspecialchars(trim($_POST['project_category'] ?? ''));
        $difficulty_level = in_array($_POST['difficulty_level'] ?? '', ['beginner', 'intermediate', 'advanced', 'expert']) ? $_POST['difficulty_level'] : null;
        $development_time = htmlspecialchars(trim($_POST['development_time'] ?? ''));

        // Handle team_size properly
        $team_size = $_POST['team_size'] ?? '';
        if ($team_size === '' || !is_numeric($team_size)) {
            $team_size = null;
        } else {
            $team_size = (string)$team_size; // Convert to string to match database
        }

        $target_audience = htmlspecialchars(trim($_POST['target_audience'] ?? ''));
        $project_goals = htmlspecialchars(trim($_POST['project_goals'] ?? ''));
        $challenges_faced = htmlspecialchars(trim($_POST['challenges_faced'] ?? ''));
        $future_enhancements = htmlspecialchars(trim($_POST['future_enhancements'] ?? ''));
        $github_repo = filter_var(trim($_POST['github_repo'] ?? ''), FILTER_VALIDATE_URL) ?: null;
        $live_demo_url = filter_var(trim($_POST['live_demo_url'] ?? ''), FILTER_VALIDATE_URL) ?: null;
        $project_license = htmlspecialchars(trim($_POST['project_license'] ?? ''));
        $keywords = htmlspecialchars(trim($_POST['keywords'] ?? ''));
        $contact_email = filter_var(trim($_POST['contact_email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null;
        $social_links = htmlspecialchars(trim($_POST['social_links'] ?? ''));

        // Classification handling
        $classification = null;
        if ($project_type == 'software') {
            $valid_software_types = [
                    'web', 'mobile', 'ai_ml', 'desktop', 'system',
                    'embedded_iot', 'cybersecurity', 'game', 'data_science', 'cloud'
            ];
            $classification = in_array($_POST['software_classification'] ?? '', $valid_software_types)
                    ? $_POST['software_classification']
                    : null;
        } elseif ($project_type == 'hardware') {
            $valid_hardware_types = [
                    'embedded', 'iot', 'robotics', 'automation', 'sensor',
                    'communication', 'power', 'wearable', 'mechatronics', 'renewable'
            ];
            $classification = in_array($_POST['hardware_classification'] ?? '', $valid_hardware_types)
                    ? $_POST['hardware_classification']
                    : null;
        }

        // File upload function with improved validation and error handling
        function uploadFile($file, $folder, $allowedTypes = [], $maxSize = 5 * 1024 * 1024) {
            if (empty($file['name'])) {
                return null;
            }

            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                error_log("File upload error: " . $file['error']);
                return null;
            }

            // Validate file
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '.' . $fileExt; // Generate unique filename

            // Check file type
            if (!empty($allowedTypes) && !in_array($fileExt, $allowedTypes)) {
                error_log("Invalid file type: " . $fileExt);
                return null;
            }

            // Check file size
            if ($file['size'] > $maxSize) {
                error_log("File too large: " . $file['size'] . " bytes");
                return null;
            }

            // Create upload directory if not exists
            $target_dir = "uploads/$folder/";
            if (!file_exists($target_dir)) {
                if (!mkdir($target_dir, 0755, true)) {
                    error_log("Failed to create directory: " . $target_dir);
                    return null;
                }
            }

            // Move uploaded file
            $target_file = $target_dir . $fileName;
            if (move_uploaded_file($file["tmp_name"], $target_file)) {
                return $target_file;
            }

            error_log("Failed to move uploaded file");
            return null;
        }

        // Upload files with type restrictions
        $image_path = null;
        $video_path = null;
        $code_file_path = null;
        $instruction_file_path = null;
        $presentation_file_path = null;
        $additional_files_path = null;

        // Handle file uploads safely
        if (isset($_FILES['images']) && $_FILES['images']['error'] !== UPLOAD_ERR_NO_FILE) {
            $image_path = uploadFile($_FILES['images'], "images", ['jpg', 'jpeg', 'png', 'gif'], 2 * 1024 * 1024);
        }
        if (isset($_FILES['videos']) && $_FILES['videos']['error'] !== UPLOAD_ERR_NO_FILE) {
            $video_path = uploadFile($_FILES['videos'], "videos", ['mp4', 'avi', 'mov'], 50 * 1024 * 1024);
        }
        if (isset($_FILES['code_file']) && $_FILES['code_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $code_file_path = uploadFile($_FILES['code_file'], "code_files", ['zip', 'rar', 'tar', 'gz']);
        }
        if (isset($_FILES['instruction_file']) && $_FILES['instruction_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $instruction_file_path = uploadFile($_FILES['instruction_file'], "instructions", ['txt', 'pdf', 'docx']);
        }
        if (isset($_FILES['presentation_file']) && $_FILES['presentation_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $presentation_file_path = uploadFile($_FILES['presentation_file'], "presentations", ['ppt', 'pptx', 'pdf'], 50 * 1024 * 1024);
        }
        if (isset($_FILES['additional_files']) && $_FILES['additional_files']['error'] !== UPLOAD_ERR_NO_FILE) {
            $additional_files_path = uploadFile($_FILES['additional_files'], "additional", ['zip', 'rar', 'tar', 'gz'], 50 * 1024 * 1024);
        }

        // Set default status for new projects
        $status = "pending";

        // Current date and time for submission_date
        $submission_date = date('Y-m-d H:i:s');

        // FIXED SQL statement and parameter binding
        $sql = "INSERT INTO projects (
            user_id, project_name, project_type, classification, 
            description, language, project_category, difficulty_level,
            development_time, team_size, target_audience,
            project_goals, challenges_faced, future_enhancements,
            github_repo, live_demo_url, project_license, keywords,
            contact_email, social_links, image_path, video_path, 
            code_file_path, instruction_file_path, presentation_file_path,
            additional_files_path, submission_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Prepare statement
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        // Bind parameters - all as strings for consistency
        $stmt->bind_param(
                "ssssssssssssssssssssssssssss", // 28 string parameters
                $user_id,
                $project_name,
                $project_type,
                $classification,
                $description,
                $language,
                $project_category,
                $difficulty_level,
                $development_time,
                $team_size,
                $target_audience,
                $project_goals,
                $challenges_faced,
                $future_enhancements,
                $github_repo,
                $live_demo_url,
                $project_license,
                $keywords,
                $contact_email,
                $social_links,
                $image_path,
                $video_path,
                $code_file_path,
                $instruction_file_path,
                $presentation_file_path,
                $additional_files_path,
                $submission_date,
                $status
        );

        // Execute statement
        if ($stmt->execute()) {
            $message = "Project submitted successfully! Your project is now under review.";
            $messageType = "success";

            // Clear form data by redirecting
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        } else {
            throw new Exception("Error executing query: " . $stmt->error);
        }

        // Close statement
        $stmt->close();

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";

        // Log the error for debugging
        error_log("Project submission error: " . $e->getMessage());
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "Project submitted successfully! Your project is now under review.";
    $messageType = "success";
}

// Close connection if it exists
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Submission - IdeaNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/layout_user.css">

    <style>
        :root {
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-700: #334155;
            --border-radius: 8px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg, var(--gray-50), #e0e7ff);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 280px;
            padding: 1rem;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding-top: 80px;
            }
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-align: center;
            padding: 1.5rem;
        }

        .form-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .form-body {
            padding: 1.5rem;
        }

        .user-info {
            background: var(--gray-50);
            padding: 1rem;
            border-radius: var(--border-radius);
            border-left: 3px solid var(--primary);
            margin-bottom: 1.5rem;
        }

        .user-info h6 {
            color: var(--gray-700);
            font-weight: 600;
            margin: 0 0 0.25rem 0;
            font-size: 0.9rem;
        }

        .user-info small {
            color: #6b7280;
            font-size: 0.8rem;
        }

        .form-section {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            border-left: 3px solid var(--primary);
        }

        .section-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 0.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
        }

        .form-control, .form-select {
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            background: linear-gradient(135deg, var(--secondary), var(--primary));
        }

        .btn-outline-secondary {
            border-color: var(--gray-200);
            color: var(--gray-700);
            padding: 0.6rem 1.5rem;
            font-size: 0.9rem;
        }

        .text-danger { color: #dc3545 !important; }
        .hidden { display: none !important; }
        .mb-3 { margin-bottom: 1rem; }
        .mb-2 { margin-bottom: 0.5rem; }

        /* Grid system */
        .row { display: flex; flex-wrap: wrap; margin: -0.5rem; }
        .col-md-6 { flex: 1; padding: 0.5rem; min-width: 0; }

        @media (max-width: 768px) {
            .col-md-6 { flex: 0 0 100%; }
            .form-body { padding: 1rem; }
            .form-section { padding: 0.75rem; }
        }

        /* File upload styling */
        .file-upload {
            border: 2px dashed var(--gray-200);
            border-radius: var(--border-radius);
            padding: 0.75rem;
            text-align: center;
            transition: all 0.3s;
            background: #fafbfc;
        }

        .file-upload:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .file-upload input[type="file"] {
            border: none;
            background: transparent;
            width: 100%;
        }

        .file-info {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        /* Alert styles */
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: #d1f7c4;
            border-color: #b8f5a3;
            color: #2d5016;
        }

        .alert-danger {
            background-color: #fed7d7;
            border-color: #feb2b2;
            color: #742a2a;
        }
    </style>
</head>
<body>
<?php include "../layout.php";?>
<!-- Main Content -->
<main class="main-content">
    <div class="form-container">
        <div class="form-header">
            <h1><i class="fas fa-rocket me-3"></i>Submit Your Project</h1>
            <p>Share your innovative ideas with the IdeaNest community</p>
        </div>

        <div class="form-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="user-info">
                <h4><i class="fas fa-user me-2"></i>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!</h4>
                <p><i class="fas fa-id-badge me-2"></i>User ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <!-- Basic Project Information -->
                <div class="form-section">
                    <h3><i class="fas fa-info-circle me-2"></i>Basic Information</h3>

                    <div class="row">
                        <div class="col-md-8 mb-4">
                            <label class="form-label">
                                <i class="fas fa-tag me-2"></i>Project Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="project_name" maxlength="255" required
                                   placeholder="Enter your innovative project name"
                                   value="<?php echo isset($_POST['project_name']) ? htmlspecialchars($_POST['project_name']) : ''; ?>">
                        </div>

                        <div class="col-md-4 mb-4">
                            <label class="form-label">
                                <i class="fas fa-layer-group me-2"></i>Project Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="project_type" id="projectType" required
                                    onchange="toggleProjectType()">
                                <option value="">Select Project Type</option>
                                <option value="software" <?php echo (isset($_POST['project_type']) && $_POST['project_type'] === 'software') ? 'selected' : ''; ?>>Software Development</option>
                                <option value="hardware" <?php echo (isset($_POST['project_type']) && $_POST['project_type'] === 'hardware') ? 'selected' : ''; ?>>Hardware Engineering</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-folder me-2"></i>Project Category
                            </label>
                            <select class="form-select" name="project_category">
                                <option value="">Select Category</option>
                                <option value="education" <?php echo (isset($_POST['project_category']) && $_POST['project_category'] === 'education') ? 'selected' : ''; ?>>Education</option>
                                <option value="healthcare" <?php echo (isset($_POST['project_category']) && $_POST['project_category'] === 'healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                                <option value="finance" <?php echo (isset($_POST['project_category']) && $_POST['project_category'] === 'finance') ? 'selected' : ''; ?>>Finance</option>
                                <option value="entertainment" <?php echo (isset($_POST['project_category']) && $_POST['project_category'] === 'entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                                <option value="productivity" <?php echo (isset($_POST['project_category']) && $_POST['project_category'] === 'productivity') ? 'selected' : ''; ?>>Productivity</option>
                                <option value="social" <?php echo (isset($_POST['project_category']) && $_POST['project_category'] === 'social') ? 'selected' : ''; ?>>Social</option>
                                <option value="business" <?php echo (isset($_POST['project_category']) && $_POST['project_category'] === 'business') ? 'selected' : ''; ?>>Business</option>
                                <option value="research" <?php echo (isset($_POST['project_category']) && $_POST['project_category'] === 'research') ? 'selected' : ''; ?>>Research</option>
                                <option value="other" <?php echo (isset($_POST['project_category']) && $_POST['project_category'] === 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-signal me-2"></i>Difficulty Level
                            </label>
                            <select class="form-select" name="difficulty_level">
                                <option value="">Select Difficulty</option>
                                <option value="beginner" <?php echo (isset($_POST['difficulty_level']) && $_POST['difficulty_level'] === 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                <option value="intermediate" <?php echo (isset($_POST['difficulty_level']) && $_POST['difficulty_level'] === 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo (isset($_POST['difficulty_level']) && $_POST['difficulty_level'] === 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                <option value="expert" <?php echo (isset($_POST['difficulty_level']) && $_POST['difficulty_level'] === 'expert') ? 'selected' : ''; ?>>Expert</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Project Classification -->
                <div class="mb-4 category-group hidden" id="softwareOptions">
                    <label class="form-label">
                        <i class="fas fa-code me-2"></i>Software Classification
                    </label>
                    <select class="form-select" name="software_classification">
                        <option value="">Select Software Category</option>
                        <option value="web" <?php echo (isset($_POST['software_classification']) && $_POST['software_classification'] === 'web') ? 'selected' : ''; ?>>Web Application</option>
                        <option value="mobile" <?php echo (isset($_POST['software_classification']) && $_POST['software_classification'] === 'mobile') ? 'selected' : ''; ?>>Mobile Application</option>
                        <option value="ai_ml" <?php echo (isset($_POST['software_classification']) && $_POST['software_classification'] === 'ai_ml') ? 'selected' : ''; ?>>AI & Machine Learning</option>
                        <option value="desktop" <?php echo (isset($_POST['software_classification']) && $_POST['software_classification'] === 'desktop') ? 'selected' : ''; ?>>Desktop Application</option>
                        <option value="system" <?php echo (isset($_POST['software_classification']) && $_POST['software_classification'] === 'system') ? 'selected' : ''; ?>>System Software</option>
                        <option value="embedded_iot" <?php echo (isset($_POST['software_classification']) && $_POST['software_classification'] === 'embedded_iot') ? 'selected' : ''; ?>>Embedded Systems / IoT</option>
                        <option value="cybersecurity" <?php echo (isset($_POST['software_classification']) && $_POST['software_classification'] === 'cybersecurity') ? 'selected' : ''; ?>>Cybersecurity</option>
                        <option value="game" <?php echo (isset($_POST['software_classification']) && $_POST['software_classification'] === 'game') ? 'selected' : ''; ?>>Game Development</option>
                        <option value="data_science" <?php echo (isset($_POST['software_classification']) && $_POST['software_classification'] === 'data_science') ? 'selected' : ''; ?>>Data Science & Analytics</option>
                        <option value="cloud" <?php echo (isset($_POST['software_classification']) && $_POST['software_classification'] === 'cloud') ? 'selected' : ''; ?>>Cloud Applications</option>
                    </select>
                </div>

                <div class="mb-4 category-group hidden" id="hardwareOptions">
                    <label class="form-label">
                        <i class="fas fa-microchip me-2"></i>Hardware Classification
                    </label>
                    <select class="form-select" name="hardware_classification">
                        <option value="">Select Hardware Category</option>
                        <option value="embedded" <?php echo (isset($_POST['hardware_classification']) && $_POST['hardware_classification'] === 'embedded') ? 'selected' : ''; ?>>Embedded Systems</option>
                        <option value="iot" <?php echo (isset($_POST['hardware_classification']) && $_POST['hardware_classification'] === 'iot') ? 'selected' : ''; ?>>IoT Projects</option>
                        <option value="robotics" <?php echo (isset($_POST['hardware_classification']) && $_POST['hardware_classification'] === 'robotics') ? 'selected' : ''; ?>>Robotics</option>
                        <option value="automation" <?php echo (isset($_POST['hardware_classification']) && $_POST['hardware_classification'] === 'automation') ? 'selected' : ''; ?>>Automation</option>
                        <option value="sensor" <?php echo (isset($_POST['hardware_classification']) && $_POST['hardware_classification'] === 'sensor') ? 'selected' : ''; ?>>Sensor-Based Projects</option>
                        <option value="communication" <?php echo (isset($_POST['hardware_classification']) && $_POST['hardware_classification'] === 'communication') ? 'selected' : ''; ?>>Communication Systems</option>
                        <option value="power" <?php echo (isset($_POST['hardware_classification']) && $_POST['hardware_classification'] === 'power') ? 'selected' : ''; ?>>Power Electronics</option>
                        <option value="wearable" <?php echo (isset($_POST['hardware_classification']) && $_POST['hardware_classification'] === 'wearable') ? 'selected' : ''; ?>>Wearable Technology</option>
                        <option value="mechatronics" <?php echo (isset($_POST['hardware_classification']) && $_POST['hardware_classification'] === 'mechatronics') ? 'selected' : ''; ?>>Mechatronics</option>
                        <option value="renewable" <?php echo (isset($_POST['hardware_classification']) && $_POST['hardware_classification'] === 'renewable') ? 'selected' : ''; ?>>Renewable Energy</option>
                    </select>
                </div>

                <!-- Project Details -->
                <div class="form-section">
                    <h3><i class="fas fa-clipboard-list me-2"></i>Project Details</h3>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-align-left me-2"></i>Project Description <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" name="description" rows="5" maxlength="2000" required
                                  placeholder="Describe your project in detail - what it does, how it works, and what makes it special..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-bullseye me-2"></i>Project Goals & Objectives
                        </label>
                        <textarea class="form-control" name="project_goals" rows="3" maxlength="500"
                                  placeholder="What are the main goals and objectives of your project?"><?php echo isset($_POST['project_goals']) ? htmlspecialchars($_POST['project_goals']) : ''; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-tools me-2"></i>Technology Stack
                            </label>
                            <input type="text" class="form-control" name="language" maxlength="200"
                                   placeholder="e.g., Python, JavaScript, React, Arduino, C++"
                                   value="<?php echo isset($_POST['language']) ? htmlspecialchars($_POST['language']) : ''; ?>">
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-tags me-2"></i>Keywords
                            </label>
                            <input type="text" class="form-control" name="keywords" maxlength="200"
                                   placeholder="machine learning, web app, automation (comma separated)"
                                   value="<?php echo isset($_POST['keywords']) ? htmlspecialchars($_POST['keywords']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- Development Information -->
                <div class="form-section">
                    <h3><i class="fas fa-cogs me-2"></i>Development Information</h3>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-clock me-2"></i>Development Time
                            </label>
                            <select class="form-select" name="development_time">
                                <option value="">Select Duration</option>
                                <option value="1-2 weeks" <?php echo (isset($_POST['development_time']) && $_POST['development_time'] === '1-2 weeks') ? 'selected' : ''; ?>>1-2 weeks</option>
                                <option value="1 month" <?php echo (isset($_POST['development_time']) && $_POST['development_time'] === '1 month') ? 'selected' : ''; ?>>1 month</option>
                                <option value="2-3 months" <?php echo (isset($_POST['development_time']) && $_POST['development_time'] === '2-3 months') ? 'selected' : ''; ?>>2-3 months</option>
                                <option value="3-6 months" <?php echo (isset($_POST['development_time']) && $_POST['development_time'] === '3-6 months') ? 'selected' : ''; ?>>3-6 months</option>
                                <option value="6+ months" <?php echo (isset($_POST['development_time']) && $_POST['development_time'] === '6+ months') ? 'selected' : ''; ?>>6+ months</option>
                                <option value="ongoing" <?php echo (isset($_POST['development_time']) && $_POST['development_time'] === 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-users me-2"></i>Team Size
                            </label>
                            <select class="form-select" name="team_size">
                                <option value="">Select Team Size</option>
                                <option value="1" <?php echo (isset($_POST['team_size']) && $_POST['team_size'] === '1') ? 'selected' : ''; ?>>Solo (1 person)</option>
                                <option value="2" <?php echo (isset($_POST['team_size']) && $_POST['team_size'] === '2') ? 'selected' : ''; ?>>2 people</option>
                                <option value="3" <?php echo (isset($_POST['team_size']) && $_POST['team_size'] === '3') ? 'selected' : ''; ?>>3-4 people</option>
                                <option value="5" <?php echo (isset($_POST['team_size']) && $_POST['team_size'] === '5') ? 'selected' : ''; ?>>5-10 people</option>
                                <option value="10" <?php echo (isset($_POST['team_size']) && $_POST['team_size'] === '10') ? 'selected' : ''; ?>>10+ people</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Target Audience & Challenges -->
                <div class="form-section">
                    <h3><i class="fas fa-target me-2"></i>Audience & Challenges</h3>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-user-friends me-2"></i>Target Audience
                        </label>
                        <textarea class="form-control" name="target_audience" rows="2" maxlength="500"
                                  placeholder="Who is your target audience? (students, professionals, general public, etc.)"><?php echo isset($_POST['target_audience']) ? htmlspecialchars($_POST['target_audience']) : ''; ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-exclamation-triangle me-2"></i>Challenges Faced
                        </label>
                        <textarea class="form-control" name="challenges_faced" rows="3" maxlength="1000"
                                  placeholder="What challenges did you encounter during development?"><?php echo isset($_POST['challenges_faced']) ? htmlspecialchars($_POST['challenges_faced']) : ''; ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-rocket me-2"></i>Future Enhancements
                        </label>
                        <textarea class="form-control" name="future_enhancements" rows="3" maxlength="1000"
                                  placeholder="What improvements or features do you plan to add in the future?"><?php echo isset($_POST['future_enhancements']) ? htmlspecialchars($_POST['future_enhancements']) : ''; ?></textarea>
                    </div>
                </div>

                <!-- Links & Repository -->
                <div class="form-section">
                    <h3><i class="fas fa-link me-2"></i>Links & Repository</h3>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fab fa-github me-2"></i>GitHub Repository
                            </label>
                            <input type="url" class="form-control" name="github_repo"
                                   placeholder="https://github.com/username/project"
                                   value="<?php echo isset($_POST['github_repo']) ? htmlspecialchars($_POST['github_repo']) : ''; ?>">
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-globe me-2"></i>Live Demo URL
                            </label>
                            <input type="url" class="form-control" name="live_demo_url"
                                   placeholder="https://your-project-demo.com"
                                   value="<?php echo isset($_POST['live_demo_url']) ? htmlspecialchars($_POST['live_demo_url']) : ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-certificate me-2"></i>Project License
                            </label>
                            <select class="form-select" name="project_license">
                                <option value="">Select License</option>
                                <option value="MIT" <?php echo (isset($_POST['project_license']) && $_POST['project_license'] === 'MIT') ? 'selected' : ''; ?>>MIT License</option>
                                <option value="Apache-2.0" <?php echo (isset($_POST['project_license']) && $_POST['project_license'] === 'Apache-2.0') ? 'selected' : ''; ?>>Apache License 2.0</option>
                                <option value="GPL-3.0" <?php echo (isset($_POST['project_license']) && $_POST['project_license'] === 'GPL-3.0') ? 'selected' : ''; ?>>GPL v3.0</option>
                                <option value="BSD-3-Clause" <?php echo (isset($_POST['project_license']) && $_POST['project_license'] === 'BSD-3-Clause') ? 'selected' : ''; ?>>BSD 3-Clause</option>
                                <option value="proprietary" <?php echo (isset($_POST['project_license']) && $_POST['project_license'] === 'proprietary') ? 'selected' : ''; ?>>Proprietary</option>
                                <option value="other" <?php echo (isset($_POST['project_license']) && $_POST['project_license'] === 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-share-alt me-2"></i>Social Links
                            </label>
                            <input type="text" class="form-control" name="social_links" maxlength="500"
                                   placeholder="LinkedIn, Twitter, Portfolio (comma separated)"
                                   value="<?php echo isset($_POST['social_links']) ? htmlspecialchars($_POST['social_links']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- Contact & Collaboration -->
                <div class="form-section">
                    <h3><i class="fas fa-handshake me-2"></i>Contact Person</h3>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-envelope me-2"></i>Contact Email
                        </label>
                        <input type="email" class="form-control" name="contact_email"
                               placeholder="your.email@example.com"
                               value="<?php echo isset($_POST['contact_email']) ? htmlspecialchars($_POST['contact_email']) : ''; ?>">
                    </div>
                </div>

                <!-- File Uploads -->
                <div class="form-section">
                    <h3><i class="fas fa-upload me-2"></i>File Uploads</h3>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="file-upload-container">
                                <label class="form-label">
                                    <i class="fas fa-image me-2"></i>Project Image
                                </label>
                                <input type="file" class="form-control" name="images" accept="image/jpeg,image/png,image/gif">
                                <div class="file-upload-info">Max size: 2MB | Formats: JPG, PNG, GIF</div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="file-upload-container">
                                <label class="form-label">
                                    <i class="fas fa-video me-2"></i>Demo Video
                                </label>
                                <input type="file" class="form-control" name="videos" accept="video/mp4,video/avi,video/quicktime">
                                <div class="file-upload-info">Max size: 10MB | Formats: MP4, AVI, MOV</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="file-upload-container">
                                <label class="form-label">
                                    <i class="fas fa-file-archive me-2"></i>Source Code
                                </label>
                                <input type="file" class="form-control" name="code_file" accept=".zip,.rar,.tar,.gz">
                                <div class="file-upload-info">Formats: ZIP, RAR, TAR, GZ</div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="file-upload-container">
                                <label class="form-label">
                                    <i class="fas fa-file-alt me-2"></i>Documentation
                                </label>
                                <input type="file" class="form-control" name="instruction_file" accept=".txt,.pdf,.docx">
                                <div class="file-upload-info">Formats: TXT, PDF, DOCX</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="file-upload-container">
                                <label class="form-label">
                                    <i class="fas fa-presentation me-2"></i>Presentation
                                </label>
                                <input type="file" class="form-control" name="presentation_file" accept=".ppt,.pptx,.pdf">
                                <div class="file-upload-info">Max size: 15MB | Formats: PPT, PPTX, PDF</div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="file-upload-container">
                                <label class="form-label">
                                    <i class="fas fa-folder-plus me-2"></i>Additional Files
                                </label>
                                <input type="file" class="form-control" name="additional_files" accept=".zip,.rar,.tar,.gz">
                                <div class="file-upload-info">Max size: 20MB | Formats: ZIP, RAR, TAR, GZ</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-end align-items-center">
                    <button type="button" class="btn btn-outline-secondary me-3" onclick="resetForm()">
                        <i class="fas fa-undo me-2"></i>Reset Form
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php if (!empty($message)) { ?>
    <div class="modal fade show" id="messageModal" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                        <?php echo $messageType == 'success' ? 'Success!' : 'Notice'; ?>
                    </h5>
                    <button type="button" class="btn-close" onclick="closeModal()"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="closeModal()">
                        <i class="fas fa-check me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" id="modalBackdrop"></div>

    <script>
        function closeModal() {
            // Remove the modal and backdrop
            const modal = document.getElementById('messageModal');
            const backdrop = document.getElementById('modalBackdrop');

            if (modal) modal.remove();
            if (backdrop) backdrop.remove();

            // Clean URL by removing success parameter
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url);
        }

        // Close modal when clicking on backdrop
        document.getElementById('modalBackdrop')?.addEventListener('click', closeModal);

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
<?php } ?>

<script src="../../assets/js/layout_user.js"></script>
<script src="../../assets/js/new_project_add.js"></script>
</body>

</html>