<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/notifications_handler.php';

$db = new Database();
$auth = new Auth($db);
$notifications = new NotificationsHandler($db);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-bell text-primary me-2"></i>
                Notifications
            </h5>
            <button class="btn btn-sm btn-outline-primary" id="markAllRead">
                <i class="fas fa-check-double me-2"></i>
                Mark All as Read
            </button>
        </div>
        <div class="card-body">
            <?php
            $user_notifications = $notifications->getUnseenCounts($_SESSION['user_id']);
            if ($user_notifications['notifications'] > 0):
            ?>
                <div class="list-group">
                    <?php
                    $stmt = $db->getConnection()->prepare('
                        SELECT * FROM notifications 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC
                    ');
                    $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
                    $result = $stmt->execute();
                    
                    while ($notification = $result->fetchArray(SQLITE3_ASSOC)):
                        $isUnread = !$notification['seen'];
                    ?>
                        <div class="list-group-item list-group-item-action <?php echo $isUnread ? 'bg-light' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                <small class="text-muted">
                                    <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                </small>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <?php if ($isUnread): ?>
                                <button class="btn btn-sm btn-link mark-read" data-id="<?php echo $notification['id']; ?>">
                                    Mark as read
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-bell-slash fa-3x mb-3"></i>
                    <p>No notifications yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark single notification as read
    document.querySelectorAll('.mark-read').forEach(button => {
        button.addEventListener('click', async function() {
            const id = this.dataset.id;
            try {
                const response = await fetch('api/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });
                
                if (response.ok) {
                    this.closest('.list-group-item').classList.remove('bg-light');
                    this.remove();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });

    // Mark all notifications as read
    document.getElementById('markAllRead').addEventListener('click', async function() {
        try {
            const response = await fetch('api/mark_all_notifications_read.php', {
                method: 'POST'
            });
            
            if (response.ok) {
                document.querySelectorAll('.list-group-item').forEach(item => {
                    item.classList.remove('bg-light');
                });
                document.querySelectorAll('.mark-read').forEach(button => {
                    button.remove();
                });
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>