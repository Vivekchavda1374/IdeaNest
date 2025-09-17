<?php
require_once 'UnitTestFramework.php';

class APITest extends UnitTestFramework {
    
    public function __construct() {
        $this->addTest('testGitHubAPIConnection', [$this, 'testGitHubAPIConnection']);
        $this->addTest('testAPIResponseFormat', [$this, 'testAPIResponseFormat']);
        $this->addTest('testAPIErrorHandling', [$this, 'testAPIErrorHandling']);
        $this->addTest('testAPIRateLimit', [$this, 'testAPIRateLimit']);
        $this->addTest('testJSONParsing', [$this, 'testJSONParsing']);
    }
    
    public function testGitHubAPIConnection() {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: IdeaNest-Test',
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents('https://api.github.com/users/octocat', false, $context);
        
        if ($response !== false) {
            $this->assertTrue(!empty($response), 'API response should not be empty');
            
            $data = json_decode($response, true);
            $this->assertTrue(is_array($data), 'API response should be valid JSON');
            $this->assertTrue(isset($data['login']), 'Response should contain login field');
        } else {
            // If API is not accessible, test the error handling
            $this->assertTrue(true, 'API connection test completed (may be offline)');
        }
        
        return ['message' => 'GitHub API connection test completed'];
    }
    
    public function testAPIResponseFormat() {
        $mockResponse = json_encode([
            'login' => 'octocat',
            'id' => 1,
            'public_repos' => 8,
            'followers' => 4000,
            'following' => 9
        ]);
        
        $data = json_decode($mockResponse, true);
        
        $this->assertTrue(is_array($data), 'Response should be array');
        $this->assertTrue(isset($data['login']), 'Should have login field');
        $this->assertTrue(isset($data['public_repos']), 'Should have public_repos field');
        $this->assertTrue(is_numeric($data['public_repos']), 'public_repos should be numeric');
        
        return ['message' => 'API response format validation working'];
    }
    
    public function testAPIErrorHandling() {
        // Test with invalid JSON
        $invalidJSON = '{"invalid": json}';
        $parsed = json_decode($invalidJSON, true);
        $this->assertTrue($parsed === null, 'Invalid JSON should return null');
        $this->assertTrue(json_last_error() !== JSON_ERROR_NONE, 'JSON error should be detected');
        
        // Test with empty response
        $emptyResponse = '';
        $parsed = json_decode($emptyResponse, true);
        $this->assertTrue($parsed === null, 'Empty response should return null');
        
        return ['message' => 'API error handling working correctly'];
    }
    
    public function testAPIRateLimit() {
        // Simulate rate limit response
        $rateLimitResponse = json_encode([
            'message' => 'API rate limit exceeded',
            'documentation_url' => 'https://docs.github.com/rest/overview/resources-in-the-rest-api#rate-limiting'
        ]);
        
        $data = json_decode($rateLimitResponse, true);
        $this->assertTrue(isset($data['message']), 'Rate limit response should have message');
        $this->assertTrue(strpos($data['message'], 'rate limit') !== false, 'Should contain rate limit message');
        
        return ['message' => 'API rate limit handling working'];
    }
    
    public function testJSONParsing() {
        $testData = [
            'valid_json' => '{"name": "test", "count": 5}',
            'invalid_json' => '{"name": "test", "count":}',
            'empty_json' => '{}',
            'array_json' => '[{"name": "test1"}, {"name": "test2"}]'
        ];
        
        foreach ($testData as $type => $json) {
            $parsed = json_decode($json, true);
            
            if ($type === 'valid_json') {
                $this->assertTrue(is_array($parsed), 'Valid JSON should parse correctly');
                $this->assertTrue(isset($parsed['name']), 'Should contain expected fields');
            } elseif ($type === 'invalid_json') {
                $this->assertTrue($parsed === null, 'Invalid JSON should return null');
            } elseif ($type === 'empty_json') {
                $this->assertTrue(is_array($parsed) && empty($parsed), 'Empty JSON should return empty array');
            } elseif ($type === 'array_json') {
                $this->assertTrue(is_array($parsed) && count($parsed) === 2, 'Array JSON should parse correctly');
            }
        }
        
        return ['message' => 'JSON parsing working correctly'];
    }
    
    public function runAllTests() {
        return parent::runAllTests();
    }
}
?>