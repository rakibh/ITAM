<?php
// File: ajax/load_dynamic_fields.php
// Purpose: Load type-based dynamic fields for equipment forms

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$equipmentTypeId = intval($_GET['type_id'] ?? 0);
$equipmentId = intval($_GET['equipment_id'] ?? 0);
$editMode = isset($_GET['edit_mode']) && $_GET['edit_mode'] == 1;

if (!$equipmentTypeId) {
    echo json_encode(['success' => false, 'message' => 'Equipment type ID required']);
    exit();
}

// Get existing values if in edit mode
$existingValues = [];
if ($editMode && $equipmentId) {
    $existingStmt = $pdo->prepare("
        SELECT ecv.field_id, ecv.field_value
        FROM equipment_custom_values ecv
        WHERE ecv.equipment_id = ?
        ORDER BY ecv.id
    ");
    $existingStmt->execute([$equipmentId]);
    while ($row = $existingStmt->fetch()) {
        $existingValues[$row['field_id']][] = $row['field_value'];
    }
}

try {
    // Get equipment type info
    $typeStmt = $pdo->prepare("SELECT * FROM equipment_types WHERE id = ?");
    $typeStmt->execute([$equipmentTypeId]);
    $equipmentType = $typeStmt->fetch();
    
    if (!$equipmentType) {
        throw new Exception('Equipment type not found');
    }
    
    // Get type-based fields
    $fieldsStmt = $pdo->prepare("
        SELECT * FROM equipment_type_fields 
        WHERE equipment_type_id = ? 
        ORDER BY display_order ASC
    ");
    $fieldsStmt->execute([$equipmentTypeId]);
    $fields = $fieldsStmt->fetchAll();
    
    // Generate HTML for fields
    $html = '';
    
    if (empty($fields)) {
        echo json_encode([
            'success' => true,
            'type_name' => $equipmentType['type_name'],
            'html' => '<div class="col-12"><p class="text-muted">No additional fields for this equipment type.</p></div>'
        ]);
        exit();
    }
    
    foreach ($fields as $field) {
        $fieldName = strtolower(str_replace(' ', '_', $field['field_name']));
        $required = $field['is_required'] ? 'required' : '';
        $requiredLabel = $field['is_required'] ? '<span class="text-danger">*</span>' : '';
        
        $html .= '<div class="col-md-6">';
        $html .= '<label class="form-label">' . htmlspecialchars($field['field_name']) . ' ' . $requiredLabel . '</label>';
        
        switch ($field['field_type']) {
            case 'text':
            case 'number':
                $type = $field['field_type'] === 'number' ? 'number' : 'text';
                $html .= '<input type="' . $type . '" class="form-control" name="custom_field[' . $field['id'] . ']" 
                         placeholder="Enter ' . htmlspecialchars($field['field_name']) . '" ' . $required . '>';
                break;
                
            case 'textarea':
                $html .= '<textarea class="form-control" name="custom_field[' . $field['id'] . ']" rows="3" 
                         placeholder="Enter ' . htmlspecialchars($field['field_name']) . '" ' . $required . '></textarea>';
                break;
                
            case 'select':
                $options = !empty($field['field_options']) ? explode(',', $field['field_options']) : [];
                $html .= '<select class="form-select" name="custom_field[' . $field['id'] . ']" ' . $required . '>';
                $html .= '<option value="">Select...</option>';
                foreach ($options as $option) {
                    $option = trim($option);
                    $html .= '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($option) . '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'multiple':
                $html .= '<div class="multiple-input-container" id="container_' . $field['id'] . '">';
                $html .= '<div class="input-group mb-2">';
                $html .= '<input type="text" class="form-control" name="custom_field[' . $field['id'] . '][]" 
                         placeholder="Enter ' . htmlspecialchars($field['field_name']) . '" ' . $required . '>';
                $html .= '<button type="button" class="btn btn-outline-success btn-add-more" data-field-id="' . $field['id'] . '">';
                $html .= '<i class="bi bi-plus"></i></button>';
                $html .= '</div>';
                $html .= '</div>';
                
                // Special handling for SSD/HDD/Monitor - allow selection by serial number
                if (in_array($field['field_name'], ['SSD', 'HDD', 'Monitor', 'RAM'])) {
                    $html .= '<small class="text-muted">Or select existing ' . htmlspecialchars($field['field_name']) . ' by serial number:</small>';
                    $html .= '<select class="form-select mt-2" name="existing_' . strtolower($field['field_name']) . '[]" multiple>';
                    
                    // Get existing equipment of this type
                    $typeMap = [
                        'SSD' => 'SSD',
                        'HDD' => 'HDD',
                        'Monitor' => 'Monitor',
                        'RAM' => 'RAM'
                    ];
                    
                    if (isset($typeMap[$field['field_name']])) {
                        $existingStmt = $pdo->prepare("
                            SELECT e.id, e.label, e.serial_number, e.brand
                            FROM equipments e
                            JOIN equipment_types et ON e.equipment_type_id = et.id
                            WHERE et.type_name = ?
                            ORDER BY e.label
                        ");
                        $existingStmt->execute([$typeMap[$field['field_name']]]);
                        $existingItems = $existingStmt->fetchAll();
                        
                        foreach ($existingItems as $item) {
                            $html .= '<option value="' . $item['id'] . '">';
                            $html .= htmlspecialchars($item['label']) . ' - ' . htmlspecialchars($item['serial_number']);
                            if ($item['brand']) {
                                $html .= ' (' . htmlspecialchars($item['brand']) . ')';
                            }
                            $html .= '</option>';
                        }
                    }
                    
                    $html .= '</select>';
                }
                break;
        }
        
        $html .= '</div>';
    }
    
    echo json_encode([
        'success' => true,
        'type_name' => $equipmentType['type_name'],
        'html' => $html
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}