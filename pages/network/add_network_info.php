<?php
// File: pages/network/add_network_info.php
// Purpose: Add new network information with optional equipment assignment

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');

// Include config files BEFORE any output
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'includes/functions.php';

requireLogin();

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
            
            // Check IP uniqueness
            $checkStmt = $pdo->prepare("SELECT id FROM network_info WHERE ip_address = ?");
            $checkStmt->execute([$ipAddress]);
            if ($checkStmt->fetch()) {
                throw new Exception('IP address already exists');
            }
            
            // Check MAC uniqueness (if provided and not N/A)
            if ($macAddress && strtoupper($macAddress) !== 'N/A') {
                $macCheckStmt = $pdo->prepare("SELECT id FROM network_info WHERE mac_address = ?");
                $macCheckStmt->execute([$macAddress]);
                if ($macCheckStmt->fetch()) {
                    throw new Exception('MAC address already exists');
                }
            }
            
            // If equipment selected, verify it's available
            if ($equipmentId) {
                $eqCheckStmt = $pdo->prepare("
                    SELECT id FROM network_info WHERE equipment_id = ?
                ");
                $eqCheckStmt->execute([$equipmentId]);
                if ($eqCheckStmt->fetch()) {
                    throw new Exception('Selected equipment already has network info assigned');
                }
            }
            
            // Insert network info
            $insertStmt = $pdo->prepare("
                INSERT INTO network_info (
                    equipment_id, ip_address, mac_address, cable_number,
                    patch_panel_number, patch_panel_port, patch_panel_location,
                    switch_number, switch_port, switch_location, remarks,
                    created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $insertStmt->execute([
                $equipmentId, $ipAddress, $macAddress, $cableNumber,
                $patchPanelNumber, $patchPanelPort, $patchPanelLocation,
                $switchNumber, $switchPort, $switchLocation, $remarks,
                getCurrentUserId()
            ]);
            
            $networkId = $pdo->lastInsertId();
            
            // Add revision
            $assignmentInfo = $equipmentId ? ' and assigned to equipment' : '';
            addRevision($pdo, 'network_info', $networkId, 'Network info created by ' . getCurrentUserName() . $assignmentInfo);
            
            // Log activity
            logActivity($pdo, 'Network', 'Add Network Info', 
                       "Created network info: IP {$ipAddress}", 'Info');
            
            // Create notification
            $notifMessage = getCurrentUserName() . " added new network info for IP: {$ipAddress}";
            if ($equipmentId) {
                $eqStmt = $pdo->prepare("SELECT label FROM equipments WHERE id = ?");
                $eqStmt->execute([$equipmentId]);
                $eqLabel = $eqStmt->fetch()['label'];
                $notifMessage .= " (assigned to {$eqLabel})";
            }
            
            createNotification($pdo, 'Network', 'add', 'New Network Info Added', 
                              $notifMessage, 
                              ['network_id' => $networkId, 'ip_address' => $ipAddress]);
            
            $pdo->commit();
            
            setFlashMessage('success', 'Network info added successfully');
            header('Location: list_network_info.php');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = 'Invalid CSRF token';
    }
}

// NOW include header after form processing
$pageTitle = 'Add Network Info';
require_once ROOT_PATH . 'layouts/header.php';

// Get available equipment (those without network info)
$equipmentStmt = $pdo->query("
    SELECT e.id, e.label, e.serial_number, et.type_name
    FROM equipments e
    JOIN equipment_types et ON e.equipment_type_id = et.id
    WHERE e.id NOT IN (
        SELECT equipment_id FROM network_info WHERE equipment_id IS NOT NULL
    )
    ORDER BY e.label
");
$availableEquipment = $equipmentStmt->fetchAll();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-plus-circle me-2"></i>Add Network Information</h1>
        <a href="list_network_info.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <div id="alert-container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <form method="POST" id="addNetworkForm">
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
                               placeholder="e.g., 192.168.1.100" pattern="^(\d{1,3}\.){3}\d{1,3}$">
                        <div class="invalid-feedback">Please enter a valid IPv4 address</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">MAC Address</label>
                        <input type="text" class="form-control" name="mac_address" id="mac_address"
                               placeholder="e.g., 00:1A:2B:3C:4D:5E or N/A">
                        <small class="text-muted">Format: XX:XX:XX:XX:XX:XX or N/A</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cable Number</label>
                        <input type="text" class="form-control" name="cable_number"
                               placeholder="e.g., CAB-101">
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
                               placeholder="e.g., PP-1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Port</label>
                        <input type="text" class="form-control" name="patch_panel_port"
                               placeholder="e.g., Port 12">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Location</label>
                        <input type="text" class="form-control" name="patch_panel_location"
                               placeholder="e.g., Server Room">
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
                               placeholder="e.g., SW-01">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Switch Port</label>
                        <input type="text" class="form-control" name="switch_port"
                               placeholder="e.g., Port 24">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Switch Location</label>
                        <input type="text" class="form-control" name="switch_location"
                               placeholder="e.g., 2nd Floor">
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipment Assignment (Optional) -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Equipment Assignment (Optional)</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Assign to Equipment</label>
                        <select class="form-select" name="equipment_id" id="equipment_id">
                            <option value="">Leave Unassigned</option>
                            <?php foreach ($availableEquipment as $equipment): ?>
                                <option value="<?php echo $equipment['id']; ?>">
                                    <?php echo htmlspecialchars($equipment['label']); ?> - 
                                    <?php echo htmlspecialchars($equipment['serial_number']); ?> 
                                    (<?php echo htmlspecialchars($equipment['type_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">You can assign this network info to equipment later if needed</small>
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
                <textarea class="form-control" name="remarks" rows="4"
                          placeholder="Any additional information about this network connection"></textarea>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="card shadow mb-4">
            <div class="card-body text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle"></i> Add Network Info
                </button>
                <a href="list_network_info.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </div>
    </form>
</main>

<script>
// IP address validation
$(document).ready(function() {
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
    $('#addNetworkForm').on('submit', function(e) {
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
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>