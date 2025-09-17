<?php
class UnitTestFramework {
    private $tests = [];
    private $results = [];
    
    public function addTest($testName, $callback) {
        $this->tests[$testName] = $callback;
    }
    
    public function runTest($testName) {
        try {
            $result = call_user_func($this->tests[$testName]);
            $this->results[] = [
                'test' => $testName,
                'status' => 'PASS',
                'details' => $result['message'] ?? 'Test passed',
                'execution_time' => $result['time'] ?? 0
            ];
        } catch (Exception $e) {
            $this->results[] = [
                'test' => $testName,
                'status' => 'FAIL',
                'details' => $e->getMessage(),
                'execution_time' => 0
            ];
        }
    }
    
    public function runAllTests() {
        foreach ($this->tests as $testName => $callback) {
            $this->runTest($testName);
        }
        return $this->results;
    }
    
    public function assert($condition, $message = 'Assertion failed') {
        if (!$condition) {
            throw new Exception($message);
        }
        return ['message' => $message];
    }
    
    public function assertEquals($expected, $actual, $message = 'Values not equal') {
        if ($expected !== $actual) {
            throw new Exception("$message. Expected: $expected, Got: $actual");
        }
        return ['message' => $message];
    }
    
    public function assertTrue($condition, $message = 'Condition is not true') {
        return $this->assert($condition === true, $message);
    }
    
    public function assertFalse($condition, $message = 'Condition is not false') {
        return $this->assert($condition === false, $message);
    }
}
?>