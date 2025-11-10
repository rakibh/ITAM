<?php
// File: pages/equipment/view_equipment.php
// Purpose: View complete equipment details with custom fields, warranty, network, and revision history

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'View Equipment';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$equipmentId = intval($_GET['id'] ?? 0);

if (!$equipmentId) {
    header('Location: list_equipment.php');
    exit();
}

// Get equipment details
$stmt = $pdo->prepare("
    SELECT e.*, et.type_name,
           u.first_name as creator_name, u.last_name as creator_lastname, u.employee_id as creator_id
    FROM equipments e
    JOIN equipment_types et ON e.equipment_type_id = et.id
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.id = ?
");
$stmt->execute([$equipmentId]);
$equipment = $stmt->fetch();

if (!$equipment) {
    header('Location: list_equipment.php');
    exit();
}

// Get custom field values
$customFieldsStmt = $pdo->prepare("
    SELECT ecv.field_value, etf.field_name
    FROM equipment_custom_values ecv
    JOIN equipment_type_fields etf ON ecv.field_id = etf.id
    WHERE ecv.equipment_id = ?
    ORDER BY etf.display_order
");
$customFieldsStmt->execute([$equipmentId]);
$customFields = $customFieldsStmt->fetchAll(PDO::FETCH_GROUP);

// Get warranty documents
$warrantyStmt = $pdo->prepare("SELECT * FROM warranty_documents WHERE equipment_id = ?");
$warrantyStmt->execute([$equipmentId]);
$warrantyDocs = $warrantyStmt->fetchAll();

// Get network info
$networkStmt = $pdo->prepare("SELECT * FROM network_info WHERE equipment_id = ?");
$networkStmt->execute([$equipmentId]);
$networkInfo = $networkStmt->fetch();

// Get revision history (last 100 for admin)
if (isAdmin()) {
    $revisionStmt = $pdo->prepare("
        SELECT er.*, u.first_name, u.last_name, u.employee_id
        FROM equipment_revisions er
        LEFT JOIN users u ON er.changed_by = u.id
        WHERE er.equipment_id = ?
        ORDER BY er.changed_at DESC
        LIMIT 100
    ");
    $revisionStmt->execute([$equipmentId]);
    $revisions = $revisionStmt->fetchAll();
}

$warrantyStatus = getWarrantyStatus($equipment['warranty_expiry_date']);
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-pc-display me-2"></i><?php echo htmlspecialchars($equipment['label']); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="edit_equipment.php?id=<?php echo $equipment['id']; ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <?php if (isAdmin()): ?>
                <button type="button" class="btn btn-danger" onclick="deleteEquipment()">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <?php endif; ?>
            </div>
            <a href="list_equipment.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- Basic Information -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="fw-bold">Equipment Type</label>
                    <p><span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($equipment['type_name']); ?></span></p>
                </div>
                <div class="col-md-4">
                    <label class="fw-bold">Label / Name</label>
                    <p><?php echo htmlspecialchars($equipment['label']); ?></p>
                </div>
                <div class="col-md-4">
                    <label class="fw-bold">Serial Number</label>
                    <p><code class="fs-6"><?php echo htmlspecialchars($equipment['serial_number']); ?></code></p>
                </div>
                <?php if ($equipment['brand']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Brand</label>
                    <p><?php echo htmlspecialchars($equipment['brand']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($equipment['model_number']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Model Number</label>
                    <p><?php echo htmlspecialchars($equipment['model_number']); ?></p>
                </div>
                <?php endif; ?>
                <div class="col-md-4">
                    <label class="fw-bold">Status</label>
                    <p><span class="badge bg-<?php 
                        echo $equipment['status'] === 'In Use' ? 'success' : 
                            ($equipment['status'] === 'Available' ? 'primary' : 
                            ($equipment['status'] === 'Under Repair' ? 'warning' : 'secondary')); 
                    ?> fs-6"><?php echo $equipment['status']; ?></span></p>
                </div>
                <div class="col-md-4">
                    <label class="fw-bold">Condition</label>
                    <p><span class="badge bg-<?php 
                        echo $equipment['condition_status'] === 'New' ? 'success' : 
                            ($equipment['condition_status'] === 'Good' ? 'primary' : 
                            ($equipment['condition_status'] === 'Needs Service' ? 'warning' : 'danger')); 
                    ?> fs-6"><?php echo $equipment['condition_status']; ?></span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Type-Based Custom Fields -->
    <?php if (!empty($customFields)): ?>
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-sliders me-2"></i><?php echo htmlspecialchars($equipment['type_name']); ?> Specifications</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($customFields as $fieldName => $values): ?>
                <div class="col-md-6">
                    <label class="fw-bold"><?php echo htmlspecialchars($fieldName); ?></label>
                    <?php if (count($values) > 1): ?>
                        <ul class="mb-0">
                            <?php foreach ($values as $value): ?>
                                <li><?php echo htmlspecialchars($value['field_value']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php echo htmlspecialchars($values[0]['field_value']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Location & Assignment -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Assignment</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="fw-bold">Location</label>
                    <p><?php echo htmlspecialchars($equipment['location'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-4">
                    <label class="fw-bold">Floor No</label>
                    <p><?php echo htmlspecialchars($equipment['floor_no'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-4">
                    <label class="fw-bold">Department</label>
                    <p><?php echo htmlspecialchars($equipment['department'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-6">
                    <label class="fw-bold">Assigned To</label>
                    <p><?php echo htmlspecialchars($equipment['assigned_to'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-6">
                    <label class="fw-bold">Designation</label>
                    <p><?php echo htmlspecialchars($equipment['designation'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Network Information -->
    <?php if ($networkInfo): ?>
    <div class="card shadow mb-4 border-info">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-ethernet me-2"></i>Network Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="fw-bold">IP Address</label>
                    <p><code class="fs-6"><?php echo htmlspecialchars($networkInfo['ip_address']); ?></code></p>
                </div>
                <?php if ($networkInfo['mac_address']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">MAC Address</label>
                    <p><code><?php echo htmlspecialchars($networkInfo['mac_address']); ?></code></p>
                </div>
                <?php endif; ?>
                <?php if ($networkInfo['cable_number']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Cable Number</label>
                    <p><?php echo htmlspecialchars($networkInfo['cable_number']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($networkInfo['patch_panel_number'] || $networkInfo['patch_panel_port'] || $networkInfo['patch_panel_location']): ?>
                <div class="col-md-12"><hr></div>
                <div class="col-md-12"><h6>Patch Panel</h6></div>
                <?php if ($networkInfo['patch_panel_number']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Panel Number</label>
                    <p><?php echo htmlspecialchars($networkInfo['patch_panel_number']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($networkInfo['patch_panel_port']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Port</label>
                    <p><?php echo htmlspecialchars($networkInfo['patch_panel_port']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($networkInfo['patch_panel_location']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Location</label>
                    <p><?php echo htmlspecialchars($networkInfo['patch_panel_location']); ?></p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <?php if ($networkInfo['switch_number'] || $networkInfo['switch_port'] || $networkInfo['switch_location']): ?>
                <div class="col-md-12"><hr></div>
                <div class="col-md-12"><h6>Switch</h6></div>
                <?php if ($networkInfo['switch_number']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Switch Number</label>
                    <p><?php echo htmlspecialchars($networkInfo['switch_number']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($networkInfo['switch_port']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Port</label>
                    <p><?php echo htmlspecialchars($networkInfo['switch_port']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($networkInfo['switch_location']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Location</label>
                    <p><?php echo htmlspecialchars($networkInfo['switch_location']); ?></p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <?php if ($networkInfo['remarks']): ?>
                <div class="col-12">
                    <label class="fw-bold">Remarks</label>
                    <p><?php echo nl2br(htmlspecialchars($networkInfo['remarks'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <a href="<?php echo BASE_URL; ?>pages/network/view_network_info.php?ip=<?php echo urlencode($networkInfo['ip_address']); ?>" 
                   class="btn btn-sm btn-outline-info" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i> View Full Network Details
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Warranty Information -->
    <div class="card shadow mb-4 border-<?php 
        echo $warrantyStatus === 'Expired' ? 'danger' : 
            (strpos($warrantyStatus, 'Expiring') !== false ? 'warning' : 
            ($warrantyStatus === 'Active' ? 'success' : 'secondary')); 
    ?>">
        <div class="card-header bg-<?php 
            echo $warrantyStatus === 'Expired' ? 'danger' : 
                (strpos($warrantyStatus, 'Expiring') !== false ? 'warning' : 
                ($warrantyStatus === 'Active' ? 'success' : 'secondary')); 
        ?> text-white">
            <h5 class="mb-0">
                <i class="bi bi-shield-check me-2"></i>Warranty Information 
                <span class="badge bg-white text-dark"><?php echo $warrantyStatus; ?></span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if ($equipment['seller_company']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Seller Company</label>
                    <p><?php echo htmlspecialchars($equipment['seller_company']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($equipment['purchase_date']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Purchase Date</label>
                    <p><?php echo formatDate($equipment['purchase_date'], 'd M Y'); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($equipment['warranty_expiry_date']): ?>
                <div class="col-md-4">
                    <label class="fw-bold">Warranty Expiry Date</label>
                    <p><?php echo formatDate($equipment['warranty_expiry_date'], 'd M Y'); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($warrantyDocs)): ?>
                <div class="col-12">
                    <label class="fw-bold">Warranty Documents</label>
                    <ul class="list-group">
                        <?php foreach ($warrantyDocs as $doc): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-file-earmark-pdf text-danger"></i> <?php echo htmlspecialchars($doc['file_name']); ?></span>
                            <a href="<?php echo BASE_URL; ?>uploads/warranty/<?php echo $doc['file_path']; ?>" 
                               class="btn btn-sm btn-outline-primary" target="_blank" download>
                                <i class="bi bi-download"></i> Download
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if (!$equipment['seller_company'] && !$equipment['purchase_date'] && !$equipment['warranty_expiry_date'] && empty($warrantyDocs)): ?>
                <div class="col-12">
                    <p class="text-muted">No warranty information available</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Optional Custom Fields -->
    <?php if ($equipment['custom_label_1'] || $equipment['custom_label_2']): ?>
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-plus-square me-2"></i>Additional Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if ($equipment['custom_label_1']): ?>
                <div class="col-md-6">
                    <label class="fw-bold"><?php echo htmlspecialchars($equipment['custom_label_1']); ?></label>
                    <p><?php echo htmlspecialchars($equipment['custom_value_1'] ?? 'N/A'); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($equipment['custom_label_2']): ?>
                <div class="col-md-6">
                    <label class="fw-bold"><?php echo htmlspecialchars($equipment['custom_label_2']); ?></label>
                    <p><?php echo htmlspecialchars($equipment['custom_value_2'] ?? 'N/A'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Remarks -->
    <?php if ($equipment['remarks']): ?>
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Remarks / Notes</h5>
        </div>
        <div class="card-body">
            <p class="mb-0"><?php echo nl2br(htmlspecialchars($equipment['remarks'])); ?></p>
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
                    <p><?php echo htmlspecialchars($equipment['creator_name'] . ' ' . ($equipment['creator_lastname'] ?? '')); ?>
                       <br><small class="text-muted"><?php echo htmlspecialchars($equipment['creator_id']); ?></small></p>
                </div>
                <div class="col-md-6">
                    <label class="fw-bold">Created At</label>
                    <p><?php echo formatDate($equipment['created_at']); ?></p>
                </div>
                <div class="col-md-6">
                    <label class="fw-bold">Last Updated</label>
                    <p><?php echo formatDate($equipment['updated_at']); ?></p>
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
                            <td><small title="<?php echo formatDate($revision['changed_at']); ?>">
                                <?php echo timeAgo($revision['changed_at']); ?>
                            </small></td>
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
function deleteEquipment() {
    if (confirm('Are you sure you want to delete this equipment? This action cannot be undone.')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>ajax/equipment_operations.php',
            type: 'POST',
            data: {
                action: 'delete',
                equipment_id: <?php echo $equipmentId; ?>,
                csrf_token: '<?php echo getCsrfToken(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(() => {
                        window.location.href = 'list_equipment.php';
                    }, 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON?.message || 'Error deleting equipment');
            }
        });
    }
}
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>