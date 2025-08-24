<?php
// Start session at the beginning of the script
session_start();
include '../../Login/Login/db.php';

// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set character set to prevent encoding issues
$conn->set_charset("utf8mb4");

$message = "";
$messageType = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user_id from session - Keep as string to match database expectations
    $user_id = $_SESSION['user_id'];

    // Sanitize and validate inputs
    $project_name = htmlspecialchars(trim($_POST['project_name'] ?? ''));
    $project_type = in_array($_POST['project_type'] ?? '', ['software', 'hardware']) ? $_POST['project_type'] : '';
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $language = htmlspecialchars(trim($_POST['language'] ?? ''));

    // New fields
    $project_category = htmlspecialchars(trim($_POST['project_category'] ?? ''));
    $difficulty_level = in_array($_POST['difficulty_level'] ?? '', ['beginner', 'intermediate', 'advanced', 'expert']) ? $_POST['difficulty_level'] : '';
    $development_time = htmlspecialchars(trim($_POST['development_time'] ?? ''));
    $team_size = filter_var($_POST['team_size'] ?? '', FILTER_VALIDATE_INT);
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
    $classification = '';
    if ($project_type == 'software') {
        $valid_software_types = [
                'web', 'mobile', 'ai_ml', 'desktop', 'system',
                'embedded_iot', 'cybersecurity', 'game', 'data_science', 'cloud'
        ];
        $classification = in_array($_POST['software_classification'] ?? '', $valid_software_types)
                ? $_POST['software_classification']
                : '';
    } elseif ($project_type == 'hardware') {
        $valid_hardware_types = [
                'embedded', 'iot', 'robotics', 'automation', 'sensor',
                'communication', 'power', 'wearable', 'mechatronics', 'renewable'
        ];
        $classification = in_array($_POST['hardware_classification'] ?? '', $valid_hardware_types)
                ? $_POST['hardware_classification']
                : '';
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
    if (isset($_FILES['images'])) {
        $image_path = uploadFile($_FILES['images'], "images", ['jpg', 'jpeg', 'png', 'gif'], 2 * 1024 * 1024);
    }
    if (isset($_FILES['videos'])) {
        $video_path = uploadFile($_FILES['videos'], "videos", ['mp4', 'avi', 'mov'], 10 * 1024 * 1024);
    }
    if (isset($_FILES['code_file'])) {
        $code_file_path = uploadFile($_FILES['code_file'], "code_files", ['zip', 'rar', 'tar', 'gz']);
    }
    if (isset($_FILES['instruction_file'])) {
        $instruction_file_path = uploadFile($_FILES['instruction_file'], "instructions", ['txt', 'pdf', 'docx']);
    }
    if (isset($_FILES['presentation_file'])) {
        $presentation_file_path = uploadFile($_FILES['presentation_file'], "presentations", ['ppt', 'pptx', 'pdf'], 15 * 1024 * 1024);
    }
    if (isset($_FILES['additional_files'])) {
        $additional_files_path = uploadFile($_FILES['additional_files'], "additional", ['zip', 'rar', 'tar', 'gz'], 20 * 1024 * 1024);
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

    // FIXED: Prepare and bind with correct parameter types
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $message = "Database error: " . $conn->error;
        $messageType = "danger";
        error_log("Prepare failed: " . $conn->error);
    } else {
        // Convert team_size to null if empty, otherwise keep as integer
        $team_size_param = ($team_size === false || $team_size === '') ? null : $team_size;

        // FIXED: Correct parameter binding - all as strings for simplicity, MySQL will handle type conversion
        $stmt->bind_param(
                "ssssssssssssssssssssssssssss", // All strings - 28 parameters
                $user_id,
                $project_name,
                $project_type,
                $classification,
                $description,
                $language,
                $project_category,
                $difficulty_level,
                $development_time,
                $team_size_param,
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
            $message = "Error submitting project: " . $stmt->error;
            $messageType = "danger";

            // Log the error for debugging
            error_log("Project submission error: " . $stmt->error);
            error_log("SQL: " . $sql);
            error_log("User ID: " . $user_id);
        }

        // Close statement
        $stmt->close();
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "Project submitted successfully! Your project is now under review.";
    $messageType = "success";
}

// Close connection
$conn->close();
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../../assets/css/new_project_add.css">
</head>

<body>
<?php
// Set the correct base path for layout include
$basePath = '../../';
include_once '../layout.php';
?>

<main class="main-content">
    <div class="form-container">
        <div class="form-header">
            <h1><i class="fas fa-rocket me-3"></i>Submit Your Project</h1>
            <p>Share your innovative ideas with the IdeaNest community</p>
        </div>

        <div class="form-body">
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
                                <i class="fas fa-tag me-2"></i>Project Name
                            </label>
                            <input type="text" class="form-control" name="project_name" maxlength="255"
                                   placeholder="Enter your innovative project name">
                        </div>

                        <div class="col-md-4 mb-4">
                            <label class="form-label">
                                <i class="fas fa-layer-group me-2"></i>Project Type
                            </label>
                            <select class="form-select" name="project_type" id="projectType"
                                    onchange="toggleProjectType()">
                                <option value="">Select Project Type</option>
                                <option value="software">Software Development</option>
                                <option value="hardware">Hardware Engineering</option>
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
                                <option value="education">Education</option>
                                <option value="healthcare">Healthcare</option>
                                <option value="finance">Finance</option>
                                <option value="entertainment">Entertainment</option>
                                <option value="productivity">Productivity</option>
                                <option value="social">Social</option>
                                <option value="business">Business</option>
                                <option value="research">Research</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-signal me-2"></i>Difficulty Level
                            </label>
                            <select class="form-select" name="difficulty_level">
                                <option value="">Select Difficulty</option>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                                <option value="expert">Expert</option>
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
                        <option value="web">Web Application</option>
                        <option value="mobile">Mobile Application</option>
                        <option value="ai_ml">AI & Machine Learning</option>
                        <option value="desktop">Desktop Application</option>
                        <option value="system">System Software</option>
                        <option value="embedded_iot">Embedded Systems / IoT</option>
                        <option value="cybersecurity">Cybersecurity</option>
                        <option value="game">Game Development</option>
                        <option value="data_science">Data Science & Analytics</option>
                        <option value="cloud">Cloud Applications</option>
                    </select>
                </div>

                <div class="mb-4 category-group hidden" id="hardwareOptions">
                    <label class="form-label">
                        <i class="fas fa-microchip me-2"></i>Hardware Classification
                    </label>
                    <select class="form-select" name="hardware_classification">
                        <option value="">Select Hardware Category</option>
                        <option value="embedded">Embedded Systems</option>
                        <option value="iot">IoT Projects</option>
                        <option value="robotics">Robotics</option>
                        <option value="automation">Automation</option>
                        <option value="sensor">Sensor-Based Projects</option>
                        <option value="communication">Communication Systems</option>
                        <option value="power">Power Electronics</option>
                        <option value="wearable">Wearable Technology</option>
                        <option value="mechatronics">Mechatronics</option>
                        <option value="renewable">Renewable Energy</option>
                    </select>
                </div>

                <!-- Project Details -->
                <div class="form-section">
                    <h3><i class="fas fa-clipboard-list me-2"></i>Project Details</h3>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-align-left me-2"></i>Project Description
                        </label>
                        <textarea class="form-control" name="description" rows="5" maxlength="2000"
                                  placeholder="Describe your project in detail - what it does, how it works, and what makes it special..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-bullseye me-2"></i>Project Goals & Objectives
                        </label>
                        <textarea class="form-control" name="project_goals" rows="3" maxlength="500"
                                  placeholder="What are the main goals and objectives of your project?"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-tools me-2"></i>Technology Stack
                            </label>
                            <input type="text" class="form-control" name="language" maxlength="200"
                                   placeholder="e.g., Python, JavaScript, React, Arduino, C++">
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-tags me-2"></i>Keywords
                            </label>
                            <input type="text" class="form-control" name="keywords" maxlength="200"
                                   placeholder="machine learning, web app, automation (comma separated)">
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
                                <option value="1-2 weeks">1-2 weeks</option>
                                <option value="1 month">1 month</option>
                                <option value="2-3 months">2-3 months</option>
                                <option value="3-6 months">3-6 months</option>
                                <option value="6+ months">6+ months</option>
                                <option value="ongoing">Ongoing</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-users me-2"></i>Team Size
                            </label>
                            <select class="form-select" name="team_size">
                                <option value="">Select Team Size</option>
                                <option value="1">Solo (1 person)</option>
                                <option value="2">2 people</option>
                                <option value="3">3-4 people</option>
                                <option value="5">5-10 people</option>
                                <option value="10">10+ people</option>
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
                                  placeholder="Who is your target audience? (students, professionals, general public, etc.)"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-exclamation-triangle me-2"></i>Challenges Faced
                        </label>
                        <textarea class="form-control" name="challenges_faced" rows="3" maxlength="1000"
                                  placeholder="What challenges did you encounter during development?"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-rocket me-2"></i>Future Enhancements
                        </label>
                        <textarea class="form-control" name="future_enhancements" rows="3" maxlength="1000"
                                  placeholder="What improvements or features do you plan to add in the future?"></textarea>
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
                                   placeholder="https://github.com/username/project">
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-globe me-2"></i>Live Demo URL
                            </label>
                            <input type="url" class="form-control" name="live_demo_url"
                                   placeholder="https://your-project-demo.com">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-certificate me-2"></i>Project License
                            </label>
                            <select class="form-select" name="project_license">
                                <option value="">Select License</option>
                                <option value="MIT">MIT License</option>
                                <option value="Apache-2.0">Apache License 2.0</option>
                                <option value="GPL-3.0">GPL v3.0</option>
                                <option value="BSD-3-Clause">BSD 3-Clause</option>
                                <option value="proprietary">Proprietary</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-share-alt me-2"></i>Social Links
                            </label>
                            <input type="text" class="form-control" name="social_links" maxlength="500"
                                   placeholder="LinkedIn, Twitter, Portfolio (comma separated)">
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
                               placeholder="your.email@example.com">
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

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-paper-plane me-2"></i>Submit Project
                </button>
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