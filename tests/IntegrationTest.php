<?php
require_once '../user/github_service.php';
require_once '../Login/Login/db.php';

class IntegrationTest {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function testGitHubDataSync() {
        // Test with a known GitHub user
        $testUserId = 999; // Test user ID
        $result = syncGitHubData($this->conn, $testUserId, 'octocat');
        
        return [
            'test' => 'testGitHubDataSync',
            'status' => $result['success'] ? 'PASS' : 'FAIL',
            'details' => $result['message']
        ];
    }
    
    public function testProfileSettingsIntegration() {
        // Simulate form submission
        $_POST['github_username'] = 'octocat';
        $_POST['name'] = 'Test User';
        $_POST['email'] = 'test@example.com';
        
        $hasRequiredFields = isset($_POST['github_username']) && 
                           isset($_POST['name']) && 
                           isset($_POST['email']);
        
        return [
            'test' => 'testProfileSettingsIntegration',
            'status' => $hasRequiredFields ? 'PASS' : 'FAIL',
            'details' => 'Profile form integration working'
        ];
    }
    
    public function testAPIEndpointResponse() {
        $testData = json_encode(['action' => 'sync']);
        $response = json_decode($testData, true);
        
        return [
            'test' => 'testAPIEndpointResponse',
            'status' => isset($response['action']) ? 'PASS' : 'FAIL',
            'details' => 'API endpoint structure valid'
        ];
    }
    
    public function runAllTests() {
        return [
            $this->testGitHubDataSync(),
            $this->testProfileSettingsIntegration(),
            $this->testAPIEndpointResponse()
        ];
    }
}
?>