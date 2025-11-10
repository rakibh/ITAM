<?php
// File: ajax/notification_operations.php
// Purpose: Handle notification-related AJAX requests

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_recent':
            $limit = intval($_GET['limit'] ?? 15);
            $userId = getCurrentUserId();
            
            $stmt = $pdo->prepare("
                SELECT n.*, nus.is_read, nus.is_acknowledged
                FROM notifications n
                JOIN notification_user_status nus ON n.id = nus.notification_id
                WHERE nus.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            $notifications = $stmt->fetchAll();
            
            // Add time_ago to each notification
            foreach ($notifications as &$notif) {
                $notif['time_ago'] = timeAgo($notif['created_at']);
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);
            break;
            
        case 'mark_read':
            $notificationId = intval($_POST['notification_id'] ?? 0);
            $userId = getCurrentUserId();
            
            $stmt = $pdo->prepare("
                UPDATE notification_user_status 
                SET is_read = TRUE, read_at = NOW()
                WHERE notification_id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'acknowledge':
            $notificationId = intval($_POST['notification_id'] ?? 0);
            $userId = getCurrentUserId();
            
            $stmt = $pdo->prepare("
                UPDATE notification_user_status 
                SET is_acknowledged = TRUE, acknowledged_at = NOW()
                WHERE notification_id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}