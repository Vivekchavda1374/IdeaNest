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
        
        $mockConn->expects($this->once())
                 ->method('prepare')
                 ->willReturn($mockStmt);
        
        $mockStmt->expects($this->once())
                 ->method('bind_param');
        
        $mockStmt->expects($this->once())
                 ->method('execute')
                 ->willReturn(true);
        
        $result = syncGitHubData($mockConn, 1, 'octocat');
        $this->assertTrue($result['success']);
    }
}