<?php
// File: pages/todos/list_todos.php
// Purpose: Task board with tabs, search, filters, and drag-drop reordering

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Task Management';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

// Get all active users for assignment dropdown
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
    <ul class="nav nav-tabs mb-3" id="taskTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" 
                    type="button" role="tab" data-tab="all">
                All <span class="badge bg-secondary" id="count-all">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="assigned-tab" data-bs-toggle="tab" data-bs-target="#assigned" 
                    type="button" role="tab" data-tab="assigned">
                Assigned <span class="badge bg-primary" id="count-assigned">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="ongoing-tab" data-bs-toggle="tab" data-bs-target="#ongoing" 
                    type="button" role="tab" data-tab="ongoing">
                Ongoing <span class="badge bg-warning" id="count-ongoing">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" 
                    type="button" role="tab" data-tab="pending">
                Pending <span class="badge bg-danger" id="count-pending">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" 
                    type="button" role="tab" data-tab="completed">
                Completed <span class="badge bg-success" id="count-completed">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" 
                    type="button" role="tab" data-tab="cancelled">
                Cancelled <span class="badge bg-secondary" id="count-cancelled">0</span>
            </button>
        </li>
    </ul>

    <!-- Search and Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchTasks" placeholder="Search tasks...">
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
                    <input type="date" class="form-control" id="filterDate" placeholder="Filter by date">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="sortBy">
                        <option value="deadline_date">Sort by Deadline</option>
                        <option value="created_at">Sort by Created</option>
                        <option value="priority">Sort by Priority</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="sortOrder">
                        <option value="ASC">Ascending</option>
                        <option value="DESC">Descending</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Task List Container -->
    <div class="tab-content" id="taskTabContent">
        <div class="tab-pane fade show active" id="all" role="tabpanel">
            <div id="taskList" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                <!-- Tasks will be loaded here via AJAX -->
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
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
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tags</label>
                            <input type="text" class="form-control" name="tags" placeholder="e.g., urgent, hardware">
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
                            <label class="form-label">Deadline Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="deadline_time" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Assign To <span class="text-danger">*</span></label>
                            <select class="form-select" name="assigned_users[]" multiple size="6" required>
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

<!-- View Task Modal -->
<div class="modal fade" id="viewTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTaskTitle">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewTaskContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
.task-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    background: white;
    cursor: move;
    transition: all 0.3s;
}
.task-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.task-card.dragging {
    opacity: 0.5;
}
.priority-low { border-left: 4px solid #6c757d; }
.priority-medium { border-left: 4px solid #17a2b8; }
.priority-high { border-left: 4px solid #ffc107; }
.priority-urgent { border-left: 4px solid #dc3545; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let currentTab = 'all';
let sortable = null;

$(document).ready(function() {
    // Load tasks on page load
    loadTasks();
    
    // Tab change
    $('[data-tab]').on('click', function() {
        currentTab = $(this).data('tab');
        loadTasks();
    });
    
    // Search with debounce
    let searchTimeout;
    $('#searchTasks').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadTasks, 500);
    });
    
    // Filter changes
    $('#filterPriority, #filterDate, #sortBy, #sortOrder').on('change', loadTasks);
    
    // Add task form submit
    $('#addTaskForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
            type: 'POST',
            data: $(this).serialize() + '&action=add',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addTaskModal').modal('hide');
                    $('#addTaskForm')[0].reset();
                    showAlert('success', response.message);
                    loadTasks();
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON?.message || 'Error creating task');
            }
        });
    });
});

function loadTasks() {
    const search = $('#searchTasks').val();
    const priority = $('#filterPriority').val();
    const dateFilter = $('#filterDate').val();
    const sortBy = $('#sortBy').val();
    const sortOrder = $('#sortOrder').val();
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'GET',
        data: {
            action: 'get_tasks',
            tab: currentTab,
            search: search,
            priority: priority,
            date_filter: dateFilter,
            sort_by: sortBy,
            sort_order: sortOrder
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderTasks(response.tasks);
                updateCounts(response.counts);
                initSortable();
            }
        },
        error: function() {
            $('#taskList').html('<div class="col-12"><div class="alert alert-danger">Error loading tasks</div></div>');
        }
    });
}

