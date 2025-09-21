<?php
// Start session at the beginning of the script
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../../Login/Login/db.php';
require_once '../../includes/csrf.php';

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
    requireCSRF();
    try {
        // Get user_id from session - Keep as string to match database expectations
        $user_id = $_SESSION['user_id'];

        // Sanitize and validate inputs
        $title = htmlspecialchars(trim($_POST['title'] ?? ''));
        $project_type = in_array($_POST['project_type'] ?? '', ['software', 'hardware']) ? $_POST['project_type'] : '';
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $language = htmlspecialchars(trim($_POST['language'] ?? ''));

        // Validate required fields
        if (empty($title)) {
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
        function uploadFile($file, $folder, $allowedTypes = [], $maxSize = 5 * 1024 * 1024)
        {
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
            $title,
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
            throw new Exception("Database execution error: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
        error_log("Project submission error: " . $e->getMessage());
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $message = "Project submitted successfully! Your project is now under review.";
    $messageType = "success";
}
?>