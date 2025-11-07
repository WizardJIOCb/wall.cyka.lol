<?php
/**
 * Wall Social Platform - Input Validator
 * 
 * Validates and sanitizes user input
 */

namespace App\Utils;

class Validator
{
    private $data;
    private $errors = [];
    private $rules = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Validate data against rules
     */
    public function validate($rules)
    {
        $this->rules = $rules;
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Apply validation rule
     */
    private function applyRule($field, $value, $rule)
    {
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $ruleParam = $parts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "$field is required");
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "$field must be a valid email address");
                }
                break;

            case 'min':
                if (!empty($value) && strlen($value) < $ruleParam) {
                    $this->addError($field, "$field must be at least $ruleParam characters");
                }
                break;

            case 'max':
                if (!empty($value) && strlen($value) > $ruleParam) {
                    $this->addError($field, "$field must not exceed $ruleParam characters");
                }
                break;

            case 'alpha_num':
                if (!empty($value) && !preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
                    $this->addError($field, "$field must contain only letters, numbers, and underscores");
                }
                break;

            case 'match':
                if (!empty($value) && $value !== ($this->data[$ruleParam] ?? null)) {
                    $this->addError($field, "$field must match $ruleParam");
                }
                break;

            case 'unique':
                $this->checkUnique($field, $value, $ruleParam);
                break;
        }
    }

    /**
     * Check if value is unique in database
     */
    private function checkUnique($field, $value, $table)
    {
        if (empty($value)) return;

        $sql = "SELECT COUNT(*) as count FROM $table WHERE $field = ?";
        $result = Database::fetchOne($sql, [$value]);
        
        if ($result['count'] > 0) {
            $this->addError($field, "$field is already taken");
        }
    }

    /**
     * Add validation error
     */
    private function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get validation errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError()
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }
        return null;
    }

    /**
     * Sanitize string
     */
    public static function sanitize($value)
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize email
     */
    public static function sanitizeEmail($email)
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
}
