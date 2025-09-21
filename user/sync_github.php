<?php
/**
 * GitHub Sync API Endpoint
 * Handles GitHub profile synchronization
 */

session_start();
header('Content-Type: application/json');

require_once '../Login/Login/db.php';
require_once 'github_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $github_username = trim($input['username'] ?? '');
    
    if (empty($github_username)) {
        echo json_encode(['success' => false, 'message' => 'GitHub username is required']);
        exit();
    }
    
    // Validate GitHub username format
    if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]){0,38}$/', $github_username)) {
        echo json_encode(['success' => false, 'message' => 'Invalid GitHub username format']);
        exit();
    }
    
    // Fetch GitHub profile
    $github_profile = fetchGitHubProfile($github_username);
    
    if (!$github_profile) {
        echo json_encode(['success' => false, 'message' => 'GitHub username not found']);
        exit();
    }
    
    // Update user's GitHub information
    $stmt = $conn->prepare("UPDATE register SET github_username = ?, github_profile_url = ?, github_repos_count = ?, github_last_sync = NOW() WHERE id = ?");
    $stmt->bind_param("ssii", $github_username, $github_profile['html_url'], $github_profile['public_repos'], $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'GitHub profile synced successfully',
            'data' => [
                'username' => $github_username,
                'profile_url' => $github_profile['html_url'],
                'repos_count' => $github_profile['public_repos'],
                'avatar_url' => $github_profile['avatar_url'],
                'name' => $github_profile['name'] ?? $github_profile['login'],
                'bio' => $github_profile['bio'] ?? '',
                'followers' => $github_profile['followers'],
                'following' => $github_profile['following']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update database']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>