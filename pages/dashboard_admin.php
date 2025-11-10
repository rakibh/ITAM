<?php
// File: pages/dashboard_admin.php
// Purpose: Admin dashboard showing system overview and statistics

// Set page title before including header
$pageTitle = 'Admin Dashboard';

// Include header (it will handle ROOT_PATH and all configs)
require_once '../layouts/header.php';
requireAdmin();

// Get statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $stmt->fetch()['count'];

// Active users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'Active'");
$stats['active_users'] = $stmt->fetch()['count'];

// Total equipment
$stmt = $pdo->query("SELECT COUNT(*) as count FROM equipments");
$stats['total_equipment'] = $stmt->fetch()['count'];

// Equipment by status
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM equipments 
    GROUP BY status
");
$equipmentByStatus = $stmt->fetchAll();

// Network IPs
$stmt = $pdo->query("SELECT COUNT(*) as count FROM network_info");
$stats['total_ips'] = $stmt->fetch()['count'];

// Assigned IPs
$stmt = $pdo->query("SELECT COUNT(*) as count FROM network_info WHERE equipment_id IS NOT NULL");
$stats['assigned_ips'] = $stmt->fetch()['count'];

// Total tasks
$stmt = $pdo->query("SELECT COUNT(*) as count FROM todos");
$stats['total_tasks'] = $stmt->fetch()['count'];

// Tasks by status
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM todos 
    GROUP BY status
");
$tasksByStatus = $stmt->fetchAll();

// Warranty expiring in 30 days
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM equipments 
    WHERE warranty_expiry_date IS NOT NULL 
    AND warranty_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");
$stats['warranties_expiring'] = $stmt->fetch()['count'];

// Expired warranties
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM equipments 
    WHERE warranty_expiry_date IS NOT NULL 
    AND warranty_expiry_date < CURDATE()
");
$stats['warranties_expired'] = $stmt->fetch()['count'];

// Recent activities
$recentActivities = $pdo->query("
    SELECT log_type, module, action, description, created_at 
    FROM system_logs 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();
?>

<?php require_once '../layouts/sidebar.php'; ?>

<!-- Main Content -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-calendar"></i> Today: <?php echo date('d M Y'); ?>
                </button>
            </div>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <!-- Users Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                            <small class="text-muted"><?php echo $stats['active_users']; ?> Active</small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipment Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Equipment</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_equipment']; ?></div>
                            <small class="text-muted">
                                <a href="<?php echo BASE_URL; ?>pages/equipment/list_equipment.php">View All</a>
                            </small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-pc-display text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network IPs Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Network IPs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_ips']; ?></div>
                            <small class="text-muted"><?php echo $stats['assigned_ips']; ?> Assigned</small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-ethernet text-info" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_tasks']; ?></div>
                            <small class="text-muted">
                                <a href="<?php echo BASE_URL; ?>pages/todos/list_todos.php">Manage Tasks</a>
                            </small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check2-square text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Equipment Status Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Equipment by Status</h6>
                </div>
                <div class="card-body">
                    <canvas id="equipmentStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Task Status Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Tasks by Status</h6>
                </div>
                <div class="card-body">
                    <canvas id="taskStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Warranty Alerts -->
    <?php if ($stats['warranties_expiring'] > 0 || $stats['warranties_expired'] > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning" role="alert">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Warranty Alerts</h5>
                <hr>
                <?php if ($stats['warranties_expired'] > 0): ?>
                <p><strong><?php echo $stats['warranties_expired']; ?></strong> equipment warranties have expired.</p>
                <?php endif; ?>
                <?php if ($stats['warranties_expiring'] > 0): ?>
                <p><strong><?php echo $stats['warranties_expiring']; ?></strong> equipment warranties expiring in the next 30 days.</p>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>pages/equipment/list_equipment.php?filter=warranty" class="btn btn-sm btn-warning">
                    View Details
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Recent System Activities</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>Module</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivities as $activity): ?>
                                <tr>
                                    <td><?php echo formatDate($activity['created_at']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $activity['log_type'] === 'Info' ? 'info' : 
                                                ($activity['log_type'] === 'Warning' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo $activity['log_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($activity['module']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo BASE_URL; ?>pages/tools/system_logs.php" class="btn btn-sm btn-primary">
                            View All Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Equipment Status Chart
const equipmentData = <?php echo json_encode($equipmentByStatus); ?>;
const equipmentLabels = equipmentData.map(item => item.status);
const equipmentCounts = equipmentData.map(item => item.count);

new Chart(document.getElementById('equipmentStatusChart'), {
    type: 'doughnut',
    data: {
        labels: equipmentLabels,
        datasets: [{
            data: equipmentCounts,
            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});

// Task Status Chart
const taskData = <?php echo json_encode($tasksByStatus); ?>;
const taskLabels = taskData.map(item => item.status);
const taskCounts = taskData.map(item => item.count);

new Chart(document.getElementById('taskStatusChart'), {
    type: 'bar',
    data: {
        labels: taskLabels,
        datasets: [{
            label: 'Tasks',
            data: taskCounts,
            backgroundColor: '#667eea'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php require_once '../layouts/footer.php'; ?>