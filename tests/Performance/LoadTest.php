<?php

namespace IdeaNest\Tests\Performance;

use PHPUnit\Framework\TestCase;

class LoadTest extends TestCase
{
    public function testDatabaseQueryPerformance()
    {
        $conn = getTestConnection();
        
        // Insert test data
        for ($i = 0; $i < 100; $i++) {
            $stmt = $conn->prepare("INSERT INTO register (name, email) VALUES (?, ?)");
            $name = "User $i";
            $email = "user$i@example.com";
            $stmt->bind_param("ss", $name, $email);
            $stmt->execute();
        }
        
        $startTime = microtime(true);
        
        // Test query performance
        $result = $conn->query("SELECT * FROM register LIMIT 50");
        $users = $result->fetch_all(MYSQLI_ASSOC);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(0.1, $executionTime, 'Query should execute in less than 100ms');
        $this->assertCount(50, $users, 'Should return 50 users');
        
        // Cleanup
        $conn->query("TRUNCATE TABLE register");
    }

    public function testGitHubAPIResponseTime()
    {
        require_once __DIR__ . '/../../user/github_service.php';
        
        $startTime = microtime(true);
        $profile = fetchGitHubProfile('octocat');
        $endTime = microtime(true);
        
        $responseTime = $endTime - $startTime;
        
        $this->assertLessThan(5.0, $responseTime, 'GitHub API should respond within 5 seconds');
        $this->assertIsArray($profile, 'Should return valid profile data');
    }

    public function testMemoryUsage()
    {
        $initialMemory = memory_get_usage();
        
        // Simulate processing large dataset
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = [
                'id' => $i,
                'name' => "User $i",
                'email' => "user$i@example.com",
                'projects' => range(1, 10)
            ];
        }
        
        $peakMemory = memory_get_peak_usage();
        $memoryUsed = $peakMemory - $initialMemory;
        
        // Should use less than 10MB for this operation
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'Memory usage should be under 10MB');
        
        unset($data);
    }

    public function testConcurrentOperations()
    {
        require_once __DIR__ . '/../../user/github_service.php';
        
        $startTime = microtime(true);
        
        // Simulate concurrent GitHub API calls
        $profiles = [];
        $users = ['octocat', 'defunkt', 'pjhyett'];
        
        foreach ($users as $user) {
            $profiles[$user] = fetchGitHubProfile($user);
            usleep(100000); // 0.1 second delay to avoid rate limiting
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        $this->assertLessThan(10.0, $totalTime, 'Concurrent operations should complete within 10 seconds');
        $this->assertCount(3, $profiles, 'Should fetch all profiles');
        
        foreach ($profiles as $profile) {
            $this->assertIsArray($profile, 'Each profile should be valid');
        }
    }
}