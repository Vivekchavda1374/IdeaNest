<?php

namespace IdeaNest\Tests\Integration;

use PHPUnit\Framework\TestCase;

class ProjectManagementTest extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        $this->conn = getTestConnection();
        cleanupTestDatabase();
    }

    protected function tearDown(): void
    {
        cleanupTestDatabase();
    }

    public function testProjectSubmissionWorkflow()
    {
        // Insert a test user
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password) VALUES (?, ?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $password = password_hash("password123", PASSWORD_DEFAULT);
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        $userId = $this->conn->insert_id;

        // Submit a project
        $stmt = $this->conn->prepare("INSERT INTO projects (title, description, user_id, project_type, status) VALUES (?, ?, ?, ?, ?)");
        $title = "Test Project";
        $description = "This is a test project description";
        $projectType = "software";
        $status = "pending";
        $stmt->bind_param("ssiss", $title, $description, $userId, $projectType, $status);
        
        $result = $stmt->execute();
        $this->assertTrue($result);
        
        $projectId = $this->conn->insert_id;
        $this->assertGreaterThan(0, $projectId);

        // Verify project was created
        $stmt = $this->conn->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $project = $result->fetch_assoc();

        $this->assertEquals($title, $project['title']);
        $this->assertEquals($description, $project['description']);
        $this->assertEquals($userId, $project['user_id']);
        $this->assertEquals($status, $project['status']);
    }

    public function testProjectApprovalWorkflow()
    {
        // Create test user and project
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password) VALUES (?, ?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $password = password_hash("password123", PASSWORD_DEFAULT);
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        $userId = $this->conn->insert_id;

        $stmt = $this->conn->prepare("INSERT INTO projects (title, description, user_id, status) VALUES (?, ?, ?, ?)");
        $title = "Test Project";
        $description = "This is a test project";
        $status = "pending";
        $stmt->bind_param("ssis", $title, $description, $userId, $status);
        $stmt->execute();
        $projectId = $this->conn->insert_id;

        // Approve the project
        $stmt = $this->conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
        $newStatus = "approved";
        $stmt->bind_param("si", $newStatus, $projectId);
        $result = $stmt->execute();
        
        $this->assertTrue($result);
        $this->assertEquals(1, $stmt->affected_rows);

        // Verify project status was updated
        $stmt = $this->conn->prepare("SELECT status FROM projects WHERE id = ?");
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $project = $result->fetch_assoc();

        $this->assertEquals("approved", $project['status']);
    }

    public function testProjectRejectionWorkflow()
    {
        // Create test user and project
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password) VALUES (?, ?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $password = password_hash("password123", PASSWORD_DEFAULT);
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        $userId = $this->conn->insert_id;

        $stmt = $this->conn->prepare("INSERT INTO projects (title, description, user_id, status) VALUES (?, ?, ?, ?)");
        $title = "Test Project";
        $description = "This is a test project";
        $status = "pending";
        $stmt->bind_param("ssis", $title, $description, $userId, $status);
        $stmt->execute();
        $projectId = $this->conn->insert_id;

        // Reject the project
        $stmt = $this->conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
        $newStatus = "rejected";
        $stmt->bind_param("si", $newStatus, $projectId);
        $result = $stmt->execute();
        
        $this->assertTrue($result);
        $this->assertEquals(1, $stmt->affected_rows);

        // Verify project status was updated
        $stmt = $this->conn->prepare("SELECT status FROM projects WHERE id = ?");
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $project = $result->fetch_assoc();

        $this->assertEquals("rejected", $project['status']);
    }

    public function testProjectFileUpload()
    {
        // Test file upload validation
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip'];
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        
        $testFiles = [
            ['name' => 'test.jpg', 'size' => 1024, 'type' => 'image/jpeg'],
            ['name' => 'test.pdf', 'size' => 2048, 'type' => 'application/pdf'],
            ['name' => 'test.zip', 'size' => 5120, 'type' => 'application/zip']
        ];

        foreach ($testFiles as $file) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $this->assertTrue(in_array($extension, $allowedTypes));
            $this->assertLessThanOrEqual($maxFileSize, $file['size']);
        }
    }

    public function testProjectSearchAndFilter()
    {
        // Create test projects with different types and statuses
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password) VALUES (?, ?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $password = password_hash("password123", PASSWORD_DEFAULT);
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        $userId = $this->conn->insert_id;

        $projects = [
            ['title' => 'Web App', 'description' => 'A web application', 'project_type' => 'software', 'status' => 'approved'],
            ['title' => 'Mobile App', 'description' => 'A mobile application', 'project_type' => 'software', 'status' => 'pending'],
            ['title' => 'IoT Device', 'description' => 'An IoT device', 'project_type' => 'hardware', 'status' => 'approved']
        ];

        foreach ($projects as $project) {
            $stmt = $this->conn->prepare("INSERT INTO projects (title, description, user_id, project_type, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiss", $project['title'], $project['description'], $userId, $project['project_type'], $project['status']);
            $stmt->execute();
        }

        // Test filtering by status
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM projects WHERE status = ?");
        $status = "approved";
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $this->assertEquals(2, $count);

        // Test filtering by project type
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM projects WHERE project_type = ?");
        $type = "software";
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $this->assertEquals(2, $count);
    }

    public function testProjectStatistics()
    {
        // Create test user
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password) VALUES (?, ?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $password = password_hash("password123", PASSWORD_DEFAULT);
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        $userId = $this->conn->insert_id;

        // Create projects with different statuses
        $statuses = ['pending', 'approved', 'rejected'];
        foreach ($statuses as $status) {
            $stmt = $this->conn->prepare("INSERT INTO projects (title, description, user_id, status) VALUES (?, ?, ?, ?)");
            $title = "Test Project " . ucfirst($status);
            $description = "Description for " . $status . " project";
            $stmt->bind_param("ssis", $title, $description, $userId, $status);
            $stmt->execute();
        }

        // Test project count by status
        $stmt = $this->conn->prepare("SELECT status, COUNT(*) as count FROM projects WHERE user_id = ? GROUP BY status");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $statusCounts = [];
        while ($row = $result->fetch_assoc()) {
            $statusCounts[$row['status']] = $row['count'];
        }

        $this->assertEquals(1, $statusCounts['pending']);
        $this->assertEquals(1, $statusCounts['approved']);
        $this->assertEquals(1, $statusCounts['rejected']);
    }
}
