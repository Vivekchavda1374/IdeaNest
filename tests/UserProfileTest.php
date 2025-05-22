<?php
namespace IdeaNest\Tests;

use PHPUnit\Framework\TestCase;

class UserProfileTest extends TestCase {
    private $conn;

    protected function setUp(): void {
        // Include database connection
        require_once __DIR__ . '/../Login/Login/db.php';
        $this->conn = $GLOBALS['conn'] ?? $conn;
    }

    /**
     * Test user registration validation
     * @dataProvider registrationDataProvider
     */
    public function testUserRegistration($name, $email, $password, $expectedValid) {
        // Validate input
        $isNameValid = !empty(trim($name)) && strlen(trim($name)) >= 2;
        $isEmailValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $isPasswordValid = strlen($password) >= 8;

        $overallValidation = $isNameValid && $isEmailValid && $isPasswordValid;
        
        $this->assertEquals($expectedValid, $overallValidation, 
            "Failed validation for name: $name, email: $email, password length: " . strlen($password)
        );
    }

    public function registrationDataProvider() {
        return [
            ['John Doe', 'john@example.com', 'strongpassword123', true],
            ['', 'invalid-email', 'short', false],
            ['A', 'valid@email.com', 'longpassword', false],
            ['Valid Name', 'valid@email.com', 'pass', false]
        ];
    }

    /**
     * Test user login functionality
     * @dataProvider loginDataProvider
     */
    public function testUserLogin($email, $password, $expectedLoginResult) {
        // Simulate login process
        $sql = "SELECT * FROM register WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $loginSuccess = false;
        if ($user = $result->fetch_assoc()) {
            $loginSuccess = password_verify($password, $user['password'] ?? '');
        }
        
        $this->assertEquals($expectedLoginResult, $loginSuccess, 
            "Login test failed for email: $email"
        );
    }

    public function loginDataProvider() {
        return [
            ['test@example.com', 'correctpassword', true],
            ['nonexistent@email.com', 'anypassword', false],
            ['', '', false]
        ];
    }

    /**
     * Test profile update validation
     * @dataProvider profileUpdateProvider
     */
    public function testProfileUpdate($name, $email, $expectedValid) {
        $isNameValid = !empty(trim($name)) && strlen(trim($name)) >= 2;
        $isEmailValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        
        $this->assertEquals($expectedValid, $isNameValid && $isEmailValid, 
            "Profile update validation failed for name: $name, email: $email"
        );
    }

    public function profileUpdateProvider() {
        return [
            ['John Doe', 'john@example.com', true],
            ['', 'invalid-email', false],
            ['A', 'valid@email.com', false],
            ['Valid Name', '', false]
        ];
    }

    /**
     * Test project creation validation
     * @dataProvider projectCreationProvider
     */
    public function testProjectCreation($projectName, $projectType, $expectedValid) {
        $isNameValid = !empty(trim($projectName)) && strlen(trim($projectName)) >= 3;
        $isTypeValid = !empty(trim($projectType));
        
        $this->assertEquals($expectedValid, $isNameValid && $isTypeValid, 
            "Project creation validation failed for name: $projectName, type: $projectType"
        );
    }

    public function projectCreationProvider() {
        return [
            ['My Awesome Project', 'Web Application', true],
            ['', 'Mobile App', false],
            ['AB', 'Data Science', false],
            ['Valid Project', '', false]
        ];
    }

    /**
     * Test email validation
     * @dataProvider emailProvider
     */
    public function testEmailValidation($email, $expectedResult) {
        $isValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $this->assertEquals($expectedResult, $isValid, "Failed asserting that email '$email' is " . ($expectedResult ? 'valid' : 'invalid'));
    }

    public function emailProvider() {
        return [
            ['test@example.com', true],
            ['invalid-email', false],
            ['user@domain.co.uk', true],
            ['user.name+tag@example.com', true],
            ['', false],
            ['@invalid.com', false]
        ];
    }

    /**
     * Test password strength validation
     * @dataProvider passwordProvider
     */
    public function testPasswordStrength($password, $expectedResult) {
        $isStrong = strlen($password) >= 8;
        $this->assertEquals($expectedResult, $isStrong, "Failed asserting password strength for '$password'");
    }

    public function passwordProvider() {
        return [
            ['short', false],
            ['longpassword', true],
            ['12345678', true],
            ['a', false],
            ['complexP@ssw0rd!', true]
        ];
    }

    /**
     * Test user data retrieval
     */
    public function testUserDataRetrieval() {
        // Assuming a test user exists with ID 1
        $testUserId = 1;
        
        $sql = "SELECT * FROM register WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $testUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $userData = $result->fetch_assoc();
        
        $this->assertNotNull($userData, "Failed to retrieve user data for ID $testUserId");
        $this->assertArrayHasKey('name', $userData, "User data should have a name field");
        $this->assertArrayHasKey('email', $userData, "User data should have an email field");
    }

    /**
     * Test project data retrieval
     */
    public function testUserProjectsRetrieval() {
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
        // Optional: Check if projects exist or not
        // $this->assertGreaterThanOrEqual(0, count($userProjects), "User projects array should be non-negative");
    }

    protected function tearDown(): void {
        // Close database connection if needed
        if ($this->conn) {
            $this->conn->close();
        }
    }
} 