<?php
require_once __DIR__ . '/../../includes/security_init.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../../Login/Login/db.php';

// Get classification from request
$classification = $_GET['classification'] ?? '';

if (empty($classification)) {
    echo json_encode(['reviewers' => []]);
    exit();
}

// Domain mapping - same as in assigned_projects.php
$domain_mapping = [
    // Software classifications
    'web' => ['Web Development', 'Web Application', 'Web'],
    'mobile' => ['Mobile Development', 'Mobile Application', 'Mobile'],
    'ai_ml' => ['AI/ML', 'AI & Machine Learning', 'Artificial Intelligence & Machine Learning', 'Data Science', 'Data Science & Analytics'],
    'desktop' => ['Desktop Application', 'Desktop'],
    'system' => ['System Software'],
    'embedded_iot' => ['IoT', 'IoT Projects', 'Internet of Things (IoT)', 'Embedded Systems', 'Embedded/IoT Software', 'Embedded'],
    'cybersecurity' => ['Cybersecurity'],
    'game' => ['Game Development'],
    'data_science' => ['Data Science', 'Data Science & Analytics'],
    'cloud' => ['Cloud-Based Applications', 'Cloud'],
    
    // Hardware classifications
    'embedded' => ['Embedded Systems', 'Embedded/IoT Software', 'Embedded'],
    'iot' => ['IoT', 'IoT Projects', 'Internet of Things (IoT)', 'Embedded/IoT Software'],
    'robotics' => ['Robotics'],
    'automation' => ['Automation'],
    'sensor' => ['Sensor-Based Projects', 'Embedded/IoT Software'],
    'communication' => ['Communication Systems'],
    'power' => ['Power Electronics'],
    'wearable' => ['Wearable Technology'],
    'mechatronics' => ['Mechatronics'],
    'renewable' => ['Renewable Energy']
];

// Get matching domain keywords
$matching_domains = $domain_mapping[$classification] ?? [];

if (empty($matching_domains)) {
    echo json_encode(['reviewers' => []]);
    exit();
}

// Build query to find subadmins with matching domains
$where_conditions = [];
$params = [];
$types = '';

foreach ($matching_domains as $domain) {
    $where_conditions[] = "LOWER(domains) LIKE ?";
    $params[] = "%" . strtolower($domain) . "%";
    $types .= 's';
}

$sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email, domains, status 
        FROM subadmins 
        WHERE status = 'active' AND (" . implode(' OR ', $where_conditions) . ")
        ORDER BY id ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$reviewers = [];
while ($row = $result->fetch_assoc()) {
    $reviewers[] = [
        'id' => $row['id'],
        'name' => trim($row['name']) ?: 'Reviewer',
        'email' => $row['email'],
        'domains' => $row['domains']
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode(['reviewers' => $reviewers]);
