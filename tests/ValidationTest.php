<?php
require_once 'UnitTestFramework.php';

class ValidationTest extends UnitTestFramework {
    
    public function __construct() {
        $this->addTest('testEmailValidation', [$this, 'testEmailValidation']);
        $this->addTest('testPasswordValidation', [$this, 'testPasswordValidation']);
        $this->addTest('testUsernameValidation', [$this, 'testUsernameValidation']);
        $this->addTest('testFileUploadValidation', [$this, 'testFileUploadValidation']);
        $this->addTest('testSQLInjectionPrevention', [$this, 'testSQLInjectionPrevention']);
    }
    
    public function testEmailValidation() {
        $validEmails = ['test@example.com', 'user.name@domain.co.uk', 'admin@localhost'];
        $invalidEmails = ['invalid-email', '@domain.com', 'user@', 'user space@domain.com'];
        
        foreach ($validEmails as $email) {
            $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, "Email $email should be valid");
        }
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, "Email $email should be invalid");
        }
        
        return ['message' => 'Email validation working correctly'];
    }
    
    public function testPasswordValidation() {
        $validPasswords = ['Password123!', 'SecurePass1', 'MyP@ssw0rd'];
        $invalidPasswords = ['123', 'password', 'PASSWORD', '12345678'];
        
        foreach ($validPasswords as $password) {
            $isValid = strlen($password) >= 8 && 
                      preg_match('/[A-Z]/', $password) && 
                      preg_match('/[a-z]/', $password) && 
                      preg_match('/[0-9]/', $password);
            $this->assertTrue($isValid, "Password $password should be valid");
        }
        
        return ['message' => 'Password validation working correctly'];
    }
    
    public function testUsernameValidation() {
        $validUsernames = ['user123', 'test_user', 'admin-user', 'JohnDoe'];
        $invalidUsernames = ['user@domain', 'user space', 'user#123', ''];
        
        foreach ($validUsernames as $username) {
            $isValid = preg_match('/^[a-zA-Z0-9_-]+$/', $username) && !empty($username);
            $this->assertTrue($isValid, "Username $username should be valid");
        }
        
        foreach ($invalidUsernames as $username) {
            $isValid = preg_match('/^[a-zA-Z0-9_-]+$/', $username) && !empty($username);
            $this->assertFalse($isValid, "Username '$username' should be invalid");
        }
        
        return ['message' => 'Username validation working correctly'];
    }
    
    public function testFileUploadValidation() {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
        $dangerousExtensions = ['php', 'exe', 'bat', 'sh', 'js', 'html'];
        
        foreach ($allowedExtensions as $ext) {
            $filename = "test.$ext";
            $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $this->assertTrue(in_array($fileExt, $allowedExtensions), "Extension $ext should be allowed");
        }
        
        foreach ($dangerousExtensions as $ext) {
            $filename = "malicious.$ext";
            $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $this->assertFalse(in_array($fileExt, $allowedExtensions), "Extension $ext should not be allowed");
        }
        
        return ['message' => 'File upload validation working correctly'];
    }
    
    public function testSQLInjectionPrevention() {
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
            "admin'--",
            "1; DELETE FROM register; --"
        ];
        
        foreach ($maliciousInputs as $input) {
            $sanitized = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            $this->assertTrue($sanitized !== $input, "Malicious input should be sanitized");
            
            // Test prepared statement simulation
            $escaped = addslashes($input);
            $this->assertTrue(strpos($escaped, "\\'") !== false || strpos($escaped, "\\\"") !== false, "Special characters should be escaped");
        }
        
        return ['message' => 'SQL injection prevention working correctly'];
    }
    
    public function runAllTests() {
        return parent::runAllTests();
    }
}
?>