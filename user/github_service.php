<?php
function fetchGitHubProfile($username) {
    if (empty($username)) {
        return false;
    }
    
    $url = "https://api.github.com/users/" . urlencode($username);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: IdeaNest-App',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    return $data && !isset($data['message']) ? $data : false;
}

function fetchGitHubRepos($username) {
    if (empty($username)) {
        return [];
    }
    
    $url = "https://api.github.com/users/" . urlencode($username) . "/repos";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: IdeaNest-App',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return [];
    }
    
    $data = json_decode($response, true);
    return is_array($data) ? $data : [];
}

function syncGitHubData($conn, $userId, $username) {
    try {
        $profile = fetchGitHubProfile($username);
        if (!$profile) {
            return ['success' => false, 'message' => 'Failed to fetch GitHub profile'];
        }
        
        $stmt = $conn->prepare("UPDATE register SET github_username = ?, github_profile_url = ?, github_repos_count = ? WHERE id = ?");
        $stmt->bind_param("ssii", $username, $profile['html_url'], $profile['public_repos'], $userId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'GitHub data synced successfully'];
        } else {
            return ['success' => false, 'message' => 'Database update failed'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>