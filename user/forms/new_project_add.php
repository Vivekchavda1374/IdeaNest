<?php
// Start session at the beginning of the script
session_start();
include '../../Login/Login/db.php';
// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Set character set to prevent encoding issues
$conn->set_charset("utf8mb4");

$message = "";
$messageType = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
    $project_name = htmlspecialchars(trim($_POST['project_name']));
    $project_type = in_array($_POST['project_type'], ['software', 'hardware']) ? $_POST['project_type'] : null;
    $description = htmlspecialchars(trim($_POST['description']));
    $language = htmlspecialchars(trim($_POST['language']));

    // Classification handling
    $classification = null;
    if ($project_type == 'software') {
        $valid_software_types = [
                'web', 'mobile', 'ai_ml', 'desktop', 'system',
                'embedded_iot', 'cybersecurity', 'game', 'data_science', 'cloud'
        ];
        $classification = in_array($_POST['software_classification'], $valid_software_types)
                ? $_POST['software_classification']
                : null;
    } elseif ($project_type == 'hardware') {
        $valid_hardware_types = [
                'embedded', 'iot', 'robotics', 'automation', 'sensor',
                'communication', 'power', 'wearable', 'mechatronics', 'renewable'
        ];
        $classification = in_array($_POST['hardware_classification'], $valid_hardware_types)
                ? $_POST['hardware_classification']
                : null;
    }

    // File upload function with improved validation
    function uploadFile($file, $folder, $allowedTypes = [], $maxSize = 5 * 1024 * 1024) {
        if (empty($file['name'])) {
            return null;
        }

        // Validate file
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = uniqid() . '.' . $fileExt; // Generate unique filename

        // Check file type
        if (!empty($allowedTypes) && !in_array($fileExt, $allowedTypes)) {
            return null;
        }

        // Check file size
        if ($file['size'] > $maxSize) {
            return null;
        }

        // Create upload directory if not exists
        $target_dir = "uploads/$folder/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // Move uploaded file
        $target_file = $target_dir . $fileName;
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $target_file;
        }

        return null;
    }

    // Upload files with type restrictions
    $image_path = uploadFile($_FILES['images'], "images", ['jpg', 'jpeg', 'png', 'gif'], 2 * 1024 * 1024);
    $video_path = uploadFile($_FILES['videos'], "videos", ['mp4', 'avi', 'mov'], 10 * 1024 * 1024);
    $code_file_path = uploadFile($_FILES['code_file'], "code_files", ['zip', 'rar', 'tar', 'gz']);
    $instruction_file_path = uploadFile($_FILES['instruction_file'], "instructions", ['txt', 'pdf', 'docx']);

    // Set default status for new projects
    $status = "pending";

    // Current date and time for submission_date
    $submission_date = date('Y-m-d H:i:s');

    // Prepare SQL statement
    $sql = "INSERT INTO projects (
        user_id, project_name, project_type, classification, 
        description, language, image_path, video_path, 
        code_file_path, instruction_file_path, 
        submission_date, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare and bind
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
            "ssssssssssss",
            $user_id,
            $project_name,
            $project_type,
            $classification,
            $description,
            $language,
            $image_path,
            $video_path,
            $code_file_path,
            $instruction_file_path,
            $submission_date,
            $status
    );

    // Execute statement
    if ($stmt->execute()) {
        $message = "Project submitted successfully!";
        $messageType = "success";
    } else {
        $message = "Error submitting project: " . $stmt->error;
        $messageType = "danger";
    }

    // Close statement
    $stmt->close();
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
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-tag me-2"></i>Project Name
                    </label>
                    <input type="text" class="form-control" name="project_name" required maxlength="255"
                           placeholder="Enter your innovative project name">
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-layer-group me-2"></i>Project Type
                    </label>
                    <select class="form-select" name="project_type" id="projectType" required
                            onchange="toggleProjectType()">
                        <option value="">Select Project Type</option>
                        <option value="software">Software Development</option>
                        <option value="hardware">Hardware Engineering</option>
                    </select>
                </div>

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

                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-align-left me-2"></i>Project Description
                    </label>
                    <textarea class="form-control" name="description" rows="5" required maxlength="1000"
                              placeholder="Describe your project in detail - what it does, how it works, and what makes it special..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-tools me-2"></i>Technology Stack
                    </label>
                    <input type="text" class="form-control" name="language" required maxlength="50"
                           placeholder="e.g., Python, JavaScript, React, Arduino, C++">
                </div>

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

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-paper-plane me-2"></i>Submit Project
                </button>
            </form>
        </div>
    </div>
</main>

<?php if (!empty($message)) { ?>
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                        <?php echo $messageType == 'success' ? 'Success!' : 'Notice'; ?>
                    </h5>
                    <button type="button" class="btn-close" onclick="window.location.href='';"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='';">
                        <i class="fas fa-check me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
<?php } ?>

<script src="../../assets/js/layout_user.js"></script>
<script src="../../assets/js/new_project_add.js"></script>
</body>

</html>