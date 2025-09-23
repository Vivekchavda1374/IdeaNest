<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'github_service.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

switch ($input['action']) {
    case 'refresh_profile':
        refreshProfile($conn, $user_id);
        break;
    
    case 'connect_github':
        connectGitHub($conn, $user_id, $input);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}

function refreshProfile($conn, $user_id) {
    try {
        // Get current GitHub username
        $stmt = $conn->prepare("SELECT github_username FROM register WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        
        if (!$user_data || empty($user_data['github_username'])) {
            echo json_encode(['success' => false, 'message' => 'No GitHub username found']);
            return;
        }
        
        $username = $user_data['github_username'];
        
        // Fetch fresh GitHub data
        $profile = fetchGitHubProfile($username);
        if (!$profile) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch GitHub profile']);
            return;
        }
        
        // Update database with fresh data including followers
        $stmt = $conn->prepare("UPDATE register SET github_profile_url = ?, github_repos_count = ?, github_followers = ?, github_following = ?, github_last_sync = NOW() WHERE id = ?");
        $stmt->bind_param("siiii", $profile['html_url'], $profile['public_repos'], $profile['followers'], $profile['following'], $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'GitHub profile refreshed successfully',
                'data' => [
                    'repos_count' => $profile['public_repos'],
                    'followers' => $profile['followers'],
                    'following' => $profile['following']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function connectGitHub($conn, $user_id, $input) {
    try {
        if (!isset($input['username']) || empty($input['username'])) {
            echo json_encode(['success' => false, 'message' => 'Username is required']);
            return;
        }
        
        $username = trim($input['username']);
        
        // Validate GitHub username format
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-])*[a-zA-Z0-9]$/', $username) && !preg_match('/^[a-zA-Z0-9]$/', $username)) {
            echo json_encode(['success' => false, 'message' => 'Invalid GitHub username format']);
            return;
        }
        
        // Fetch GitHub profile to validate username
        $profile = fetchGitHubProfile($username);
        if (!$profile) {
            echo json_encode(['success' => false, 'message' => 'GitHub user not found or API error']);
            return;
        }
        
        // Update database with all profile data
        $stmt = $conn->prepare("UPDATE register SET github_username = ?, github_profile_url = ?, github_repos_count = ?, github_followers = ?, github_following = ?, github_last_sync = NOW() WHERE id = ?");
        $stmt->bind_param("ssiiii", $username, $profile['html_url'], $profile['public_repos'], $profile['followers'], $profile['following'], $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'GitHub profile connected successfully',
                'data' => [
                    'username' => $username,
                    'profile_url' => $profile['html_url'],
                    'repos_count' => $profile['public_repos']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save GitHub profile']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>