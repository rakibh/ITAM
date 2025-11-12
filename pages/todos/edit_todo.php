<?php
// Folder: pages/todos/
// File: edit_todo.php
// Purpose: Standalone page to edit existing task (alternative to modal)

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Edit Task';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$todoId = intval($_GET['id'] ?? 0);

if (!$todoId) {
    header('Location: list_todos.php');
    exit();
}

// Get task details
$stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
$stmt->execute([$todoId]);
$task = $stmt->fetch();

if (!$task) {
    setFlashMessage('danger', 'Task not found');
    header('Location: list_todos.php');
    exit();
}

// Check permission
if ($task['created_by'] != getCurrentUserId() && !isAdmin()) {
    setFlashMessage('danger', 'You do not have permission to edit this task');
    header('Location: list_todos.php');
    exit();
}

// Get assigned users
$stmt = $pdo->prepare("SELECT user_id FROM todo_assignments WHERE todo_id = ?");
$stmt->execute([$todoId]);
$assignedUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get all active users
$usersStmt = $pdo->query("SELECT id, first_name, last_name, employee_id FROM users WHERE status = 'Active' ORDER BY first_name");
$allUsers = $usersStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
        
        if (empty($assignedUsers)) {
            throw new Exception('At least one user must be assigned');
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
        $assignStmt = $pdo->prepare("INSERT INTO todo_assignments (todo_id, user_id) VALUES (?, ?)");
        foreach ($assignedUsers as $userId) {
            $assignStmt->execute([$todoId, intval($userId)]);
        }
        
        // Log activity
        logActivity($pdo, 'Tasks', 'Update Task', getCurrentUserName() . " updated task: {$title}", 'Info');
        
        // Create notification
        createNotification($pdo, 'Todo', 'update', 'Task Updated', 
                          getCurrentUserName() . " updated task: {$title}", 
                          ['todo_id' => $todoId]);
        
        setFlashMessage('success', 'Task updated successfully!');
        header('Location: view_todo.php?id=' . $todoId);
        exit();
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-pencil me-2"></i>Edit Task</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="view_todo.php?id=<?php echo $todoId; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Task
            </a>
        </div>
    </div>

    <div id="alert-container">
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Task Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required 
                               placeholder="Enter a clear and concise task title"
                               value="<?php echo htmlspecialchars($_POST['title'] ?? $task['title']); ?>">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" 
                                  placeholder="Provide detailed information about the task..."><?php echo htmlspecialchars($_POST['description'] ?? $task['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Tags (comma separated)</label>
                        <input type="text" class="form-control" name="tags" 
                               placeholder="e.g., urgent, hardware, network"
                               value="<?php echo htmlspecialchars($_POST['tags'] ?? $task['tags'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Priority <span class="text-danger">*</span></label>
                        <select class="form-select" name="priority" required>
                            <?php 
                            $currentPriority = $_POST['priority'] ?? $task['priority'];
                            $priorities = ['Low', 'Medium', 'High', 'Urgent'];
                            foreach ($priorities as $priority) {
                                $selected = $currentPriority === $priority ? 'selected' : '';
                                echo "<option value=\"{$priority}\" {$selected}>{$priority}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Deadline Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="deadline_date" required
                               value="<?php echo htmlspecialchars($_POST['deadline_date'] ?? $task['deadline_date']); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Deadline Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" name="deadline_time" required
                               value="<?php echo htmlspecialchars($_POST['deadline_time'] ?? $task['deadline_time']); ?>">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Assign To <span class="text-danger">*</span></label>
                        <select class="form-select" name="assigned_users[]" multiple size="8" required>
                            <?php 
                            $currentAssignments = $_POST['assigned_users'] ?? $assignedUserIds;
                            foreach ($allUsers as $user): 
                                $selected = in_array($user['id'], $currentAssignments) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '') . ' (' . $user['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple users</small>
                    </div>
                    
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> Changes to this task will notify all assigned users.
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="view_todo.php?id=<?php echo $todoId; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Update Task
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    // Form validation
    $('form').on('submit', function(e) {
        const assignedUsers = $('select[name="assigned_users[]"]').val();
        if (!assignedUsers || assignedUsers.length === 0) {
            e.preventDefault();
            alert('Please assign at least one user to this task.');
            return false;
        }
    });
});
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>