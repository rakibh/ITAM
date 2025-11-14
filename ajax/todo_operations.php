<?php
// Folder: ajax/
// File: todo_operations.php
// Purpose: Handle todo/task CRUD operations - 5 STAGE SYSTEM

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_tasks':
            $filter = sanitize($_GET['filter'] ?? 'all');
            $userId = getCurrentUserId();
            
            $where = [];
            $params = [];
            
            // NEW 5-STAGE SYSTEM
            switch ($filter) {
                case 'todo':
                    // To Do tab - only To Do status
                    $where[] = "t.status = 'To Do'";
                    break;
                    
                case 'doing':
                    // Doing tab - only Doing status
                    $where[] = "t.status = 'Doing'";
                    break;
                    
                case 'pastdue':
                    // Past Due tab - Past Due status OR overdue tasks
                    $where[] = "(t.status = 'Past Due' OR (CONCAT(t.deadline_date, ' ', t.deadline_time) < NOW() AND t.status NOT IN ('Done', 'Dropped')))";
                    break;
                    
                case 'done':
                    // Done tab - only Done status
                    $where[] = "t.status = 'Done'";
                    break;
                    
                case 'dropped':
                    // Dropped tab - only Dropped status
                    $where[] = "t.status = 'Dropped'";
                    break;
                    
                default: // 'all'
                    // All tab - show all active tasks (not done/dropped)
                    $where[] = "t.status NOT IN ('Done', 'Dropped')";
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $sql = "
                SELECT t.*, 
                       u.first_name as creator_first_name,
                       u.last_name as creator_last_name,
                       GROUP_CONCAT(DISTINCT CONCAT(u2.first_name, ' ', COALESCE(u2.last_name, '')) SEPARATOR ', ') as assigned_to_names
                FROM todos t
                LEFT JOIN users u ON t.created_by = u.id
                LEFT JOIN todo_assignments ta ON t.id = ta.todo_id
                LEFT JOIN users u2 ON ta.user_id = u2.id
                {$whereClause}
                GROUP BY t.id, t.title, t.description, t.tags, t.priority, t.status, 
                         t.deadline_date, t.deadline_time, t.created_by, t.display_order, 
                         t.created_at, t.updated_at, u.first_name, u.last_name
                ORDER BY t.display_order ASC, t.deadline_date ASC, t.deadline_time ASC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tasks = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true, 
                'tasks' => $tasks,
                'count' => count($tasks),
                'filter' => $filter
            ]);
            break;
            
        case 'add':
            // CSRF token validation
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $title = sanitize($_POST['title'] ?? '');
            $description = sanitize($_POST['description'] ?? '') ?: null;
            $tags = sanitize($_POST['tags'] ?? '') ?: null;
            $priority = sanitize($_POST['priority'] ?? 'Medium');
            $deadlineDate = sanitize($_POST['deadline_date'] ?? '');
            $deadlineTime = sanitize($_POST['deadline_time'] ?? '23:59:00');
            $assignedUsers = $_POST['assigned_users'] ?? [];
            
            if (empty($title) || empty($deadlineDate)) {
                throw new Exception('Title and deadline date are required');
            }
            
            // Ensure time has seconds
            if (strlen($deadlineTime) == 5) {
                $deadlineTime .= ':00';
            }
            
            // Validate deadline is in future
            $deadlineDateTime = new DateTime($deadlineDate . ' ' . $deadlineTime, new DateTimeZone('Asia/Dhaka'));
            $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
            
            if ($deadlineDateTime < $now) {
                throw new Exception('Deadline must be in the future');
            }
            
            // Insert todo with default status 'To Do'
            $stmt = $pdo->prepare("
                INSERT INTO todos (title, description, tags, priority, deadline_date, deadline_time, created_by, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'To Do')
            ");
            $stmt->execute([$title, $description, $tags, $priority, $deadlineDate, $deadlineTime, getCurrentUserId()]);
            $todoId = $pdo->lastInsertId();
            
            // Assign users
            if (!empty($assignedUsers)) {
                $assignStmt = $pdo->prepare("INSERT INTO todo_assignments (todo_id, user_id) VALUES (?, ?)");
                foreach ($assignedUsers as $userId) {
                    $assignStmt->execute([$todoId, intval($userId)]);
                }
            }
            
            // Log activity
            logActivity($pdo, 'Tasks', 'Add Task', getCurrentUserName() . " created task: {$title}", 'Info');
            
            // Create notification
            createNotification($pdo, 'Todo', 'add', 'New Task Assigned', 
                              getCurrentUserName() . " assigned a new task: {$title}", 
                              ['todo_id' => $todoId]);
            
            echo json_encode(['success' => true, 'message' => 'Task created successfully', 'todo_id' => $todoId]);
            break;
            
        case 'update':
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $todoId = intval($_POST['todo_id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $description = sanitize($_POST['description'] ?? '') ?: null;
            $tags = sanitize($_POST['tags'] ?? '') ?: null;
            $priority = sanitize($_POST['priority'] ?? 'Medium');
            $deadlineDate = sanitize($_POST['deadline_date'] ?? '');
            $deadlineTime = sanitize($_POST['deadline_time'] ?? '23:59:00');
            $assignedUsers = $_POST['assigned_users'] ?? [];
            
            if (empty($title) || empty($deadlineDate)) {
                throw new Exception('Title and deadline date are required');
            }
            
            // Ensure time has seconds
            if (strlen($deadlineTime) == 5) {
                $deadlineTime .= ':00';
            }
            
            // Check permission
            $stmt = $pdo->prepare("SELECT created_by FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            $todo = $stmt->fetch();
            
            if (!$todo) {
                throw new Exception('Task not found');
            }
            
            if ($todo['created_by'] != getCurrentUserId() && !isAdmin()) {
                throw new Exception('You do not have permission to update this task');
            }
            
            // Update todo
            $stmt = $pdo->prepare("
                UPDATE todos 
                SET title = ?, description = ?, tags = ?, priority = ?, 
                    deadline_date = ?, deadline_time = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $tags, $priority, $deadlineDate, $deadlineTime, $todoId]);
            
            // Update assignments
            $pdo->prepare("DELETE FROM todo_assignments WHERE todo_id = ?")->execute([$todoId]);
            if (!empty($assignedUsers)) {
                $assignStmt = $pdo->prepare("INSERT INTO todo_assignments (todo_id, user_id) VALUES (?, ?)");
                foreach ($assignedUsers as $userId) {
                    $assignStmt->execute([$todoId, intval($userId)]);
                }
            }
            
            // Log activity
            logActivity($pdo, 'Tasks', 'Update Task', getCurrentUserName() . " updated task: {$title}", 'Info');
            
            // Create notification
            createNotification($pdo, 'Todo', 'update', 'Task Updated', 
                              getCurrentUserName() . " updated task: {$title}", 
                              ['todo_id' => $todoId]);
            
            echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
            break;
            
        case 'change_status':
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $todoId = intval($_POST['todo_id'] ?? 0);
            $newStatus = sanitize($_POST['status'] ?? '');
            
            // NEW 5-STAGE SYSTEM VALIDATION
            $validStatuses = ['To Do', 'Doing', 'Past Due', 'Done', 'Dropped'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            // Get task info
            $stmt = $pdo->prepare("SELECT title, status FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            $todo = $stmt->fetch();
            
            if (!$todo) {
                throw new Exception('Task not found');
            }
            
            // Update status
            $stmt = $pdo->prepare("UPDATE todos SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $todoId]);
            
            // Log activity
            logActivity($pdo, 'Tasks', 'Status Change', 
                       getCurrentUserName() . " changed task '{$todo['title']}' status to {$newStatus}", 'Info');
            
            // Create notification
            createNotification($pdo, 'Todo', 'status_change', 'Task Status Changed', 
                              getCurrentUserName() . " changed task '{$todo['title']}' to {$newStatus}", 
                              ['todo_id' => $todoId, 'new_status' => $newStatus]);
            
            echo json_encode(['success' => true, 'message' => 'Task status updated to ' . $newStatus]);
            break;
            
        case 'delete':
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $todoId = intval($_POST['todo_id'] ?? 0);
            
            // Get task info
            $stmt = $pdo->prepare("SELECT title, created_by FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            $todo = $stmt->fetch();
            
            if (!$todo) {
                throw new Exception('Task not found');
            }
            
            // Check permission
            if ($todo['created_by'] != getCurrentUserId() && !isAdmin()) {
                throw new Exception('You do not have permission to delete this task');
            }
            
            // Delete task
            $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            
            // Log activity
            logActivity($pdo, 'Tasks', 'Delete Task', 
                       getCurrentUserName() . " deleted task: {$todo['title']}", 'Warning');
            
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
            break;
            
        case 'add_comment':
            $todoId = intval($_POST['todo_id'] ?? 0);
            $comment = sanitize($_POST['comment'] ?? '');
            
            if (empty($comment)) {
                throw new Exception('Comment cannot be empty');
            }
            
            $stmt = $pdo->prepare("INSERT INTO todo_comments (todo_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$todoId, getCurrentUserId(), $comment]);
            
            echo json_encode(['success' => true, 'message' => 'Comment added']);
            break;
            
        case 'get_task_details':
            $todoId = intval($_GET['todo_id'] ?? 0);
            
            // Get assigned user IDs
            $stmt = $pdo->prepare("SELECT user_id FROM todo_assignments WHERE todo_id = ?");
            $stmt->execute([$todoId]);
            $assignedUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode(['success' => true, 'assigned_user_ids' => $assignedUserIds]);
            break;
            
        case 'reorder':
            $orderedIds = $_POST['ordered_ids'] ?? [];
            
            if (empty($orderedIds)) {
                throw new Exception('No tasks to reorder');
            }
            
            $updateStmt = $pdo->prepare("UPDATE todos SET display_order = ? WHERE id = ?");
            foreach ($orderedIds as $index => $todoId) {
                $updateStmt->execute([$index, intval($todoId)]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Tasks reordered']);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
} catch (PDOException $e) {
    error_log("Todo operation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}