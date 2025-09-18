<?php
/**
 * Comprehensive Form Validation
 * Validates all form inputs with proper sanitization
 */

class FormValidator {
    private $errors = [];
    private $data = [];
    
    public function validate($input, $rules) {
        $this->errors = [];
        $this->data = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? "";
            $this->data[$field] = $this->validateField($field, $value, $fieldRules);
        }
        
        return empty($this->errors);
    }
    
    private function validateField($field, $value, $rules) {
        $value = trim($value);
        
        // Required validation
        if (in_array("required", $rules) && empty($value)) {
            $this->errors[$field] = ucfirst($field) . " is required";
            return "";
        }
        
        if (empty($value)) {
            return "";
        }
        
        // Email validation
        if (in_array("email", $rules)) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "Invalid email format";
                return "";
            }
            return filter_var($value, FILTER_SANITIZE_EMAIL);
        }
        
        // URL validation
        if (in_array("url", $rules)) {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                $this->errors[$field] = "Invalid URL format";
                return "";
            }
            return filter_var($value, FILTER_SANITIZE_URL);
        }
        
        // Integer validation
        if (in_array("integer", $rules)) {
            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                $this->errors[$field] = "Must be a valid number";
                return "";
            }
            return (int)$value;
        }
        
        // String length validation
        foreach ($rules as $rule) {
            if (strpos($rule, "max:") === 0) {
                $max = (int)substr($rule, 4);
                if (strlen($value) > $max) {
                    $this->errors[$field] = ucfirst($field) . " must be less than $max characters";
                    return "";
                }
            }
            
            if (strpos($rule, "min:") === 0) {
                $min = (int)substr($rule, 4);
                if (strlen($value) < $min) {
                    $this->errors[$field] = ucfirst($field) . " must be at least $min characters";
                    return "";
                }
            }
        }
        
        // Default sanitization
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, "UTF-8");
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getData() {
        return $this->data;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : "";
    }
}

// Helper functions for common validations
function validateLoginForm($input) {
    $validator = new FormValidator();
    $rules = [
        "email" => ["required", "email"],
        "password" => ["required", "min:6"]
    ];
    
    if ($validator->validate($input, $rules)) {
        return ["success" => true, "data" => $validator->getData()];
    }
    
    return ["success" => false, "errors" => $validator->getErrors()];
}

function validateRegistrationForm($input) {
    $validator = new FormValidator();
    $rules = [
        "name" => ["required", "min:2", "max:100"],
        "email" => ["required", "email"],
        "password" => ["required", "min:8"],
        "enrollment_number" => ["required", "min:5", "max:50"]
    ];
    
    if ($validator->validate($input, $rules)) {
        return ["success" => true, "data" => $validator->getData()];
    }
    
    return ["success" => false, "errors" => $validator->getErrors()];
}

function validateProjectForm($input) {
    $validator = new FormValidator();
    $rules = [
        "title" => ["required", "min:5", "max:255"],
        "description" => ["required", "min:20", "max:2000"],
        "project_type" => ["required"],
        "language" => ["max:200"],
        "github_repo" => ["url"],
        "live_demo_url" => ["url"],
        "contact_email" => ["email"]
    ];
    
    if ($validator->validate($input, $rules)) {
        return ["success" => true, "data" => $validator->getData()];
    }
    
    return ["success" => false, "errors" => $validator->getErrors()];
}

function validateBlogForm($input) {
    $validator = new FormValidator();
    $rules = [
        "title" => ["required", "min:5", "max:255"],
        "content" => ["required", "min:20", "max:5000"],
        "projectType" => ["required"],
        "classification" => ["required"]
    ];
    
    if ($validator->validate($input, $rules)) {
        return ["success" => true, "data" => $validator->getData()];
    }
    
    return ["success" => false, "errors" => $validator->getErrors()];
}
?>