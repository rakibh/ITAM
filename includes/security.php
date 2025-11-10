<?php
// File: includes/security.php
// Purpose: Security functions for input validation, XSS prevention, and file upload security

/**
 * Sanitize string input (prevent XSS)
 */
function secureInput($input, $allowHTML = false) {
    if (is_array($input)) {
        return array_map(function($item) use ($allowHTML) {
            return secureInput($item, $allowHTML);
        }, $input);
    }
    
    $input = trim($input);
    
    if ($allowHTML) {
        // Allow specific HTML tags (for rich text editors)
        return strip_tags($input, '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3>');
    }
    
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Bangladesh format)
 */
function validatePhone($phone) {
    // Allow: +880, 880, 01, or just digits
    $pattern = '/^(\+?880|0)?1[3-9]\d{8}$/';
    $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);
    return preg_match($pattern, $cleaned);
}

/**
 * Validate IP address (IPv4)
 */
function validateIPAddress($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
}

/**
 * Validate MAC address
 */
function validateMACAddress($mac) {
    $pattern = '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/';
    return preg_match($pattern, $mac);
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters';
    }
    
    if (!preg_match('/[A-Za-z]/', $password)) {
        $errors[] = 'Password must contain at least one letter';
    }
    
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[!@#$%^&*]/', $password)) {
        $errors[] = 'Password must contain at least one special character (!@#$%^&*)';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Secure file upload validation
 */
function validateFileUpload($file, $allowedTypes, $maxSize, $allowedExtensions = []) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Invalid file upload';
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'File size exceeds maximum allowed';
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = 'No file was uploaded';
            break;
        default:
            $errors[] = 'Unknown upload error';
            break;
    }
    
    if (!empty($errors)) {
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $sizeMB = round($maxSize / (1024 * 1024), 2);
        $errors[] = "File size exceeds {$sizeMB}MB limit";
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
    }
    
    // Check file extension
    if (!empty($allowedExtensions)) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Invalid file extension. Allowed: ' . implode(', ', $allowedExtensions);
        }
    }
    
    // Check for malicious content (basic check)
    $content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
    if (preg_match('/<\?php|<script|javascript:/i', $content)) {
        $errors[] = 'File contains potentially malicious content';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'mime_type' => $mimeType
    ];
}

/**
 * Generate secure random filename
 */
function generateSecureFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $timestamp = date('YmdHis');
    $random = bin2hex(random_bytes(8));
    return $timestamp . '_' . $random . '.' . strtolower($extension);
}

/**
 * Prevent SQL injection by escaping
 */
function escapeSQLLike($string, $escapeChar = '\\') {
    return str_replace(
        [$escapeChar, '%', '_'],
        [$escapeChar . $escapeChar, $escapeChar . '%', $escapeChar . '_'],
        $string
    );
}

/**
 * Rate limiting check
 */
function checkRateLimit($pdo, $identifier, $action, $maxAttempts, $timeWindow) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM system_logs 
        WHERE ip_address = ? 
        AND action = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$identifier, $action, $timeWindow]);
    $result = $stmt->fetch();
    
    return $result['count'] < $maxAttempts;
}

/**
 * Log security event
 */
function logSecurityEvent($pdo, $eventType, $description, $severity = 'Warning') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (log_type, module, action, description, user_id, ip_address, created_at)
            VALUES (?, 'Security', ?, ?, ?, ?, NOW())
        ");
        
        $userId = getCurrentUserId() ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $stmt->execute([$severity, $eventType, $description, $userId, $ipAddress]);
        return true;
    } catch (PDOException $e) {
        error_log("Security log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check for suspicious patterns in input
 */
function detectSuspiciousInput($input) {
    $patterns = [
        '/(\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b|\bunion\b)/i', // SQL keywords
        '/<script\b[^>]*>(.*?)<\/script>/is', // Script tags
        '/javascript:/i', // JavaScript protocol
        '/<iframe\b[^>]*>/i', // Iframe tags
        '/on\w+\s*=\s*["\']?[^"\']*["\']?/i' // Event handlers
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Sanitize filename to prevent directory traversal
 */
function sanitizeFilename($filename) {
    // Remove any path components
    $filename = basename($filename);
    
    // Remove any non-alphanumeric characters except dots, hyphens, and underscores
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Remove multiple dots
    $filename = preg_replace('/\.+/', '.', $filename);
    
    // Limit length
    if (strlen($filename) > 255) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = substr($filename, 0, 255 - strlen($extension) - 1) . '.' . $extension;
    }
    
    return $filename;
}

/**
 * Check if user agent is suspicious
 */
function isSuspiciousUserAgent() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $suspiciousPatterns = [
        '/bot/i',
        '/crawler/i',
        '/spider/i',
        '/scraper/i',
        '/curl/i',
        '/wget/i'
    ];
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Hash sensitive data
 */
function hashSensitiveData($data, $pepper = '') {
    if (!$pepper) {
        $pepper = defined('SECURITY_PEPPER') ? SECURITY_PEPPER : 'default_pepper_change_me';
    }
    return hash('sha256', $data . $pepper);
}

/**
 * Verify data integrity
 */
function verifyDataIntegrity($data, $hash, $pepper = '') {
    return hash_equals($hash, hashSensitiveData($data, $pepper));
}