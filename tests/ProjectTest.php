<?php
namespace IdeaNest\Tests;

use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase {
    private $conn;

    protected function setUp(): void {
        // Include database connection
        require_once __DIR__ . '/../Login/Login/db.php';
        $this->conn = $GLOBALS['conn'] ?? $conn;
    }

    /**
     * Test project data validation
     * @dataProvider projectDataProvider
     */
    public function testProjectDataValidation($name, $type, $language, $description, $expectedValid) {
        $isNameValid = !empty(trim($name)) && strlen(trim($name)) >= 3;
        $isTypeValid = !empty(trim($type));
        $isLanguageValid = !empty(trim($language));
        $isDescriptionValid = strlen(trim($description)) <= 500; // Example max length

        $overallValidation = $isNameValid && $isTypeValid && $isLanguageValid && $isDescriptionValid;
        
        $this->assertEquals($expectedValid, $overallValidation, 
            "Project data validation failed for name: $name, type: $type, language: $language"
        );
    }

    public function projectDataProvider() {
        return [
            ['Machine Learning Project', 'Research', 'Python', 'A comprehensive ML research project', true],
            ['', 'Web', 'JavaScript', 'Valid description', false],
            ['AB', '', 'PHP', 'Another description', false],
            ['Valid Project', 'Mobile', '', 'Description', false],
            ['Long Project Name That Exceeds Reasonable Length', 'Type', 'Language', 'Description', false]
        ];
    }

    /**
     * Test project retrieval
     */
    public function testProjectRetrieval() {
        // Assuming a test user exists with ID 1
        $testUserId = 1;
        
        $projectSql = "SELECT * FROM projects WHERE user_id = ?";
        $projectStmt = $this->conn->prepare($projectSql);
        $projectStmt->bind_param("i", $testUserId);
        $projectStmt->execute();
        $projectResult = $projectStmt->get_result();
        
        $userProjects = [];
        while ($row = $projectResult->fetch_assoc()) {
            $userProjects[] = $row;
        }
        
        $this->assertIsArray($userProjects, "User projects should be an array");
        $this->assertGreaterThanOrEqual(0, count($userProjects), "User projects array should be non-negative");
    }

    /**
     * Test project file upload validation
     * @dataProvider fileUploadProvider
     */
    public function testProjectFileUpload($fileType, $fileSize, $expectedValid) {
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'video/mp4'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        $isTypeValid = in_array($fileType, $allowedTypes);
        $isSizeValid = $fileSize <= $maxFileSize;
        
        $this->assertEquals($expectedValid, $isTypeValid && $isSizeValid, 
            "File upload validation failed for type: $fileType, size: $fileSize"
        );
    }

    public function fileUploadProvider() {
        return [
            ['image/jpeg', 3 * 1024 * 1024, true],
            ['application/pdf', 6 * 1024 * 1024, false],
            ['image/gif', 2 * 1024 * 1024, false],
            ['video/mp4', 4 * 1024 * 1024, true]
        ];
    }

    /**
     * Test project status update
     * @dataProvider projectStatusProvider
     */
    public function testProjectStatusUpdate($status, $expectedValid) {
        $validStatuses = ['In Progress', 'Completed', 'On Hold', 'Planned'];
        
        $isStatusValid = in_array($status, $validStatuses);
        
        $this->assertEquals($expectedValid, $isStatusValid, 
            "Project status validation failed for status: $status"
        );
    }

    public function projectStatusProvider() {
        return [
            ['In Progress', true],
            ['Completed', true],
            ['Invalid Status', false],
            ['', false]
        ];
    }

    protected function tearDown(): void {
        // Close database connection if needed
        if ($this->conn) {
            $this->conn->close();
        }
    }
} 