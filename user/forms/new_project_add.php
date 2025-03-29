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
        $classification = in_array($_POST['software_classification'], ['web', 'mobile', 'desktop', 'embedded'])
            ? $_POST['software_classification']
            : null;
    } elseif ($project_type == 'hardware') {
        $classification = in_array($_POST['hardware_classification'], ['iot', 'robotics', 'electronics'])
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
        body {
            background-color: rgb(220, 222, 225);
        }

        .container {
            max-width: 700px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 40px;
            transition: 0.5s;
        }

        .container:hover {
            box-shadow: 0px 1px 10px 1px rgba(0, 0, 0, 0.2);
        }

        .hidden {
            display: none;
        }

        .user-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h3 class="text-center mb-4">Submit Your Project</h3>

    <div class="user-info">
        <p><strong>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!</strong></p>
        <p>User ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Project Name</label>
            <input type="text" class="form-control" name="project_name" required maxlength="255">
        </div>
        <div class="mb-3">
            <label class="form-label">Project Type</label>
            <select class="form-select" name="project_type" id="projectType" required onchange="toggleProjectType()">
                <option value="">Select Type</option>
                <option value="software">Software</option>
                <option value="hardware">Hardware</option>
            </select>
        </div>
        <div class="mb-3 hidden" id="softwareOptions">
            <label class="form-label">Software Classification</label>
            <select class="form-select" name="software_classification">
                <option value="">Select Classification</option>
                <option value="web">Web Application</option>
                <option value="mobile">Mobile Application</option>
                <option value="desktop">Desktop Software</option>
                <option value="embedded">Embedded Software</option>
            </select>
        </div>
        <div class="mb-3 hidden" id="hardwareOptions">
            <label class="form-label">Hardware Classification</label>
            <select class="form-select" name="hardware_classification">
                <option value="">Select Classification</option>
                <option value="iot">IoT Device</option>
                <option value="robotics">Robotics</option>
                <option value="electronics">Electronics Circuit</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="4" required maxlength="1000"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Programming Language</label>
            <input type="text" class="form-control" name="language" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Upload Image (Max 2MB)</label>
            <input type="file" class="form-control" name="images" accept="image/jpeg,image/png,image/gif">
        </div>
        <div class="mb-3">
            <label class="form-label">Upload Video (Max 10MB)</label>
            <input type="file" class="form-control" name="videos" accept="video/mp4,video/avi,video/quicktime">
        </div>
        <div class="mb-3">
            <label class="form-label">Upload Code File</label>
            <input type="file" class="form-control" name="code_file" accept=".zip,.rar,.tar,.gz">
        </div>
        <div class="mb-3">
            <label class="form-label">Upload Instructions</label>
            <input type="file" class="form-control" name="instruction_file" accept=".txt,.pdf,.docx">
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
            </div>
        </div>
    </div>
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