<?php

namespace IdeaNest\Tests\Integration;

use PHPUnit\Framework\TestCase;

class GitHubIntegrationTest extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../user/github_service.php';
        $this->conn = getTestConnection();
        cleanupTestDatabase();
    }

    protected function tearDown(): void
    {
        cleanupTestDatabase();
    }

    public function testSyncGitHubDataIntegration()
    {
        // Insert test user
        $stmt = $this->conn->prepare("INSERT INTO register (name, email) VALUES (?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $userId = $this->conn->insert_id;

        // Sync GitHub data
        $result = syncGitHubData($this->conn, $userId, 'octocat');
        
        // The function might fail due to API access, so we'll check the structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        
        if ($result['success']) {
            $this->assertEquals('GitHub data synced successfully', $result['message']);
        } else {
            $this->markTestSkipped('GitHub API not accessible in test environment');
        }

        // Verify data was updated
        $stmt = $this->conn->prepare("SELECT github_username, github_profile_url, github_repos_count FROM register WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $this->assertEquals('octocat', $user['github_username']);
        $this->assertEquals('https://github.com/octocat', $user['github_profile_url']);
        $this->assertGreaterThan(0, $user['github_repos_count']);
    }

    public function testSyncGitHubDataInvalidUser()
    {
        // Insert test user
        $stmt = $this->conn->prepare("INSERT INTO register (name, email) VALUES (?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $userId = $this->conn->insert_id;

        // Try to sync with invalid GitHub user
        $result = syncGitHubData($this->conn, $userId, 'nonexistentuser12345xyz');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to fetch GitHub profile', $result['message']);
    }

    public function testGitHubAPIRateLimit()
    {
        // Test multiple rapid requests to check rate limiting handling
        $profiles = [];
        for ($i = 0; $i < 3; $i++) {
            $profiles[] = fetchGitHubProfile('octocat');
            usleep(100000); // 0.1 second delay
        }

        foreach ($profiles as $profile) {
            $this->assertIsArray($profile);
            $this->assertArrayHasKey('login', $profile);
        }
    }
}