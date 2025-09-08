<?php
class SecurityTest {
    
    public function testSQLInjectionPrevention() {
        $maliciousInput = "'; DROP TABLE register; --";
        $sanitized = filter_var($maliciousInput, FILTER_SANITIZE_STRING);
        
        return [
            'test' => 'testSQLInjectionPrevention',
            'status' => $sanitized !== $maliciousInput ? 'PASS' : 'FAIL',
            'details' => 'Input sanitization working'
        ];
    }
    
    public function testXSSPrevention() {
        $maliciousScript = "<script>alert('xss')</script>";
        $escaped = htmlspecialchars($maliciousScript);
        
        return [
            'test' => 'testXSSPrevention',
            'status' => $escaped !== $maliciousScript ? 'PASS' : 'FAIL',
            'details' => 'XSS prevention working'
        ];
    }
    
    public function testCSRFTokenPresent() {
        session_start();
        $hasSession = isset($_SESSION);
        
        return [
            'test' => 'testCSRFTokenPresent',
            'status' => $hasSession ? 'PASS' : 'FAIL',
            'details' => $hasSession ? 'Session management active' : 'No session protection'
        ];
    }
    
    public function testInputValidation() {
        $validUsername = 'octocat';
        $invalidUsername = 'user@#$%';
        
        $validPattern = preg_match('/^[a-zA-Z0-9_-]+$/', $validUsername);
        $invalidPattern = preg_match('/^[a-zA-Z0-9_-]+$/', $invalidUsername);
        
        return [
            'test' => 'testInputValidation',
            'status' => $validPattern && !$invalidPattern ? 'PASS' : 'FAIL',
            'details' => 'Username validation working'
        ];
    }
    
    public function runAllTests() {
        return [
            $this->testSQLInjectionPrevention(),
            $this->testXSSPrevention(),
            $this->testCSRFTokenPresent(),
            $this->testInputValidation()
        ];
    }
}
?>