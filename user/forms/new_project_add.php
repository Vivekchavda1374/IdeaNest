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
    <title>Project Submission - IdeaNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    :root {
        --primary-color: #6366f1;
        --secondary-color: #8b5cf6;
        --accent-color: #4895ef;
        --white: #ffffff;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-500: #64748b;
        --gray-700: #334155;
        --gray-900: #0f172a;
        --danger-color: #ef4444;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --sidebar-width: 280px;
        --border-radius: 12px;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: linear-gradient(135deg, var(--gray-50) 0%, #e0e7ff 100%);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        color: var(--gray-900);
        line-height: 1.6;
    }

    /* Main Content Area */
    .main-content {
        margin-left: var(--sidebar-width);
        min-height: 100vh;
        padding: 2rem;
        transition: margin-left 0.3s ease;
    }

    @media (max-width: 1024px) {
        .main-content {
            margin-left: 0;
            padding: 1rem;
            padding-top: 80px; /* Account for mobile menu button */
        }
    }

    /* Form Container */
    .form-container {
        max-width: 900px;
        margin: 0 auto;
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-xl);
        overflow: hidden;
        position: relative;
    }

    .form-container::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    }

    .form-header {
        padding: 2rem;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: var(--white);
        text-align: center;
    }

    .form-header h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .form-header p {
        opacity: 0.9;
        font-size: 1.1rem;
    }

    .form-body {
        padding: 2rem;
    }

    .user-info {
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: var(--gray-50);
        border-radius: var(--border-radius);
        border-left: 4px solid var(--primary-color);
    }

    .user-info h4 {
        color: var(--gray-900);
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .user-info p {
        color: var(--gray-500);
        margin: 0;
        font-size: 0.9rem;
    }

    /* Form Controls */
    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--gray-700);
        display: block;
    }

    .form-control,
    .form-select {
        border: 2px solid var(--gray-200);
        border-radius: var(--border-radius);
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: var(--white);
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        outline: none;
    }

    .form-control::placeholder {
        color: var(--gray-500);
    }

    /* Category Groups */
    .category-group {
        margin-bottom: 1.5rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, var(--gray-50), #f0f4ff);
        border-radius: var(--border-radius);
        border: 1px solid var(--gray-200);
        border-left: 4px solid var(--accent-color);
    }

    .category-group .form-label {
        color: var(--primary-color);
        font-weight: 700;
    }

    .hidden {
        display: none;
    }

    /* File Upload Styling */
    .file-upload-container {
        position: relative;
    }

    .file-upload-info {
        font-size: 0.875rem;
        color: var(--gray-500);
        margin-top: 0.25rem;
    }

    /* Button Styling */
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border: none;
        border-radius: var(--border-radius);
        padding: 1rem 2rem;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: var(--shadow-md);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    /* Modal Styling */
    .modal-content {
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-xl);
        border: none;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: var(--white);
        border-bottom: none;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
    }

    .modal-header .btn-close {
        filter: invert(1);
        opacity: 0.8;
    }

    .modal-header .btn-close:hover {
        opacity: 1;
    }

    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    /* Animations */
    .form-container {
        animation: slideInUp 0.6s ease;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Success/Error States */
    .form-control.is-valid {
        border-color: var(--success-color);
    }

    .form-control.is-invalid {
        border-color: var(--danger-color);
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .form-body {
            padding: 1.5rem;
        }
        
        .form-header h1 {
            font-size: 1.5rem;
        }
        
        .user-info {
            padding: 1rem;
        }
        
        .category-group {
            padding: 1rem;
        }
    }
    </style>
</head>

<body>
    <?php include_once '../layout.php'; ?>

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

    <script>
    function toggleProjectType() {
        const projectType = document.getElementById("projectType").value;
        const softwareOptions = document.getElementById("softwareOptions");
        const hardwareOptions = document.getElementById("hardwareOptions");

        // Hide both options first
        softwareOptions.classList.add("hidden");
        hardwareOptions.classList.add("hidden");

        // Show relevant option based on selection
        if (projectType === "software") {
            softwareOptions.classList.remove("hidden");
        } else if (projectType === "hardware") {
            hardwareOptions.classList.remove("hidden");
        }
    }

    // Form validation and enhancement
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        // Add real-time validation
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                }
            });
        });

        // Form submission enhancement
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (input.value.trim() === '') {
                    input.classList.add('is-invalid');
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Scroll to first invalid field
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
            }
        });
    });
    </script>
</body>

</html>