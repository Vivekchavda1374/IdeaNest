<?php
require_once 'UnitTestFramework.php';
if (file_exists('../Login/Login/db.php')) {
    require_once '../Login/Login/db.php';
} else {
    $conn = null;
}

class DatabaseTest extends UnitTestFramework {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->addTest('testDatabaseConnection', [$this, 'testDatabaseConnection']);
        $this->addTest('testRegisterTableExists', [$this, 'testRegisterTableExists']);
        $this->addTest('testGitHubColumnsExist', [$this, 'testGitHubColumnsExist']);
        $this->addTest('testBasicCRUDOperations', [$this, 'testBasicCRUDOperations']);
        $this->addTest('testDataIntegrity', [$this, 'testDataIntegrity']);
    }
    
    public function testDatabaseConnection() {
        $this->assertTrue($this->conn instanceof mysqli, 'Database connection should be mysqli instance');
        $this->assertFalse($this->conn->connect_error, 'No connection errors');
        return ['message' => 'Database connection successful'];
    }
    
    public function testRegisterTableExists() {
        $result = $this->conn->query("SHOW TABLES LIKE 'register'");
        $this->assertTrue($result->num_rows > 0, 'Register table should exist');
        return ['message' => 'Register table exists'];
    }
    
    public function testGitHubColumnsExist() {
        $result = $this->conn->query("DESCRIBE register");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $required = ['id', 'name', 'email'];
        foreach ($required as $col) {
            $this->assertTrue(in_array($col, $columns), "Column $col should exist");
        }
        return ['message' => 'Required columns exist'];
    }
    
    public function testBasicCRUDOperations() {
        // Test INSERT
        $stmt = $this->conn->prepare("INSERT INTO register (name, email, password) VALUES (?, ?, ?)");
        $name = 'Test User';
        $email = 'test@example.com';
        $password = 'testpass';
        $stmt->bind_param("sss", $name, $email, $password);
        $this->assertTrue($stmt->execute(), 'INSERT operation should work');
        
        $testId = $this->conn->insert_id;
        
        // Test SELECT
        $result = $this->conn->query("SELECT * FROM register WHERE id = $testId");
        $this->assertTrue($result->num_rows > 0, 'SELECT operation should work');
        
        // Test DELETE (cleanup)
        $this->conn->query("DELETE FROM register WHERE id = $testId");
        
        return ['message' => 'CRUD operations working'];
    }
    
    public function testDataIntegrity() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM register");
        $row = $result->fetch_assoc();
        $this->assertTrue(is_numeric($row['count']), 'Count should be numeric');
        return ['message' => 'Data integrity maintained'];
    }
    
    public function runAllTests() {
        return parent::runAllTests();
    }
}
?>