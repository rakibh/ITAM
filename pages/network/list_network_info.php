<?php
// File: pages/network/list_network_info.php
// Purpose: Display all network information with filters, search, and export

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Network Information';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

// Pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = DEFAULT_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Filters
$search = sanitize($_GET['search'] ?? '');
$assignedFilter = sanitize($_GET['assigned'] ?? '');
$sortBy = sanitize($_GET['sort'] ?? 'created_at');
$sortOrder = sanitize($_GET['order'] ?? 'DESC');

// Valid sort columns
$validSortColumns = ['ip_address', 'cable_number', 'patch_panel_number', 'switch_number', 'created_at', 'updated_at'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'created_at';
}
$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Build WHERE clause
$where = ['1=1'];
$params = [];

if (!empty($search)) {
    $where[] = "(ni.ip_address LIKE ? OR ni.mac_address LIKE ? OR ni.cable_number LIKE ? OR 
                 ni.patch_panel_number LIKE ? OR ni.switch_number LIKE ? OR e.label LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($assignedFilter === 'assigned') {
    $where[] = "ni.equipment_id IS NOT NULL";
} elseif ($assignedFilter === 'unassigned') {
    $where[] = "ni.equipment_id IS NULL";
}

$whereClause = implode(' AND ', $where);

// Get total count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM network_info ni
    LEFT JOIN equipments e ON ni.equipment_id = e.id
    WHERE {$whereClause}
");
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get network info list
$stmt = $pdo->prepare("
    SELECT ni.*, 
           e.label as equipment_label, e.serial_number, e.id as equipment_id,
           et.type_name as equipment_type,
           u.first_name as creator_name, u.employee_id as creator_id
    FROM network_info ni
    LEFT JOIN equipments e ON ni.equipment_id = e.id
    LEFT JOIN equipment_types et ON e.equipment_type_id = et.id
    LEFT JOIN users u ON ni.created_by = u.id
    WHERE {$whereClause}
    ORDER BY ni.{$sortBy} {$sortOrder}
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$networkRecords = $stmt->fetchAll();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-ethernet me-2"></i>Network Information</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportData('all')">Export All</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportData('filtered')">Export Filtered</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportData('selected')">Export Selected</a></li>
                </ul>
            </div>
            <a href="add_network_info.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle"></i> Add Network Info
            </a>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="search" placeholder="Search IP, MAC, Cable, Switch..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="assigned">
                        <option value="">All</option>
                        <option value="assigned" <?php echo $assignedFilter === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="unassigned" <?php echo $assignedFilter === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-1">
                    <a href="list_network_info.php" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Network Info Table -->
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0">Total Records: <?php echo $totalRecords; ?></h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAll">
                <label class="form-check-label" for="selectAll">Select All</label>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover" id="networkTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllHeader"></th>
                            <th>#</th>
                            <th>
                                <a href="?sort=ip_address&order=<?php echo $sortBy === 'ip_address' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                    IP Address <?php if ($sortBy === 'ip_address') echo $sortOrder === 'ASC' ? '▲' : '▼'; ?>
                                </a>
                            </th>
                            <th>MAC Address</th>
                            <th>Cable No</th>
                            <th>Patch Panel</th>
                            <th>Switch</th>
                            <th>Assigned Equipment</th>
                            <?php if (isAdmin()): ?>
                            <th>Created By</th>
                            <th>Updated</th>
                            <?php endif; ?>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($networkRecords)): ?>
                        <tr>
                            <td colspan="<?php echo isAdmin() ? 11 : 9; ?>" class="text-center text-muted py-4">No network records found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($networkRecords as $index => $record): ?>
                            <tr>
                                <td><input type="checkbox" class="network-checkbox" value="<?php echo $record['id']; ?>"></td>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td><code><?php echo htmlspecialchars($record['ip_address']); ?></code></td>
                                <td><?php echo $record['mac_address'] ? '<code>' . htmlspecialchars($record['mac_address']) . '</code>' : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($record['cable_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($record['patch_panel_number']): ?>
                                        <?php echo htmlspecialchars($record['patch_panel_number']); ?>
                                        <?php if ($record['patch_panel_port']): ?>
                                            - Port <?php echo htmlspecialchars($record['patch_panel_port']); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['switch_number']): ?>
                                        <?php echo htmlspecialchars($record['switch_number']); ?>
                                        <?php if ($record['switch_port']): ?>
                                            - Port <?php echo htmlspecialchars($record['switch_port']); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['equipment_id']): ?>
                                        <a href="<?php echo BASE_URL; ?>pages/equipment/view_equipment.php?id=<?php echo $record['equipment_id']; ?>" 
                                           target="_blank" class="text-decoration-none">
                                            <span class="badge bg-success">
                                                <?php echo htmlspecialchars($record['equipment_label']); ?>
                                            </span>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <?php if (isAdmin()): ?>
                                <td>
                                    <small><?php echo htmlspecialchars($record['creator_name'] ?? 'N/A'); ?></small>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($record['creator_id'] ?? ''); ?></small>
                                </td>
                                <td><small><?php echo formatDate($record['updated_at']); ?></small></td>
                                <?php endif; ?>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="view_network_info.php?id=<?php echo $record['id']; ?>" 
                                           class="btn btn-outline-primary" target="_blank" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit_network_info.php?id=<?php echo $record['id']; ?>" 
                                           class="btn btn-outline-warning" target="_blank" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($record['equipment_id']): ?>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="unassignNetwork(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['ip_address']); ?>')" 
                                                title="Unassign">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-outline-success" 
                                                onclick="showAssignModal(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['ip_address']); ?>')" 
                                                title="Assign">
                                            <i class="bi bi-link"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteNetwork(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['ip_address']); ?>')" 
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <?php echo getPaginationHTML($page, $totalPages, 'list_network_info.php'); ?>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Assign Network Modal -->
<div class="modal fade" id="assignNetworkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Network Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignNetworkForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="network_id" id="assign_network_id">
                <div class="modal-body">
                    <p>Assign IP: <strong id="assign_ip_display"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Select Equipment</label>
                        <select class="form-select" name="equipment_id" id="assign_equipment_select" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Select all checkboxes
$('#selectAll, #selectAllHeader').on('change', function() {
    $('.network-checkbox').prop('checked', $(this).prop('checked'));
});

// Show assign modal
function showAssignModal(networkId, ipAddress) {
    $('#assign_network_id').val(networkId);
    $('#assign_ip_display').text(ipAddress);
    
    // Load available equipment
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/network_operations.php',
        type: 'GET',
        data: { action: 'get_available_equipment' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Select Equipment...</option>';
                response.equipment.forEach(function(eq) {
                    options += `<option value="${eq.id}">${eq.label} - ${eq.serial_number} (${eq.type_name})</option>`;
                });
                $('#assign_equipment_select').html(options);
            }
        }
    });
    
    $('#assignNetworkModal').modal('show');
}

