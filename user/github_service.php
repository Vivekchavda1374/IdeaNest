<?php
function fetchGitHubProfile($username) {
    $url = "https://api.github.com/users/" . urlencode($username);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: IdeaNest-App',
                'Accept: application/vnd.github.v3+json'
            ]
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    return $response ? json_decode($response, true) : false;
}

function fetchGitHubRepos($username) {
    $url = "https://api.github.com/users/" . urlencode($username) . "/repos?sort=updated&per_page=10";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: IdeaNest-App',
                'Accept: application/vnd.github.v3+json'
            ]
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    return $response ? json_decode($response, true) : false;
}

function syncGitHubData($conn, $user_id, $username) {
    $profile = fetchGitHubProfile($username);
    if (!$profile) {
        return ['success' => false, 'message' => 'GitHub user not found'];
    }
    
    // Update user profile
    $stmt = $conn->prepare("UPDATE register SET 
        github_username = ?, 
        github_profile_url = ?, 
        github_repos_count = ?, 
        github_followers = ?, 
        github_following = ?, 
        github_bio = ?, 
        github_location = ?, 
        github_company = ?, 
        github_last_sync = NOW() 
        WHERE id = ?");
    
    $stmt->bind_param("ssiiiissi", 
        $profile['login'],
        $profile['html_url'],
        $profile['public_repos'],
        $profile['followers'],
        $profile['following'],
        $profile['bio'],
        $profile['location'],
        $profile['company'],
        $user_id
    );
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
    
    // Fetch and store repositories
    $repos = fetchGitHubRepos($username);
    if ($repos) {
        // Clear existing repos
        $stmt = $conn->prepare("DELETE FROM user_github_repos WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Insert new repos
        $stmt = $conn->prepare("INSERT INTO user_github_repos 
            (user_id, repo_name, repo_full_name, repo_description, repo_url, language, stars_count, forks_count, is_private) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($repos as $repo) {
            $stmt->bind_param("isssssiii",
                $user_id,
                $repo['name'],
                $repo['full_name'],
                $repo['description'],
                $repo['html_url'],
                $repo['language'],
                $repo['stargazers_count'],
                $repo['forks_count'],
                $repo['private'] ? 1 : 0
            );
            $stmt->execute();
        }
    }
    
    return ['success' => true, 'message' => 'GitHub profile synced successfully'];
}
?>