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
            
        case 'mark_all_read':
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId !== getCurrentUserId()) {
                throw new Exception('Unauthorized');
            }
            
            $stmt = $pdo->prepare("
                UPDATE notification_user_status 
                SET is_read = TRUE, read_at = NOW()
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            
            echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
            break;
            
        case 'acknowledge_all':
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId !== getCurrentUserId()) {
                throw new Exception('Unauthorized');
            }
            
            $stmt = $pdo->prepare("
                UPDATE notification_user_status 
                SET is_acknowledged = TRUE, acknowledged_at = NOW()
                WHERE user_id = ? AND is_acknowledged = FALSE
            ");
            $stmt->execute([$userId]);
            
            echo json_encode(['success' => true, 'message' => 'All notifications acknowledged']);
            break;
            
        case 'delete':
            requireAdmin();
            
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $notificationId = intval($_POST['notification_id'] ?? 0);
            
            if (!$notificationId) {
                throw new Exception('Notification ID required');
            }
            
            // Delete notification (cascade will delete user statuses)
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$notificationId]);
            
            logActivity($pdo, 'Notifications', 'Delete', 
                       getCurrentUserName() . " deleted notification ID: {$notificationId}", 'Info');
            
            echo json_encode(['success' => true, 'message' => 'Notification deleted']);
            break;
            
        case 'clear_old':
            requireAdmin();
            
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            // Delete notifications older than 30 days
            $stmt = $pdo->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $deletedCount = $stmt->rowCount();
            
            logActivity($pdo, 'Notifications', 'Clear Old', 
                       getCurrentUserName() . " cleared {$deletedCount} old notifications", 'Info');
            
            echo json_encode([
                'success' => true, 
                'message' => "Deleted {$deletedCount} old notifications"
            ]);
            break;
            
        case 'get_count':
            $userId = getCurrentUserId();
            
            // Get unacknowledged count
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notification_user_status 
                WHERE user_id = ? AND is_acknowledged = FALSE
            ");
            $stmt->execute([$userId]);
            $count = $stmt->fetch()['count'];
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
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