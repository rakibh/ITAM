<?php
// Folder: pages/todos/
// File: list_todos.php
// Purpose: Main task board with tabs, search, filter - UPDATED VERSION

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Task Management';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$userId = getCurrentUserId();

// Get task counts for tabs
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN t.status NOT IN ('Completed', 'Cancelled') THEN 1 END) as all_count,
        COUNT(CASE WHEN t.created_by = ? THEN 1 END) as assigned_count,
        COUNT(CASE WHEN t.status = 'Ongoing' THEN 1 END) as ongoing_count,
        COUNT(CASE WHEN t.status = 'Pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN t.status = 'Completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN t.status = 'Cancelled' THEN 1 END) as cancelled_count
    FROM todos t
");
$stmt->execute([$userId]);
$taskCounts = $stmt->fetch();

// Get all active users for assignment
$usersStmt = $pdo->query("SELECT id, first_name, last_name, employee_id FROM users WHERE status = 'Active' ORDER BY first_name");
$allUsers = $usersStmt->fetchAll();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-check2-square me-2"></i>Task Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                <i class="bi bi-plus-circle me-1"></i>Create Task
            </button>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- Task Tabs -->
    <ul class="nav nav-tabs mb-4" id="taskTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" 
                    type="button" role="tab" data-filter="all">
                All <span class="badge bg-secondary ms-1"><?php echo $taskCounts['all_count']; ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="assigned-tab" data-bs-toggle="tab" data-bs-target="#assigned" 
                    type="button" role="tab" data-filter="assigned">
                Assigned <span class="badge bg-info ms-1"><?php echo $taskCounts['assigned_count']; ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="ongoing-tab" data-bs-toggle="tab" data-bs-target="#ongoing" 
                    type="button" role="tab" data-filter="ongoing">
                Ongoing <span class="badge bg-warning ms-1"><?php echo $taskCounts['ongoing_count']; ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" 
                    type="button" role="tab" data-filter="pending">
                Pending <span class="badge bg-danger ms-1"><?php echo $taskCounts['pending_count']; ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" 
                    type="button" role="tab" data-filter="completed">
                Completed <span class="badge bg-success ms-1"><?php echo $taskCounts['completed_count']; ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" 
                    type="button" role="tab" data-filter="cancelled">
                Cancelled <span class="badge bg-secondary ms-1"><?php echo $taskCounts['cancelled_count']; ?></span>
            </button>
        </li>
    </ul>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchTasks" placeholder="Search by title, tag, or description...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="filterPriority">
                        <option value="">All Priorities</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="sortBy">
                        <option value="deadline">Sort by Deadline</option>
                        <option value="created">Sort by Created Date</option>
                        <option value="priority">Sort by Priority</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="sortOrder">
                        <option value="desc" selected>Descending (Newest First)</option>
                        <option value="asc">Ascending (Oldest First)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                        <i class="bi bi-funnel me-1"></i>Apply
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task List Container -->
    <div class="tab-content" id="taskTabsContent">
        <div class="tab-pane fade show active" id="taskListContainer" role="tabpanel">
            <div id="tasksList" class="row">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Loading tasks...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addTaskForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Describe the task..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tags (comma separated)</label>
                            <input type="text" class="form-control" name="tags" placeholder="e.g., urgent, hardware, network">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" name="priority" required>
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deadline Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="deadline_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deadline Time <small class="text-muted">(Optional)</small></label>
                            <input type="time" class="form-control" name="deadline_time" value="23:59">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Assign To <span class="text-danger">*</span></label>
                            <select class="form-select" name="assigned_users[]" multiple size="5" required>
                                <?php foreach ($allUsers as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '') . ' (' . $user['employee_id'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple users</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editTaskForm">
                <input type="hidden" name="todo_id" id="edit_todo_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tags (comma separated)</label>
                            <input type="text" class="form-control" name="tags" id="edit_tags">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" name="priority" id="edit_priority" required>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deadline Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="deadline_date" id="edit_deadline_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deadline Time <small class="text-muted">(Optional)</small></label>
                            <input type="time" class="form-control" name="deadline_time" id="edit_deadline_time">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Assign To <span class="text-danger">*</span></label>
                            <select class="form-select" name="assigned_users[]" id="edit_assigned_users" multiple size="5" required>
                                <?php foreach ($allUsers as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '') . ' (' . $user['employee_id'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple users</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                    <small>This will update the task status and move it to the corresponding tab.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusChange">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentFilter = 'all';
let allTasksData = [];
let pendingStatusChange = null;

$(document).ready(function() {
    const today = new Date().toISOString().split('T')[0];
    $('input[name="deadline_date"]').attr('min', today);
    
    loadTasks('all');
    
    $('[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        currentFilter = $(e.target).data('filter');
        loadTasks(currentFilter);
    });
    
    $('#searchTasks').on('input', debounce(function() {
        filterTasks();
    }, 300));
});

function loadTasks(filter) {
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'GET',
        data: { action: 'get_tasks', filter: filter },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                allTasksData = response.tasks || [];
                displayTasks(allTasksData);
            } else {
                $('#tasksList').html('<div class="col-12"><div class="alert alert-warning">' + (response.message || 'No tasks found') + '</div></div>');
            }
        },
        error: function(xhr) {
            $('#tasksList').html('<div class="col-12"><div class="alert alert-danger">Failed to load tasks. Please refresh the page.</div></div>');
        }
    });
}

