<?php
// File: layouts/header.php
// Purpose: Common header with modern UI and notification bell

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'includes/functions.php';

requireLogin();

$notifCount = 0;
try {
    $notifStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notification_user_status 
        WHERE user_id = ? AND is_acknowledged = FALSE
    ");
    $notifStmt->execute([getCurrentUserId()]);
    $notifCount = $notifStmt->fetch()['count'];
} catch (Exception $e) {
    // Table might not exist yet
}

// Get current user data
$currentUser = null;
try {
    $userStmt = $pdo->prepare("SELECT profile_photo, role FROM users WHERE id = ?");
    $userStmt->execute([getCurrentUserId()]);
    $currentUser = $userStmt->fetch();
} catch (Exception $e) {
    // Handle error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="<?php echo SITE_NAME; ?> - Equipment and Asset Management System">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Stylesheets -->
    <link href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/custom.css" rel="stylesheet">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', 'Google Sans', sans-serif;
            color: #1B1C1D;
            background-color: #f5f7fa;
        }
        
        /* Modern Navbar */
        .navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 0.75rem 1rem;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
            letter-spacing: -0.5px;
            transition: transform 0.2s;
        }
        
        .navbar-brand:hover {
            transform: translateY(-2px);
        }
        
        .navbar-brand i {
            font-size: 1.5rem;
            vertical-align: middle;
        }
        
        /* Notification Bell */
        .notification-bell {
            position: relative;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.1);
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notification-bell:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }
        
        .notification-bell i {
            font-size: 1.25rem;
        }
        
        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            box-shadow: 0 2px 8px rgba(238, 90, 111, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* User Profile */
        .user-profile {
            display: flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .user-profile:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
            margin-right: 0.5rem;
        }
        
        .user-avatar-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
        }
        
        .user-name {
            font-weight: 500;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .user-role-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
            margin-left: 0.5rem;
        }
        
        /* Notification Dropdown */
        .notification-dropdown {
            min-width: 420px;
            max-width: 420px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            padding: 0;
            margin-top: 0.5rem;
        }
        
        .notification-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.25rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-header h6 {
            margin: 0;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .notification-list {
            max-height: 450px;
            overflow-y: auto;
            padding: 0;
        }
        
        .notification-item {
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-unread {
            background: linear-gradient(90deg, #e3f2fd 0%, #f5f5f5 100%);
            border-left: 3px solid #2196F3;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        
        .notification-content {
            flex: 1;
            padding: 0 0.75rem;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .notification-message {
            font-size: 0.85rem;
            color: #6c757d;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: #95a5a6;
            margin-top: 0.25rem;
        }
        
        .acknowledge-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .acknowledge-btn:hover {
            transform: scale(1.1);
        }
        
        .notification-footer {
            padding: 0.75rem 1.25rem;
            text-align: center;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }
        
        .notification-footer a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .notification-footer a:hover {
            color: #764ba2;
        }
        
        .empty-notifications {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #95a5a6;
        }
        
        .empty-notifications i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        /* User Dropdown */
        .user-dropdown {
            min-width: 240px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }
        
        .user-dropdown .dropdown-item {
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .user-dropdown .dropdown-item i {
            width: 20px;
            font-size: 1.1rem;
        }
        
        .user-dropdown .dropdown-item:hover {
            background: #f8f9fa;
            padding-left: 1.5rem;
        }
        
        .user-dropdown .dropdown-divider {
            margin: 0.5rem 0;
        }
        
        /* Scrollbar Styling */
        .notification-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .notification-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .notification-list::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 3px;
        }
        
        .notification-list::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .notification-dropdown {
                min-width: 100vw;
                max-width: 100vw;
                margin: 0;
                border-radius: 0;
            }
            
            .user-name {
                display: none;
            }
            
            .user-role-badge {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <i class="bi bi-pc-display-horizontal"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Notifications -->
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link notification-bell" href="#" id="notificationDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            <?php if ($notifCount > 0): ?>
                                <span class="notification-badge" id="notificationBadge">
                                    <?php echo $notifCount > 99 ? '99+' : $notifCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown shadow-lg">
                            <li class="notification-header">
                                <h6>Notifications</h6>
                                <?php if ($notifCount > 0): ?>
                                    <span class="badge bg-white text-primary"><?php echo $notifCount; ?> New</span>
                                <?php endif; ?>
                            </li>
                            <div class="notification-list" id="notificationList">
                                <div class="empty-notifications">
                                    <i class="bi bi-hourglass-split"></i>
                                    <p class="mb-0">Loading...</p>
                                </div>
                            </div>
                            <li class="notification-footer">
                                <a href="<?php echo BASE_URL; ?>pages/notifications/list_notifications.php">
                                    View All Notifications <i class="bi bi-arrow-right"></i>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- User Profile -->
                    <li class="nav-item dropdown">
                        <a class="nav-link p-0" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-profile">
                                <?php if ($currentUser && $currentUser['profile_photo']): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $currentUser['profile_photo']; ?>" 
                                         class="user-avatar" alt="Profile">
                                <?php else: ?>
                                    <div class="user-avatar-icon">
                                        <i class="bi bi-person-circle fs-5"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="user-name"><?php echo getCurrentUserName(); ?></span>
                                <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                                    <span class="user-role-badge">ADMIN</span>
                                <?php endif; ?>
                                <i class="bi bi-chevron-down ms-2"></i>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end user-dropdown shadow-lg">
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/users/profile.php">
                                    <i class="bi bi-person"></i> My Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">