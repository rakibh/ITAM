<?php
// File: layouts/sidebar.php
// Purpose: Modern sidebar navigation with role-based menu items

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>

<style>
    /* Modern Sidebar Styles */
    .sidebar {
        position: fixed;
        top: 65px;
        bottom: 0;
        left: 0;
        z-index: 100;
        padding: 1.5rem 0;
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.08);
        background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
        overflow-y: auto;
        transition: all 0.3s ease;
    }

    /* Scrollbar Styling */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }

    /* Navigation List */
    .sidebar .nav {
        padding: 0 0.75rem;
    }

    /* Navigation Items */
    .sidebar .nav-link {
        font-weight: 500;
        color: #4a5568;
        padding: 0.75rem 1rem;
        margin-bottom: 0.25rem;
        border-radius: 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .sidebar .nav-link::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transform: scaleY(0);
        transition: transform 0.3s ease;
    }

    .sidebar .nav-link:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        color: #667eea;
        transform: translateX(5px);
    }

    .sidebar .nav-link:hover::before {
        transform: scaleY(1);
    }

    .sidebar .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .sidebar .nav-link.active::before {
        display: none;
    }

    .sidebar .nav-link.active:hover {
        transform: translateX(0);
    }

    .sidebar .nav-link i {
        font-size: 1.25rem;
        margin-right: 0.75rem;
        width: 24px;
        text-align: center;
        transition: transform 0.3s ease;
    }

    .sidebar .nav-link:hover i {
        transform: scale(1.1);
    }

    .sidebar .nav-link.active i {
        animation: iconBounce 0.5s ease;
    }

    @keyframes iconBounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }

    /* Section Headings */
    .sidebar-heading {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 1px;
        color: #a0aec0;
        padding: 1.5rem 1rem 0.5rem 1rem;
        margin-top: 1rem;
        display: flex;
        align-items: center;
    }

    .sidebar-heading::before {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, transparent 0%, #e2e8f0 50%, transparent 100%);
        margin-right: 0.75rem;
    }

    .sidebar-heading:first-child {
        margin-top: 0;
    }

    /* Badge for Counts */
    .nav-badge {
        margin-left: auto;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        font-weight: 600;
    }

    .sidebar .nav-link.active .nav-badge {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    /* User Info Card */
    .sidebar-user-card {
        margin: 0 0.75rem 1.5rem 0.75rem;
        padding: 1rem;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        border-radius: 12px;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }

    .sidebar-user-avatar {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .sidebar-user-info {
        flex: 1;
        margin-left: 0.75rem;
    }

    .sidebar-user-name {
        font-weight: 600;
        color: #2d3748;
        font-size: 0.95rem;
        margin-bottom: 0.25rem;
    }

    .sidebar-user-role {
        font-size: 0.75rem;
        color: #718096;
        display: flex;
        align-items: center;
    }

    .sidebar-user-role i {
        margin-right: 0.25rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            position: relative;
            top: 0;
            width: 100%;
            height: auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .sidebar-user-card {
            display: none;
        }
    }

    /* Tooltip Enhancement */
    .nav-link[title]:hover::after {
        content: attr(title);
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        margin-left: 1rem;
        padding: 0.5rem 0.75rem;
        background: #2d3748;
        color: white;
        font-size: 0.85rem;
        border-radius: 6px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        animation: tooltipFadeIn 0.2s ease forwards;
        z-index: 1000;
    }

    @keyframes tooltipFadeIn {
        to { opacity: 1; }
    }
</style>

<!-- Sidebar -->
<nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="sidebarMenu">
    <div class="position-sticky">
        
        <!-- User Info Card -->
        <?php
        try {
            $userStmt = $pdo->prepare("SELECT profile_photo, role FROM users WHERE id = ?");
            $userStmt->execute([getCurrentUserId()]);
            $userData = $userStmt->fetch();
        } catch (Exception $e) {
            $userData = null;
        }
        ?>
        <div class="sidebar-user-card">
            <div class="d-flex align-items-center">
                <?php if ($userData && $userData['profile_photo']): ?>
                    <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $userData['profile_photo']; ?>" 
                         class="sidebar-user-avatar" alt="Profile">
                <?php else: ?>
                    <div class="sidebar-user-avatar d-flex align-items-center justify-content-center bg-white">
                        <i class="bi bi-person-circle fs-4 text-primary"></i>
                    </div>
                <?php endif; ?>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo getCurrentUserName(); ?></div>
                    <div class="sidebar-user-role">
                        <?php if (isAdmin()): ?>
                            <i class="bi bi-shield-check"></i> Administrator
                        <?php else: ?>
                            <i class="bi bi-person"></i> User
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($currentPage, 'dashboard') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/<?php echo isAdmin() ? 'dashboard_admin' : 'dashboard_user'; ?>.php"
                   title="Dashboard Overview">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <?php if (isAdmin()): ?>
            <!-- User Management (Admin Only) -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentDir === 'users' && $currentPage !== 'profile.php') ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/users/list_users.php"
                   title="Manage Users">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                    <?php
                    try {
                        $userCountStmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'Active'");
                        $userCount = $userCountStmt->fetch()['count'];
                        if ($userCount > 0) {
                            echo '<span class="nav-badge">' . $userCount . '</span>';
                        }
                    } catch (Exception $e) {}
                    ?>
                </a>
            </li>
            <?php endif; ?>

            <!-- Equipment -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentDir === 'equipment') ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/equipment/list_equipment.php"
                   title="Equipment Management">
                    <i class="bi bi-pc-display"></i>
                    <span>Equipment</span>
                    <?php
                    try {
                        $equipCountStmt = $pdo->query("SELECT COUNT(*) as count FROM equipments");
                        $equipCount = $equipCountStmt->fetch()['count'];
                        if ($equipCount > 0) {
                            echo '<span class="nav-badge">' . $equipCount . '</span>';
                        }
                    } catch (Exception $e) {}
                    ?>
                </a>
            </li>

            <!-- Network -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentDir === 'network') ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/network/list_network_info.php"
                   title="Network Information">
                    <i class="bi bi-ethernet"></i>
                    <span>Network</span>
                    <?php
                    try {
                        $networkCountStmt = $pdo->query("SELECT COUNT(*) as count FROM network_info");
                        $networkCount = $networkCountStmt->fetch()['count'];
                        if ($networkCount > 0) {
                            echo '<span class="nav-badge">' . $networkCount . '</span>';
                        }
                    } catch (Exception $e) {}
                    ?>
                </a>
            </li>

            <!-- To-Do Tasks -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentDir === 'todos') ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/todos/list_todos.php"
                   title="Task Management">
                    <i class="bi bi-check2-square"></i>
                    <span>Tasks</span>
                    <?php
                    try {
                        $taskStmt = $pdo->prepare("
                            SELECT COUNT(*) as count 
                            FROM todos t
                            JOIN todo_assignments ta ON t.id = ta.todo_id
                            WHERE ta.user_id = ? AND t.status NOT IN ('Completed', 'Cancelled')
                        ");
                        $taskStmt->execute([getCurrentUserId()]);
                        $taskCount = $taskStmt->fetch()['count'];
                        if ($taskCount > 0) {
                            echo '<span class="nav-badge">' . $taskCount . '</span>';
                        }
                    } catch (Exception $e) {}
                    ?>
                </a>
            </li>

            <!-- Notifications -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentDir === 'notifications') ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/notifications/list_notifications.php"
                   title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                    <?php
                    try {
                        $notifStmt = $pdo->prepare("
                            SELECT COUNT(*) as count 
                            FROM notification_user_status 
                            WHERE user_id = ? AND is_acknowledged = FALSE
                        ");
                        $notifStmt->execute([getCurrentUserId()]);
                        $notifCount = $notifStmt->fetch()['count'];
                        if ($notifCount > 0) {
                            echo '<span class="nav-badge bg-danger text-white">' . $notifCount . '</span>';
                        }
                    } catch (Exception $e) {}
                    ?>
                </a>
            </li>

            <?php if (isAdmin()): ?>
            <!-- Tools & Settings (Admin Only) -->
            <li class="nav-item">
                <h6 class="sidebar-heading">
                    <span>Tools & Settings</span>
                </h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'system_settings.php') ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/tools/system_settings.php"
                   title="System Configuration">
                    <i class="bi bi-gear"></i>
                    <span>System Settings</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'optimize_database.php') ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/tools/optimize_database.php"
                   title="Optimize Database">
                    <i class="bi bi-lightning"></i>
                    <span>DB Optimize</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'backup_database.php') ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/tools/backup_database.php"
                   title="Database Backup">
                    <i class="bi bi-cloud-download"></i>
                    <span>DB Backup</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'system_logs.php') ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/tools/system_logs.php"
                   title="System Logs">
                    <i class="bi bi-file-text"></i>
                    <span>System Logs</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Spacer -->
            <li class="nav-item" style="height: 2rem;"></li>

            <!-- Profile (Bottom) -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage === 'profile.php') ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/users/profile.php"
                   title="My Profile">
                    <i class="bi bi-person-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>

            <!-- Logout -->
            <li class="nav-item">
                <a class="nav-link text-danger" 
                   href="<?php echo BASE_URL; ?>logout.php"
                   title="Logout"
                   onclick="return confirm('Are you sure you want to logout?');">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>