// Assign network
$('#assignNetworkForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/network_operations.php',
        type: 'POST',
        data: $(this).serialize() + '&action=assign',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#assignNetworkModal').modal('hide');
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseJSON?.message || 'Error assigning network');
        }
    });
});

// Unassign network
function unassignNetwork(networkId, ipAddress) {
    if (confirm(`Unassign IP ${ipAddress} from its equipment?`)) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>ajax/network_operations.php',
            type: 'POST',
            data: {
                action: 'unassign',
                network_id: networkId,
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
                showAlert('danger', xhr.responseJSON?.message || 'Error unassigning network');
            }
        });
    }
}

// Delete network
function deleteNetwork(networkId, ipAddress) {
    if (confirm(`Delete network info for IP ${ipAddress}? This action cannot be undone.`)) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>ajax/network_operations.php',
            type: 'POST',
            data: {
                action: 'delete',
                network_id: networkId,
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
                showAlert('danger', xhr.responseJSON?.message || 'Error deleting network');
            }
        });
    }
}

// Export functionality
function exportData(type) {
    let url = '<?php echo BASE_URL; ?>ajax/network_operations.php?action=export&type=' + type;
    
    if (type === 'filtered') {
        url += '&' + new URLSearchParams(window.location.search).toString();
    } else if (type === 'selected') {
        let selected = [];
        $('.network-checkbox:checked').each(function() {
            selected.push($(this).val());
        });
        
        if (selected.length === 0) {
            alert('Please select at least one record to export');
            return;
        }
        
        url += '&ids=' + selected.join(',');
    }
    
    window.open(url, '_blank');
}

// Highlight search terms
<?php if (!empty($search)): ?>
$(document).ready(function() {
    const searchTerm = '<?php echo addslashes($search); ?>';
    $('td').each(function() {
        const text = $(this).text();
        if (text.toLowerCase().includes(searchTerm.toLowerCase())) {
            $(this).html(highlightText(text, searchTerm));
        }
    });
});
<?php endif; ?>
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>