<?php
// File: config/config.php
// Purpose: Main configuration - PHP 8.3.14 Compatible

// Site Configuration
define('SITE_NAME', 'IT Equipment Manager');
define('BASE_URL', 'http://localhost/it-claud/ITAM/');
define('SITE_URL', BASE_URL);

// Font Configuration
define('SITE_FONT', 'Inter');
define('SITE_FONT_SIZE', '16px');

// Path Configuration (will be set by header.php)
if (defined('ROOT_PATH')) {
    define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
    define('PROFILE_UPLOAD_PATH', UPLOAD_PATH . 'profiles/');
    define('WARRANTY_UPLOAD_PATH', UPLOAD_PATH . 'warranty/');
    define('BACKUP_PATH', ROOT_PATH . 'backups/');
    define('CACHE_PATH', ROOT_PATH . 'cache/');
    define('TEMP_PATH', ROOT_PATH . 'temp/');
    define('LOG_PATH', ROOT_PATH . 'logs/');
    define('ASSET_PATH', ROOT_PATH . 'assets/');
    define('IMAGE_PATH', ASSET_PATH . 'images/');
    
    // Create directories if they don't exist
    $directories = [
        UPLOAD_PATH, PROFILE_UPLOAD_PATH, WARRANTY_UPLOAD_PATH,
        BACKUP_PATH, CACHE_PATH, TEMP_PATH, LOG_PATH, IMAGE_PATH
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}

// File Upload Settings
define('MAX_PROFILE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_WARRANTY_SIZE', 15 * 1024 * 1024); // 15MB
define('PROFILE_MAX_WIDTH', 800);
define('PROFILE_MAX_HEIGHT', 800);

// Session Configuration
define('SESSION_LIFETIME', 0);
define('SESSION_NAME', 'IT_EQUIP_SESS');

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 600);

// Pagination
define('DEFAULT_PER_PAGE', 100);

// Date/Time Format
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'h:i A');
define('DATETIME_FORMAT', 'Y-m-d h:i A');

// Password Policy
define('MIN_PASSWORD_LENGTH', 6);
define('PASSWORD_PATTERN', '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{6,}$/');

// Notification Settings
define('NOTIFICATION_REFRESH_INTERVAL', 30);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_error.log');

// Timezone
date_default_timezone_set('Asia/Dhaka');

// Include database
if (!isset($pdo)) {
    require_once __DIR__ . '/database.php';
}
?>