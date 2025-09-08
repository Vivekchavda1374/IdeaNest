<?php
require_once '../Login/Login/db.php';

class DatabaseTest {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function testGitHubColumnsExist() {
        $result = $this->conn->query("DESCRIBE register");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $required = ['github_username', 'github_profile_url', 'github_repos_count'];
        $missing = array_diff($required, $columns);
        
        return [
            'test' => 'testGitHubColumnsExist',
            'status' => empty($missing) ? 'PASS' : 'FAIL',
            'details' => empty($missing) ? 'All GitHub columns exist' : 'Missing: ' . implode(', ', $missing)
        ];
    }
    
    public function testGitHubReposTableExists() {
        $result = $this->conn->query("SHOW TABLES LIKE 'user_github_repos'");
        $exists = $result->num_rows > 0;
        
        return [
            'test' => 'testGitHubReposTableExists',
            'status' => $exists ? 'PASS' : 'FAIL',
            'details' => $exists ? 'user_github_repos table exists' : 'Table missing'
        ];
    }
    
    public function testForeignKeyConstraint() {
        $result = $this->conn->query("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'user_github_repos' AND CONSTRAINT_NAME LIKE '%fk%'");
        $hasForeignKey = $result->num_rows > 0;
        
        return [
            'test' => 'testForeignKeyConstraint',
            'status' => $hasForeignKey ? 'PASS' : 'FAIL',
            'details' => $hasForeignKey ? 'Foreign key constraint exists' : 'No foreign key found'
        ];
    }
    
    public function runAllTests() {
        return [
            $this->testGitHubColumnsExist(),
            $this->testGitHubReposTableExists(),
            $this->testForeignKeyConstraint()
        ];
    }
}
?>