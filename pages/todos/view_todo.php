<?php
// File: pages/todos/view_todo.php
// Purpose: View task details with comments and activity log

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'View Task';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$todoId = intval($_GET['id'] ?? 0);

// Get task details
$stmt = $pdo->prepare("
    SELECT t.*,
           CONCAT(creator.first_name, ' ', COALESCE(creator.last_name, '')) as creator_name,
           creator.profile_photo as creator_photo
    FROM todos t
    JOIN users creator ON t.created_by = creator.id
    WHERE t.id = ?
");
$stmt->execute([$todoId]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: ' . BASE_URL . 'pages/todos/list_todos.php');
    exit();
}

// Get assigned users
$assignedStmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.employee_id, u.profile_photo
    FROM todo_assignments ta
    JOIN users u ON ta.user_id = u.id
    WHERE ta.todo_id = ?
    ORDER BY u.first_name
");
$assignedStmt->execute([$todoId]);
$assignedUsers = $assignedStmt->fetchAll();

// Check if current user can edit
$canEdit = ($task['created_by'] == getCurrentUserId() || isAdmin());
$isAssigned = false;
foreach ($assignedUsers as $user) {
    if ($user['id'] == getCurrentUserId()) {
        $isAssigned = true;
        break;
    }
}

// Get comments/activity log
$commentsStmt = $pdo->prepare("
    SELECT tc.*, 
           CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) as user_name,
           u.profile_photo
    FROM todo_comments tc
    JOIN users u ON tc.user_id = u.id
    WHERE tc.todo_id = ?
    ORDER BY tc.created_at DESC
");
$commentsStmt->execute([$todoId]);
$comments = $commentsStmt->fetchAll();

// Calculate deadline status
$deadline = new DateTime($task['deadline_date'] . ' ' . $task['deadline_time'], new DateTimeZone('Asia/Dhaka'));
$now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
$isOverdue = $deadline < $now && $task['status'] !== 'Completed';
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-check2-square me-2"></i>Task Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo BASE_URL; ?>pages/todos/list_todos.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <?php if ($canEdit): ?>
            <a href="<?php echo BASE_URL; ?>pages/todos/edit_todo.php?id=<?php echo $todoId; ?>" class="btn btn-warning me-2">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <button type="button" class="btn btn-danger" onclick="deleteTask()">
                <i class="bi bi-trash"></i> Delete
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div id="alert-container"></div>

    <div class="row">
        <!-- Main Task Details -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                        <span class="badge <?php 
                            echo $task['status'] === 'Completed' ? 'bg-success' : 
                                ($task['status'] === 'Ongoing' ? 'bg-warning' : 
                                ($task['status'] === 'Cancelled' ? 'bg-secondary' : 
                                ($task['status'] === 'Pending' ? 'bg-danger' : 'bg-primary')));
                        ?> fs-6">
                            <?php echo $task['status']; ?>
                        </span>
                    </div>

                    <?php if ($task['description']): ?>
                    <div class="mb-3">
                        <h6>Description</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Priority:</strong>
                            <span class="badge <?php 
                                echo $task['priority'] === 'Urgent' ? 'bg-danger' : 
                                    ($task['priority'] === 'High' ? 'bg-warning' : 
                                    ($task['priority'] === 'Medium' ? 'bg-info' : 'bg-secondary'));
                            ?>">
                                <?php echo $task['priority']; ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Deadline:</strong>
                            <span class="<?php echo $isOverdue ? 'text-danger' : ''; ?>">
                                <?php echo formatDate($task['deadline_date'] . ' ' . $task['deadline_time']); ?>
                                <?php if ($isOverdue): ?>
                                    <i class="bi bi-exclamation-triangle text-danger"></i> Overdue
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($task['tags']): ?>
                    <div class="mb-3">
                        <strong>Tags:</strong>
                        <?php 
                        $tags = explode(',', $task['tags']);
                        foreach ($tags as $tag): 
                        ?>
                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(trim($tag)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <strong>Created by:</strong>
                        <?php if ($task['creator_photo']): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $task['creator_photo']; ?>" 
                                 class="rounded-circle me-1" width="24" height="24" alt="Profile">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($task['creator_name']); ?>
                        <small class="text-muted">(<?php echo formatDate($task['created_at']); ?>)</small>
                    </div>

                    <!-- Status Actions -->
                    <?php if ($isAssigned || $canEdit): ?>
                    <div class="d-flex gap-2 mt-4">
                        <?php if ($task['status'] === 'Assigned'): ?>
                        <button class="btn btn-warning" onclick="changeStatus('Ongoing')">
                            <i class="bi bi-play-fill"></i> Start Task
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($task['status'] === 'Ongoing'): ?>
                        <button class="btn btn-success" onclick="changeStatus('Completed')">
                            <i class="bi bi-check-circle"></i> Mark as Completed
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($task['status'] !== 'Cancelled' && $task['status'] !== 'Completed'): ?>
                        <button class="btn btn-danger" onclick="changeStatus('Cancelled')">
                            <i class="bi bi-x-circle"></i> Cancel Task
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Comments & Activity Log</h5>
                </div>
                <div class="card-body">
                    <!-- Add Comment Form -->
                    <?php if ($isAssigned || $canEdit): ?>
                    <form id="addCommentForm" class="mb-4">
                        <div class="mb-2">
                            <textarea class="form-control" name="comment" rows="2" 
                                      placeholder="Add a comment..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-send"></i> Post Comment
                        </button>
                    </form>
                    <?php endif; ?>

                    <!-- Comments List -->
                    <div id="commentsList">
                        <?php if (empty($comments)): ?>
                            <p class="text-muted text-center py-3">No comments yet</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                            <div class="d-flex mb-3 pb-3 border-bottom">
                                <div class="flex-shrink-0 me-3">
                                    <?php if ($comment['profile_photo']): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $comment['profile_photo']; ?>" 
                                             class="rounded-circle" width="40" height="40" alt="Profile">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle fs-2"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <strong><?php echo htmlspecialchars($comment['user_name']); ?></strong>
                                        <small class="text-muted"><?php echo timeAgo($comment['created_at']); ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Assigned Users -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Assigned To</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($assignedUsers)): ?>
                        <p class="text-muted">No users assigned</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($assignedUsers as $user): ?>
                            <div class="list-group-item d-flex align-items-center px-0">
                                <?php if ($user['profile_photo']): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $user['profile_photo']; ?>" 
                                         class="rounded-circle me-2" width="32" height="32" alt="Profile">
                                <?php else: ?>
                                    <i class="bi bi-person-circle fs-4 me-2"></i>
                                <?php endif; ?>
                                <div>
                                    <div><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['employee_id']); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Task Info -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">Task Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Created</small>
                        <div><?php echo formatDate($task['created_at']); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Last Updated</small>
                        <div><?php echo formatDate($task['updated_at']); ?></div>
                    </div>
                    <div>
                        <small class="text-muted">Display Order</small>
                        <div><?php echo $task['display_order']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Add comment
