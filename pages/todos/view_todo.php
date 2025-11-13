<?php
// Folder: pages/todos/
// File: view_todo.php
// Purpose: View detailed task information - FIXED CSRF & Cancel Modal

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'View Task';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$todoId = intval($_GET['id'] ?? 0);

if (!$todoId) {
    header('Location: list_todos.php');
    exit();
}

// Get task details
$stmt = $pdo->prepare("
    SELECT t.*, 
           u.first_name as creator_first_name,
           u.last_name as creator_last_name,
           u.employee_id as creator_employee_id,
           u.profile_photo as creator_photo
    FROM todos t
    JOIN users u ON t.created_by = u.id
    WHERE t.id = ?
");
$stmt->execute([$todoId]);
$task = $stmt->fetch();

if (!$task) {
    echo '<script>alert("Task not found"); window.close();</script>';
    exit();
}

// Get assigned users
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.employee_id, u.profile_photo
    FROM todo_assignments ta
    JOIN users u ON ta.user_id = u.id
    WHERE ta.todo_id = ?
    ORDER BY u.first_name
");
$stmt->execute([$todoId]);
$assignedUsers = $stmt->fetchAll();

// Get comments
$stmt = $pdo->prepare("
    SELECT tc.*, u.first_name, u.last_name, u.profile_photo
    FROM todo_comments tc
    JOIN users u ON tc.user_id = u.id
    WHERE tc.todo_id = ?
    ORDER BY tc.created_at DESC
");
$stmt->execute([$todoId]);
$comments = $stmt->fetchAll();

// Check if deadline has passed
$deadlineDateTime = new DateTime($task['deadline_date'] . ' ' . $task['deadline_time'], new DateTimeZone('Asia/Dhaka'));
$now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
$isOverdue = $deadlineDateTime < $now && $task['status'] !== 'Completed' && $task['status'] !== 'Cancelled';

// Check if current user can manage this task
$canManage = ($task['created_by'] == getCurrentUserId() || isAdmin());
$isAssigned = false;
foreach ($assignedUsers as $user) {
    if ($user['id'] == getCurrentUserId()) {
        $isAssigned = true;
        break;
    }
}

// Check if user can cancel
$canCancel = $canManage && $task['status'] !== 'Completed' && $task['status'] !== 'Cancelled';
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-check2-square me-2"></i>Task Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($task['status'] !== 'Completed' && $task['status'] !== 'Cancelled' && ($canManage || $isAssigned)): ?>
                    <?php if ($task['status'] === 'Assigned'): ?>
                        <button type="button" class="btn btn-success" onclick="promptStatusChange('Ongoing')">
                            <i class="bi bi-play-circle me-1"></i>Start Task
                        </button>
                    <?php elseif ($task['status'] === 'Ongoing'): ?>
                        <button type="button" class="btn btn-success" onclick="promptStatusChange('Completed')">
                            <i class="bi bi-check-circle me-1"></i>Mark Complete
                        </button>
                    <?php elseif ($task['status'] === 'Pending'): ?>
                        <button type="button" class="btn btn-warning" onclick="promptStatusChange('Ongoing')">
                            <i class="bi bi-play-circle me-1"></i>Resume
                        </button>
                    <?php endif; ?>
                    <?php if ($canCancel): ?>
                        <button type="button" class="btn btn-danger" onclick="promptCancelTask()">
                            <i class="bi bi-x-circle me-1"></i>Cancel Task
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" onclick="window.close()">
                    <i class="bi bi-arrow-left me-1"></i>Close
                </button>
            </div>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- Task Information Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-2"><?php echo htmlspecialchars($task['title']); ?></h5>
                    <div>
                        <span class="badge status-<?php echo strtolower($task['status']); ?> me-2">
                            <?php echo $task['status']; ?>
                        </span>
                        <span class="badge priority-<?php echo strtolower($task['priority']); ?> me-2">
                            <?php echo $task['priority']; ?> Priority
                        </span>
                        <?php if ($isOverdue): ?>
                            <span class="badge bg-danger">OVERDUE</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Task Information</h6>
                    
                    <div class="mb-3">
                        <strong>Created By:</strong><br>
                        <div class="d-flex align-items-center mt-1">
                            <?php if ($task['creator_photo']): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $task['creator_photo']; ?>" 
                                     class="rounded-circle me-2" width="32" height="32" alt="Profile">
                            <?php else: ?>
                                <i class="bi bi-person-circle fs-4 me-2"></i>
                            <?php endif; ?>
                            <span>
                                <?php echo htmlspecialchars($task['creator_first_name'] . ' ' . ($task['creator_last_name'] ?? '')); ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($task['creator_employee_id']); ?>)</small>
                            </span>
                        </div>
                    </div>
                    
                    <p class="mb-2">
                        <strong>Created:</strong> 
                        <?php echo formatDate($task['created_at']); ?>
                        <small class="text-muted">(<?php echo timeAgo($task['created_at']); ?>)</small>
                    </p>
                    
                    <?php if ($task['updated_at'] !== $task['created_at']): ?>
                    <p class="mb-2">
                        <strong>Last Updated:</strong> 
                        <?php echo formatDate($task['updated_at']); ?>
                        <small class="text-muted">(<?php echo timeAgo($task['updated_at']); ?>)</small>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Deadline & Tags</h6>
                    
                    <p class="mb-2">
                        <strong>Deadline:</strong><br>
                        <i class="bi bi-calendar-event me-1"></i>
                        <?php echo formatDate($task['deadline_date'] . ' ' . $task['deadline_time']); ?>
                        <?php if ($isOverdue): ?>
                            <br><span class="text-danger small">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Task is overdue by <?php 
                                    $diff = $now->diff($deadlineDateTime);
                                    echo $diff->days . ' day' . ($diff->days != 1 ? 's' : '');
                                ?>
                            </span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($task['tags']): ?>
                    <p class="mb-2">
                        <strong>Tags:</strong><br>
                        <?php 
                        $tags = explode(',', $task['tags']);
                        foreach ($tags as $tag) {
                            echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(trim($tag)) . '</span>';
                        }
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($task['description']): ?>
            <div class="mb-4">
                <h6 class="text-muted mb-2">Description</h6>
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Assigned Users -->
            <div class="mb-3">
                <h6 class="text-muted mb-3">Assigned To (<?php echo count($assignedUsers); ?>)</h6>
                <?php if (empty($assignedUsers)): ?>
                    <p class="text-muted">No users assigned to this task</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($assignedUsers as $user): ?>
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center p-2 bg-light rounded">
                                    <?php if ($user['profile_photo']): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $user['profile_photo']; ?>" 
                                             class="rounded-circle me-2" width="40" height="40" alt="Profile">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle fs-3 me-2"></i>
                                    <?php endif; ?>
                                    <div>
                                        <div><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['employee_id']); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="m-0"><i class="bi bi-chat-dots me-2"></i>Activity & Comments (<?php echo count($comments); ?>)</h6>
        </div>
        <div class="card-body">
            <!-- Add Comment Form -->
            <form id="addCommentForm" class="mb-4">
                <input type="hidden" name="todo_id" value="<?php echo $todoId; ?>">
                <div class="input-group">
                    <textarea class="form-control" name="comment" placeholder="Add a comment or update..." rows="2" required></textarea>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-send me-1"></i>Post
                    </button>
                </div>
            </form>

            <!-- Comments List -->
            <div id="commentsList">
                <?php if (empty($comments)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-chat-dots fs-1 d-block mb-2"></i>
                        <p>No comments yet. Be the first to comment!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="d-flex mb-3 pb-3 border-bottom">
                            <div class="flex-shrink-0 me-3">
                                <?php if ($comment['profile_photo']): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $comment['profile_photo']; ?>" 
                                         class="rounded-circle" width="48" height="48" alt="Profile">
                                <?php else: ?>
                                    <i class="bi bi-person-circle" style="font-size: 3rem; color: #6c757d;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <strong><?php echo htmlspecialchars($comment['first_name'] . ' ' . ($comment['last_name'] ?? '')); ?></strong>
                                        <small class="text-muted ms-2">
                                            <?php echo timeAgo($comment['created_at']); ?>
                                            <span class="mx-1">•</span>
                                            <?php echo formatDate($comment['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="bg-light p-2 rounded">
                                    <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Status Change Confirmation Modal -->
<div class="modal fade" id="statusChangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Status Change</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="statusChangeMessage"></p>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <small>This will update the task status.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusChange">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Task Confirmation Modal -->
<div class="modal fade" id="cancelTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Cancel Task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong>cancel</strong> the task "<strong><?php echo htmlspecialchars($task['title']); ?></strong>"?</p>
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> Cancelled tasks cannot be resumed.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Task Active</button>
                <button type="button" class="btn btn-danger" id="confirmCancelTask">Yes, Cancel Task</button>
            </div>
        </div>
    </div>
</div>

<script>
let pendingStatusChange = null;

// Add comment
$('#addCommentForm').on('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = $(this).find('button[type="submit"]');
    const originalHtml = submitBtn.html();
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Posting...');
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: $(this).serialize() + '&action=add_comment&csrf_token=<?php echo getCsrfToken(); ?>',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1000);
            }
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Error adding comment');
            submitBtn.prop('disabled', false).html(originalHtml);
        }
    });
});

