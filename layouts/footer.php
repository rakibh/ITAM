<?php
// File: layouts/footer.php
// Purpose: Common footer with JavaScript includes and notification auto-refresh
?>
        </div><!-- End sidebar column -->
    </div><!-- End row -->
</div><!-- End container-fluid -->

<!-- Footer -->
<footer class="bg-light text-center text-muted py-3 mt-5 border-top">
    <div class="container">
        <p class="mb-0">
            <i class="bi bi-c-circle"></i> <?php echo date('Y'); ?> 
            <strong><?php echo SITE_NAME; ?></strong>. All rights reserved.
        </p>
    </div>
</footer>

<!-- Scripts -->
<script src="<?php echo BASE_URL; ?>assets/js/jquery-3.6.0.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/custom.js"></script>

<script>
// Notification system with improved UI
function loadNotifications() {
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/notification_operations.php',
        type: 'GET',
        data: { action: 'get_recent', limit: 15 },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.notifications.length > 0) {
                let html = '';
                let unackCount = 0;
                
                response.notifications.forEach(function(notif) {
                    const isUnread = !notif.is_read;
                    const isUnack = !notif.is_acknowledged;
                    
                    if (isUnack) unackCount++;
                    
                    // Type configuration
                    const typeConfig = {
                        'Equipment': { icon: 'pc-display', color: '#667eea', bg: '#e8eaf6' },
                        'Network': { icon: 'ethernet', color: '#26c6da', bg: '#e0f7fa' },
                        'User': { icon: 'person', color: '#66bb6a', bg: '#e8f5e9' },
                        'Todo': { icon: 'check-square', color: '#ffa726', bg: '#fff3e0' },
                        'Warranty': { icon: 'shield-check', color: '#ef5350', bg: '#ffebee' },
                        'System': { icon: 'gear', color: '#78909c', bg: '#eceff1' }
                    };
                    
                    const config = typeConfig[notif.type] || typeConfig['System'];
                    
                    const ackBtn = isUnack ? 
                        `<button class="btn btn-sm btn-outline-success acknowledge-btn" data-id="${notif.id}" title="Acknowledge" onclick="event.stopPropagation();">
                            <i class="bi bi-check-circle"></i>
                        </button>` : '';
                    
                    html += `
                        <li class="notification-item ${isUnread ? 'notification-unread' : ''}" data-notif-id="${notif.id}">
                            <div class="d-flex align-items-start">
                                <div class="notification-icon" style="background-color: ${config.bg}; color: ${config.color};">
                                    <i class="bi bi-${config.icon}"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">${notif.title}</div>
                                    <div class="notification-message">${notif.message}</div>
                                    <div class="notification-time">
                                        <i class="bi bi-clock"></i> ${notif.time_ago}
                                    </div>
                                </div>
                                ${ackBtn ? `<div class="flex-shrink-0">${ackBtn}</div>` : ''}
                            </div>
                        </li>
                    `;
                });
                
                $('#notificationList').html(html);
                
                // Update badge with animation
                const badge = $('#notificationBadge');
                if (unackCount > 0) {
                    badge.text(unackCount > 99 ? '99+' : unackCount).fadeIn(200);
                } else {
                    badge.fadeOut(200);
                }
            } else {
                $('#notificationList').html(`
                    <div class="empty-notifications">
                        <i class="bi bi-bell-slash"></i>
                        <p class="mb-0">No notifications yet</p>
                        <small class="text-muted">You're all caught up!</small>
                    </div>
                `);
                $('#notificationBadge').fadeOut(200);
            }
        },
        error: function() {
            $('#notificationList').html(`
                <div class="empty-notifications">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <p class="mb-0 text-danger">Error loading notifications</p>
                    <small class="text-muted">Please try again later</small>
                </div>
            `);
        }
    });
}

// Mark notification as read with smooth animation
$(document).on('click', '.notification-item', function(e) {
    if ($(e.target).closest('.acknowledge-btn').length) {
        return; // Don't mark as read if clicking acknowledge button
    }
    
    e.preventDefault();
    e.stopPropagation();
    
    const notifId = $(this).data('notif-id');
    const $item = $(this);
    
    // Visual feedback
    $item.css('opacity', '0.6');
    
    $.post('<?php echo BASE_URL; ?>ajax/notification_operations.php', {
        action: 'mark_read',
        notification_id: notifId
    }, function() {
        $item.removeClass('notification-unread');
        $item.animate({ opacity: 1 }, 300);
        
        // Reload after a delay to show the change
        setTimeout(loadNotifications, 500);
    });
});

// Acknowledge notification with animation
$(document).on('click', '.acknowledge-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const notifId = $(this).data('id');
    const $btn = $(this);
    const $item = $btn.closest('.notification-item');
    
    // Visual feedback
    $btn.html('<i class="bi bi-check-circle-fill"></i>').prop('disabled', true);
    $item.css('opacity', '0.6');
    
    $.post('<?php echo BASE_URL; ?>ajax/notification_operations.php', {
        action: 'acknowledge',
        notification_id: notifId
    }, function() {
        // Slide up and remove
        $item.slideUp(300, function() {
            loadNotifications();
        });
    }).fail(function() {
        $btn.html('<i class="bi bi-check-circle"></i>').prop('disabled', false);
        $item.css('opacity', '1');
    });
});

// Load notifications on page load with fade in
$(document).ready(function() {
    loadNotifications();
    
    // Add fade in animation when dropdown opens
    $('#notificationDropdown').on('show.bs.dropdown', function() {
        loadNotifications();
    });
});

// Auto-refresh notifications every 30 seconds
setInterval(loadNotifications, <?php echo NOTIFICATION_REFRESH_INTERVAL * 1000; ?>);

// Add loading indicator during AJAX calls
$(document).ajaxStart(function() {
    // Optional: Add a subtle loading indicator
}).ajaxStop(function() {
    // Optional: Remove loading indicator
});

// Smooth scroll to top for long pages
$(window).scroll(function() {
    if ($(this).scrollTop() > 300) {
        if (!$('#backToTop').length) {
            $('body').append(`
                <button id="backToTop" class="btn btn-primary btn-sm" 
                        style="position: fixed; bottom: 30px; right: 30px; z-index: 1000; 
                               border-radius: 50%; width: 45px; height: 45px; 
                               box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: none;">
                    <i class="bi bi-arrow-up"></i>
                </button>
            `);
            $('#backToTop').fadeIn();
        } else {
            $('#backToTop').fadeIn();
        }
    } else {
        $('#backToTop').fadeOut();
    }
});

$(document).on('click', '#backToTop', function() {
    $('html, body').animate({ scrollTop: 0 }, 600);
});

// Toast notification for quick actions
function showToast(message, type = 'success') {
    const bgColor = type === 'success' ? '#28a745' : type === 'danger' ? '#dc3545' : '#ffc107';
    const icon = type === 'success' ? 'check-circle' : type === 'danger' ? 'x-circle' : 'info-circle';
    
    const toast = $(`
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11000;">
            <div class="toast show" role="alert">
                <div class="toast-body" style="background-color: ${bgColor}; color: white; border-radius: 8px; padding: 15px;">
                    <i class="bi bi-${icon} me-2"></i>
                    ${message}
                </div>
            </div>
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(function() {
        toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}
</script>

</body>
</html>