$('#addCommentForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: $(this).serialize() + '&action=add_comment&todo_id=<?php echo $todoId; ?>',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addCommentForm')[0].reset();
                prependComment(response.comment);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Error adding comment');
        }
    });
});

function prependComment(comment) {
    const photoHtml = comment.profile_photo 
        ? `<img src="<?php echo BASE_URL; ?>uploads/profiles/${comment.profile_photo}" class="rounded-circle" width="40" height="40" alt="Profile">`
        : '<i class="bi bi-person-circle fs-2"></i>';
    
    const html = `
        <div class="d-flex mb-3 pb-3 border-bottom">
            <div class="flex-shrink-0 me-3">${photoHtml}</div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                    <strong>${escapeHtml(comment.first_name + ' ' + (comment.last_name || ''))}</strong>
                    <small class="text-muted">just now</small>
                </div>
                <p class="mb-0">${escapeHtml(comment.comment).replace(/\n/g, '<br>')}</p>
            </div>
        </div>
    `;
    
    $('#commentsList').prepend(html);
}

function changeStatus(newStatus) {
    if (!confirm(`Are you sure you want to change status to "${newStatus}"?`)) {
        return;
    }
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: {
            action: 'update_status',
            todo_id: <?php echo $todoId; ?>,
            status: newStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Error updating status');
        }
    });
}

function deleteTask() {
    if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: {
            action: 'delete',
            todo_id: <?php echo $todoId; ?>
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => {
                    window.location.href = '<?php echo BASE_URL; ?>pages/todos/list_todos.php';
                }, 1500);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Error deleting task');
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>