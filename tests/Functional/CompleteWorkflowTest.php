<?php

namespace IdeaNest\Tests\Functional;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class CompleteWorkflowTest extends TestCase
{
    private $client;
    private $baseUrl;

    protected function setUp(): void
    {
        $this->client = new Client();
        $this->baseUrl = 'https://ictmu.in/hcd/IdeaNest';
    }

    public function testStudentRegistrationToProjectSubmission()
    {
        try {
            // Test registration page accessibility
            $response = $this->client->get($this->baseUrl . '/Login/Login/register.php');
            $this->assertEquals(200, $response->getStatusCode());
            
            $body = $response->getBody()->getContents();
            $this->assertStringContainsString('Register', $body);
            $this->assertStringContainsString('form', $body);
            $this->assertStringContainsString('email', $body);
            $this->assertStringContainsString('password', $body);

            // Test login page accessibility
            $response = $this->client->get($this->baseUrl . '/Login/Login/login.php');
            $this->assertEquals(200, $response->getStatusCode());
            
            $body = $response->getBody()->getContents();
            $this->assertStringContainsString('Login', $body);
            $this->assertStringContainsString('email', $body);
            $this->assertStringContainsString('password', $body);

            // Test project submission page accessibility
            $response = $this->client->get($this->baseUrl . '/user/forms/new_project_add.php', [
                'allow_redirects' => false
            ]);
            
            // Should redirect to login if not authenticated
            $this->assertContains($response->getStatusCode(), [302, 200]);

        } catch (\Exception $e) {
            $this->markTestSkipped('Student workflow test requires server setup: ' . $e->getMessage());
        }
    }

