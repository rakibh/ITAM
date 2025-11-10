<?php
// File: pages/equipment/add_equipment.php
// Purpose: Add new equipment with dynamic fields, network info, and warranty

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Add Equipment';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

// Get equipment types
$typeStmt = $pdo->query("SELECT id, type_name FROM equipment_types ORDER BY type_name");
$equipmentTypes = $typeStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (verifyCsrfToken($_POST['csrf_token'])) {
        try {
            $pdo->beginTransaction();
            
            // Basic fields
            $label = sanitize($_POST['label'] ?? '');
            $equipmentTypeId = intval($_POST['equipment_type_id'] ?? 0);
            $brand = sanitize($_POST['brand'] ?? '') ?: null;
            $modelNumber = sanitize($_POST['model_number'] ?? '') ?: null;
            $serialNumber = sanitize($_POST['serial_number'] ?? '');
            $location = sanitize($_POST['location'] ?? '') ?: null;
            $floorNo = sanitize($_POST['floor_no'] ?? '') ?: null;
            $department = sanitize($_POST['department'] ?? '') ?: null;
            $assignedTo = sanitize($_POST['assigned_to'] ?? '') ?: null;
            $designation = sanitize($_POST['designation'] ?? '') ?: null;
            $status = sanitize($_POST['status'] ?? 'Available');
            $condition = sanitize($_POST['condition'] ?? 'Good');
            $remarks = sanitize($_POST['remarks'] ?? '') ?: null;
            
            // Optional custom fields
            $customLabel1 = sanitize($_POST['custom_label_1'] ?? '') ?: null;
            $customValue1 = sanitize($_POST['custom_value_1'] ?? '') ?: null;
            $customLabel2 = sanitize($_POST['custom_label_2'] ?? '') ?: null;
            $customValue2 = sanitize($_POST['custom_value_2'] ?? '') ?: null;
            
            // Warranty fields
            $sellerCompany = sanitize($_POST['seller_company'] ?? '') ?: null;
            $purchaseDate = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
            $warrantyExpiryDate = !empty($_POST['warranty_expiry_date']) ? $_POST['warranty_expiry_date'] : null;
            
            // Validation
            if (empty($label) || empty($serialNumber) || !$equipmentTypeId) {
                throw new Exception('Label, Type, and Serial Number are required');
            }
            
            // Check serial number uniqueness (except N/A)
            if (strtoupper($serialNumber) !== 'N/A') {
                $checkStmt = $pdo->prepare("SELECT id FROM equipments WHERE serial_number = ?");
                $checkStmt->execute([$serialNumber]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Serial number already exists');
                }
            }
            
            // Insert equipment
            $insertStmt = $pdo->prepare("
                INSERT INTO equipments (
                    label, equipment_type_id, brand, model_number, serial_number,
                    location, floor_no, department, assigned_to, designation,
                    status, condition_status, seller_company, purchase_date, warranty_expiry_date,
                    remarks, custom_label_1, custom_value_1, custom_label_2, custom_value_2,
                    created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $insertStmt->execute([
                $label, $equipmentTypeId, $brand, $modelNumber, $serialNumber,
                $location, $floorNo, $department, $assignedTo, $designation,
                $status, $condition, $sellerCompany, $purchaseDate, $warrantyExpiryDate,
                $remarks, $customLabel1, $customValue1, $customLabel2, $customValue2,
                getCurrentUserId()
            ]);
            
            $equipmentId = $pdo->lastInsertId();
            
            // Handle type-based custom fields
            if (isset($_POST['custom_field']) && is_array($_POST['custom_field'])) {
                $customFieldStmt = $pdo->prepare("
                    INSERT INTO equipment_custom_values (equipment_id, field_id, field_value)
                    VALUES (?, ?, ?)
                ");
                
                foreach ($_POST['custom_field'] as $fieldId => $value) {
                    if (is_array($value)) {
                        // Multiple values (like RAM, SSD, HDD)
                        foreach ($value as $v) {
                            if (!empty(trim($v))) {
                                $customFieldStmt->execute([$equipmentId, $fieldId, trim($v)]);
                            }
                        }
                    } else {
                        if (!empty(trim($value))) {
                            $customFieldStmt->execute([$equipmentId, $fieldId, trim($value)]);
                        }
                    }
                }
            }
            
            // Handle warranty documents
            if (isset($_FILES['warranty_documents']) && !empty($_FILES['warranty_documents']['name'][0])) {
                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                
                foreach ($_FILES['warranty_documents']['tmp_name'] as $key => $tmpName) {
                    if (!empty($tmpName) && $_FILES['warranty_documents']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['warranty_documents']['name'][$key],
                            'type' => $_FILES['warranty_documents']['type'][$key],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['warranty_documents']['error'][$key],
                            'size' => $_FILES['warranty_documents']['size'][$key]
                        ];
                        
                        $uploadResult = uploadFile($file, WARRANTY_UPLOAD_PATH, $allowedTypes, MAX_WARRANTY_SIZE);
                        
                        if ($uploadResult['success']) {
                            $docStmt = $pdo->prepare("
                                INSERT INTO warranty_documents (equipment_id, file_name, file_path)
                                VALUES (?, ?, ?)
                            ");
                            $docStmt->execute([
                                $equipmentId,
                                $file['name'],
                                $uploadResult['filename']
                            ]);
                        }
                    }
                }
            }
            
            // Handle network info if enabled
            if (isset($_POST['has_network']) && $_POST['has_network'] === '1') {
                $ipAddress = sanitize($_POST['ip_address'] ?? '');
                
                if (!empty($ipAddress)) {
                    if (!validateIPAddress($ipAddress)) {
                        throw new Exception('Invalid IP address format');
                    }
                    
                    // Check IP uniqueness
                    $ipCheckStmt = $pdo->prepare("SELECT id FROM network_info WHERE ip_address = ?");
                    $ipCheckStmt->execute([$ipAddress]);
                    if ($ipCheckStmt->fetch()) {
                        throw new Exception('IP address already exists');
                    }
                    
                    $macAddress = sanitize($_POST['mac_address'] ?? '') ?: null;
                    $cableNumber = sanitize($_POST['cable_number'] ?? '') ?: null;
                    $patchPanelNumber = sanitize($_POST['patch_panel_number'] ?? '') ?: null;
                    $patchPanelPort = sanitize($_POST['patch_panel_port'] ?? '') ?: null;
                    $patchPanelLocation = sanitize($_POST['patch_panel_location'] ?? '') ?: null;
                    $switchNumber = sanitize($_POST['switch_number'] ?? '') ?: null;
                    $switchPort = sanitize($_POST['switch_port'] ?? '') ?: null;
                    $switchLocation = sanitize($_POST['switch_location'] ?? '') ?: null;
                    $networkRemarks = sanitize($_POST['network_remarks'] ?? '') ?: null;
                    
                    $networkStmt = $pdo->prepare("
                        INSERT INTO network_info (
                            equipment_id, ip_address, mac_address, cable_number,
                            patch_panel_number, patch_panel_port, patch_panel_location,
                            switch_number, switch_port, switch_location, remarks, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $networkStmt->execute([
                        $equipmentId, $ipAddress, $macAddress, $cableNumber,
                        $patchPanelNumber, $patchPanelPort, $patchPanelLocation,
                        $switchNumber, $switchPort, $switchLocation, $networkRemarks,
                        getCurrentUserId()
                    ]);
                }
            }
            
            // Add revision
            addRevision($pdo, 'equipments', $equipmentId, 'Equipment created by ' . getCurrentUserName());
            
            // Log activity
            logActivity($pdo, 'Equipment', 'Add Equipment', 
                       "Created equipment: {$label} ({$serialNumber})", 'Info');
            
            // Create notification
            createNotification($pdo, 'Equipment', 'add', 'New Equipment Added', 
                              getCurrentUserName() . " added new equipment: {$label} ({$serialNumber})", 
                              ['equipment_id' => $equipmentId]);
            
            $pdo->commit();
            
            setFlashMessage('success', 'Equipment added successfully');
            header('Location: view_equipment.php?id=' . $equipmentId);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = 'Invalid CSRF token';
    }
}
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-plus-circle me-2"></i>Add New Equipment</h1>
        <a href="list_equipment.php" class="btn btn-secondary">
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

    <form method="POST" enctype="multipart/form-data" id="addEquipmentForm">
        <?php echo csrfField(); ?>
        
        <!-- Identification Block -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Identification</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Label / Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="label" required 
                               placeholder="e.g., Laptop-HR-01">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Equipment Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="equipment_type_id" id="equipment_type_id" required>
                            <option value="">Select Type...</option>
                            <?php foreach ($equipmentTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>">
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Brand / Manufacturer</label>
                        <input type="text" class="form-control" name="brand" placeholder="e.g., Dell, HP, Lenovo">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Model Number</label>
                        <input type="text" class="form-control" name="model_number" placeholder="e.g., Latitude 5420">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Serial Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="serial_number" required 
                               placeholder="Enter serial number or N/A">
                        <small class="text-muted">Must be unique (except "N/A")</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Connection Block -->
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-ethernet me-2"></i>Network Connection</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="has_network" name="has_network" value="1">
                    <label class="form-check-label" for="has_network">
                        Does this device have a network connection?
                    </label>
                </div>
            </div>
            <div class="card-body" id="network_fields" style="display: none;">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">IP Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ip_address" id="ip_address"
                               placeholder="e.g., 192.168.1.100" pattern="^(\d{1,3}\.){3}\d{1,3}$">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">MAC Address</label>
                        <input type="text" class="form-control" name="mac_address" 
                               placeholder="e.g., 00:1A:2B:3C:4D:5E">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cable Number</label>
                        <input type="text" class="form-control" name="cable_number" placeholder="e.g., CAB-101">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Number</label>
                        <input type="text" class="form-control" name="patch_panel_number" placeholder="e.g., PP-1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Port</label>
                        <input type="text" class="form-control" name="patch_panel_port" placeholder="e.g., Port 12">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Location</label>
                        <input type="text" class="form-control" name="patch_panel_location" placeholder="e.g., Server Room">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Switch Number</label>
                        <input type="text" class="form-control" name="switch_number" placeholder="e.g., SW-01">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Switch Port</label>
                        <input type="text" class="form-control" name="switch_port" placeholder="e.g., Port 24">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Switch Location</label>
                        <input type="text" class="form-control" name="switch_location" placeholder="e.g., 2nd Floor">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Network Remarks</label>
                        <textarea class="form-control" name="network_remarks" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Type-Based Custom Fields (Dynamic) -->
        <div class="card shadow mb-4" id="custom_fields_card" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-sliders me-2"></i><span id="type_name_label">Equipment</span> Specifications</h5>
            </div>
            <div class="card-body">
                <div class="row g-3" id="dynamic_fields">
                    <!-- Fields loaded via AJAX -->
                </div>
            </div>
        </div>

        <!-- Location Block -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location & Assignment</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" placeholder="e.g., Head Office">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Floor No</label>
                        <input type="text" class="form-control" name="floor_no" placeholder="e.g., 3rd Floor">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department" placeholder="e.g., IT Department">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assigned To (Name)</label>
                        <input type="text" class="form-control" name="assigned_to" placeholder="e.g., John Doe">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input type="text" class="form-control" name="designation" placeholder="e.g., IT Manager">
                    </div>
                </div>
            </div>
        </div>

        <!-- Status & Condition Block -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Status & Condition</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" required>
                            <option value="In Use">In Use</option>
                            <option value="Available" selected>Available</option>
                            <option value="Under Repair">Under Repair</option>
                            <option value="Retired">Retired</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Condition <span class="text-danger">*</span></label>
                        <select class="form-select" name="condition" required>
                            <option value="New">New</option>
                            <option value="Good" selected>Good</option>
                            <option value="Needs Service">Needs Service</option>
                            <option value="Damaged">Damaged</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warranty Block -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Warranty Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Seller Company</label>
                        <input type="text" class="form-control" name="seller_company" placeholder="e.g., Tech Solutions Ltd">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" class="form-control" name="purchase_date">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warranty Expiry Date</label>
                        <input type="date" class="form-control" name="warranty_expiry_date">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Warranty Documents</label>
                        <input type="file" class="form-control" name="warranty_documents[]" multiple
                               accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">PDF, JPG, PNG - Max 15MB per file. Multiple files allowed.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Optional Custom Fields -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-square me-2"></i>Additional Custom Fields (Optional)</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Custom Label 1</label>
                        <input type="text" class="form-control" name="custom_label_1" placeholder="e.g., Processor Speed">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Custom Value 1</label>
                        <input type="text" class="form-control" name="custom_value_1" placeholder="e.g., 2.4 GHz">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Custom Label 2</label>
                        <input type="text" class="form-control" name="custom_label_2" placeholder="e.g., Graphics Card">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Custom Value 2</label>
                        <input type="text" class="form-control" name="custom_value_2" placeholder="e.g., NVIDIA GTX 1650">
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
                          placeholder="Any additional information or notes about this equipment"></textarea>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="card shadow mb-4">
            <div class="card-body text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle"></i> Add Equipment
                </button>
                <a href="list_equipment.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </div>
    </form>
</main>

<script>
// Toggle network fields
$('#has_network').on('change', function() {
    if ($(this).is(':checked')) {
        $('#network_fields').slideDown();
        $('#ip_address').attr('required', true);
    } else {
        $('#network_fields').slideUp();
        $('#ip_address').attr('required', false);
    }
});

// Load dynamic fields based on equipment type
$('#equipment_type_id').on('change', function() {
    const typeId = $(this).val();
    
    if (!typeId) {
        $('#custom_fields_card').hide();
        $('#dynamic_fields').html('');
        return;
    }
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/load_dynamic_fields.php',
        type: 'GET',
        data: { type_id: typeId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.html) {
                $('#type_name_label').text(response.type_name);
                $('#dynamic_fields').html(response.html);
                $('#custom_fields_card').slideDown();
                
                // Initialize add more button
                initAddMoreButtons();
            } else {
                $('#custom_fields_card').hide();
            }
        },
        error: function() {
            showAlert('warning', 'Could not load type-specific fields');
        }
    });
});

// Add more button functionality
function initAddMoreButtons() {
    $(document).on('click', '.btn-add-more', function() {
        const fieldId = $(this).data('field-id');
        const container = $('#container_' + fieldId);
        const firstInput = container.find('.input-group:first').clone();
        
        firstInput.find('input').val('');
        firstInput.find('.btn-add-more')
            .removeClass('btn-outline-success btn-add-more')
            .addClass('btn-outline-danger btn-remove')
            .html('<i class="bi bi-dash"></i>');
        
        container.append(firstInput);
    });
    
    $(document).on('click', '.btn-remove', function() {
        $(this).closest('.input-group').remove();
    });
}

// IP address validation
$('#ip_address').on('blur', function() {
    const ip = $(this).val();
    if (ip && !validateIP(ip)) {
        $(this).addClass('is-invalid');
        $(this).after('<div class="invalid-feedback">Invalid IP address format</div>');
    } else {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
    }
});

// Form validation before submit
$('#addEquipmentForm').on('submit', function(e) {
    // Additional validation if needed
    if ($('#has_network').is(':checked')) {
        const ip = $('#ip_address').val();
        if (!ip || !validateIP(ip)) {
            e.preventDefault();
            showAlert('danger', 'Please enter a valid IP address');
            $('#ip_address').focus();
            return false;
        }
    }
});
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>