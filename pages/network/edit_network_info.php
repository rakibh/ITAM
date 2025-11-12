<?php
// File: pages/network/edit_network_info.php
// Purpose: Edit existing network information

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

// Get network info
$stmt = $pdo->prepare("
    SELECT ni.*, e.label as equipment_label, e.serial_number, e.id as equipment_id
    FROM network_info ni
    LEFT JOIN equipments e ON ni.equipment_id = e.id
    WHERE ni.id = ?
");
$stmt->execute([$networkId]);
$network = $stmt->fetch();

if (!$network) {
    header('Location: list_network_info.php');
    exit();
}

// Handle form submission BEFORE including header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (verifyCsrfToken($_POST['csrf_token'])) {
        try {
            $pdo->beginTransaction();
            
            // Network fields
            $ipAddress = sanitize($_POST['ip_address'] ?? '');
            $macAddress = sanitize($_POST['mac_address'] ?? '') ?: null;
            $cableNumber = sanitize($_POST['cable_number'] ?? '') ?: null;
            $patchPanelNumber = sanitize($_POST['patch_panel_number'] ?? '') ?: null;
            $patchPanelPort = sanitize($_POST['patch_panel_port'] ?? '') ?: null;
            $patchPanelLocation = sanitize($_POST['patch_panel_location'] ?? '') ?: null;
            $switchNumber = sanitize($_POST['switch_number'] ?? '') ?: null;
            $switchPort = sanitize($_POST['switch_port'] ?? '') ?: null;
            $switchLocation = sanitize($_POST['switch_location'] ?? '') ?: null;
            $remarks = sanitize($_POST['remarks'] ?? '') ?: null;
            $equipmentId = !empty($_POST['equipment_id']) ? intval($_POST['equipment_id']) : null;
            
            // Validation
            if (empty($ipAddress)) {
                throw new Exception('IP Address is required');
            }
            
            // Validate IP format
            if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                throw new Exception('Invalid IP address format');
            }
            
            // Check IP uniqueness (excluding current record)
            $checkStmt = $pdo->prepare("SELECT id FROM network_info WHERE ip_address = ? AND id != ?");
            $checkStmt->execute([$ipAddress, $networkId]);
            if ($checkStmt->fetch()) {
                throw new Exception('IP address already exists');
            }
            
            // Check MAC uniqueness (if provided and not N/A)
            if ($macAddress && strtoupper($macAddress) !== 'N/A') {
                $macCheckStmt = $pdo->prepare("SELECT id FROM network_info WHERE mac_address = ? AND id != ?");
                $macCheckStmt->execute([$macAddress, $networkId]);
                if ($macCheckStmt->fetch()) {
                    throw new Exception('MAC address already exists');
                }
            }
            
            // If equipment changed, verify new equipment is available
            if ($equipmentId && $equipmentId != $network['equipment_id']) {
                $eqCheckStmt = $pdo->prepare("SELECT id FROM network_info WHERE equipment_id = ? AND id != ?");
                $eqCheckStmt->execute([$equipmentId, $networkId]);
                if ($eqCheckStmt->fetch()) {
                    throw new Exception('Selected equipment already has network info assigned');
                }
            }
            
            // Track changes for revision
            $changes = [];
            if ($ipAddress !== $network['ip_address']) {
                $changes[] = "IP changed from {$network['ip_address']} to {$ipAddress}";
            }
            if ($equipmentId != $network['equipment_id']) {
                if ($equipmentId && !$network['equipment_id']) {
                    $eqStmt = $pdo->prepare("SELECT label FROM equipments WHERE id = ?");
                    $eqStmt->execute([$equipmentId]);
                    $eqLabel = $eqStmt->fetch()['label'];
                    $changes[] = "Assigned to equipment: {$eqLabel}";
                } elseif (!$equipmentId && $network['equipment_id']) {
                    $changes[] = "Unassigned from equipment: {$network['equipment_label']}";
                } elseif ($equipmentId && $network['equipment_id']) {
                    $eqStmt = $pdo->prepare("SELECT label FROM equipments WHERE id = ?");
                    $eqStmt->execute([$equipmentId]);
                    $eqLabel = $eqStmt->fetch()['label'];
                    $changes[] = "Reassigned from {$network['equipment_label']} to {$eqLabel}";
                }
            }
            
            // Update network info
            $updateStmt = $pdo->prepare("
                UPDATE network_info SET
                    equipment_id = ?, ip_address = ?, mac_address = ?, cable_number = ?,
                    patch_panel_number = ?, patch_panel_port = ?, patch_panel_location = ?,
                    switch_number = ?, switch_port = ?, switch_location = ?, remarks = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $equipmentId, $ipAddress, $macAddress, $cableNumber,
                $patchPanelNumber, $patchPanelPort, $patchPanelLocation,
                $switchNumber, $switchPort, $switchLocation, $remarks,
                $networkId
            ]);
            
            // Add revision
            $changeDescription = !empty($changes) 
                ? 'Updated by ' . getCurrentUserName() . ': ' . implode(', ', $changes)
                : 'Updated by ' . getCurrentUserName();
            addRevision($pdo, 'network_info', $networkId, $changeDescription);
            
            // Log activity
            logActivity($pdo, 'Network', 'Update Network Info', 
                       "Updated network info (IP: {$ipAddress})", 'Info');
            
            // Create notification
            createNotification($pdo, 'Network', 'update', 'Network Info Updated', 
                              getCurrentUserName() . " updated network info (IP: {$ipAddress})", 
                              ['network_id' => $networkId, 'ip_address' => $ipAddress]);
            
            $pdo->commit();
            
            setFlashMessage('success', 'Network info updated successfully');
            header('Location: view_network_info.php?id=' . $networkId);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = 'Invalid CSRF token';
    }
}