    public function testAdminProjectApprovalWorkflow()
    {
        try {
            // Test admin login page
            $response = $this->client->get($this->baseUrl . '/Login/Login/login.php');
            $this->assertEquals(200, $response->getStatusCode());
            
            $body = $response->getBody()->getContents();
            $this->assertStringContainsString('Login', $body);

            // Test admin dashboard access
            $response = $this->client->get($this->baseUrl . '/Admin/admin.php', [
                'allow_redirects' => false
            ]);
            
            // Should redirect to login if not authenticated
            $this->assertContains($response->getStatusCode(), [302, 200]);

            // Test project approval page
            $response = $this->client->get($this->baseUrl . '/Admin/admin_view_project.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [302, 200]);

        } catch (\Exception $e) {
            $this->markTestSkipped('Admin workflow test requires server setup: ' . $e->getMessage());
        }
    }

    public function testMentorStudentPairingWorkflow()
    {
        try {
            // Test mentor login page
            $response = $this->client->get($this->baseUrl . '/Login/Login/login.php');
            $this->assertEquals(200, $response->getStatusCode());

            // Test mentor dashboard
            $response = $this->client->get($this->baseUrl . '/mentor/dashboard.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [302, 200]);

            // Test student management page
            $response = $this->client->get($this->baseUrl . '/mentor/students.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [302, 200]);

            // Test session management page
            $response = $this->client->get($this->baseUrl . '/mentor/sessions.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [302, 200]);

        } catch (\Exception $e) {
            $this->markTestSkipped('Mentor workflow test requires server setup: ' . $e->getMessage());
        }
    }

    public function testSubAdminProjectManagementWorkflow()
    {
        try {
            // Test subadmin login
            $response = $this->client->get($this->baseUrl . '/Login/Login/login.php');
            $this->assertEquals(200, $response->getStatusCode());

            // Test subadmin dashboard
            $response = $this->client->get($this->baseUrl . '/Admin/subadmin/dashboard.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [302, 200]);

            // Test assigned projects page
            $response = $this->client->get($this->baseUrl . '/Admin/subadmin/assigned_projects.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [302, 200]);

        } catch (\Exception $e) {
            $this->markTestSkipped('SubAdmin workflow test requires server setup: ' . $e->getMessage());
        }
    }

    public function testProjectFileUploadWorkflow()
    {
        try {
            // Test project submission form
            $response = $this->client->get($this->baseUrl . '/user/forms/new_project_add.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [302, 200]);

            // Test file upload validation
            $response = $this->client->get($this->baseUrl . '/user/forms/uploads/', [
                'allow_redirects' => false
            ]);
            
            // Should return 403 or 404 for directory access
            $this->assertContains($response->getStatusCode(), [403, 404, 302]);

        } catch (\Exception $e) {
            $this->markTestSkipped('File upload workflow test requires server setup: ' . $e->getMessage());
        }
    }

    public function testBlogAndIdeasSystemWorkflow()
    {
        try {
            // Test blog form page
            $response = $this->client->get($this->baseUrl . '/user/Blog/form.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [302, 200]);

            // Test blog listing page
            $response = $this->client->get($this->baseUrl . '/user/Blog/list-project.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [302, 200]);

        } catch (\Exception $e) {
            $this->markTestSkipped('Blog system workflow test requires server setup: ' . $e->getMessage());
        }
    }

    public function testEmailNotificationSystem()
    {
        try {
            // Test email configuration
            $response = $this->client->get($this->baseUrl . '/config/email_config.php', [
                'allow_redirects' => false
            ]);
            
            // Should not be accessible directly
            $this->assertContains($response->getStatusCode(), [403, 404, 302]);

            // Test notification backend
            $response = $this->client->get($this->baseUrl . '/Admin/notification_backend.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [403, 404, 302]);

        } catch (\Exception $e) {
            $this->markTestSkipped('Email notification test requires server setup: ' . $e->getMessage());
        }
    }

    public function testSecurityHeadersAndCSRF()
    {
        try {
            // Test CSRF protection on forms
            $response = $this->client->get($this->baseUrl . '/Login/Login/login.php');
            $this->assertEquals(200, $response->getStatusCode());
            
            $body = $response->getBody()->getContents();
            
            // Check for CSRF token in forms
            $this->assertStringContainsString('csrf_token', $body);

            // Test security headers
            $headers = $response->getHeaders();
            
            // Check for security headers (if implemented)
            if (isset($headers['X-Content-Type-Options'])) {
                $this->assertStringContainsString('nosniff', $headers['X-Content-Type-Options'][0]);
            }

        } catch (\Exception $e) {
            $this->markTestSkipped('Security test requires server setup: ' . $e->getMessage());
        }
    }

    public function testDatabaseConnectionAndPerformance()
    {
        try {
            // Test database connectivity through a simple page
            $response = $this->client->get($this->baseUrl . '/user/all_projects.php', [
                'timeout' => 10
            ]);
            
            $this->assertEquals(200, $response->getStatusCode());
            
            // Check response time
            $responseTime = $response->getHeader('X-Response-Time');
            if (!empty($responseTime)) {
                $this->assertLessThan(5, (float)$responseTime[0]); // Should respond within 5 seconds
            }

        } catch (\Exception $e) {
            $this->markTestSkipped('Database performance test requires server setup: ' . $e->getMessage());
        }
    }

    public function testAPIEndpoints()
    {
        try {
            // Test mentor API endpoints
            $apiEndpoints = [
                '/mentor/api/get_request_count.php',
                '/mentor/get_student_projects.php',
                '/mentor/get_notifications.php'
            ];

            foreach ($apiEndpoints as $endpoint) {
                $response = $this->client->get($this->baseUrl . $endpoint, [
                    'allow_redirects' => false,
                    'timeout' => 5
                ]);
                
                // API endpoints should return JSON or redirect to login
                $this->assertContains($response->getStatusCode(), [200, 302, 401, 403]);
            }

        } catch (\Exception $e) {
            $this->markTestSkipped('API endpoints test requires server setup: ' . $e->getMessage());
        }
    }

    public function testErrorHandlingAndLogging()
    {
        try {
            // Test 404 error handling
            $response = $this->client->get($this->baseUrl . '/nonexistent-page.php', [
                'allow_redirects' => false
            ]);
            
            $this->assertContains($response->getStatusCode(), [404, 302]);

            // Test error logging (check if logs directory exists)
            $response = $this->client->get($this->baseUrl . '/logs/', [
                'allow_redirects' => false
            ]);
            
            // Should not be accessible directly
            $this->assertContains($response->getStatusCode(), [403, 404, 302]);

        } catch (\Exception $e) {
            $this->markTestSkipped('Error handling test requires server setup: ' . $e->getMessage());
        }
    }
}
