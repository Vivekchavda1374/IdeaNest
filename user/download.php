<?php
// user/download.php - Corrected for your file structure
session_start();

// Get the requested file
$file_param = isset($_GET['file']) ? $_GET['file'] : '';

if (empty($file_param)) {
    http_response_code(400);
    exit('No file specified');
}

// Sanitize the file path to prevent directory traversal
$file_param = str_replace(['../', '../', '..\\', '..\\\\'], '', $file_param);

// Build the full path - corrected to use forms/uploads/
$base_upload_dir = __DIR__ . '/forms/uploads/';
$file_path = $base_upload_dir . $file_param;

// Additional security: ensure the resolved path is within uploads directory
$real_base_dir = realpath($base_upload_dir);
$real_file_path = realpath($file_path);

if (!$real_file_path || strpos($real_file_path, $real_base_dir) !== 0) {
    http_response_code(403);
    exit('Access denied - invalid path');
}

// Check if file exists
if (!file_exists($real_file_path) || !is_file($real_file_path)) {
    http_response_code(404);
    exit('File not found: ' . $file_param);
}

// Get file info
$file_info = pathinfo($real_file_path);
$file_extension = strtolower($file_info['extension']);
$file_name = $file_info['basename'];

// Define MIME types
$mime_types = [
    'pdf' => 'application/pdf',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    '7z' => 'application/x-7z-compressed',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'mp4' => 'video/mp4',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime'
];

$mime_type = isset($mime_types[$file_extension]) ? $mime_types[$file_extension] : 'application/octet-stream';

// Clear any output buffers
if (ob_get_level()) {
    ob_end_clean();
}

// Set headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($real_file_path));
header('Content-Description: File Transfer');

// For PDFs and images, display inline; for others, force download
if (in_array($file_extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif'])) {
    header('Content-Disposition: inline; filename="' . $file_name . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Output the file
readfile($real_file_path);
exit;
?>