// Status change with modal
function promptStatusChange(newStatus) {
    pendingStatusChange = newStatus;
    
    let message = `Are you sure you want to change the status to <strong>${newStatus}</strong>?`;
    
    if (newStatus === 'Completed') {
        message = 'Mark this task as <strong>completed</strong>? Make sure all work is finished.';
    }
    
    $('#statusChangeMessage').html(message);
    $('#statusChangeModal').modal('show');
}

$('#confirmStatusChange').on('click', function() {
    if (!pendingStatusChange) return;
    
    $('#statusChangeModal').modal('hide');
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: { 
            action: 'change_status', 
            todo_id: <?php echo $todoId; ?>, 
            status: pendingStatusChange,
            csrf_token: '<?php echo getCsrfToken(); ?>'
        },
        dataType: 'json',
        beforeSend: function() {
            showLoading();
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1500);
            }
        },
        error: function(xhr) {
            hideLoading();
            showAlert('danger', xhr.responseJSON?.message || 'Error changing status');
        }
    });
    
    pendingStatusChange = null;
});

// Cancel task with modal
function promptCancelTask() {
    $('#cancelTaskModal').modal('show');
}

$('#confirmCancelTask').on('click', function() {
    $('#cancelTaskModal').modal('hide');
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: { 
            action: 'change_status', 
            todo_id: <?php echo $todoId; ?>, 
            status: 'Cancelled',
            csrf_token: '<?php echo getCsrfToken(); ?>'
        },
        dataType: 'json',
        beforeSend: function() {
            showLoading();
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                showAlert('success', 'Task cancelled successfully');
                setTimeout(() => location.reload(), 1500);
            }
        },
        error: function(xhr) {
            hideLoading();
            showAlert('danger', xhr.responseJSON?.message || 'Error cancelling task');
        }
    });
});

// Loading overlay
function showLoading() {
    $('body').append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>');
}

function hideLoading() {
    $('.loading-overlay').remove();
}
</script>

<style>
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
</style>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>