function displayTasks(tasks) {
    if (tasks.length === 0) {
        $('#tasksList').html('<div class="col-12 text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i><p>No tasks found</p></div>');
        return;
    }
    
    let html = '';
    tasks.forEach(function(task) {
        const deadline = new Date(task.deadline_date + ' ' + task.deadline_time);
        const now = new Date();
        const isOverdue = deadline < now && task.status !== 'Completed' && task.status !== 'Cancelled';
        
        const priorityClass = 'priority-' + task.priority.toLowerCase();
        const statusClass = 'status-' + task.status.toLowerCase();
        
        html += `
            <div class="col-md-6 col-lg-4 mb-3 task-card" data-task-id="${task.id}">
                <div class="card h-100 ${isOverdue ? 'border-danger border-2' : ''}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title mb-0 flex-grow-1">${escapeHtml(task.title)}</h6>
                            <span class="badge ${priorityClass} ms-2">${task.priority}</span>
                        </div>
                        ${task.description ? `<p class="card-text small text-muted mb-2">${escapeHtml(task.description).substring(0, 100)}${task.description.length > 100 ? '...' : ''}</p>` : ''}
                        ${task.tags ? `<div class="mb-2">${task.tags.split(',').map(t => '<span class="badge bg-secondary me-1 small">' + escapeHtml(t.trim()) + '</span>').join('')}</div>` : ''}
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="bi bi-calendar me-1"></i>${formatDateTime(task.deadline_date + ' ' + task.deadline_time)}
                            </small>
                            ${isOverdue ? '<span class="badge bg-danger ms-2 small">OVERDUE</span>' : ''}
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block"><i class="bi bi-person me-1"></i><strong>Assigned:</strong> ${escapeHtml(task.assigned_to_names || 'None')}</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge ${statusClass}">${task.status}</span>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="viewTask(${task.id})" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>
                                ${canChangeStatus(task) ? `
                                    <button class="btn btn-outline-success" onclick="promptStatusChange(${task.id}, '${getNextStatus(task.status)}', '${escapeHtml(task.title)}')" title="${getNextStatus(task.status)}">
                                        <i class="bi bi-${getStatusIcon(getNextStatus(task.status))}"></i>
                                    </button>
                                ` : ''}
                                ${canEdit(task) ? `
                                    <button class="btn btn-outline-warning" onclick="editTask(${task.id})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                ` : ''}
                                ${canDelete(task) ? `
                                    <button class="btn btn-outline-danger" onclick="deleteTask(${task.id}, '${escapeHtml(task.title)}')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#tasksList').html(html);
}

function filterTasks() {
    const searchTerm = $('#searchTasks').val().toLowerCase();
    const priorityFilter = $('#filterPriority').val();
    
    let filtered = allTasksData.filter(task => {
        let matchesSearch = true;
        let matchesPriority = true;
        
        if (searchTerm) {
            matchesSearch = task.title.toLowerCase().includes(searchTerm) ||
                           (task.description && task.description.toLowerCase().includes(searchTerm)) ||
                           (task.tags && task.tags.toLowerCase().includes(searchTerm));
        }
        
        if (priorityFilter) {
            matchesPriority = task.priority === priorityFilter;
        }
        
        return matchesSearch && matchesPriority;
    });
    
    displayTasks(filtered);
}

function applyFilters() {
    const sortBy = $('#sortBy').val();
    const sortOrder = $('#sortOrder').val();
    
    let sorted = [...allTasksData];
    
    if (sortBy === 'deadline') {
        sorted.sort((a, b) => {
            const diff = new Date(a.deadline_date + ' ' + a.deadline_time) - new Date(b.deadline_date + ' ' + b.deadline_time);
            return sortOrder === 'asc' ? diff : -diff;
        });
    } else if (sortBy === 'created') {
        sorted.sort((a, b) => {
            const diff = new Date(a.created_at) - new Date(b.created_at);
            return sortOrder === 'asc' ? diff : -diff;
        });
    } else if (sortBy === 'priority') {
        const priorityOrder = { 'Urgent': 4, 'High': 3, 'Medium': 2, 'Low': 1 };
        sorted.sort((a, b) => {
            const diff = priorityOrder[a.priority] - priorityOrder[b.priority];
            return sortOrder === 'asc' ? diff : -diff;
        });
    }
    
    allTasksData = sorted;
    filterTasks();
}

$('#addTaskForm').on('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = $(this).find('button[type="submit"]');
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Creating...');
    
    // Set default time if empty
    let formData = $(this).serialize();
    if (!$('input[name="deadline_time"]').val()) {
        formData += '&deadline_time=23:59';
    }
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: formData + '&action=add&csrf_token=<?php echo getCsrfToken(); ?>',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addTaskModal').modal('hide');
                $('#addTaskForm')[0].reset();
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('danger', response.message || 'Error creating task');
                submitBtn.prop('disabled', false).html('Create Task');
            }
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Error creating task');
            submitBtn.prop('disabled', false).html('Create Task');
        }
    });
});

function editTask(taskId) {
    const task = allTasksData.find(t => t.id == taskId);
    if (!task) return;
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'GET',
        data: { action: 'get_task_details', todo_id: taskId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#edit_todo_id').val(task.id);
                $('#edit_title').val(task.title);
                $('#edit_description').val(task.description || '');
                $('#edit_tags').val(task.tags || '');
                $('#edit_priority').val(task.priority);
                $('#edit_deadline_date').val(task.deadline_date);
                $('#edit_deadline_time').val(task.deadline_time || '23:59');
                $('#edit_assigned_users').val(response.assigned_user_ids);
                $('#editTaskModal').modal('show');
            }
        }
    });
}

$('#editTaskForm').on('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = $(this).find('button[type="submit"]');
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');
    
    // Set default time if empty
    let formData = $(this).serialize();
    if (!$('#edit_deadline_time').val()) {
        formData += '&deadline_time=23:59';
    }
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: formData + '&action=update&csrf_token=<?php echo getCsrfToken(); ?>',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editTaskModal').modal('hide');
                showAlert('success', response.message);
                loadTasks(currentFilter);
            } else {
                showAlert('danger', response.message || 'Error updating task');
                submitBtn.prop('disabled', false).html('Update Task');
            }
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Error updating task');
            submitBtn.prop('disabled', false).html('Update Task');
        }
    });
});

function viewTask(taskId) {
    window.open('<?php echo BASE_URL; ?>pages/todos/view_todo.php?id=' + taskId, '_blank');
}

function promptStatusChange(taskId, newStatus, taskTitle) {
    pendingStatusChange = { taskId: taskId, newStatus: newStatus };
    
    let message = `Are you sure you want to change the status of "<strong>${taskTitle}</strong>" to <strong>${newStatus}</strong>?`;
    
    $('#statusChangeMessage').html(message);
    $('#statusChangeModal').modal('show');
}

$('#confirmStatusChange').on('click', function() {
    if (!pendingStatusChange) return;
    
    const { taskId, newStatus } = pendingStatusChange;
    
    $('#statusChangeModal').modal('hide');
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: { 
            action: 'change_status', 
            todo_id: taskId, 
            status: newStatus,
            csrf_token: '<?php echo getCsrfToken(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                
                // Switch to the appropriate tab based on new status
                let targetTab = 'all';
                if (newStatus === 'Ongoing') {
                    targetTab = 'ongoing';
                } else if (newStatus === 'Completed') {
                    targetTab = 'completed';
                } else if (newStatus === 'Pending') {
                    targetTab = 'pending';
                }
                
                // Switch tab and reload
                $(`[data-filter="${targetTab}"]`).tab('show');
                currentFilter = targetTab;
                loadTasks(targetTab);
            } else {
                showAlert('danger', response.message || 'Error changing status');
            }
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Error changing status');
        }
    });
    
    pendingStatusChange = null;
});

function deleteTask(taskId, title) {
    if (!confirm('Are you sure you want to delete task "' + title + '"?\n\nThis action cannot be undone.')) return;
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: { 
            action: 'delete', 
            todo_id: taskId,
            csrf_token: '<?php echo getCsrfToken(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                loadTasks(currentFilter);
            } else {
                showAlert('danger', response.message || 'Error deleting task');
            }
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Error deleting task');
        }
    });
}

function canChangeStatus(task) {
    return task.status !== 'Completed' && task.status !== 'Cancelled';
}

function canEdit(task) {
    return task.created_by == <?php echo getCurrentUserId(); ?> || <?php echo isAdmin() ? 'true' : 'false'; ?>;
}

function canDelete(task) {
    return task.created_by == <?php echo getCurrentUserId(); ?> || <?php echo isAdmin() ? 'true' : 'false'; ?>;
}

function getNextStatus(currentStatus) {
    const statusFlow = {
        'Assigned': 'Ongoing',
        'Ongoing': 'Completed',
        'Pending': 'Ongoing'
    };
    return statusFlow[currentStatus] || 'Completed';
}

function getStatusIcon(status) {
    const icons = {
        'Ongoing': 'play-circle',
        'Completed': 'check-circle',
        'Cancelled': 'x-circle'
    };
    return icons[status] || 'check';
}

function formatDateTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>