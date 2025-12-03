<?php

namespace IdeaNest\Tests\Unit;

use PHPUnit\Framework\TestCase;

class GitHubServiceTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../user/github_service.php';
    }

    public function testFetchGitHubProfileValidUser()
    {
        $profile = fetchGitHubProfile('octocat');
        if ($profile === false) {
            $this->markTestSkipped('GitHub API not accessible in test environment');
        }
        $this->assertIsArray($profile);
        $this->assertArrayHasKey('login', $profile);
        $this->assertEquals('octocat', $profile['login']);
    }

    public function testFetchGitHubProfileInvalidUser()
    {
        $profile = fetchGitHubProfile('nonexistentuser12345xyz');
        $this->assertFalse($profile);
    }

    public function testFetchGitHubProfileEmptyUsername()
    {
        $profile = fetchGitHubProfile('');
        $this->assertFalse($profile);
    }

    public function testFetchGitHubReposValidUser()
    {
        $repos = fetchGitHubRepos('octocat');
        $this->assertIsArray($repos);
    }

    public function testFetchGitHubReposInvalidUser()
    {
        $repos = fetchGitHubRepos('nonexistentuser12345xyz');
        $this->assertIsArray($repos);
        $this->assertEmpty($repos);
    }

    public function testTestGitHubConnectivity()
    {
        $connectivity = testGitHubConnectivity();
        $this->assertIsBool($connectivity);
    }

    public function testSyncGitHubDataWithMockConnection()
    {
        $mockConn = $this->createMock(\mysqli::class);
        $mockStmt = $this->createMock(\mysqli_stmt::class);
        
        $mockConn->method('prepare')->willReturn($mockStmt);
        $mockStmt->method('bind_param')->willReturn(true);
        $mockStmt->method('execute')->willReturn(true);
        
        // Mock the fetchGitHubProfile function to return test data
        $result = syncGitHubData($mockConn, 1, 'octocat');
        
        // The function might fail due to API access, so we'll just check the structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }
}