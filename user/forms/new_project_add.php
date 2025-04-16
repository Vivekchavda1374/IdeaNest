<?php
// Start session at the beginning of the script
session_start();

// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Database configuration - consider moving to a separate config file
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ideanest";

// Create connection with error handling
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
    <title>Project Submission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --accent-color: #4895ef;
        --light-color: #f8f9fa;
        --dark-color: #212529;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .container {
        max-width: 800px;
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin: 40px auto;
        position: relative;
        overflow: hidden;
    }

    .container::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 10px 15px;
        transition: all 0.3s;
    }

    .form-control:focus,
    .form-select:focus {
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
        border-color: var(--primary-color);
    }

    .btn-primary {
        background-color: var(--primary-color);
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        background-color: var(--secondary-color);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
    }

    .hidden {
        display: none;
    }

    .user-info {
        margin-bottom: 25px;
        padding: 15px;
        background-color: var(--light-color);
        border-radius: 10px;
        border-left: 4px solid var(--accent-color);
    }

    h3 {
        color: var(--dark-color);
        margin-bottom: 25px;
        position: relative;
        padding-bottom: 15px;
    }

    h3::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: var(--primary-color);
    }

    .form-label {
        font-weight: 500;
        margin-bottom: 8px;
    }

    .modal-content {
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        border-bottom: none;
        padding-bottom: 0;
    }

    .category-group {
        margin-bottom: 20px;
        padding: 15px;
        border-radius: 10px;
        background-color: #f8f9fa;
        border-left: 4px solid var(--accent-color);
    }
    </style>
</head>

<body>
    <div class="container">
        <h3 class="text-center">Submit Your Project</h3>

        <div class="user-info">
            <p class="mb-0"><strong>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!</strong>
            </p>
            <p class="mb-0 text-muted">User ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Project Name</label>
                <input type="text" class="form-control" name="project_name" required maxlength="255"
                    placeholder="Enter your project name">
            </div>

            <div class="mb-3">
                <label class="form-label">Project Type</label>
                <select class="form-select" name="project_type" id="projectType" required
                    onchange="toggleProjectType()">
                    <option value="">Select Type</option>
                    <option value="software">Software</option>
                    <option value="hardware">Hardware</option>
                </select>
            </div>

            <div class="mb-3 category-group hidden" id="softwareOptions">
                <label class="form-label">Software Classification</label>
                <select class="form-select" name="software_classification">
                    <option value="">Select Classification</option>
                    <option value="web">Web Application (Web App)</option>
                    <option value="mobile">Mobile Application (Mobile App)</option>
                    <option value="ai_ml">Artificial Intelligence & Machine Learning (AI/ML)</option>
                    <option value="desktop">Desktop Application</option>
                    <option value="system">System Software</option>
                    <option value="embedded_iot">Embedded Systems / IoT Software</option>
                    <option value="cybersecurity">Cybersecurity Software</option>
                    <option value="game">Game Development</option>
                    <option value="data_science">Data Science & Analytics</option>
                    <option value="cloud">Cloud-Based Applications</option>
                </select>
            </div>

            <div class="mb-3 category-group hidden" id="hardwareOptions">
                <label class="form-label">Hardware Classification</label>
                <select class="form-select" name="hardware_classification">
                    <option value="">Select Classification</option>
                    <option value="embedded">Embedded Systems Projects</option>
                    <option value="iot">IoT (Internet of Things) Projects</option>
                    <option value="robotics">Robotics Projects</option>
                    <option value="automation">Automation Projects</option>
                    <option value="sensor">Sensor-Based Projects</option>
                    <option value="communication">Communication Systems Projects</option>
                    <option value="power">Power Electronics Projects</option>
                    <option value="wearable">Wearable Technology Projects</option>
                    <option value="mechatronics">Mechatronics Projects</option>
                    <option value="renewable">Renewable Energy Projects</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="4" required maxlength="1000"
                    placeholder="Describe your project..."></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Programming Language/Technology</label>
                <input type="text" class="form-control" name="language" required maxlength="50"
                    placeholder="e.g., Python, JavaScript, Arduino">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Upload Image (Max 2MB)</label>
                    <input type="file" class="form-control" name="images" accept="image/jpeg,image/png,image/gif">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Upload Video (Max 10MB)</label>
                    <input type="file" class="form-control" name="videos" accept="video/mp4,video/avi,video/quicktime">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Upload Code File</label>
                    <input type="file" class="form-control" name="code_file" accept=".zip,.rar,.tar,.gz">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Upload Instructions</label>
                    <input type="file" class="form-control" name="instruction_file" accept=".txt,.pdf,.docx">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Submit Project</button>
        </form>
    </div>

    <?php if (!empty($message)) { ?>
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-<?php echo $messageType; ?>">Message</h5>
                    <button type="button" class="btn-close" onclick="window.location.href='';"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='';">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    <?php } ?>

    <script>
    function toggleProjectType() {
        const projectType = document.getElementById("projectType").value;
        const softwareOptions = document.getElementById("softwareOptions");
        const hardwareOptions = document.getElementById("hardwareOptions");

        softwareOptions.classList.toggle("hidden", projectType !== "software");
        hardwareOptions.classList.toggle("hidden", projectType !== "hardware");
    }
    </script>
</body>

</html>