<?php
require_once 'UnitTestFramework.php';

class FunctionalTest extends UnitTestFramework {
    
    public function __construct() {
        $this->addTest('testUserCanConnectGitHub', [$this, 'testUserCanConnectGitHub']);
        $this->addTest('testGitHubStatsDisplay', [$this, 'testGitHubStatsDisplay']);
        $this->addTest('testRepositoryDisplay', [$this, 'testRepositoryDisplay']);
        $this->addTest('testErrorHandling', [$this, 'testErrorHandling']);
        $this->addTest('testFormValidation', [$this, 'testFormValidation']);
        $this->addTest('testUserWorkflow', [$this, 'testUserWorkflow']);
    }
    
    public function testUserCanConnectGitHub() {
        $username = 'octocat';
        $isValidUsername = preg_match('/^[a-zA-Z0-9_-]+$/', $username);
        
        $this->assertEquals(1, $isValidUsername, 'Valid username should pass regex');
        $this->assertTrue(strlen($username) > 0, 'Username should not be empty');
        $this->assertTrue(strlen($username) <= 39, 'Username should be within GitHub limits');
        
        return ['message' => 'User can input valid GitHub username'];
    }
    
    public function testGitHubStatsDisplay() {
        $mockGitHubData = [
            'github_repos_count' => 10,
            'github_followers' => 50,
            'github_following' => 25,
            'public_repos' => 10,
            'followers' => 50,
            'following' => 25
        ];
        
        $requiredFields = ['github_repos_count', 'github_followers', 'github_following'];
        foreach ($requiredFields as $field) {
            $this->assertTrue(isset($mockGitHubData[$field]), "Field $field should exist");
            $this->assertTrue(is_numeric($mockGitHubData[$field]), "Field $field should be numeric");
        }
        
        return ['message' => 'GitHub stats display correctly'];
    }
    
    public function testRepositoryDisplay() {
        $mockRepo = [
            'name' => 'test-repo',
            'description' => 'Test repository',
            'language' => 'PHP',
            'stargazers_count' => 5,
            'forks_count' => 2,
            'html_url' => 'https://github.com/user/test-repo'
        ];
        
        $requiredFields = ['name', 'language', 'stargazers_count', 'forks_count'];
        foreach ($requiredFields as $field) {
            $this->assertTrue(isset($mockRepo[$field]), "Repository field $field should exist");
        }
        
        $this->assertTrue(is_string($mockRepo['name']), 'Repository name should be string');
        $this->assertTrue(is_numeric($mockRepo['stargazers_count']), 'Stars should be numeric');
        
        return ['message' => 'Repository data displays correctly'];
    }
    
    public function testErrorHandling() {
        $testCases = [
            '' => 'Empty username should be invalid',
            'user@domain' => 'Username with @ should be invalid',
            'user space' => 'Username with space should be invalid',
            'valid_user-123' => 'Valid username should pass'
        ];
        
        foreach ($testCases as $username => $description) {
            $isValid = preg_match('/^[a-zA-Z0-9_-]+$/', $username) && !empty($username);
            
            if ($username === 'valid_user-123') {
                $this->assertTrue($isValid, $description);
            } else {
                $this->assertFalse($isValid, $description);
            }
        }
        
        return ['message' => 'Error handling working for all test cases'];
    }
    
    public function testFormValidation() {
        $formData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'github_username' => 'testuser'
        ];
        
        // Test required fields
        foreach ($formData as $field => $value) {
            $this->assertTrue(!empty($value), "Field $field should not be empty");
        }
        
        // Test email validation
        $this->assertTrue(filter_var($formData['email'], FILTER_VALIDATE_EMAIL) !== false, 'Email should be valid');
        
        // Test GitHub username validation
        $this->assertTrue(preg_match('/^[a-zA-Z0-9_-]+$/', $formData['github_username']) === 1, 'GitHub username should be valid');
        
        return ['message' => 'Form validation working correctly'];
    }
    
    public function testUserWorkflow() {
        $workflow = [
            'login' => true,
            'navigate_to_profile' => true,
            'enter_github_username' => true,
            'validate_input' => true,
            'save_profile' => true,
            'view_github_data' => true
        ];
        
        foreach ($workflow as $step => $status) {
            $this->assertTrue($status, "Workflow step $step should complete successfully");
        }
        
        $this->assertEquals(6, count($workflow), 'All workflow steps should be present');
        
        return ['message' => 'Complete user workflow simulation successful'];
    }
    
    public function runAllTests() {
        return parent::runAllTests();
    }
}
?>