<?php
// File: includes/validation.php
// Purpose: Form validation functions for user inputs

/**
 * Validate required field
 */
function validateRequired($value, $fieldName = 'Field') {
    if (empty(trim($value))) {
        return [
            'valid' => false,
            'message' => "{$fieldName} is required"
        ];
    }
    return ['valid' => true];
}

/**
 * Validate string length
 */
function validateLength($value, $min, $max, $fieldName = 'Field') {
    $length = strlen($value);
    
    if ($length < $min) {
        return [
            'valid' => false,
            'message' => "{$fieldName} must be at least {$min} characters"
        ];
    }
    
    if ($length > $max) {
        return [
            'valid' => false,
            'message' => "{$fieldName} must not exceed {$max} characters"
        ];
    }
    
    return ['valid' => true];
}

/**
 * Validate email with domain check
 */
function validateEmailAdvanced($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'valid' => false,
            'message' => 'Invalid email format'
        ];
    }
    
    // Optional: Check if domain has MX record
    $domain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($domain, 'MX')) {
        return [
            'valid' => false,
            'message' => 'Email domain does not exist'
        ];
    }
    
    return ['valid' => true];
}

/**
 * Validate unique value in database
 */
function validateUnique($pdo, $table, $column, $value, $excludeId = null) {
    $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
    $params = [$value];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        return [
            'valid' => false,
            'message' => ucfirst($column) . ' already exists'
        ];
    }
    
    return ['valid' => true];
}

/**
 * Validate employee ID format
 */
function validateEmployeeID($employeeId) {
    // Example: E-001, EMP-123, etc.
    if (!preg_match('/^[A-Z]+-\d+$/', $employeeId)) {
        return [
            'valid' => false,
            'message' => 'Invalid Employee ID format (e.g., E-001)'
        ];
    }
    return ['valid' => true];
}

/**
 * Validate username format
 */
function validateUsername($username) {
    // Only alphanumeric, underscore, hyphen, 3-50 chars
    if (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username)) {
        return [
            'valid' => false,
            'message' => 'Username must be 3-50 characters (letters, numbers, _ and - only)'
        ];
    }
    return ['valid' => true];
}

/**
 * Validate phone number (Bangladesh)
 */
function validatePhoneBD($phone) {
    // Remove spaces and special characters
    $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    // Match patterns: +8801XXXXXXXXX, 8801XXXXXXXXX, 01XXXXXXXXX
    if (!preg_match('/^(\+?880|0)?1[3-9]\d{8}$/', $cleaned)) {
        return [
            'valid' => false,
            'message' => 'Invalid Bangladesh phone number format'
        ];
    }
    return ['valid' => true];
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    if ($d && $d->format($format) === $date) {
        return ['valid' => true];
    }
    return [
        'valid' => false,
        'message' => 'Invalid date format'
    ];
}

/**
 * Validate date range
 */
function validateDateRange($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    
    if ($start > $end) {
        return [
            'valid' => false,
            'message' => 'Start date must be before end date'
        ];
    }
    return ['valid' => true];
}

/**
 * Validate IP address format
 */
function validateIP($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return [
            'valid' => false,
            'message' => 'Invalid IP address format'
        ];
    }
    return ['valid' => true];
}

/**
 * Validate MAC address format
 */
function validateMAC($mac) {
    if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac)) {
        return [
            'valid' => false,
            'message' => 'Invalid MAC address format (e.g., 00:1A:2B:3C:4D:5E)'
        ];
    }
    return ['valid' => true];
}

/**
 * Validate serial number uniqueness
 */
function validateSerialNumber($pdo, $serialNumber, $excludeId = null) {
    // Skip if N/A (allowed to duplicate)
    if (strtoupper(trim($serialNumber)) === 'N/A') {
        return ['valid' => true];
    }
    
    return validateUnique($pdo, 'equipments', 'serial_number', $serialNumber, $excludeId);
}

/**
 * Validate file size
 */
function validateFileSize($file, $maxSizeMB) {
    $maxSize = $maxSizeMB * 1024 * 1024;
    
    if ($file['size'] > $maxSize) {
        return [
            'valid' => false,
            'message' => "File size exceeds {$maxSizeMB}MB limit"
        ];
    }
    return ['valid' => true];
}

/**
 * Validate file type
 */
function validateFileType($file, $allowedTypes) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return [
            'valid' => false,
            'message' => 'Invalid file type'
        ];
    }
    return ['valid' => true];
}

/**
 * Validate image dimensions
 */
function validateImageDimensions($file, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($file['tmp_name']);
    
    if ($imageInfo === false) {
        return [
            'valid' => false,
            'message' => 'Invalid image file'
        ];
    }
    
    if ($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight) {
        return [
            'valid' => false,
            'message' => "Image dimensions must not exceed {$maxWidth}x{$maxHeight}px"
        ];
    }
    
    return ['valid' => true];
}

/**
 * Validate password match
 */
function validatePasswordMatch($password, $confirmPassword) {
    if ($password !== $confirmPassword) {
        return [
            'valid' => false,
            'message' => 'Passwords do not match'
        ];
    }
    return ['valid' => true];
}

/**
 * Validate enum value
 */
function validateEnum($value, $allowedValues, $fieldName = 'Field') {
    if (!in_array($value, $allowedValues)) {
        return [
            'valid' => false,
            'message' => "{$fieldName} must be one of: " . implode(', ', $allowedValues)
        ];
    }
    return ['valid' => true];
}

/**
 * Validate numeric range
 */
function validateNumericRange($value, $min, $max, $fieldName = 'Value') {
    if (!is_numeric($value)) {
        return [
            'valid' => false,
            'message' => "{$fieldName} must be numeric"
        ];
    }
    
    if ($value < $min || $value > $max) {
        return [
            'valid' => false,
            'message' => "{$fieldName} must be between {$min} and {$max}"
        ];
    }
    
    return ['valid' => true];
}

/**
 * Validate URL format
 */
function validateURL($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return [
            'valid' => false,
            'message' => 'Invalid URL format'
        ];
    }
    return ['valid' => true];
}

/**
 * Validate JSON string
 */
function validateJSON($jsonString) {
    json_decode($jsonString);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'valid' => false,
            'message' => 'Invalid JSON format'
        ];
    }
    return ['valid' => true];
}

/**
 * Batch validation - validate multiple fields at once
 */
function validateFields($rules) {
    $errors = [];
    
    foreach ($rules as $field => $validation) {
        $value = $validation['value'] ?? '';
        $validators = $validation['validators'] ?? [];
        
        foreach ($validators as $validator => $params) {
            $result = null;
            
            switch ($validator) {
                case 'required':
                    $result = validateRequired($value, $params['name'] ?? $field);
                    break;
                case 'length':
                    $result = validateLength($value, $params['min'], $params['max'], $params['name'] ?? $field);
                    break;
                case 'email':
                    $result = validateEmailAdvanced($value);
                    break;
                case 'phone':
                    $result = validatePhoneBD($value);
                    break;
                case 'ip':
                    $result = validateIP($value);
                    break;
                case 'mac':
                    $result = validateMAC($value);
                    break;
                    // Add more validators as needed
            }
            
            if ($result && !$result['valid']) {
                $errors[$field] = $result['message'];
                break; // Stop validating this field on first error
            }
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}