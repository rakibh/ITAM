<?php
// File: pages/network/view_network_info.php
// Purpose: View complete network information details with revision history

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');

// Include config files BEFORE any output
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'includes/functions.php';

requireLogin();

$networkId = intval($_GET['id'] ?? 0);

if (!$networkId) {
    header('Location: list_network_info.php');
    exit();
}

// Get network info with equipment details
$stmt = $pdo->prepare("
    SELECT ni.*, 
           e.id as equipment_id, e.label as equipment_label, e.serial_number,
           et.type_name as equipment_type,
           u.first_name as creator_name, u.last_name as creator_lastname, u.employee_id as creator_id
    FROM network_info ni
    LEFT JOIN equipments e ON ni.equipment_id = e.id
    LEFT JOIN equipment_types et ON e.equipment_type_id = et.id
    LEFT JOIN users u ON ni.created_by = u.id
    WHERE ni.id = ?
");
$stmt->execute([$networkId]);
$network = $stmt->fetch();

if (!$network) {
    header('Location: list_network_info.php');
    exit();
}

// Get revision history (last 100 for admin)
if (isAdmin()) {
    $revisionStmt = $pdo->prepare("
        SELECT nr.*, u.first_name, u.last_name, u.employee_id
        FROM network_revisions nr
        LEFT JOIN users u ON nr.changed_by = u.id
        WHERE nr.network_id = ?
        ORDER BY nr.changed_at DESC
        LIMIT 100
    ");
    $revisionStmt->execute([$networkId]);
    $revisions = $revisionStmt->fetchAll();
}
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-hdd-network me-2"></i><?php echo htmlspecialchars($network['ip_address']); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="edit_network_info.php?id=<?php echo $network['id']; ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <?php if (!$network['equipment_id']): ?>
                <button type="button" class="btn btn-danger" onclick="deleteNetwork()">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <?php else: ?>
                <button type="button" class="btn btn-outline-danger" onclick="unassignNetwork()">
                    <i class="bi bi-link-45deg"></i> Unassign
                </button>
                <?php endif; ?>
            </div>
            <a href="list_network_info.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- Basic Network Information -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Network Details</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="fw-bold">IP Address</label>
                    <p><code class="fs-5"><?php echo htmlspecialchars($network['ip_address']); ?></code></p>
                </div>
                <?php if ($network['mac_address']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">MAC Address</label>
                    <p><code><?php echo htmlspecialchars($network['mac_address']); ?></code></p>
                </div>
                <?php endif; ?>
                <?php if ($network['cable_number']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Cable Number</label>
                    <p><?php echo htmlspecialchars($network['cable_number']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Patch Panel Information -->
    <?php if ($network['patch_panel_number'] || $network['patch_panel_port'] || $network['patch_panel_location']): ?>
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-grid-3x3 me-2"></i>Patch Panel Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if ($network['patch_panel_number']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Patch Panel Number</label>
                    <p><?php echo htmlspecialchars($network['patch_panel_number']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($network['patch_panel_port']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Patch Panel Port</label>
                    <p><?php echo htmlspecialchars($network['patch_panel_port']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($network['patch_panel_location']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Patch Panel Location</label>
                    <p><?php echo htmlspecialchars($network['patch_panel_location']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Switch Information -->
    <?php if ($network['switch_number'] || $network['switch_port'] || $network['switch_location']): ?>
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Switch Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if ($network['switch_number']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Switch Number</label>
                    <p><?php echo htmlspecialchars($network['switch_number']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($network['switch_port']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Switch Port</label>
                    <p><?php echo htmlspecialchars($network['switch_port']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($network['switch_location']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Switch Location</label>
                    <p><?php echo htmlspecialchars($network['switch_location']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Assigned Equipment -->
    <div class="card shadow mb-4 border-<?php echo $network['equipment_id'] ? 'success' : 'secondary'; ?>">
        <div class="card-header bg-<?php echo $network['equipment_id'] ? 'success' : 'secondary'; ?> text-white">
            <h5 class="mb-0">
                <i class="bi bi-pc-display me-2"></i>Assigned Equipment
                <?php if ($network['equipment_id']): ?>
                    <span class="badge bg-white text-success">Assigned</span>
                <?php else: ?>
                    <span class="badge bg-white text-secondary">Unassigned</span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($network['equipment_id']): ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-bold">Equipment Label</label>
                        <p>
                            <a href="<?php echo BASE_URL; ?>pages/equipment/view_equipment.php?id=<?php echo $network['equipment_id']; ?>" 
                               target="_blank" class="text-decoration-none">
                                <?php echo htmlspecialchars($network['equipment_label']); ?>
                                <i class="bi bi-box-arrow-up-right ms-1"></i>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold">Serial Number</label>
                        <p><code><?php echo htmlspecialchars($network['serial_number']); ?></code></p>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold">Equipment Type</label>
                        <p><span class="badge bg-secondary"><?php echo htmlspecialchars($network['equipment_type']); ?></span></p>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="unassignNetwork()">
                        <i class="bi bi-link-45deg"></i> Unassign from Equipment
                    </button>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">This network info is not assigned to any equipment.</p>
                <div class="mt-3">
                    <a href="edit_network_info.php?id=<?php echo $network['id']; ?>" class="btn btn-sm btn-success">
                        <i class="bi bi-link"></i> Assign to Equipment
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Remarks -->
    <?php if ($network['remarks']): ?>
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Remarks / Notes</h5>
        </div>
        <div class="card-body">
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($network['remarks'])); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- System Information -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>System Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="fw-bold">Created By</label>
                    <p>
                        <?php echo htmlspecialchars($network['creator_name'] . ' ' . ($network['creator_lastname'] ?? '')); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($network['creator_id']); ?></small>
                    </p>
                </div>
                <div class="col-md-6">
                    <label class="fw-bold">Created At</label>
                    <p><?php echo formatDate($network['created_at']); ?></p>
                </div>
                <div class="col-md-6">
                    <label class="fw-bold">Last Updated</label>
                    <p><?php echo formatDate($network['updated_at']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Revision History (Admin Only) -->
    <?php if (isAdmin() && !empty($revisions)): ?>
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-clock-history me-2"></i>Revision History
                <span class="badge bg-secondary"><?php echo count($revisions); ?> changes</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="200">Time</th>
                            <th width="200">Changed By</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revisions as $revision): ?>
                        <tr>
                            <td>
                                <small title="<?php echo formatDate($revision['changed_at']); ?>">
                                    <?php echo timeAgo($revision['changed_at']); ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($revision['first_name']): ?>
                                    <?php echo htmlspecialchars($revision['first_name'] . ' ' . ($revision['last_name'] ?? '')); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($revision['employee_id']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">System</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($revision['change_description']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
function deleteNetwork() {
    if (confirm('Delete network info for IP <?php echo htmlspecialchars($network['ip_address']); ?>? This action cannot be undone.')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>ajax/network_operations.php',
            type: 'POST',
            data: {
                action: 'delete',
                network_id: <?php echo $networkId; ?>,
                csrf_token: '<?php echo getCsrfToken(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(() => {
                        window.location.href = 'list_network_info.php';
                    }, 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON?.message || 'Error deleting network info');
            }
        });
    }
}

function unassignNetwork() {
    if (confirm('Unassign this network info from its equipment?')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>ajax/network_operations.php',
            type: 'POST',
            data: {
                action: 'unassign',
                network_id: <?php echo $networkId; ?>,
                csrf_token: '<?php echo getCsrfToken(); ?>'
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
                showAlert('danger', xhr.responseJSON?.message || 'Error unassigning network info');
            }
        });
    }
}
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>