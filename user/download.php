<?php
// Include security configuration
require_once dirname(__DIR__) . '/config/security.php';
require_once dirname(__DIR__) . '/includes/error_handler.php';

// Set security headers
setSecurityHeaders();

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
if (!isset($_SESSION['user_name']) || empty($_SESSION['user_name'])) {
    http_response_code(401);
    die('Unauthorized access');
}

// Rate limiting
if (!checkRateLimit('file_download', 30, 60)) {
    http_response_code(429);
    die('Too many download requests');
}

// Get and validate file parameter
$file = $_GET['file'] ?? '';
if (empty($file)) {
    http_response_code(400);
    die('File parameter required');
}

// Sanitize file path
$file = basename($file); // Prevent directory traversal

// Determine the correct file path based on file location
$possible_paths = [
    __DIR__ . '/forms/uploads/instructions/' . $file,
    __DIR__ . '/forms/uploads/presentations/' . $file,
    __DIR__ . '/forms/uploads/additional/' . $file,
    __DIR__ . '/forms/uploads/code_files/' . $file,
    __DIR__ . '/forms/uploads/images/' . $file,
    __DIR__ . '/forms/uploads/videos/' . $file,
    __DIR__ . '/uploads/instructions/' . $file,
    __DIR__ . '/uploads/presentations/' . $file,
    __DIR__ . '/uploads/additional/' . $file,
    __DIR__ . '/uploads/code_files/' . $file,
    __DIR__ . '/uploads/images/' . $file,
    __DIR__ . '/uploads/videos/' . $file
];

$file_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path) && is_readable($path)) {
        $file_path = $path;
        break;
    }
}

if (!$file_path) {
    http_response_code(404);
    die('File not found');
}



// Validate file extension
$allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'mp4', 'avi', 'mov'];
$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    http_response_code(403);
    die('File type not allowed');
}

// Set appropriate headers
$mime_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    'mp4' => 'video/mp4',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime'
];

$mime_type = $mime_types[$file_extension] ?? 'application/octet-stream';

// Set download headers
header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: public, max-age=3600');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Output file
readfile($file_path);
exit();
?>