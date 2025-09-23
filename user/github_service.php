<?php

function fetchGitHubProfile($username)
{
    if (empty($username)) {
        return false;
    }

    $url = "https://api.github.com/users/" . urlencode($username);

    // Try cURL first (preferred method)
    if (function_exists("curl_init")) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "IdeaNest-App");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false && $httpCode === 200) {
            $data = json_decode($response, true);
            return $data && !isset($data["message"]) ? $data : false;
        }
    }

    // Fallback to file_get_contents
    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: IdeaNest-App",
            "timeout" => 10
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false;
    }

    $data = json_decode($response, true);
    return $data && !isset($data["message"]) ? $data : false;
}

function fetchGitHubRepos($username)
{
    if (empty($username)) {
        return [];
    }

    $url = "https://api.github.com/users/" . urlencode($username) . "/repos";

    // Try cURL first
    if (function_exists("curl_init")) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "IdeaNest-App");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false && $httpCode === 200) {
            $data = json_decode($response, true);
            return is_array($data) ? $data : [];
        }
    }

    // Fallback to file_get_contents
    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: IdeaNest-App",
            "timeout" => 10
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return [];
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : [];
}

function syncGitHubData($conn, $userId, $username)
{
    try {
        $profile = fetchGitHubProfile($username);
        if (!$profile) {
            return ["success" => false, "message" => "Failed to fetch GitHub profile"];
        }

        $stmt = $conn->prepare("UPDATE register SET github_username = ?, github_profile_url = ?, github_repos_count = ? WHERE id = ?");
        $stmt->bind_param("ssii", $username, $profile["html_url"], $profile["public_repos"], $userId);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "GitHub data synced successfully"];
        } else {
            return ["success" => false, "message" => "Database update failed"];
        }
    } catch (Exception $e) {
        return ["success" => false, "message" => $e->getMessage()];
    }
}

// Test GitHub connectivity
function testGitHubConnectivity()
{
    $testUser = "octocat";
    $profile = fetchGitHubProfile($testUser);
    return $profile !== false;
}
