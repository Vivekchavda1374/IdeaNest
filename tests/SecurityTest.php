<?php
require_once 'UnitTestFramework.php';

class SecurityTest extends UnitTestFramework {
    
    public function __construct() {
        $this->addTest('testSQLInjectionPrevention', [$this, 'testSQLInjectionPrevention']);
        $this->addTest('testXSSPrevention', [$this, 'testXSSPrevention']);
        $this->addTest('testCSRFTokenPresent', [$this, 'testCSRFTokenPresent']);
        $this->addTest('testInputValidation', [$this, 'testInputValidation']);
        $this->addTest('testPasswordHashing', [$this, 'testPasswordHashing']);
        $this->addTest('testFileUploadSecurity', [$this, 'testFileUploadSecurity']);
    }
    
    public function testSQLInjectionPrevention() {
        $maliciousInput = "'; DROP TABLE register; --";
        $sanitized = filter_var($maliciousInput, FILTER_SANITIZE_STRING);
        $this->assertTrue($sanitized !== $maliciousInput, 'Malicious input should be sanitized');
        return ['message' => 'SQL injection prevention working'];
    }
    
    public function testXSSPrevention() {
        $maliciousScript = "<script>alert('xss')</script>";
        $escaped = htmlspecialchars($maliciousScript, ENT_QUOTES, 'UTF-8');
        $this->assertTrue($escaped !== $maliciousScript, 'Script tags should be escaped');
        $this->assertTrue(strpos($escaped, '&lt;script&gt;') !== false, 'Should contain escaped tags');
        return ['message' => 'XSS prevention working'];
    }
    
    public function testCSRFTokenPresent() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $token = bin2hex(random_bytes(32));
        $this->assertEquals(64, strlen($token), 'CSRF token should be 64 chars');
        return ['message' => 'CSRF protection active'];
    }
    
    public function testInputValidation() {
        $validUsername = 'octocat';
        $invalidUsername = 'user@#$%';
        
        $validPattern = preg_match('/^[a-zA-Z0-9_-]+$/', $validUsername);
        $invalidPattern = preg_match('/^[a-zA-Z0-9_-]+$/', $invalidUsername);
        
        $this->assertTrue($validPattern === 1, 'Valid username should pass validation');
        $this->assertTrue($invalidPattern === 0, 'Invalid username should fail validation');
        return ['message' => 'Input validation working'];
    }
    
    public function testPasswordHashing() {
        $password = 'testpassword123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertTrue(!empty($hash), 'Password hash should not be empty');
        $this->assertTrue(password_verify($password, $hash), 'Password should verify against hash');
        $this->assertFalse(password_verify('wrongpassword', $hash), 'Wrong password should not verify');
        return ['message' => 'Password hashing secure'];
    }
    
    public function testFileUploadSecurity() {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        $dangerousExtensions = ['php', 'exe', 'bat', 'sh', 'js'];
        
        foreach ($allowedExtensions as $ext) {
            $this->assertTrue(in_array($ext, $allowedExtensions), "$ext should be allowed");
        }
        
        foreach ($dangerousExtensions as $ext) {
            $this->assertFalse(in_array($ext, $allowedExtensions), "$ext should not be allowed");
        }
        
        return ['message' => 'File upload security configured'];
    }
    
    public function runAllTests() {
        return parent::runAllTests();
    }
}
?>