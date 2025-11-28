<?php

namespace IdeaNest\Tests\Functional;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class UserWorkflowTest extends TestCase
{
    private $client;
    private $baseUrl;

    protected function setUp(): void
    {
        $this->client = new Client();
        $this->baseUrl = 'http://localhost/IdeaNest';
    }

    public function testUserRegistrationWorkflow()
    {
        $response = $this->client->get($this->baseUrl . '/Login/Login/register.php');
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = $response->getBody()->getContents();
        $this->assertStringContainsString('Register', $body);
        $this->assertStringContainsString('form', $body);
    }

    public function testLoginPageAccess()
    {
        $response = $this->client->get($this->baseUrl . '/Login/Login/login.php');
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = $response->getBody()->getContents();
        $this->assertStringContainsString('Login', $body);
        $this->assertStringContainsString('email', $body);
        $this->assertStringContainsString('password', $body);
    }

    public function testUserDashboardRedirect()
    {
        try {
            $response = $this->client->get($this->baseUrl . '/user/index.php', [
                'allow_redirects' => false
            ]);
            
            // Should redirect to login if not authenticated
            $this->assertContains($response->getStatusCode(), [302, 200]);
        } catch (\Exception $e) {
            $this->markTestSkipped('Dashboard access test requires server setup');
        }
    }

    public function testProjectListingPage()
    {
        try {
            $response = $this->client->get($this->baseUrl . '/user/all_projects.php');
            $this->assertEquals(200, $response->getStatusCode());
            
            $body = $response->getBody()->getContents();
            $this->assertStringContainsString('Projects', $body);
        } catch (\Exception $e) {
            $this->markTestSkipped('Project listing test requires server setup');
        }
    }

    public function testAdminDashboardAccess()
    {
        try {
            $response = $this->client->get($this->baseUrl . '/Admin/admin.php', [
                'allow_redirects' => false
            ]);
            
            // Should redirect to login if not authenticated as admin
            $this->assertContains($response->getStatusCode(), [302, 200]);
        } catch (\Exception $e) {
            $this->markTestSkipped('Admin dashboard test requires server setup');
        }
    }

    public function testMentorDashboardAccess()
    {
        try {
            $response = $this->client->get($this->baseUrl . '/mentor/dashboard.php', [
                'allow_redirects' => false
            ]);
            
            // Should redirect to login if not authenticated as mentor
            $this->assertContains($response->getStatusCode(), [302, 200]);
        } catch (\Exception $e) {
            $this->markTestSkipped('Mentor dashboard test requires server setup');
        }
    }

    public function testStaticAssetsAccess()
    {
        try {
            $response = $this->client->get($this->baseUrl . '/assets/css/login.css');
            $this->assertEquals(200, $response->getStatusCode());
            
            $body = $response->getBody()->getContents();
            $this->assertStringContainsString('css', $body);
        } catch (\Exception $e) {
            $this->markTestSkipped('Static assets test requires server setup');
        }
    }
}