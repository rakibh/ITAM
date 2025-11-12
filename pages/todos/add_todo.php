<?php
// Folder: pages/todos/
// File: add_todo.php
// Purpose: Standalone page to create new task (alternative to modal)

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Create New Task';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

// Get all active users for assignment
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
        
        setFlashMessage('success', 'Task created successfully!');
        header('Location: list_todos.php');
        exit();
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-plus-circle me-2"></i>Create New Task</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="list_todos.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Tasks
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
            <form method="POST" id="addTaskForm">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Task Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required 
                               placeholder="Enter a clear and concise task title"
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" 
                                  placeholder="Provide detailed information about the task..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Tags (comma separated)</label>
                        <input type="text" class="form-control" name="tags" 
                               placeholder="e.g., urgent, hardware, network, maintenance"
                               value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>">
                        <small class="text-muted">Tags help in filtering and organizing tasks</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Priority <span class="text-danger">*</span></label>
                        <select class="form-select" name="priority" required>
                            <option value="Low" <?php echo ($_POST['priority'] ?? '') === 'Low' ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo ($_POST['priority'] ?? 'Medium') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo ($_POST['priority'] ?? '') === 'High' ? 'selected' : ''; ?>>High</option>
                            <option value="Urgent" <?php echo ($_POST['priority'] ?? '') === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Deadline Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="deadline_date" required
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo htmlspecialchars($_POST['deadline_date'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Deadline Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" name="deadline_time" required
                               value="<?php echo htmlspecialchars($_POST['deadline_time'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Assign To <span class="text-danger">*</span></label>
                        <select class="form-select" name="assigned_users[]" multiple size="8" required>
                            <?php foreach ($allUsers as $user): ?>
                                <option value="<?php echo $user['id']; ?>"
                                    <?php echo in_array($user['id'], $_POST['assigned_users'] ?? []) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '') . ' (' . $user['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple users. At least one user must be assigned.</small>
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="list_todos.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Create Task
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
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    $('input[name="deadline_date"]').attr('min', today);
    
    // Form validation
    $('#addTaskForm').on('submit', function(e) {
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