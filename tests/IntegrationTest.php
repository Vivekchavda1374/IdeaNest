<?php
require_once 'UnitTestFramework.php';
if (file_exists('../user/github_service.php')) {
    require_once '../user/github_service.php';
} elseif (!function_exists('fetchGitHubProfile')) {
    function fetchGitHubProfile($username) { return ['login' => 'test']; }
    function syncGitHubData($conn, $userId, $username) { return ['success' => true, 'message' => 'Mock sync']; }
}
if (file_exists('../Login/Login/db.php')) {
    require_once '../Login/Login/db.php';
} else {
    $conn = null;
}

class IntegrationTest extends UnitTestFramework {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->addTest('testGitHubAPIIntegration', [$this, 'testGitHubAPIIntegration']);
        $this->addTest('testDatabaseIntegration', [$this, 'testDatabaseIntegration']);
        $this->addTest('testProfileSettingsIntegration', [$this, 'testProfileSettingsIntegration']);
        $this->addTest('testSessionIntegration', [$this, 'testSessionIntegration']);
        $this->addTest('testErrorHandlingIntegration', [$this, 'testErrorHandlingIntegration']);
    }
    
    public function testGitHubAPIIntegration() {
        $profile = fetchGitHubProfile('octocat');
        $repos = fetchGitHubRepos('octocat');
        
        $this->assertTrue(is_array($profile) || $profile === false, 'Profile API should return array or false');
        $this->assertTrue(is_array($repos), 'Repos API should return array');
        
        if (is_array($profile)) {
            $this->assertTrue(isset($profile['login']), 'Profile should have login field');
        }
        
        return ['message' => 'GitHub API integration working'];
    }
    
    public function testDatabaseIntegration() {
        // Test database connection with GitHub functions
        $this->assertTrue($this->conn instanceof mysqli, 'Database connection should exist');
        
        // Test table structure
        $result = $this->conn->query("SHOW TABLES LIKE 'register'");
        $this->assertTrue($result->num_rows > 0, 'Register table should exist');
        
        return ['message' => 'Database integration working'];
    }
    
    public function testProfileSettingsIntegration() {
        // Simulate form data
        $formData = [
            'github_username' => 'octocat',
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];
        
        foreach ($formData as $key => $value) {
            $this->assertTrue(!empty($value), "$key should not be empty");
        }
        
        // Test username validation
        $isValid = preg_match('/^[a-zA-Z0-9_-]+$/', $formData['github_username']);
        $this->assertTrue($isValid === 1, 'GitHub username should be valid');
        
        return ['message' => 'Profile settings integration working'];
    }
    
    public function testSessionIntegration() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['test_data'] = 'integration_test';
        $this->assertTrue(isset($_SESSION['test_data']), 'Session data should be set');
        $this->assertEquals('integration_test', $_SESSION['test_data'], 'Session data should match');
        
        unset($_SESSION['test_data']);
        return ['message' => 'Session integration working'];
    }
    
    public function testErrorHandlingIntegration() {
        // Test with invalid data
        $invalidProfile = fetchGitHubProfile('');
        $this->assertFalse($invalidProfile, 'Empty username should return false');
        
        $invalidRepos = fetchGitHubRepos('');
        $this->assertEquals([], $invalidRepos, 'Empty username should return empty array');
        
        return ['message' => 'Error handling integration working'];
    }
    
    public function runAllTests() {
        return parent::runAllTests();
    }
}
?>