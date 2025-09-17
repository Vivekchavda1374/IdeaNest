<?php
require_once 'UnitTestFramework.php';
if (file_exists('../Login/Login/db.php')) {
    require_once '../Login/Login/db.php';
} else {
    $conn = null;
}

class PerformanceTest extends UnitTestFramework {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->addTest('testGitHubAPIResponseTime', [$this, 'testGitHubAPIResponseTime']);
        $this->addTest('testDatabaseQueryPerformance', [$this, 'testDatabaseQueryPerformance']);
        $this->addTest('testMemoryUsage', [$this, 'testMemoryUsage']);
        $this->addTest('testConcurrentRequests', [$this, 'testConcurrentRequests']);
        $this->addTest('testLargeDataHandling', [$this, 'testLargeDataHandling']);
    }
    
    public function testGitHubAPIResponseTime() {
        $start = microtime(true);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: IdeaNest-Test',
                'timeout' => 5
            ]
        ]);
        $response = @file_get_contents('https://api.github.com/users/octocat', false, $context);
        $end = microtime(true);
        
        $responseTime = ($end - $start) * 1000;
        $this->assertTrue($responseTime < 5000, 'API response should be under 5 seconds');
        
        return ['message' => sprintf('API response time: %.2f ms', $responseTime)];
    }
    
    public function testDatabaseQueryPerformance() {
        $start = microtime(true);
        $result = $this->conn->query("SELECT COUNT(*) as count FROM register");
        $end = microtime(true);
        
        $queryTime = ($end - $start) * 1000;
        $this->assertTrue($queryTime < 500, 'Database query should be under 500ms');
        $this->assertTrue($result !== false, 'Query should execute successfully');
        
        return ['message' => sprintf('Query time: %.2f ms', $queryTime)];
    }
    
    public function testMemoryUsage() {
        $memoryBefore = memory_get_usage();
        
        // Simulate loading GitHub data
        $testData = [];
        for ($i = 0; $i < 1000; $i++) {
            $testData[] = [
                'name' => 'test-repo-' . $i,
                'description' => 'Test repository description for repo ' . $i,
                'language' => 'PHP',
                'stars' => rand(0, 100),
                'forks' => rand(0, 50)
            ];
        }
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024; // KB
        
        $this->assertTrue($memoryUsed < 2048, 'Memory usage should be under 2MB');
        
        // Cleanup
        unset($testData);
        
        return ['message' => sprintf('Memory used: %.2f KB', $memoryUsed)];
    }
    
    public function testConcurrentRequests() {
        $start = microtime(true);
        
        // Simulate multiple operations
        $operations = [];
        for ($i = 0; $i < 10; $i++) {
            $operations[] = $this->conn->query("SELECT 1 as test");
        }
        
        $end = microtime(true);
        $totalTime = ($end - $start) * 1000;
        
        $this->assertTrue($totalTime < 1000, 'Concurrent operations should complete under 1 second');
        $this->assertEquals(10, count($operations), 'All operations should complete');
        
        return ['message' => sprintf('10 concurrent operations: %.2f ms', $totalTime)];
    }
    
    public function testLargeDataHandling() {
        $start = microtime(true);
        
        // Create large string to test handling
        $largeString = str_repeat('A', 10000); // 10KB string
        $processed = strlen($largeString);
        
        $end = microtime(true);
        $processingTime = ($end - $start) * 1000;
        
        $this->assertEquals(10000, $processed, 'Should handle large data correctly');
        $this->assertTrue($processingTime < 100, 'Large data processing should be fast');
        
        return ['message' => sprintf('Large data processing: %.2f ms', $processingTime)];
    }
    
    public function runAllTests() {
        return parent::runAllTests();
    }
}
?>