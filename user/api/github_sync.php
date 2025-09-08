<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../../Login/Login/db.php';
include '../github_service.php';

$user_id = $_SESSION['user_id'];
$github_service = new GitHubService($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'sync':
            // Get current GitHub username
            $github_data = $github_service->getUserGitHubData($user_id);
            if (empty($github_data['github_username'])) {
                echo json_encode(['success' => false, 'message' => 'No GitHub username configured']);
                exit();
            }
            
            $result = $github_service->syncUserGitHub($user_id, $github_data['github_username']);
            echo json_encode($result);
            break;
            
        case 'connect':
            $username = trim($input['username'] ?? '');
            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Username is required']);
                exit();
            }
            
            $result = $github_service->syncUserGitHub($user_id, $username);
            echo json_encode($result);
            break;
            
        case 'disconnect':
            // Clear GitHub data
            $stmt = $conn->prepare("UPDATE register SET 
                github_username = NULL, 
                github_profile_url = NULL, 
                github_repos_count = 0, 
                github_followers = 0, 
                github_following = 0, 
                github_bio = NULL, 
                github_location = NULL, 
                github_company = NULL, 
                github_last_sync = NULL 
                WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                // Clear repositories
                $stmt = $conn->prepare("DELETE FROM user_github_repos WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'GitHub account disconnected']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to disconnect GitHub account']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    // GET request - return current GitHub data
    $github_data = $github_service->getUserGitHubData($user_id);
    $github_repos = $github_service->getUserRepos($user_id);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'profile' => $github_data,
            'repos' => $github_repos
        ]
    ]);
}
?>