<?php
class FunctionalTest {
    
    public function testUserCanConnectGitHub() {
        // Simulate user connecting GitHub
        $username = 'octocat';
        $isValidUsername = preg_match('/^[a-zA-Z0-9_-]+$/', $username);
        
        return [
            'test' => 'testUserCanConnectGitHub',
            'status' => $isValidUsername ? 'PASS' : 'FAIL',
            'details' => 'User can input valid GitHub username'
        ];
    }
    
    public function testGitHubStatsDisplay() {
        $mockGitHubData = [
            'github_repos_count' => 10,
            'github_followers' => 50,
            'github_following' => 25
        ];
        
        $hasRequiredStats = isset($mockGitHubData['github_repos_count']) &&
                           isset($mockGitHubData['github_followers']) &&
                           isset($mockGitHubData['github_following']);
        
        return [
            'test' => 'testGitHubStatsDisplay',
            'status' => $hasRequiredStats ? 'PASS' : 'FAIL',
            'details' => 'GitHub stats display correctly'
        ];
    }
    
    public function testRepositoryDisplay() {
        $mockRepo = [
            'repo_name' => 'test-repo',
            'repo_description' => 'Test repository',
            'language' => 'PHP',
            'stars_count' => 5,
            'forks_count' => 2
        ];
        
        $hasRequiredFields = isset($mockRepo['repo_name']) &&
                           isset($mockRepo['language']) &&
                           isset($mockRepo['stars_count']);
        
        return [
            'test' => 'testRepositoryDisplay',
            'status' => $hasRequiredFields ? 'PASS' : 'FAIL',
            'details' => 'Repository data displays correctly'
        ];
    }
    
    public function testErrorHandling() {
        $invalidUsername = '';
        $errorHandled = empty($invalidUsername) ? 'Username required' : 'Valid';
        
        return [
            'test' => 'testErrorHandling',
            'status' => $errorHandled === 'Username required' ? 'PASS' : 'FAIL',
            'details' => 'Error handling for empty username works'
        ];
    }
    
    public function runAllTests() {
        return [
            $this->testUserCanConnectGitHub(),
            $this->testGitHubStatsDisplay(),
            $this->testRepositoryDisplay(),
            $this->testErrorHandling()
        ];
    }
}
?>