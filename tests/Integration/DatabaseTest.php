<?php

namespace IdeaNest\Tests\Integration;

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
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

    public function testDatabaseConnection()
    {
        $this->assertInstanceOf(\mysqli::class, $this->conn);
        $this->assertFalse($this->conn->connect_error);
    }

    public function testInsertUser()
    {
        $stmt = $this->conn->prepare("INSERT INTO register (name, email) VALUES (?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $stmt->bind_param("ss", $name, $email);
        
        $result = $stmt->execute();
        $this->assertTrue($result);
        
        $userId = $this->conn->insert_id;
        $this->assertGreaterThan(0, $userId);
    }

    public function testInsertProject()
    {
        // First insert a user
        $stmt = $this->conn->prepare("INSERT INTO register (name, email) VALUES (?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $userId = $this->conn->insert_id;

        // Then insert a project
        $stmt = $this->conn->prepare("INSERT INTO projects (title, description, user_id) VALUES (?, ?, ?)");
        $title = "Test Project";
        $description = "Test Description";
        $stmt->bind_param("ssi", $title, $description, $userId);
        
        $result = $stmt->execute();
        $this->assertTrue($result);
        
        $projectId = $this->conn->insert_id;
        $this->assertGreaterThan(0, $projectId);
    }

    public function testUpdateUserGitHubInfo()
    {
        // Insert user
        $stmt = $this->conn->prepare("INSERT INTO register (name, email) VALUES (?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $userId = $this->conn->insert_id;

        // Update GitHub info
        $stmt = $this->conn->prepare("UPDATE register SET github_username = ?, github_profile_url = ?, github_repos_count = ? WHERE id = ?");
        $username = "testuser";
        $profileUrl = "https://github.com/testuser";
        $reposCount = 5;
        $stmt->bind_param("ssii", $username, $profileUrl, $reposCount, $userId);
        
        $result = $stmt->execute();
        $this->assertTrue($result);
        $this->assertEquals(1, $stmt->affected_rows);
    }

    public function testQueryUserProjects()
    {
        // Insert user and project
        $stmt = $this->conn->prepare("INSERT INTO register (name, email) VALUES (?, ?)");
        $name = "Test User";
        $email = "test@example.com";
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $userId = $this->conn->insert_id;

        $stmt = $this->conn->prepare("INSERT INTO projects (title, description, user_id) VALUES (?, ?, ?)");
        $title = "Test Project";
        $description = "Test Description";
        $stmt->bind_param("ssi", $title, $description, $userId);
        $stmt->execute();

        // Query projects
        $result = $this->conn->query("SELECT * FROM projects WHERE user_id = $userId");
        $this->assertEquals(1, $result->num_rows);
        
        $project = $result->fetch_assoc();
        $this->assertEquals($title, $project['title']);
        $this->assertEquals($description, $project['description']);
    }
}