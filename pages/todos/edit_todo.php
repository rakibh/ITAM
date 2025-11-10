<?php
// File: pages/todos/edit_todo.php
// Purpose: Edit existing task (creator or admin only)

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Edit Task';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$todoId = intval($_GET['id'] ?? 0);

// Get task details
$stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
$stmt->execute([$todoId]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: ' . BASE_URL . 'pages/todos/list_todos.php');
    exit();
}

// Check permission
if ($task['created_by'] != getCurrentUserId() && !isAdmin()) {
    header('Location: ' . BASE_URL . 'pages/todos/view_todo.php?id=' . $todoId);
    exit();
}

// Get assigned users
$assignedStmt = $pdo->prepare("
    SELECT user_id FROM todo_assignments WHERE todo_id = ?
");
$assignedStmt->execute([$todoId]);
$assignedUserIds = array_column($assignedStmt->fetchAll(), 'user_id');

// Get all active users
$usersStmt = $pdo->query("
    SELECT id, first_name, last_name, employee_id 
    FROM users 
    WHERE status = 'Active' 
    ORDER BY first_name
");
$allUsers = $usersStmt->fetchAll();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-pencil me-2"></i>Edit Task</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo BASE_URL; ?>pages/todos/view_todo.php?id=<?php echo $todoId; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div id="alert-container"></div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="editTaskForm">
                        <input type="hidden" name="todo_id" value="<?php echo $todoId; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" 
                                   value="<?php echo htmlspecialchars($task['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($task['description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tags</label>
                                <input type="text" class="form-control" name="tags" 
                                       value="<?php echo htmlspecialchars($task['tags']); ?>" 
                                       placeholder="e.g., urgent, hardware">
                                <small class="text-muted">Separate tags with commas</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" name="priority" required>
                                    <option value="Low" <?php echo $task['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="Medium" <?php echo $task['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="High" <?php echo $task['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                                    <option value="Urgent" <?php echo $task['priority'] === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Deadline Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="deadline_date" 
                                       value="<?php echo $task['deadline_date']; ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Deadline Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="deadline_time" 
                                       value="<?php echo $task['deadline_time']; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assign To <span class="text-danger">*</span></label>
                            <select class="form-select" name="assigned_users[]" multiple size="8" required>
                                <?php foreach ($allUsers as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo in_array($user['id'], $assignedUserIds) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '') . ' (' . $user['employee_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple users</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> Current task status is <strong><?php echo $task['status']; ?></strong>. 
                            To change the status, please use the status buttons in the view page.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                            <a href="<?php echo BASE_URL; ?>pages/todos/view_todo.php?id=<?php echo $todoId; ?>" 
                               class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit History (for admins) -->
            <?php if (isAdmin()): ?>
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Edit History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Field</th>
                                    <th>Change</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo formatDate($task['created_at']); ?></td>
                                    <td>Created</td>
                                    <td>Task created</td>
                                </tr>
                                <?php if ($task['updated_at'] !== $task['created_at']): ?>
                                <tr>
                                    <td><?php echo formatDate($task['updated_at']); ?></td>
                                    <td>Updated</td>
                                    <td>Task last modified</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    // Form validation
    $('#editTaskForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate deadline is in future
        const deadlineDate = $('input[name="deadline_date"]').val();
        const deadlineTime = $('input[name="deadline_time"]').val();
        const deadline = new Date(deadlineDate + ' ' + deadlineTime);
        const now = new Date();
        
        if (deadline <= now) {
            showAlert('warning', 'Deadline must be in the future');
            return false;
        }
        
        // Validate at least one user is assigned
        const assignedUsers = $('select[name="assigned_users[]"]').val();
        if (!assignedUsers || assignedUsers.length === 0) {
            showAlert('warning', 'Please assign at least one user');
            return false;
        }
        
        // Submit form
        $.ajax({
            url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
            type: 'POST',
            data: $(this).serialize() + '&action=update',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(function() {
                        window.location.href = '<?php echo BASE_URL; ?>pages/todos/view_todo.php?id=<?php echo $todoId; ?>';
                    }, 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON?.message || 'Error updating task');
            }
        });
    });
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    $('input[name="deadline_date"]').attr('min', today);
});
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>