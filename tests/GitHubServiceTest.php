<?php
require_once '../user/github_service.php';

class GitHubServiceTest {
    
    public function testFetchGitHubProfile() {
        $profile = fetchGitHubProfile('octocat');
        return [
            'test' => 'fetchGitHubProfile',
            'status' => is_array($profile) && isset($profile['login']) ? 'PASS' : 'FAIL',
            'details' => is_array($profile) ? 'Valid profile data returned' : 'Failed to fetch profile'
        ];
    }
    
    public function testFetchGitHubProfileInvalid() {
        $profile = fetchGitHubProfile('invalid-user-12345');
        return [
            'test' => 'fetchGitHubProfileInvalid',
            'status' => $profile === false ? 'PASS' : 'FAIL',
            'details' => $profile === false ? 'Correctly returns false for invalid user' : 'Should return false'
        ];
    }
    
    public function testFetchGitHubRepos() {
        $repos = fetchGitHubRepos('octocat');
        return [
            'test' => 'fetchGitHubRepos',
            'status' => is_array($repos) && count($repos) > 0 ? 'PASS' : 'FAIL',
            'details' => is_array($repos) ? count($repos) . ' repositories found' : 'Failed to fetch repos'
        ];
    }
    
    public function runAllTests() {
        return [
            $this->testFetchGitHubProfile(),
            $this->testFetchGitHubProfileInvalid(),
            $this->testFetchGitHubRepos()
        ];
    }
}
?>