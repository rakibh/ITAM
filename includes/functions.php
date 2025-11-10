<?php
// File: includes/functions.php
// Purpose: Common utility functions used throughout the application

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date to GMT+6
 */
function formatDate($date, $format = DATETIME_FORMAT) {
    if (empty($date)) return 'N/A';
    $dt = new DateTime($date, new DateTimeZone('Asia/Dhaka'));
    return $dt->format($format);
}

/**
 * Time ago format
 */
function timeAgo($datetime) {
    $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
    $ago = new DateTime($datetime, new DateTimeZone('Asia/Dhaka'));
    $diff = $now->diff($ago);

    if ($diff->d == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return 'just now';
            }
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->d < 7) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    } elseif ($diff->d < 30) {
        $weeks = floor($diff->d / 7);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff->m < 12) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    } else {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Upload file with validation
 */
function uploadFile($file, $uploadPath, $allowedTypes, $maxSize) {
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }

    // Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        $sizeMB = $maxSize / (1024 * 1024);
        return ['success' => false, 'message' => "File size exceeds {$sizeMB}MB limit"];
    }

    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('file_', true) . '.' . $extension;
    $destination = $uploadPath . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    return [
        'success' => true,
        'filename' => $filename,
        'filepath' => $destination
    ];
}

/**
 * Delete file safely
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Create notification
 */
function createNotification($pdo, $type, $event, $title, $message, $context = null, $createdBy = null) {
    try {
        // Insert notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (type, event, title, message, context_json, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $createdByName = $createdBy ?? getCurrentUserName();
        $contextJson = $context ? json_encode($context) : null;
        
        $stmt->execute([$type, $event, $title, $message, $contextJson, $createdByName]);
        $notificationId = $pdo->lastInsertId();
        
        // Create status for all users
        $userStmt = $pdo->query("SELECT id FROM users WHERE status = 'Active'");
        $insertStatusStmt = $pdo->prepare("
            INSERT INTO notification_user_status (notification_id, user_id)
            VALUES (?, ?)
        ");
        
        while ($user = $userStmt->fetch()) {
            $insertStatusStmt->execute([$notificationId, $user['id']]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Notification creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log system activity
 */
function logActivity($pdo, $module, $action, $description, $logType = 'Info') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (log_type, module, action, description, user_id, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $userId = getCurrentUserId();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $stmt->execute([$logType, $module, $action, $description, $userId, $ipAddress]);
        return true;
    } catch (PDOException $e) {
        error_log("Activity logging error: " . $e->getMessage());
        return false;
    }
}

/**
 * Add revision history
 */
function addRevision($pdo, $table, $recordId, $description) {
    try {
        $userId = getCurrentUserId();
        $revisionTable = $table . '_revisions';
        $idColumn = rtrim($table, 's') . '_id';
        
        $stmt = $pdo->prepare("
            INSERT INTO {$revisionTable} ({$idColumn}, changed_by, change_description, changed_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([$recordId, $userId, $description]);
        return true;
    } catch (PDOException $e) {
        error_log("Revision history error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get pagination HTML
 */
function getPaginationHTML($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Get system setting
 */
function getSystemSetting($pdo, $key, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Set system setting
 */
function setSystemSetting($pdo, $key, $value) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check warranty status
 */
function getWarrantyStatus($expiryDate) {
    if (empty($expiryDate)) return 'N/A';
    
    $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
    $expiry = new DateTime($expiryDate, new DateTimeZone('Asia/Dhaka'));
    $diff = $now->diff($expiry);
    
    if ($expiry < $now) {
        return 'Expired';
    } elseif ($diff->days <= 15) {
        return 'Expiring Soon (15 days)';
    } elseif ($diff->days <= 30) {
        return 'Expiring Soon (30 days)';
    } else {
        return 'Active';
    }
}
