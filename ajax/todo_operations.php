<?php
// Folder: ajax/
// File: todo_operations.php
// Purpose: Handle todo/task CRUD operations via AJAX

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            // Validate required fields
            $title = sanitize($_POST['title'] ?? '');
            $description = sanitize($_POST['description'] ?? '') ?: null;
            $tags = sanitize($_POST['tags'] ?? '') ?: null;
            $priority = sanitize($_POST['priority'] ?? 'Medium');
            $deadlineDate = sanitize($_POST['deadline_date'] ?? '');
            $deadlineTime = sanitize($_POST['deadline_time'] ?? '');
            $assignedUsers = $_POST['assigned_users'] ?? [];
            
            if (empty($title) || empty($deadlineDate) || empty($deadlineTime)) {
                throw new Exception('Title, deadline date, and time are required');
            }
            
            // Validate deadline is in future
            $deadlineDateTime = new DateTime($deadlineDate . ' ' . $deadlineTime, new DateTimeZone('Asia/Dhaka'));
            $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
            
            if ($deadlineDateTime < $now) {
                throw new Exception('Deadline must be in the future');
            }
            
            // Insert todo
            $stmt = $pdo->prepare("
                INSERT INTO todos (title, description, tags, priority, deadline_date, deadline_time, created_by, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Assigned')
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
            $todoId = intval($_POST['todo_id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $description = sanitize($_POST['description'] ?? '') ?: null;
            $tags = sanitize($_POST['tags'] ?? '') ?: null;
            $priority = sanitize($_POST['priority'] ?? 'Medium');
            $deadlineDate = sanitize($_POST['deadline_date'] ?? '');
            $deadlineTime = sanitize($_POST['deadline_time'] ?? '');
            $assignedUsers = $_POST['assigned_users'] ?? [];
            
            if (empty($title) || empty($deadlineDate) || empty($deadlineTime)) {
                throw new Exception('Title, deadline date, and time are required');
            }
            
            // Check if user has permission to update
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
            $todoId = intval($_POST['todo_id'] ?? 0);
            $newStatus = sanitize($_POST['status'] ?? '');
            
            $validStatuses = ['Assigned', 'Ongoing', 'Pending', 'Completed', 'Cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            // Get current task info
            $stmt = $pdo->prepare("SELECT title, status FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            $todo = $stmt->fetch();
            
            if (!$todo) {
                throw new Exception('Task not found');
            }
            
            // Validation: Cannot complete unless it was ongoing
            if ($newStatus === 'Completed' && $todo['status'] !== 'Ongoing') {
                throw new Exception('Task must be in Ongoing status before marking as Completed');
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
            
            echo json_encode(['success' => true, 'message' => 'Task status updated']);
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
            
        case 'delete':
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
            
            // Delete task (assignments and comments will cascade)
            $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            
            // Log activity
            logActivity($pdo, 'Tasks', 'Delete Task', 
                       getCurrentUserName() . " deleted task: {$todo['title']}", 'Warning');
            
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
            break;
            
        case 'get_tasks':
            $filter = sanitize($_GET['filter'] ?? 'all');
            $userId = getCurrentUserId();
            
            $where = [];
            $params = [];
            
            switch ($filter) {
                case 'assigned':
                    // Tasks created by current user
                    $where[] = "t.created_by = ?";
                    $params[] = $userId;
                    break;
                    
                case 'ongoing':
                    // Tasks in Ongoing status that are assigned to current user
                    $where[] = "t.status = 'Ongoing'";
                    $where[] = "EXISTS (SELECT 1 FROM todo_assignments ta WHERE ta.todo_id = t.id AND ta.user_id = ?)";
                    $params[] = $userId;
                    break;
                    
                case 'pending':
                    // All pending tasks
                    $where[] = "t.status = 'Pending'";
                    break;
                    
                case 'completed':
                    // All completed tasks
                    $where[] = "t.status = 'Completed'";
                    break;
                    
                case 'cancelled':
                    // All cancelled tasks
                    $where[] = "t.status = 'Cancelled'";
                    break;
                    
                default: // all
                    // Show all active tasks (not completed or cancelled)
                    // OR tasks where current user is assigned
                    // OR tasks created by current user
                    $where[] = "(t.status NOT IN ('Completed', 'Cancelled') 
                                OR EXISTS (SELECT 1 FROM todo_assignments ta WHERE ta.todo_id = t.id AND ta.user_id = ?)
                                OR t.created_by = ?)";
                    $params[] = $userId;
                    $params[] = $userId;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $stmt = $pdo->prepare("
                SELECT t.*, 
                       u.first_name as creator_first_name,
                       u.last_name as creator_last_name,
                       (SELECT GROUP_CONCAT(CONCAT(u2.first_name, ' ', COALESCE(u2.last_name, '')) SEPARATOR ', ')
                        FROM todo_assignments ta2
                        JOIN users u2 ON ta2.user_id = u2.id
                        WHERE ta2.todo_id = t.id) as assigned_to_names
                FROM todos t
                JOIN users u ON t.created_by = u.id
                {$whereClause}
                ORDER BY t.display_order ASC, t.deadline_date ASC, t.deadline_time ASC
            ");
            $stmt->execute($params);
            $tasks = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'get_task_details':
            $todoId = intval($_GET['todo_id'] ?? 0);
            
            // Get assigned user IDs
            $stmt = $pdo->prepare("SELECT user_id FROM todo_assignments WHERE todo_id = ?");
            $stmt->execute([$todoId]);
            $assignedUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode(['success' => true, 'assigned_user_ids' => $assignedUserIds]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (PDOException $e) {
    error_log("Todo operation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}