<?php
// File: ajax/todo_operations.php
// Purpose: Handle all todo/task CRUD operations via AJAX

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
            $description = sanitize($_POST['description'] ?? '');
            $tags = sanitize($_POST['tags'] ?? '');
            $priority = sanitize($_POST['priority'] ?? 'Medium');
            $deadlineDate = sanitize($_POST['deadline_date'] ?? '');
            $deadlineTime = sanitize($_POST['deadline_time'] ?? '');
            $assignedUsers = $_POST['assigned_users'] ?? [];
            
            if (empty($title) || empty($deadlineDate) || empty($deadlineTime)) {
                throw new Exception('Title, deadline date, and time are required');
            }
            
            if (empty($assignedUsers)) {
                throw new Exception('Please assign at least one user');
            }
            
            // Validate deadline is in future
            $deadline = new DateTime($deadlineDate . ' ' . $deadlineTime, new DateTimeZone('Asia/Dhaka'));
            $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
            if ($deadline <= $now) {
                throw new Exception('Deadline must be in the future');
            }
            
            // Insert todo
            $stmt = $pdo->prepare("
                INSERT INTO todos (title, description, tags, priority, status, deadline_date, deadline_time, created_by)
                VALUES (?, ?, ?, ?, 'Assigned', ?, ?, ?)
            ");
            $stmt->execute([$title, $description, $tags, $priority, $deadlineDate, $deadlineTime, getCurrentUserId()]);
            $todoId = $pdo->lastInsertId();
            
            // Assign users
            $assignStmt = $pdo->prepare("
                INSERT INTO todo_assignments (todo_id, user_id) VALUES (?, ?)
            ");
            
            foreach ($assignedUsers as $userId) {
                $assignStmt->execute([$todoId, (int)$userId]);
            }
            
            // Get assigned user names for notification
            $userStmt = $pdo->prepare("
                SELECT CONCAT(first_name, ' ', COALESCE(last_name, '')) as name 
                FROM users WHERE id IN (" . implode(',', array_map('intval', $assignedUsers)) . ")
            ");
            $userStmt->execute();
            $assignedNames = array_column($userStmt->fetchAll(), 'name');
            
            // Create notification for assigned users
            $message = getCurrentUserName() . " assigned you a task: {$title} (Due: " . formatDate($deadlineDate . ' ' . $deadlineTime) . ")";
            createNotification($pdo, 'Todo', 'assign', 'New Task Assigned', $message, ['todo_id' => $todoId]);
            
            // Log activity
            logActivity($pdo, 'Tasks', 'Create Task', "Created task: {$title}, assigned to " . implode(', ', $assignedNames), 'Info');
            
            echo json_encode([
                'success' => true, 
                'message' => 'Task created successfully',
                'todo_id' => $todoId
            ]);
            break;
            
        case 'update':
            $todoId = intval($_POST['todo_id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $tags = sanitize($_POST['tags'] ?? '');
            $priority = sanitize($_POST['priority'] ?? 'Medium');
            $deadlineDate = sanitize($_POST['deadline_date'] ?? '');
            $deadlineTime = sanitize($_POST['deadline_time'] ?? '');
            $assignedUsers = $_POST['assigned_users'] ?? [];
            
            // Get existing task
            $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            $task = $stmt->fetch();
            
            if (!$task) {
                throw new Exception('Task not found');
            }
            
            // Check permission (creator or admin)
            if ($task['created_by'] != getCurrentUserId() && !isAdmin()) {
                throw new Exception('You do not have permission to edit this task');
            }
            
            // Update task
            $stmt = $pdo->prepare("
                UPDATE todos 
                SET title = ?, description = ?, tags = ?, priority = ?, 
                    deadline_date = ?, deadline_time = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $tags, $priority, $deadlineDate, $deadlineTime, $todoId]);
            
            // Update assignments if changed
            if (!empty($assignedUsers)) {
                // Delete old assignments
                $pdo->prepare("DELETE FROM todo_assignments WHERE todo_id = ?")->execute([$todoId]);
                
                // Insert new assignments
                $assignStmt = $pdo->prepare("INSERT INTO todo_assignments (todo_id, user_id) VALUES (?, ?)");
                foreach ($assignedUsers as $userId) {
                    $assignStmt->execute([$todoId, (int)$userId]);
                }
            }
            
            // Create notification
            createNotification($pdo, 'Todo', 'update', 'Task Updated', 
                              getCurrentUserName() . " updated task: {$title}", 
                              ['todo_id' => $todoId]);
            
            // Log activity
            logActivity($pdo, 'Tasks', 'Update Task', "Updated task: {$title}", 'Info');
            
            echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
            break;
            
        case 'update_status':
            $todoId = intval($_POST['todo_id'] ?? 0);
            $newStatus = sanitize($_POST['status'] ?? '');
            
            $validStatuses = ['Assigned', 'Ongoing', 'Pending', 'Completed', 'Cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            // Get task
            $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            $task = $stmt->fetch();
            
            if (!$task) {
                throw new Exception('Task not found');
            }
            
            // Check if user is assigned to this task
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM todo_assignments 
                WHERE todo_id = ? AND user_id = ?
            ");
            $checkStmt->execute([$todoId, getCurrentUserId()]);
            $isAssigned = $checkStmt->fetch()['count'] > 0;
            
            if (!$isAssigned && !isAdmin() && $task['created_by'] != getCurrentUserId()) {
                throw new Exception('You do not have permission to change this task status');
            }
            
            // Validate status transitions
            if ($newStatus === 'Completed' && $task['status'] !== 'Ongoing') {
                throw new Exception('Task must be "Ongoing" before marking as Completed');
            }
            
            // Update status
            $stmt = $pdo->prepare("UPDATE todos SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $todoId]);
            
            // Add comment for status change
            $commentStmt = $pdo->prepare("
                INSERT INTO todo_comments (todo_id, user_id, comment)
                VALUES (?, ?, ?)
            ");
            $commentStmt->execute([
                $todoId, 
                getCurrentUserId(), 
                "Status changed to: {$newStatus}"
            ]);
            
            // Create notification
            $statusColors = [
                'Ongoing' => 'started',
                'Completed' => 'completed',
                'Cancelled' => 'cancelled',
                'Pending' => 'is now pending'
            ];
            $statusText = $statusColors[$newStatus] ?? 'updated';
            createNotification($pdo, 'Todo', 'status_change', 'Task Status Changed', 
                              getCurrentUserName() . " {$statusText} task: {$task['title']}", 
                              ['todo_id' => $todoId]);
            
            // Log activity
            logActivity($pdo, 'Tasks', 'Status Change', "Changed task '{$task['title']}' status to {$newStatus}", 'Info');
            
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            break;
            
        case 'add_comment':
            $todoId = intval($_POST['todo_id'] ?? 0);
            $comment = sanitize($_POST['comment'] ?? '');
            
            if (empty($comment)) {
                throw new Exception('Comment cannot be empty');
            }
            
            // Check if user has access to this task
            $stmt = $pdo->prepare("
                SELECT t.title, t.created_by,
                       (SELECT COUNT(*) FROM todo_assignments WHERE todo_id = ? AND user_id = ?) as is_assigned
                FROM todos t WHERE t.id = ?
            ");
            $stmt->execute([$todoId, getCurrentUserId(), $todoId]);
            $task = $stmt->fetch();
            
            if (!$task) {
                throw new Exception('Task not found');
            }
            
            if (!$task['is_assigned'] && !isAdmin() && $task['created_by'] != getCurrentUserId()) {
                throw new Exception('You do not have permission to comment on this task');
            }
            
            // Insert comment
            $stmt = $pdo->prepare("
                INSERT INTO todo_comments (todo_id, user_id, comment)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$todoId, getCurrentUserId(), $comment]);
            
            // Get comment details for response
            $commentId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("
                SELECT tc.*, u.first_name, u.last_name, u.profile_photo
                FROM todo_comments tc
                JOIN users u ON tc.user_id = u.id
                WHERE tc.id = ?
            ");
            $stmt->execute([$commentId]);
            $commentData = $stmt->fetch();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Comment added',
                'comment' => $commentData
            ]);
            break;
            
        case 'delete':
            $todoId = intval($_POST['todo_id'] ?? 0);
            
            // Get task
            $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            $task = $stmt->fetch();
            
            if (!$task) {
                throw new Exception('Task not found');
            }
            
            // Only creator or admin can delete
            if ($task['created_by'] != getCurrentUserId() && !isAdmin()) {
                throw new Exception('You do not have permission to delete this task');
            }
            
            // Delete task (cascade will delete assignments and comments)
            $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            
            // Create notification
            createNotification($pdo, 'Todo', 'delete', 'Task Deleted', 
                              getCurrentUserName() . " deleted task: {$task['title']}", 
                              ['todo_id' => $todoId]);
            
            // Log activity
            logActivity($pdo, 'Tasks', 'Delete Task', "Deleted task: {$task['title']}", 'Warning');
            
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
            break;
            
        case 'update_order':
            // Handle drag & drop reordering
            $orderedIds = $_POST['ordered_ids'] ?? [];
            
            if (empty($orderedIds)) {
                throw new Exception('No order data provided');
            }
            
            $updateStmt = $pdo->prepare("UPDATE todos SET display_order = ? WHERE id = ?");
            foreach ($orderedIds as $order => $id) {
                $updateStmt->execute([$order, (int)$id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Order updated']);
            break;
            
        case 'get_tasks':
            // Get tasks for list view with filters
            $tab = sanitize($_GET['tab'] ?? 'all');
            $search = sanitize($_GET['search'] ?? '');
            $priority = sanitize($_GET['priority'] ?? '');
            $dateFilter = sanitize($_GET['date_filter'] ?? '');
            $sortBy = sanitize($_GET['sort_by'] ?? 'deadline_date');
            $sortOrder = sanitize($_GET['sort_order'] ?? 'ASC');
            
            $userId = getCurrentUserId();
            $where = [];
            $params = [];
            
            // Tab filtering
            switch ($tab) {
                case 'assigned':
                    $where[] = "t.created_by = ?";
                    $params[] = $userId;
                    $where[] = "t.status NOT IN ('Completed', 'Cancelled')";
                    break;
                case 'ongoing':
                    $where[] = "t.status = 'Ongoing'";
                    $where[] = "EXISTS (SELECT 1 FROM todo_assignments WHERE todo_id = t.id AND user_id = ?)";
                    $params[] = $userId;
                    break;
                case 'pending':
                    $where[] = "t.status = 'Pending'";
                    break;
                case 'completed':
                    $where[] = "t.status = 'Completed'";
                    break;
                case 'cancelled':
                    $where[] = "t.status = 'Cancelled'";
                    break;
                default: // all
                    $where[] = "t.status NOT IN ('Completed', 'Cancelled')";
                    $where[] = "(t.created_by = ? OR EXISTS (SELECT 1 FROM todo_assignments WHERE todo_id = t.id AND user_id = ?))";
                    $params[] = $userId;
                    $params[] = $userId;
            }
            
            // Search filter
            if (!empty($search)) {
                $where[] = "(t.title LIKE ? OR t.description LIKE ? OR t.tags LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Priority filter
            if (!empty($priority)) {
                $where[] = "t.priority = ?";
                $params[] = $priority;
            }
            
            // Date filter
            if (!empty($dateFilter)) {
                $where[] = "DATE(t.deadline_date) = ?";
                $params[] = $dateFilter;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get tasks with assigned users
            $stmt = $pdo->prepare("
                SELECT t.*,
                       CONCAT(creator.first_name, ' ', COALESCE(creator.last_name, '')) as creator_name,
                       (SELECT GROUP_CONCAT(CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) SEPARATOR ', ')
                        FROM todo_assignments ta
                        JOIN users u ON ta.user_id = u.id
                        WHERE ta.todo_id = t.id) as assigned_to,
                       (SELECT COUNT(*) FROM todo_comments WHERE todo_id = t.id) as comment_count
                FROM todos t
                JOIN users creator ON t.created_by = creator.id
                {$whereClause}
                ORDER BY t.{$sortBy} {$sortOrder}, t.id DESC
            ");
            $stmt->execute($params);
            $tasks = $stmt->fetchAll();
            
            // Get counts for each tab
            $countStmt = $pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN status NOT IN ('Completed', 'Cancelled') THEN 1 END) as all_count,
                    COUNT(CASE WHEN created_by = ? AND status NOT IN ('Completed', 'Cancelled') THEN 1 END) as assigned_count,
                    COUNT(CASE WHEN status = 'Ongoing' THEN 1 END) as ongoing_count,
                    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled_count
                FROM todos t
                WHERE (t.created_by = ? OR EXISTS (SELECT 1 FROM todo_assignments WHERE todo_id = t.id AND user_id = ?))
            ");
            $countStmt->execute([$userId, $userId, $userId]);
            $counts = $countStmt->fetch();
            
            echo json_encode([
                'success' => true,
                'tasks' => $tasks,
                'counts' => $counts
            ]);
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