<?php
// Folder: pages/todos/
// File: delete_todo.php
// Purpose: Delete task with confirmation

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Delete Task';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$todoId = intval($_GET['id'] ?? $_POST['todo_id'] ?? 0);

if (!$todoId) {
    header('Location: list_todos.php');
    exit();
}

// Get task details
$stmt = $pdo->prepare("
    SELECT t.*, 
           u.first_name as creator_first_name,
           u.last_name as creator_last_name
    FROM todos t
    JOIN users u ON t.created_by = u.id
    WHERE t.id = ?
");
$stmt->execute([$todoId]);
$task = $stmt->fetch();

if (!$task) {
    setFlashMessage('danger', 'Task not found');
    header('Location: list_todos.php');
    exit();
}

// Check permission
if ($task['created_by'] != getCurrentUserId() && !isAdmin()) {
    setFlashMessage('danger', 'You do not have permission to delete this task');
    header('Location: list_todos.php');
    exit();
}

// Get assigned users count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM todo_assignments WHERE todo_id = ?");
$stmt->execute([$todoId]);
$assignedCount = $stmt->fetch()['count'];

// Get comments count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM todo_comments WHERE todo_id = ?");
$stmt->execute([$todoId]);
$commentsCount = $stmt->fetch()['count'];

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $confirmText = sanitize($_POST['confirm_text'] ?? '');
    
    if (strtolower($confirmText) === 'delete') {
        try {
            // Delete task (assignments and comments will cascade)
            $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
            $stmt->execute([$todoId]);
            
            // Log activity
            logActivity($pdo, 'Tasks', 'Delete Task', 
                       getCurrentUserName() . " deleted task: {$task['title']}", 'Warning');
            
            // Create notification
            createNotification($pdo, 'Todo', 'delete', 'Task Deleted', 
                              getCurrentUserName() . " deleted task: {$task['title']}", 
                              ['todo_id' => $todoId]);
            
            setFlashMessage('success', 'Task deleted successfully');
            header('Location: list_todos.php');
            exit();
            
        } catch (Exception $e) {
            $errorMessage = 'Error deleting task: ' . $e->getMessage();
        }
    } else {
        $errorMessage = 'Please type "delete" to confirm deletion';
    }
}
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-trash me-2 text-danger"></i>Delete Task</h1>
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

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-danger shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="m-0"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Task Deletion</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h6 class="alert-heading"><strong>Warning!</strong> This action cannot be undone.</h6>
                        <hr>
                        <p class="mb-0">You are about to permanently delete this task and all associated data.</p>
                    </div>

                    <!-- Task Information -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Task Details</h6>
                        <table class="table table-bordered">
                            <tr>
                                <th width="30%">Title:</th>
                                <td><strong><?php echo htmlspecialchars($task['title']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge status-<?php echo strtolower($task['status']); ?>">
                                        <?php echo $task['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Priority:</th>
                                <td>
                                    <span class="badge priority-<?php echo strtolower($task['priority']); ?>">
                                        <?php echo $task['priority']; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td><?php echo htmlspecialchars($task['creator_first_name'] . ' ' . ($task['creator_last_name'] ?? '')); ?></td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td><?php echo formatDate($task['created_at']); ?></td>
                            </tr>
                            <tr>
                                <th>Deadline:</th>
                                <td><?php echo formatDate($task['deadline_date'] . ' ' . $task['deadline_time']); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Impact Information -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">What will be deleted:</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-x-circle text-danger me-2"></i>
                                The task record
                            </li>
                            <?php if ($assignedCount > 0): ?>
                            <li class="mb-2">
                                <i class="bi bi-x-circle text-danger me-2"></i>
                                <?php echo $assignedCount; ?> user assignment(s)
                            </li>
                            <?php endif; ?>
                            <?php if ($commentsCount > 0): ?>
                            <li class="mb-2">
                                <i class="bi bi-x-circle text-danger me-2"></i>
                                <?php echo $commentsCount; ?> comment(s)
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Confirmation Form -->
                    <form method="POST">
                        <input type="hidden" name="todo_id" value="<?php echo $todoId; ?>">
                        <input type="hidden" name="confirm_delete" value="1">
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <strong>Type "delete" to confirm:</strong>
                            </label>
                            <input type="text" class="form-control" name="confirm_text" required
                                   placeholder="Type delete here" autocomplete="off">
                            <small class="text-muted">This confirmation is case-insensitive</small>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="view_todo.php?id=<?php echo $todoId; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash me-1"></i>Delete Task Permanently
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    // Confirm before submission
    $('form').on('submit', function(e) {
        const confirmText = $('input[name="confirm_text"]').val().toLowerCase();
        if (confirmText !== 'delete') {
            e.preventDefault();
            alert('Please type "delete" to confirm deletion');
            return false;
        }
        
        return confirm('Are you absolutely sure? This cannot be undone!');
    });
    
    // Focus on confirmation input
    $('input[name="confirm_text"]').focus();
});
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>