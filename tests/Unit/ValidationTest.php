<?php

namespace IdeaNest\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../includes/validation.php';
    }

    public function testSanitizeInputString()
    {
        $input = '<script>alert("xss")</script>Hello';
        $result = sanitizeInput($input);
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Hello', $result);
    }

    public function testSanitizeInputEmail()
    {
        $input = 'test@example.com<script>';
        $result = sanitizeInput($input, 'email');
        $this->assertEquals('test@example.com', $result);
    }

    public function testValidateEmailValid()
    {
        $result = validateInput('test@example.com', 'email', true);
        $this->assertTrue($result['valid']);
        $this->assertEquals('test@example.com', $result['value']);
    }

    public function testValidateEmailInvalid()
    {
        $result = validateInput('invalid-email', 'email', true);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid email format', $result['message']);
    }

    public function testValidateRequiredFieldEmpty()
    {
        $result = validateInput('', 'string', true);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Field is required', $result['message']);
    }

    public function testSanitizeInputArray()
    {
        $input = ['<script>test</script>', 'normal text'];
        $result = sanitizeInput($input);
        $this->assertEquals(['&lt;script&gt;test&lt;/script&gt;', 'normal text'], $result);
    }
}