function renderTasks(tasks) {
    let html = '';
    
    if (tasks.length === 0) {
        html = '<div class="col-12 text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No tasks found</div>';
    } else {
        tasks.forEach(function(task) {
            const priorityClass = 'priority-' + task.priority.toLowerCase();
            const statusBadge = getStatusBadge(task.status);
            const deadline = new Date(task.deadline_date + ' ' + task.deadline_time);
            const isOverdue = deadline < new Date() && task.status !== 'Completed';
            
            html += `
                <div class="col" data-task-id="${task.id}">
                    <div class="task-card ${priorityClass}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">${escapeHtml(task.title)}</h6>
                            <span class="badge ${statusBadge.class}">${statusBadge.text}</span>
                        </div>
                        ${task.description ? '<p class="text-muted small mb-2">' + escapeHtml(task.description.substring(0, 100)) + (task.description.length > 100 ? '...' : '') + '</p>' : ''}
                        <div class="d-flex justify-content-between align-items-center text-small">
                            <div>
                                <i class="bi bi-flag-fill me-1"></i>
                                <span class="badge priority-${task.priority.toLowerCase()}">${task.priority}</span>
                            </div>
                            <div class="${isOverdue ? 'text-danger' : ''}">
                                <i class="bi bi-clock me-1"></i>
                                ${formatDeadline(deadline)}
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <small class="text-muted">
                                <i class="bi bi-person me-1"></i>${escapeHtml(task.assigned_to || 'Unassigned')}
                            </small>
                        </div>
                        <div class="mt-2 d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewTask(${task.id})">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="changeStatus(${task.id}, 'Ongoing')" ${task.status === 'Ongoing' ? 'disabled' : ''}>
                                <i class="bi bi-play-fill"></i> Start
                            </button>
                            ${task.status === 'Ongoing' ? `<button class="btn btn-sm btn-outline-success" onclick="changeStatus(${task.id}, 'Completed')"><i class="bi bi-check-circle"></i> Complete</button>` : ''}
                            <button class="btn btn-sm btn-outline-danger" onclick="changeStatus(${task.id}, 'Cancelled')">
                                <i class="bi bi-x-circle"></i> Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    $('#taskList').html(html);
}

function updateCounts(counts) {
    $('#count-all').text(counts.all_count);
    $('#count-assigned').text(counts.assigned_count);
    $('#count-ongoing').text(counts.ongoing_count);
    $('#count-pending').text(counts.pending_count);
    $('#count-completed').text(counts.completed_count);
    $('#count-cancelled').text(counts.cancelled_count);
}

function getStatusBadge(status) {
    const badges = {
        'Assigned': { class: 'bg-primary', text: 'Assigned' },
        'Ongoing': { class: 'bg-warning', text: 'Ongoing' },
        'Pending': { class: 'bg-danger', text: 'Pending' },
        'Completed': { class: 'bg-success', text: 'Completed' },
        'Cancelled': { class: 'bg-secondary', text: 'Cancelled' }
    };
    return badges[status] || badges['Assigned'];
}

function formatDeadline(date) {
    const now = new Date();
    const diff = date - now;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    
    if (diff < 0) return 'Overdue';
    if (days === 0) return `${hours}h remaining`;
    return `${days}d ${hours}h`;
}

function viewTask(taskId) {
    window.open('<?php echo BASE_URL; ?>pages/todos/view_todo.php?id=' + taskId, '_blank');
}

function changeStatus(taskId, newStatus) {
    if (!confirm(`Are you sure you want to change task status to "${newStatus}"?`)) {
        return;
    }
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/todo_operations.php',
        type: 'POST',
        data: {
            action: 'update_status',
            todo_id: taskId,
            status: newStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                loadTasks();
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Error updating status');
        }
    });
}

function initSortable() {
    if (sortable) {
        sortable.destroy();
    }
    
    const taskList = document.getElementById('taskList');
    if (taskList && currentTab === 'all') {
        sortable = new Sortable(taskList, {
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function(evt) {
                const orderedIds = [];
                $('#taskList .col').each(function(index) {
                    orderedIds.push($(this).data('task-id'));
                });
                
                $.post('<?php echo BASE_URL; ?>ajax/todo_operations.php', {
                    action: 'update_order',
                    ordered_ids: orderedIds
                });
            }
        });
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>