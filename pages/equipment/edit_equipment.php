<?php
// File: pages/equipment/edit_equipment.php
// Purpose: Edit existing equipment with all fields, warranty, and network info

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Edit Equipment';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$equipmentId = intval($_GET['id'] ?? 0);

if (!$equipmentId) {
    header('Location: list_equipment.php');
    exit();
}

// Get equipment details
$stmt = $pdo->prepare("
    SELECT e.*, et.type_name
    FROM equipments e
    JOIN equipment_types et ON e.equipment_type_id = et.id
    WHERE e.id = ?
");
$stmt->execute([$equipmentId]);
$equipment = $stmt->fetch();

if (!$equipment) {
    header('Location: list_equipment.php');
    exit();
}

// Get equipment types
$typeStmt = $pdo->query("SELECT id, type_name FROM equipment_types ORDER BY type_name");
$equipmentTypes = $typeStmt->fetchAll();

// Get custom field values
$customFieldsStmt = $pdo->prepare("
    SELECT ecv.*, etf.field_name, etf.field_type
    FROM equipment_custom_values ecv
    JOIN equipment_type_fields etf ON ecv.field_id = etf.id
    WHERE ecv.equipment_id = ?
    ORDER BY etf.display_order
");
$customFieldsStmt->execute([$equipmentId]);
$customFieldValues = $customFieldsStmt->fetchAll(PDO::FETCH_GROUP);

// Get warranty documents
$warrantyStmt = $pdo->prepare("SELECT * FROM warranty_documents WHERE equipment_id = ?");
$warrantyStmt->execute([$equipmentId]);
$warrantyDocs = $warrantyStmt->fetchAll();

// Get network info
$networkStmt = $pdo->prepare("SELECT * FROM network_info WHERE equipment_id = ?");
$networkStmt->execute([$equipmentId]);
$networkInfo = $networkStmt->fetch();

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
            
            // Check serial number uniqueness (excluding current equipment)
            if (strtoupper($serialNumber) !== 'N/A') {
                $checkStmt = $pdo->prepare("SELECT id FROM equipments WHERE serial_number = ? AND id != ?");
                $checkStmt->execute([$serialNumber, $equipmentId]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Serial number already exists');
                }
            }
            
            // Update equipment
            $updateStmt = $pdo->prepare("
                UPDATE equipments SET
                    label = ?, equipment_type_id = ?, brand = ?, model_number = ?, serial_number = ?,
                    location = ?, floor_no = ?, department = ?, assigned_to = ?, designation = ?,
                    status = ?, condition_status = ?, seller_company = ?, purchase_date = ?, warranty_expiry_date = ?,
                    remarks = ?, custom_label_1 = ?, custom_value_1 = ?, custom_label_2 = ?, custom_value_2 = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $label, $equipmentTypeId, $brand, $modelNumber, $serialNumber,
                $location, $floorNo, $department, $assignedTo, $designation,
                $status, $condition, $sellerCompany, $purchaseDate, $warrantyExpiryDate,
                $remarks, $customLabel1, $customValue1, $customLabel2, $customValue2,
                $equipmentId
            ]);
            
            // Update type-based custom fields
            // Delete existing custom values
            $deleteCustomStmt = $pdo->prepare("DELETE FROM equipment_custom_values WHERE equipment_id = ?");
            $deleteCustomStmt->execute([$equipmentId]);
            
            // Insert new custom field values
            if (isset($_POST['custom_field']) && is_array($_POST['custom_field'])) {
                $customFieldStmt = $pdo->prepare("
                    INSERT INTO equipment_custom_values (equipment_id, field_id, field_value)
                    VALUES (?, ?, ?)
                ");
                
                foreach ($_POST['custom_field'] as $fieldId => $value) {
                    if (is_array($value)) {
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
            
            // Handle warranty document deletion
            if (isset($_POST['delete_warranty']) && is_array($_POST['delete_warranty'])) {
                $deleteDocStmt = $pdo->prepare("DELETE FROM warranty_documents WHERE id = ? AND equipment_id = ?");
                foreach ($_POST['delete_warranty'] as $docId) {
                    // Get file info before deleting
                    $docInfoStmt = $pdo->prepare("SELECT file_path FROM warranty_documents WHERE id = ?");
                    $docInfoStmt->execute([$docId]);
                    $docInfo = $docInfoStmt->fetch();
                    
                    if ($docInfo) {
                        deleteFile(WARRANTY_UPLOAD_PATH . $docInfo['file_path']);
                        $deleteDocStmt->execute([$docId, $equipmentId]);
                    }
                }
            }
            
            // Handle network info
            if (isset($_POST['has_network']) && $_POST['has_network'] === '1') {
                $ipAddress = sanitize($_POST['ip_address'] ?? '');
                
                if (!empty($ipAddress)) {
                    if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        throw new Exception('Invalid IP address format');
                    }
                    
                    // Check IP uniqueness (excluding this equipment's network)
                    $ipCheckStmt = $pdo->prepare("SELECT id FROM network_info WHERE ip_address = ? AND equipment_id != ?");
                    $ipCheckStmt->execute([$ipAddress, $equipmentId]);
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
                    
                    if ($networkInfo) {
                        // Update existing network info
                        $networkStmt = $pdo->prepare("
                            UPDATE network_info SET
                                ip_address = ?, mac_address = ?, cable_number = ?,
                                patch_panel_number = ?, patch_panel_port = ?, patch_panel_location = ?,
                                switch_number = ?, switch_port = ?, switch_location = ?, remarks = ?,
                                updated_at = NOW()
                            WHERE equipment_id = ?
                        ");
                        
                        $networkStmt->execute([
                            $ipAddress, $macAddress, $cableNumber,
                            $patchPanelNumber, $patchPanelPort, $patchPanelLocation,
                            $switchNumber, $switchPort, $switchLocation, $networkRemarks,
                            $equipmentId
                        ]);
                    } else {
                        // Insert new network info
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
            } else if (isset($_POST['unassign_network']) && $_POST['unassign_network'] === '1' && $networkInfo) {
                // Unassign network info
                $unassignStmt = $pdo->prepare("UPDATE network_info SET equipment_id = NULL WHERE equipment_id = ?");
                $unassignStmt->execute([$equipmentId]);
            }
            
            // Add revision
            addRevision($pdo, 'equipments', $equipmentId, 'Equipment updated by ' . getCurrentUserName());
            
            // Log activity
            logActivity($pdo, 'Equipment', 'Update Equipment', 
                       "Updated equipment: {$label} ({$serialNumber})", 'Info');
            
            // Create notification
            createNotification($pdo, 'Equipment', 'update', 'Equipment Updated', 
                              getCurrentUserName() . " updated equipment: {$label} ({$serialNumber})", 
                              ['equipment_id' => $equipmentId]);
            
            $pdo->commit();
            
            setFlashMessage('success', 'Equipment updated successfully');
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

$flash = getFlashMessage();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit Equipment</h1>
        <a href="view_equipment.php?id=<?php echo $equipmentId; ?>" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Cancel
        </a>
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

    <form method="POST" enctype="multipart/form-data" id="editEquipmentForm">
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
                               value="<?php echo htmlspecialchars($equipment['label']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Equipment Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="equipment_type_id" id="equipment_type_id" required>
                            <option value="">Select Type...</option>
                            <?php foreach ($equipmentTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>" 
                                        <?php echo $equipment['equipment_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Brand / Manufacturer</label>
                        <input type="text" class="form-control" name="brand" 
                               value="<?php echo htmlspecialchars($equipment['brand'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Model Number</label>
                        <input type="text" class="form-control" name="model_number" 
                               value="<?php echo htmlspecialchars($equipment['model_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Serial Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="serial_number" required 
                               value="<?php echo htmlspecialchars($equipment['serial_number']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Connection Block -->
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-ethernet me-2"></i>Network Connection</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="has_network" name="has_network" value="1"
                           <?php echo $networkInfo ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="has_network">
                        Does this device have a network connection?
                    </label>
                </div>
            </div>
            <div class="card-body" id="network_fields" style="display: <?php echo $networkInfo ? 'block' : 'none'; ?>;">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">IP Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ip_address" id="ip_address"
                               value="<?php echo htmlspecialchars($networkInfo['ip_address'] ?? ''); ?>"
                               pattern="^(\d{1,3}\.){3}\d{1,3}$">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">MAC Address</label>
                        <input type="text" class="form-control" name="mac_address" 
                               value="<?php echo htmlspecialchars($networkInfo['mac_address'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cable Number</label>
                        <input type="text" class="form-control" name="cable_number" 
                               value="<?php echo htmlspecialchars($networkInfo['cable_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Number</label>
                        <input type="text" class="form-control" name="patch_panel_number" 
                               value="<?php echo htmlspecialchars($networkInfo['patch_panel_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Port</label>
                        <input type="text" class="form-control" name="patch_panel_port" 
                               value="<?php echo htmlspecialchars($networkInfo['patch_panel_port'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Patch Panel Location</label>
                        <input type="text" class="form-control" name="patch_panel_location" 
                               value="<?php echo htmlspecialchars($networkInfo['patch_panel_location'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Switch Number</label>
                        <input type="text" class="form-control" name="switch_number" 
                               value="<?php echo htmlspecialchars($networkInfo['switch_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Switch Port</label>
                        <input type="text" class="form-control" name="switch_port" 
                               value="<?php echo htmlspecialchars($networkInfo['switch_port'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Switch Location</label>
                        <input type="text" class="form-control" name="switch_location" 
                               value="<?php echo htmlspecialchars($networkInfo['switch_location'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Network Remarks</label>
                        <textarea class="form-control" name="network_remarks" rows="2"><?php echo htmlspecialchars($networkInfo['remarks'] ?? ''); ?></textarea>
                    </div>
                    <?php if ($networkInfo): ?>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="unassign_network" value="1" id="unassign_network">
                            <label class="form-check-label text-danger" for="unassign_network">
                                <i class="bi bi-exclamation-triangle me-1"></i>Unassign network info from this equipment
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Type-Based Custom Fields (Dynamic) -->
        <div class="card shadow mb-4" id="custom_fields_card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-sliders me-2"></i><span id="type_name_label"><?php echo htmlspecialchars($equipment['type_name']); ?></span> Specifications</h5>
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
                        <input type="text" class="form-control" name="location" 
                               value="<?php echo htmlspecialchars($equipment['location'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Floor No</label>
                        <input type="text" class="form-control" name="floor_no" 
                               value="<?php echo htmlspecialchars($equipment['floor_no'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" name="department" 
                               value="<?php echo htmlspecialchars($equipment['department'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assigned To (Name)</label>
                        <input type="text" class="form-control" name="assigned_to" 
                               value="<?php echo htmlspecialchars($equipment['assigned_to'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input type="text" class="form-control" name="designation" 
                               value="<?php echo htmlspecialchars($equipment['designation'] ?? ''); ?>">
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
                            <option value="In Use" <?php echo $equipment['status'] === 'In Use' ? 'selected' : ''; ?>>In Use</option>
                            <option value="Available" <?php echo $equipment['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                            <option value="Under Repair" <?php echo $equipment['status'] === 'Under Repair' ? 'selected' : ''; ?>>Under Repair</option>
                            <option value="Retired" <?php echo $equipment['status'] === 'Retired' ? 'selected' : ''; ?>>Retired</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Condition <span class="text-danger">*</span></label>
                        <select class="form-select" name="condition" required>
                            <option value="New" <?php echo $equipment['condition_status'] === 'New' ? 'selected' : ''; ?>>New</option>
                            <option value="Good" <?php echo $equipment['condition_status'] === 'Good' ? 'selected' : ''; ?>>Good</option>
                            <option value="Needs Service" <?php echo $equipment['condition_status'] === 'Needs Service' ? 'selected' : ''; ?>>Needs Service</option>
                            <option value="Damaged" <?php echo $equipment['condition_status'] === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
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
                        <input type="text" class="form-control" name="seller_company" 
                               value="<?php echo htmlspecialchars($equipment['seller_company'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" class="form-control" name="purchase_date" 
                               value="<?php echo $equipment['purchase_date']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warranty Expiry Date</label>
                        <input type="date" class="form-control" name="warranty_expiry_date" 
                               value="<?php echo $equipment['warranty_expiry_date']; ?>">
                    </div>
                    
                    <?php if (!empty($warrantyDocs)): ?>
                    <div class="col-12">
                        <label class="form-label">Existing Warranty Documents</label>
                        <ul class="list-group">
                            <?php foreach ($warrantyDocs as $doc): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-file-earmark-pdf text-danger"></i> <?php echo htmlspecialchars($doc['file_name']); ?></span>
                                <div>
                                    <a href="<?php echo BASE_URL; ?>uploads/warranty/<?php echo $doc['file_path']; ?>" 
                                       class="btn btn-sm btn-outline-primary me-2" target="_blank" download>
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="delete_warranty[]" 
                                               value="<?php echo $doc['id']; ?>" id="del_<?php echo $doc['id']; ?>">
                                        <label class="form-check-label text-danger" for="del_<?php echo $doc['id']; ?>">
                                            Delete
                                        </label>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-12">
                        <label class="form-label">Add More Warranty Documents</label>
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
                        <input type="text" class="form-control" name="custom_label_1" 
                               value="<?php echo htmlspecialchars($equipment['custom_label_1'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Custom Value 1</label>
                        <input type="text" class="form-control" name="custom_value_1" 
                               value="<?php echo htmlspecialchars($equipment['custom_value_1'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Custom Label 2</label>
                        <input type="text" class="form-control" name="custom_label_2" 
                               value="<?php echo htmlspecialchars($equipment['custom_label_2'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Custom Value 2</label>
                        <input type="text" class="form-control" name="custom_value_2" 
                               value="<?php echo htmlspecialchars($equipment['custom_value_2'] ?? ''); ?>">
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
                <textarea class="form-control" name="remarks" rows="4"><?php echo htmlspecialchars($equipment['remarks'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="card shadow mb-4">
            <div class="card-body text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle"></i> Update Equipment
                </button>
                <a href="view_equipment.php?id=<?php echo $equipmentId; ?>" class="btn btn-secondary btn-lg">
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

// Load dynamic fields on page load
$(document).ready(function() {
    const typeId = $('#equipment_type_id').val();
    if (typeId) {
        loadDynamicFields(typeId, true); // true = edit mode
    }
});

// Load dynamic fields on type change
$('#equipment_type_id').on('change', function() {
    const typeId = $(this).val();
    if (typeId) {
        loadDynamicFields(typeId, false);
    } else {
        $('#dynamic_fields').html('');
    }
});

function loadDynamicFields(typeId, isEdit) {
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/load_dynamic_fields.php',
        type: 'GET',
        data: { 
            type_id: typeId,
            equipment_id: <?php echo $equipmentId; ?>,
            edit_mode: isEdit ? 1 : 0
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.html) {
                $('#type_name_label').text(response.type_name);
                $('#dynamic_fields').html(response.html);
                initAddMoreButtons();
                
                // Pre-fill existing values if in edit mode
                if (isEdit && response.existing_values) {
                    // Values are already populated in the HTML from server
                }
            }
        },
        error: function() {
            showAlert('warning', 'Could not load type-specific fields');
        }
    });
}

// Add more button functionality
function initAddMoreButtons() {
    $(document).off('click', '.btn-add-more').on('click', '.btn-add-more', function() {
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
    
    $(document).off('click', '.btn-remove').on('click', '.btn-remove', function() {
        $(this).closest('.input-group').remove();
    });
}

// IP address validation
$('#ip_address').on('blur', function() {
    const ip = $(this).val();
    if (ip && !validateIP(ip)) {
        $(this).addClass('is-invalid');
        if (!$(this).siblings('.invalid-feedback').length) {
            $(this).after('<div class="invalid-feedback">Invalid IP address format</div>');
        }
    } else {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
    }
});

// Unassign network confirmation
$('#unassign_network').on('change', function() {
    if ($(this).is(':checked')) {
        if (!confirm('Are you sure you want to unassign network info from this equipment?')) {
            $(this).prop('checked', false);
        }
    }
});
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>