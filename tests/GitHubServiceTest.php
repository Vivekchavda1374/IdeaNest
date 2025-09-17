<?php
require_once 'UnitTestFramework.php';
if (file_exists('../user/github_service.php')) {
    require_once '../user/github_service.php';
} elseif (!function_exists('fetchGitHubProfile')) {
    function fetchGitHubProfile($username) { return ['login' => 'test']; }
    function fetchGitHubRepos($username) { return []; }
    function syncGitHubData($conn, $userId, $username) { return ['success' => true, 'message' => 'Mock sync']; }
}

class GitHubServiceTest extends UnitTestFramework {
    
    public function __construct() {
        $this->addTest('testFetchGitHubProfile', [$this, 'testFetchGitHubProfile']);
        $this->addTest('testFetchGitHubProfileInvalid', [$this, 'testFetchGitHubProfileInvalid']);
        $this->addTest('testFetchGitHubRepos', [$this, 'testFetchGitHubRepos']);
        $this->addTest('testEmptyUsername', [$this, 'testEmptyUsername']);
    }
    
    public function testFetchGitHubProfile() {
        $profile = fetchGitHubProfile('octocat');
        $this->assertTrue(is_array($profile), 'Profile should be an array');
        $this->assertTrue(isset($profile['login']), 'Profile should have login field');
        return ['message' => 'GitHub profile fetched successfully'];
    }
    
    public function testFetchGitHubProfileInvalid() {
        $profile = fetchGitHubProfile('invalid-user-999999');
        $this->assertFalse($profile, 'Invalid user should return false');
        return ['message' => 'Invalid user correctly handled'];
    }
    
    public function testFetchGitHubRepos() {
        $repos = fetchGitHubRepos('octocat');
        $this->assertTrue(is_array($repos), 'Repos should be an array');
        return ['message' => count($repos) . ' repositories found'];
    }
    
    public function testEmptyUsername() {
        $profile = fetchGitHubProfile('');
        $this->assertFalse($profile, 'Empty username should return false');
        
        $repos = fetchGitHubRepos('');
        $this->assertEquals([], $repos, 'Empty username should return empty array for repos');
        return ['message' => 'Empty username validation working'];
    }
    
    public function runAllTests() {
        return parent::runAllTests();
    }
}
?>