<?php
// File: pages/notifications/list_notifications.php
// Purpose: Display all notifications with filters, search, and pagination

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Notifications';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$userId = getCurrentUserId();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Filters
$typeFilter = sanitize($_GET['type'] ?? '');
$eventFilter = sanitize($_GET['event'] ?? '');
$readFilter = sanitize($_GET['read'] ?? '');
$ackFilter = sanitize($_GET['ack'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');

// Build WHERE clause
$where = ['1=1'];
$params = [$userId];

if (!empty($typeFilter)) {
    $where[] = "n.type = ?";
    $params[] = $typeFilter;
}

if (!empty($eventFilter)) {
    $where[] = "n.event = ?";
    $params[] = $eventFilter;
}

if ($readFilter !== '') {
    $where[] = "nus.is_read = ?";
    $params[] = ($readFilter === '1') ? 1 : 0;
}

if ($ackFilter !== '') {
    $where[] = "nus.is_acknowledged = ?";
    $params[] = ($ackFilter === '1') ? 1 : 0;
}

if (!empty($search)) {
    $where[] = "(n.title LIKE ? OR n.message LIKE ? OR n.created_by LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($dateFrom)) {
    $where[] = "DATE(n.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $where[] = "DATE(n.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = 'nus.user_id = ? AND ' . implode(' AND ', $where);

// Get total count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM notifications n
    JOIN notification_user_status nus ON n.id = nus.notification_id
    WHERE {$whereClause}
");
$countStmt->execute($params);
$totalNotifications = $countStmt->fetch()['total'];
$totalPages = ceil($totalNotifications / $perPage);

// Get notifications
$stmt = $pdo->prepare("
    SELECT n.*, nus.is_read, nus.is_acknowledged, nus.read_at, nus.acknowledged_at
    FROM notifications n
    JOIN notification_user_status nus ON n.id = nus.notification_id
    WHERE {$whereClause}
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Get unacknowledged count
$unackStmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM notification_user_status 
    WHERE user_id = ? AND is_acknowledged = FALSE
");
$unackStmt->execute([$userId]);
$unackCount = $unackStmt->fetch()['count'];
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-bell me-2"></i>Notifications
            <?php if ($unackCount > 0): ?>
                <span class="badge bg-danger"><?php echo $unackCount; ?> Unacknowledged</span>
            <?php endif; ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="markAllAsRead()">
                    <i class="bi bi-check-all"></i> Mark All Read
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="acknowledgeAll()">
                    <i class="bi bi-check-circle"></i> Acknowledge All
                </button>
            </div>
            <?php if (isAdmin()): ?>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearOldNotifications()">
                <i class="bi bi-trash"></i> Clear Old (30+ days)
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search title, message, or user..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="Equipment" <?php echo $typeFilter === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                        <option value="Network" <?php echo $typeFilter === 'Network' ? 'selected' : ''; ?>>Network</option>
                        <option value="User" <?php echo $typeFilter === 'User' ? 'selected' : ''; ?>>User</option>
                        <option value="Todo" <?php echo $typeFilter === 'Todo' ? 'selected' : ''; ?>>Task</option>
                        <option value="System" <?php echo $typeFilter === 'System' ? 'selected' : ''; ?>>System</option>
                        <option value="Warranty" <?php echo $typeFilter === 'Warranty' ? 'selected' : ''; ?>>Warranty</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="event">
                        <option value="">All Events</option>
                        <option value="add" <?php echo $eventFilter === 'add' ? 'selected' : ''; ?>>Add</option>
                        <option value="update" <?php echo $eventFilter === 'update' ? 'selected' : ''; ?>>Update</option>
                        <option value="delete" <?php echo $eventFilter === 'delete' ? 'selected' : ''; ?>>Delete</option>
                        <option value="assign" <?php echo $eventFilter === 'assign' ? 'selected' : ''; ?>>Assign</option>
                        <option value="unassign" <?php echo $eventFilter === 'unassign' ? 'selected' : ''; ?>>Unassign</option>
                        <option value="status_change" <?php echo $eventFilter === 'status_change' ? 'selected' : ''; ?>>Status Change</option>
                        <option value="role_change" <?php echo $eventFilter === 'role_change' ? 'selected' : ''; ?>>Role Change</option>
                        <option value="expiring" <?php echo $eventFilter === 'expiring' ? 'selected' : ''; ?>>Expiring</option>
                        <option value="expired" <?php echo $eventFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <select class="form-select" name="read">
                        <option value="">All</option>
                        <option value="1" <?php echo $readFilter === '1' ? 'selected' : ''; ?>>Read</option>
                        <option value="0" <?php echo $readFilter === '0' ? 'selected' : ''; ?>>Unread</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="ack">
                        <option value="">All Status</option>
                        <option value="1" <?php echo $ackFilter === '1' ? 'selected' : ''; ?>>Acknowledged</option>
                        <option value="0" <?php echo $ackFilter === '0' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_from" placeholder="From" 
                           value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_to" placeholder="To" 
                           value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="list_notifications.php" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="card shadow">
        <div class="card-header">
            <h6 class="m-0">Total: <?php echo $totalNotifications; ?> notifications</h6>
        </div>
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-bell-slash" style="font-size: 3rem;"></i>
                    <p class="mt-3">No notifications found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">Type</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th width="120">From</th>
                                <th width="150">Created</th>
                                <th width="80">Status</th>
                                <th width="150">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notif): ?>
                            <tr class="<?php echo !$notif['is_read'] ? 'fw-bold' : ''; ?>" data-notif-id="<?php echo $notif['id']; ?>">
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $notif['type'] === 'Equipment' ? 'primary' : 
                                            ($notif['type'] === 'Network' ? 'info' : 
                                            ($notif['type'] === 'User' ? 'success' : 
                                            ($notif['type'] === 'Todo' ? 'warning' : 
                                            ($notif['type'] === 'Warranty' ? 'danger' : 'secondary')))); 
                                    ?>">
                                        <i class="bi bi-<?php 
                                            echo $notif['type'] === 'Equipment' ? 'pc-display' : 
                                                ($notif['type'] === 'Network' ? 'ethernet' : 
                                                ($notif['type'] === 'User' ? 'person' : 
                                                ($notif['type'] === 'Todo' ? 'check-square' : 
                                                ($notif['type'] === 'Warranty' ? 'shield-check' : 'gear')))); 
                                        ?>"></i>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($notif['title']); ?></td>
                                <td>
                                    <?php 
                                    $message = htmlspecialchars($notif['message']);
                                    echo strlen($message) > 100 ? substr($message, 0, 100) . '...' : $message;
                                    ?>
                                </td>
                                <td><small><?php echo htmlspecialchars($notif['created_by']); ?></small></td>
                                <td>
                                    <small title="<?php echo formatDate($notif['created_at']); ?>">
                                        <?php echo timeAgo($notif['created_at']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($notif['is_acknowledged']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> Done
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-clock"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if (!$notif['is_read']): ?>
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="markAsRead(<?php echo $notif['id']; ?>)" title="Mark as Read">
                                            <i class="bi bi-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if (!$notif['is_acknowledged']): ?>
                                        <button type="button" class="btn btn-outline-success" 
                                                onclick="acknowledgeNotification(<?php echo $notif['id']; ?>)" title="Acknowledge">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-outline-info" 
                                                data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $notif['id']; ?>" 
                                                title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        
                                        <?php if (isAdmin()): ?>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteNotification(<?php echo $notif['id']; ?>)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal<?php echo $notif['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo htmlspecialchars($notif['title']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="fw-bold">Type:</label>
                                                <span class="badge bg-primary"><?php echo $notif['type']; ?></span>
                                                <span class="badge bg-secondary"><?php echo ucfirst($notif['event']); ?></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="fw-bold">Message:</label>
                                                <p><?php echo nl2br(htmlspecialchars($notif['message'])); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="fw-bold">From:</label>
                                                <p><?php echo htmlspecialchars($notif['created_by']); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="fw-bold">Created:</label>
                                                <p><?php echo formatDate($notif['created_at']); ?></p>
                                            </div>
                                            <?php if ($notif['context_json']): ?>
                                            <div class="mb-3">
                                                <label class="fw-bold">Details:</label>
                                                <pre class="bg-light p-2 rounded"><?php echo htmlspecialchars($notif['context_json']); ?></pre>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <?php if (!$notif['is_acknowledged']): ?>
                                            <button type="button" class="btn btn-success" 
                                                    onclick="acknowledgeNotification(<?php echo $notif['id']; ?>)">
                                                <i class="bi bi-check-circle"></i> Acknowledge
                                            </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <?php 
                    $queryParams = http_build_query(array_filter([
                        'search' => $search,
                        'type' => $typeFilter,
                        'event' => $eventFilter,
                        'read' => $readFilter,
                        'ack' => $ackFilter,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo
                    ]));
                    $baseUrl = 'list_notifications.php?' . $queryParams;
                    echo getPaginationHTML($page, $totalPages, $baseUrl);
                    ?>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Mark single notification as read
function markAsRead(notificationId) {
    $.post('<?php echo BASE_URL; ?>ajax/notification_operations.php', {
        action: 'mark_read',
        notification_id: notificationId
    }, function() {
        $('tr[data-notif-id="' + notificationId + '"]').removeClass('fw-bold');
        $('tr[data-notif-id="' + notificationId + '"]').find('.btn-outline-primary').remove();
        showAlert('success', 'Marked as read');
    });
}

// Acknowledge single notification
function acknowledgeNotification(notificationId) {
    $.post('<?php echo BASE_URL; ?>ajax/notification_operations.php', {
        action: 'acknowledge',
        notification_id: notificationId
    }, function() {
        location.reload();
    });
}

// Mark all as read
function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        $.post('<?php echo BASE_URL; ?>ajax/notification_operations.php', {
            action: 'mark_all_read',
            user_id: <?php echo $userId; ?>
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    }
}

// Acknowledge all
function acknowledgeAll() {
    if (confirm('Acknowledge all notifications?')) {
        $.post('<?php echo BASE_URL; ?>ajax/notification_operations.php', {
            action: 'acknowledge_all',
            user_id: <?php echo $userId; ?>
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    }
}

// Delete notification (admin only)
function deleteNotification(notificationId) {
    if (confirm('Delete this notification? This action cannot be undone.')) {
        $.post('<?php echo BASE_URL; ?>ajax/notification_operations.php', {
            action: 'delete',
            notification_id: notificationId,
            csrf_token: '<?php echo getCsrfToken(); ?>'
        }, function(response) {
            if (response.success) {
                showAlert('success', 'Notification deleted');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('danger', response.message);
            }
        });
    }
}

// Clear old notifications (admin only)
function clearOldNotifications() {
    if (confirm('Delete all notifications older than 30 days? This action cannot be undone.')) {
        $.post('<?php echo BASE_URL; ?>ajax/notification_operations.php', {
            action: 'clear_old',
            csrf_token: '<?php echo getCsrfToken(); ?>'
        }, function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('danger', response.message);
            }
        });
    }
}
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>