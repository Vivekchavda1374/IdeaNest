<?php
class PerformanceTest {
    
    public function testGitHubAPIResponseTime() {
        $start = microtime(true);
        $response = @file_get_contents('https://api.github.com/users/octocat');
        $end = microtime(true);
        
        $responseTime = ($end - $start) * 1000; // Convert to milliseconds
        
        return [
            'test' => 'testGitHubAPIResponseTime',
            'status' => $responseTime < 2000 ? 'PASS' : 'FAIL', // Under 2 seconds
            'details' => sprintf('Response time: %.2f ms', $responseTime)
        ];
    }
    
    public function testDatabaseQueryPerformance() {
        global $conn;
        
        $start = microtime(true);
        $result = $conn->query("SELECT COUNT(*) FROM register WHERE github_username IS NOT NULL");
        $end = microtime(true);
        
        $queryTime = ($end - $start) * 1000;
        
        return [
            'test' => 'testDatabaseQueryPerformance',
            'status' => $queryTime < 100 ? 'PASS' : 'FAIL', // Under 100ms
            'details' => sprintf('Query time: %.2f ms', $queryTime)
        ];
    }
    
    public function testMemoryUsage() {
        $memoryBefore = memory_get_usage();
        
        // Simulate loading GitHub data
        $testData = array_fill(0, 100, [
            'name' => 'test-repo',
            'description' => 'Test repository',
            'language' => 'PHP'
        ]);
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024; // KB
        
        return [
            'test' => 'testMemoryUsage',
            'status' => $memoryUsed < 1024 ? 'PASS' : 'FAIL', // Under 1MB
            'details' => sprintf('Memory used: %.2f KB', $memoryUsed)
        ];
    }
    
    public function runAllTests() {
        return [
            $this->testGitHubAPIResponseTime(),
            $this->testDatabaseQueryPerformance(),
            $this->testMemoryUsage()
        ];
    }
}
?>