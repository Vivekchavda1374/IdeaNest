<?php

namespace IdeaNest\Tests\Unit;

use PHPUnit\Framework\TestCase;

class FormValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../includes/form_validation.php';
    }

    public function testValidateLoginFormValid()
    {
        $input = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        $result = validateLoginForm($input);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('test@example.com', $result['data']['email']);
    }

    public function testValidateLoginFormInvalidEmail()
    {
        $input = [
            'email' => 'invalid-email',
            'password' => 'password123'
        ];
        
        $result = validateLoginForm($input);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function testValidateLoginFormMissingPassword()
    {
        $input = [
            'email' => 'test@example.com',
            'password' => ''
        ];
        
        $result = validateLoginForm($input);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    public function testValidateRegistrationFormValid()
    {
        $input = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'enrollment_number' => '12345'
        ];
        
        $result = validateRegistrationForm($input);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('John Doe', $result['data']['name']);
    }

    public function testValidateRegistrationFormShortName()
    {
        $input = [
            'name' => 'J',
            'email' => 'john@example.com',
            'password' => 'password123',
            'enrollment_number' => '12345'
        ];
        
        $result = validateRegistrationForm($input);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('name', $result['errors']);
    }

    public function testValidateProjectFormValid()
    {
        $input = [
            'title' => 'My Awesome Project',
            'description' => 'This is a detailed description of my project that explains what it does and how it works.',
            'project_type' => 'software',
            'language' => 'PHP',
            'github_repo' => 'https://github.com/user/repo',
            'live_demo_url' => 'https://example.com',
            'contact_email' => 'contact@example.com'
        ];
        
        $result = validateProjectForm($input);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('My Awesome Project', $result['data']['title']);
    }

    public function testValidateProjectFormShortDescription()
    {
        $input = [
            'title' => 'My Project',
            'description' => 'Short',
            'project_type' => 'software'
        ];
        
        $result = validateProjectForm($input);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('description', $result['errors']);
    }

    public function testValidateBlogFormValid()
    {
        $input = [
            'title' => 'My Blog Post',
            'content' => 'This is a detailed blog post content that explains the topic thoroughly.',
            'projectType' => 'software',
            'classification' => 'web-development'
        ];
        
        $result = validateBlogForm($input);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('My Blog Post', $result['data']['title']);
    }

    public function testFormValidatorClass()
    {
        $validator = new \FormValidator();
        
        $input = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        $rules = [
            'name' => ['required', 'min:2'],
            'email' => ['required', 'email']
        ];
        
        $result = $validator->validate($input, $rules);
        
        $this->assertTrue($result);
        $this->assertFalse($validator->hasErrors());
        $this->assertEmpty($validator->getErrors());
    }

    public function testFormValidatorWithErrors()
    {
        $validator = new \FormValidator();
        
        $input = [
            'name' => '',
            'email' => 'invalid-email'
        ];
        
        $rules = [
            'name' => ['required', 'min:2'],
            'email' => ['required', 'email']
        ];
        
        $result = $validator->validate($input, $rules);
        
        $this->assertFalse($result);
        $this->assertTrue($validator->hasErrors());
        $this->assertArrayHasKey('name', $validator->getErrors());
        $this->assertArrayHasKey('email', $validator->getErrors());
    }
}
