<?php
// File: pages/dashboard_user.php
// Purpose: User dashboard showing personal tasks and assigned equipment

define('ROOT_PATH', dirname(__DIR__) . '/');
$pageTitle = 'Dashboard';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$userId = getCurrentUserId();

// Get user's assigned tasks
$stmt = $pdo->prepare("
    SELECT t.*, 
           (SELECT GROUP_CONCAT(CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) SEPARATOR ', ')
            FROM todo_assignments ta
            JOIN users u ON ta.user_id = u.id
            WHERE ta.todo_id = t.id) as assigned_to
    FROM todos t
    JOIN todo_assignments ta ON t.id = ta.todo_id
    WHERE ta.user_id = ?
    ORDER BY t.deadline_date ASC, t.deadline_time ASC
    LIMIT 10
");
$stmt->execute([$userId]);
$userTasks = $stmt->fetchAll();

// Get task statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN t.status = 'Assigned' THEN 1 ELSE 0 END) as assigned,
        SUM(CASE WHEN t.status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing,
        SUM(CASE WHEN t.status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed
    FROM todos t
    JOIN todo_assignments ta ON t.id = ta.todo_id
    WHERE ta.user_id = ?
");
$stmt->execute([$userId]);
$taskStats = $stmt->fetch();

// Get recent notifications
$stmt = $pdo->prepare("
    SELECT n.*, nus.is_read, nus.is_acknowledged
    FROM notifications n
    JOIN notification_user_status nus ON n.id = nus.notification_id
    WHERE nus.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$recentNotifications = $stmt->fetchAll();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<!-- Main Content -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-speedometer2 me-2"></i>Welcome, <?php echo getCurrentUserName(); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-calendar"></i> <?php echo date('d M Y'); ?>
                </button>
            </div>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- Task Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['total']; ?></h5>
                    <p class="card-text">Total Tasks</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['assigned']; ?></h5>
                    <p class="card-text">Assigned</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['ongoing']; ?></h5>
                    <p class="card-text">Ongoing</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['completed']; ?></h5>
                    <p class="card-text">Completed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- My Tasks -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">My Tasks</h6>
                    <a href="<?php echo BASE_URL; ?>pages/todos/list_todos.php" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($userTasks)): ?>
                        <p class="text-muted text-center py-4">No tasks assigned to you yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Deadline</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userTasks as $task): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                                        <td>
                                            <span class="badge priority-<?php echo strtolower($task['priority']); ?>">
                                                <?php echo $task['priority']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-<?php echo strtolower($task['status']); ?>">
                                                <?php echo $task['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($task['deadline_date'] . ' ' . $task['deadline_time']); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>pages/todos/view_todo.php?id=<?php echo $task['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Notifications</h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($recentNotifications)): ?>
                        <p class="text-muted text-center py-4">No notifications</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recentNotifications as $notif): ?>
                            <a href="#" class="list-group-item list-group-item-action <?php echo $notif['is_read'] ? '' : 'fw-bold'; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                    <small><?php echo timeAgo($notif['created_at']); ?></small>
                                </div>
                                <p class="mb-1 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_URL; ?>pages/users/profile.php" class="btn btn-outline-primary btn-lg w-100">
                                <i class="bi bi-person-circle d-block fs-1 mb-2"></i>
                                My Profile
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_URL; ?>pages/equipment/list_equipment.php" class="btn btn-outline-success btn-lg w-100">
                                <i class="bi bi-pc-display d-block fs-1 mb-2"></i>
                                Equipment
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_URL; ?>pages/network/list_network_info.php" class="btn btn-outline-info btn-lg w-100">
                                <i class="bi bi-ethernet d-block fs-1 mb-2"></i>
                                Network
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_URL; ?>pages/todos/list_todos.php" class="btn btn-outline-warning btn-lg w-100">
                                <i class="bi bi-check2-square d-block fs-1 mb-2"></i>
                                Tasks
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>