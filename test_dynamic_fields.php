<?php
// File: test_dynamic_fields.php (Place in root directory for testing)
// Purpose: Test if dynamic fields AJAX endpoint works

define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'includes/functions.php';

requireLogin();

// Test direct access to load_dynamic_fields.php
$testTypeId = 1; // Desktop PC

echo "<h1>Testing Dynamic Fields Loading</h1>";
echo "<hr>";

// Test 1: Check if ajax/load_dynamic_fields.php exists
$ajaxFile = ROOT_PATH . 'ajax/load_dynamic_fields.php';
echo "<h3>Test 1: File Existence</h3>";
echo "File path: " . $ajaxFile . "<br>";
echo "File exists: " . (file_exists($ajaxFile) ? '✅ YES' : '❌ NO') . "<br>";
echo "<hr>";

// Test 2: Check equipment types
echo "<h3>Test 2: Equipment Types in Database</h3>";
$typeStmt = $pdo->query("SELECT id, type_name FROM equipment_types ORDER BY type_name LIMIT 5");
$types = $typeStmt->fetchAll();
echo "<ul>";
foreach ($types as $type) {
    echo "<li>ID: {$type['id']} - {$type['type_name']}</li>";
}
echo "</ul>";
echo "<hr>";

// Test 3: Check if fields exist for type
echo "<h3>Test 3: Fields for Equipment Type ID = {$testTypeId}</h3>";
$fieldsStmt = $pdo->prepare("
    SELECT etf.*, et.type_name
    FROM equipment_type_fields etf
    JOIN equipment_types et ON etf.equipment_type_id = et.id
    WHERE etf.equipment_type_id = ?
    ORDER BY etf.display_order
");
$fieldsStmt->execute([$testTypeId]);
$fields = $fieldsStmt->fetchAll();

if (empty($fields)) {
    echo "❌ No fields found for this type<br>";
} else {
    echo "✅ Found " . count($fields) . " fields:<br>";
    echo "<ul>";
    foreach ($fields as $field) {
        echo "<li>{$field['field_name']} (Type: {$field['field_type']}, Required: " . ($field['is_required'] ? 'Yes' : 'No') . ")</li>";
    }
    echo "</ul>";
}
echo "<hr>";

// Test 4: Test AJAX call with jQuery
echo "<h3>Test 4: AJAX Call Test</h3>";
echo "<p>Select a type to test dynamic loading:</p>";
echo "<select id='test_type_id' class='form-select' style='max-width: 300px;'>";
echo "<option value=''>Select Type...</option>";
foreach ($types as $type) {
    echo "<option value='{$type['id']}'>{$type['type_name']}</option>";
}
echo "</select>";
echo "<div id='ajax_result' style='margin-top: 20px; padding: 15px; border: 1px solid #ddd; min-height: 100px;'></div>";

echo "<hr>";
echo "<h3>Test 5: Check BASE_URL Configuration</h3>";
echo "BASE_URL: " . BASE_URL . "<br>";
echo "Expected AJAX URL: " . BASE_URL . "ajax/load_dynamic_fields.php<br>";
?>

<!DOCTYPE html>
<html>
<head>
    <link href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<script src="<?php echo BASE_URL; ?>assets/js/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#test_type_id').on('change', function() {
        const typeId = $(this).val();
        
        if (!typeId) {
            $('#ajax_result').html('');
            return;
        }
        
        $('#ajax_result').html('⏳ Loading...');
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>ajax/load_dynamic_fields.php',
            type: 'GET',
            data: { type_id: typeId },
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);
                
                let html = '<strong>✅ AJAX Success!</strong><br>';
                html += '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
                
                if (response.html) {
                    html += '<hr><strong>Generated HTML:</strong><br>';
                    html += '<div style="background: #f5f5f5; padding: 10px;">' + response.html + '</div>';
                }
                
                $('#ajax_result').html(html);
            },
            error: function(xhr, status, error) {
                console.error('Error:', xhr.responseText);
                
                let html = '<strong>❌ AJAX Error!</strong><br>';
                html += 'Status: ' + status + '<br>';
                html += 'Error: ' + error + '<br>';
                html += '<strong>Response:</strong><br>';
                html += '<pre>' + xhr.responseText + '</pre>';
                
                $('#ajax_result').html(html);
            }
        });
    });
});
</script>
</body>
</html>