// Get available equipment (including currently assigned) BEFORE including header
$equipmentStmt = $pdo->prepare("
    SELECT e.id, e.label, e.serial_number, et.type_name
    FROM equipments e
    JOIN equipment_types et ON e.equipment_type_id = et.id
    WHERE e.id NOT IN (
        SELECT equipment_id FROM network_info WHERE equipment_id IS NOT NULL AND id != ?
    )
    ORDER BY e.label
");
$equipmentStmt->execute([$networkId]);
$availableEquipment = $equipmentStmt->fetchAll();

// NOW include header
$pageTitle = 'Edit Network Info';
require_once ROOT_PATH . 'layouts/header.php';

$flash = getFlashMessage();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit Network Information</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if (!$network['equipment_id']): ?>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteNetworkModal">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <?php else: ?>
                <button type="button" class="btn btn-outline-warning" onclick="showUnassignFirst()">
                    <i class="bi bi-exclamation-triangle"></i> Delete (Must Unassign First)
                </button>
                <?php endif; ?>
            </div>
            <a href="view_network_info.php?id=<?php echo $networkId; ?>" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
        </div>
    </div>

    <div id="alert-container">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <form method="POST" id="editNetworkForm">
        <?php echo csrfField(); ?>
        
        <!-- Basic Network Info -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Network Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">IP Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ip_address" id="ip_address" required
                               value="<?php echo htmlspecialchars($network['ip_address']); ?>"
                               pattern="^(\d{1,3}\.){3}\d{1,3}$">
                        <div class="invalid-feedback">Please enter a valid IPv4 address</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">MAC Address</label>
                        <input type="text" class="form-control" name="mac_address" id="mac_address"
                               value="<?php echo htmlspecialchars($network['mac_address'] ?? ''); ?>">
                        <small class="text-muted">Format: XX:XX:XX:XX:XX:XX or N/A</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cable Number</label>
                        <input type="text" class="form-control" name="cable_number"
                               value="<?php echo htmlspecialchars($network['cable_number'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Patch Panel Information -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-grid-3x3 me-2"></i>Patch Panel Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Number</label>
                        <input type="text" class="form-control" name="patch_panel_number"
                               value="<?php echo htmlspecialchars($network['patch_panel_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Port</label>
                        <input type="text" class="form-control" name="patch_panel_port"
                               value="<?php echo htmlspecialchars($network['patch_panel_port'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Location</label>
                        <input type="text" class="form-control" name="patch_panel_location"
                               value="<?php echo htmlspecialchars($network['patch_panel_location'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Switch Information -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Switch Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Switch Number</label>
                        <input type="text" class="form-control" name="switch_number"
                               value="<?php echo htmlspecialchars($network['switch_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Switch Port</label>
                        <input type="text" class="form-control" name="switch_port"
                               value="<?php echo htmlspecialchars($network['switch_port'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Switch Location</label>
                        <input type="text" class="form-control" name="switch_location"
                               value="<?php echo htmlspecialchars($network['switch_location'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipment Assignment -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Equipment Assignment</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Assign to Equipment</label>
                        <select class="form-select" name="equipment_id" id="equipment_id">
                            <option value="">Unassigned</option>
                            <?php foreach ($availableEquipment as $equipment): ?>
                                <option value="<?php echo $equipment['id']; ?>"
                                        <?php echo $network['equipment_id'] == $equipment['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($equipment['label']); ?> - 
                                    <?php echo htmlspecialchars($equipment['serial_number']); ?> 
                                    (<?php echo htmlspecialchars($equipment['type_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($network['equipment_id']): ?>
                        <small class="text-muted mt-2 d-block">
                            Currently assigned to: 
                            <a href="<?php echo BASE_URL; ?>pages/equipment/view_equipment.php?id=<?php echo $network['equipment_id']; ?>" target="_blank">
                                <?php echo htmlspecialchars($network['equipment_label']); ?>
                            </a>
                        </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remarks -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Remarks / Notes</h5>
            </div>
            <div class="card-body">
                <textarea class="form-control" name="remarks" rows="4"><?php echo htmlspecialchars($network['remarks'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="card shadow mb-4">
            <div class="card-body text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle"></i> Update Network Info
                </button>
                <a href="view_network_info.php?id=<?php echo $networkId; ?>" class="btn btn-secondary btn-lg">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </div>
    </form>
</main>

<!-- Delete Network Modal (Only if Unassigned) -->
<div class="modal fade" id="deleteNetworkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to delete this network information?</p>
                <div class="alert alert-warning">
                    <strong>IP Address:</strong> <?php echo htmlspecialchars($network['ip_address']); ?><br>
                    <?php if ($network['mac_address']): ?>
                    <strong>MAC Address:</strong> <?php echo htmlspecialchars($network['mac_address']); ?><br>
                    <?php endif; ?>
                </div>
                <p class="text-danger mb-0"><strong>This action cannot be undone!</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                    <i class="bi bi-trash"></i> Yes, Delete Network Info
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Unassign First Warning Modal -->
<div class="modal fade" id="unassignWarningModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Cannot Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">This network info is currently assigned to equipment and cannot be deleted.</p>
                <div class="alert alert-info">
                    <strong>Assigned to:</strong> <?php echo htmlspecialchars($network['equipment_label'] ?? 'Equipment'); ?>
                </div>
                <p class="mb-0">Please unassign it from the equipment first by selecting "Unassigned" in the dropdown above, then save changes.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK, I Understand</button>
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal" onclick="focusEquipmentDropdown()">
                    <i class="bi bi-link-45deg"></i> Go to Assignment Section
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // IP address validation
    $('#ip_address').on('input blur', function() {
        const ip = $(this).val().trim();
        if (ip && !validateIP(ip)) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    // MAC address validation
    $('#mac_address').on('blur', function() {
        const mac = $(this).val().trim();
        if (mac && mac.toUpperCase() !== 'N/A') {
            const macPattern = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;
            if (!macPattern.test(mac)) {
                $(this).addClass('is-invalid');
                if (!$(this).siblings('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Invalid MAC address format (XX:XX:XX:XX:XX:XX)</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').remove();
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });

    // Form validation before submit
    $('#editNetworkForm').on('submit', function(e) {
        const ip = $('#ip_address').val().trim();
        
        if (!ip || !validateIP(ip)) {
            e.preventDefault();
            $('#ip_address').addClass('is-invalid');
            showAlert('danger', 'Please enter a valid IP address');
            $('#ip_address').focus();
            return false;
        }
        
        const mac = $('#mac_address').val().trim();
        if (mac && mac.toUpperCase() !== 'N/A') {
            const macPattern = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;
            if (!macPattern.test(mac)) {
                e.preventDefault();
                $('#mac_address').addClass('is-invalid');
                showAlert('danger', 'Please enter a valid MAC address');
                $('#mac_address').focus();
                return false;
            }
        }
    });
});

// Show warning if trying to delete assigned network
function showUnassignFirst() {
    $('#unassignWarningModal').modal('show');
}

// Focus on equipment dropdown
function focusEquipmentDropdown() {
    $('html, body').animate({
        scrollTop: $('#equipment_id').offset().top - 100
    }, 500);
    $('#equipment_id').focus();
}

// Confirm and execute delete
function confirmDelete() {
    $('#deleteNetworkModal').modal('hide');
    
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
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>