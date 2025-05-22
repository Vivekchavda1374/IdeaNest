<?php
namespace IdeaNest\Tests;

use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase {
    private $conn;

    protected function setUp(): void {
        // Include database connection
        require_once __DIR__ . '/../Login/Login/db.php';
        $this->conn = $GLOBALS['conn'] ?? $conn;
    }

    /**
     * Test password hashing
     * @dataProvider passwordHashProvider
     */
    public function testPasswordHashing($password) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Verify the hashed password
        $this->assertTrue(
            password_verify($password, $hashedPassword), 
            "Password hashing and verification failed for password: $password"
        );
    }

    public function passwordHashProvider() {
        return [
            ['strongpassword123'],
            ['complexP@ssw0rd!'],
            ['12345678'],
            ['verylongpasswordthatexceedsminimumrequirements']
        ];
    }

    /**
     * Test password strength validation
     * @dataProvider passwordStrengthProvider
     */
    public function testPasswordStrength($password, $expectedStrength) {
        $strengthChecks = [
            'length' => strlen($password) >= 8,
            'uppercase' => preg_match('/[A-Z]/', $password),
            'lowercase' => preg_match('/[a-z]/', $password),
            'number' => preg_match('/[0-9]/', $password),
            'special' => preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)
        ];

        $strengthScore = 0;
        foreach ($strengthChecks as $check) {
            if ($check) $strengthScore++;
        }
        
        $this->assertEquals($expectedStrength, $strengthScore, 
            "Password strength validation failed for password: $password"
        );
    }

    public function passwordStrengthProvider() {
        return [
            ['short', 1],
            ['longpassword', 2],
            ['Longpassword', 3],
            ['Longpassword1', 4],
            ['Longpassword1!', 5]
        ];
    }

    /**
     * Test email uniqueness
     * @dataProvider emailUniqueProvider
     */
    public function testEmailUniqueness($email) {
        $sql = "SELECT COUNT(*) as count FROM register WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $this->assertLessThanOrEqual(1, $row['count'], 
            "Email uniqueness check failed for email: $email"
        );
    }

    public function emailUniqueProvider() {
        return [
            ['test@example.com'],
            ['user@domain.com'],
            ['unique@email.com']
        ];
    }

    /**
     * Test session management simulation
     */
    public function testSessionManagement() {
        // Simulate session start and user ID setting
        $userId = 1;
        $userName = 'TestUser';

        $sessionData = [
            'user_id' => $userId,
            'user_name' => $userName
        ];

        $this->assertArrayHasKey('user_id', $sessionData, "Session should contain user ID");
        $this->assertArrayHasKey('user_name', $sessionData, "Session should contain user name");
        $this->assertEquals($userId, $sessionData['user_id'], "User ID mismatch in session");
        $this->assertEquals($userName, $sessionData['user_name'], "User name mismatch in session");
    }

    protected function tearDown(): void {
        // Close database connection if needed
        if ($this->conn) {
            $this->conn->close();
        }
    }
} 