<?php
// File: layouts/footer.php
// Purpose: Common footer with JavaScript includes
?>
        </div><!-- End sidebar column -->
    </div><!-- End row -->
</div><!-- End container-fluid -->

<!-- Footer -->
<footer class="bg-light text-center text-muted py-3 mt-5">
    <div class="container">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
    </div>
</footer>

<!-- Scripts -->
<script src="<?php echo BASE_URL; ?>assets/js/jquery-3.6.0.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/custom.js"></script>

<script>
// Notification auto-refresh
function loadNotifications() {
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/notification_operations.php',
        type: 'GET',
        data: { action: 'get_recent', limit: 15 },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.notifications.length > 0) {
                let html = '';
                response.notifications.forEach(function(notif) {
                    const isRead = notif.is_read ? '' : 'fw-bold';
                    const ackBtn = notif.is_acknowledged ? '' : 
                        `<button class="btn btn-sm btn-outline-primary acknowledge-btn" data-id="${notif.id}">
                            <i class="bi bi-check"></i>
                        </button>`;
                    
                    html += `
                        <li>
                            <a class="dropdown-item ${isRead}" href="#" data-notif-id="${notif.id}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <strong>${notif.title}</strong><br>
                                        <small class="text-muted">${notif.message}</small><br>
                                        <small class="text-muted">${notif.time_ago}</small>
                                    </div>
                                    ${ackBtn}
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                    `;
                });
                $('#notificationList').html(html);
            } else {
                $('#notificationList').html('<li><a class="dropdown-item text-muted" href="#">No new notifications</a></li>');
            }
        }
    });
}

// Load notifications on page load
loadNotifications();

// Auto-refresh every 30 seconds
setInterval(loadNotifications, <?php echo NOTIFICATION_REFRESH_INTERVAL * 1000; ?>);

// Mark notification as read when clicked
$(document).on('click', '[data-notif-id]', function(e) {
    e.preventDefault();
    const notifId = $(this).data('notif-id');
    
    $.post('<?php echo BASE_URL; ?>ajax/notification_operations.php', {
        action: 'mark_read',
        notification_id: notifId
    }, function() {
        loadNotifications();
    });
});

// Acknowledge notification
$(document).on('click', '.acknowledge-btn', function(e) {
    e.stopPropagation();
    const notifId = $(this).data('id');
    
    $.post('<?php echo BASE_URL; ?>ajax/notification_operations.php', {
        action: 'acknowledge',
        notification_id: notifId
    }, function() {
        loadNotifications();
        location.reload(); // Reload to update badge count
    });
});
</script>

</body>
</html>