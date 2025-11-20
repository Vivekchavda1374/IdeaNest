<?php
/**
 * Secure File Download Handler
 * Replaces the old download.php with security improvements
 */

session_start();
require_once '../Login/Login/db.php';
require_once '../config/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Unauthorized access');
}

// Get file parameter
$fileParam = $_GET['file'] ?? '';

if (empty($fileParam)) {
    http_response_code(400);
    die('No file specified');
}

// Decode and sanitize file path
$filePath = base64_decode($fileParam);

// Define allowed base directories
$allowedDirs = [
    realpath(__DIR__ . '/../uploads/'),
    realpath(__DIR__ . '/../user/uploads/'),
    realpath(__DIR__ . '/../Admin/uploads/')
];

// Validate file path
$realPath = realpath($filePath);
$isValid = false;

foreach ($allowedDirs as $allowedDir) {
    if ($allowedDir && $realPath && strpos($realPath, $allowedDir) === 0) {
        $isValid = true;
        break;
    }
}

if (!$isValid) {
    http_response_code(403);
    die('Invalid file path');
}

// Check if file exists
if (!file_exists($realPath)) {
    http_response_code(404);
    die('File not found');
}

// Check file permissions
if (!is_readable($realPath)) {
    http_response_code(403);
    die('File not readable');
}

// Get file info
$fileName = basename($realPath);
$fileSize = filesize($realPath);
$mimeType = mime_content_type($realPath);

// Set headers for download
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Read and output file
readfile($realPath);
exit;
?>
