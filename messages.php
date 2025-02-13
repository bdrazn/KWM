<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/notifications_handler.php';

$db = new Database();
$auth = new Auth($db);

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$notifications_handler = new NotificationsHandler($db);

// Get selected conversation
$selected_user = isset($_GET['user']) ? intval($_GET['user']) : null;

// Get conversations
$stmt = $db->getConnection()->prepare('
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END as other_user_id,
        u.email as other_user_email,
        MAX(m.created_at) as last_message_time,
        COUNT(CASE WHEN m.receiver_id = ? AND m.seen = 0 THEN 1 END) as unread_count
    FROM messages m
    JOIN users u ON (
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END = u.id
    )
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY other_user_id
    ORDER BY last_message_time DESC
');

$stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
$stmt->bindValue(2, $_SESSION['user_id'], SQLITE3_INTEGER);
$stmt->bindValue(3, $_SESSION['user_id'], SQLITE3_INTEGER);
$stmt->bindValue(4, $_SESSION['user_id'], SQLITE3_INTEGER);
$stmt->bindValue(5, $_SESSION['user_id'], SQLITE3_INTEGER);
$conversations = $stmt->execute();

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-teal text-white">
                    <h5 class="card-title mb-0">Conversations</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php
                    $has_conversations = false;
                    while ($conv = $conversations->fetchArray(SQLITE3_ASSOC)) {
                        $has_conversations = true;
                        $active = $selected_user == $conv['other_user_id'] ? 'active' : '';
                        ?>
                        <a href="?user=<?php echo $conv['other_user_id']; ?>" 
                           class="list-group-item list-group-item-action <?php echo $active; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($conv['other_user_email']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('M j, g:i a', strtotime($conv['last_message_time'])); ?>
                                    </small>
                                </div>
                                <?php if ($conv['unread_count'] > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php
                    }
                    if (!$has_conversations) {
                        echo '<div class="list-group-item text-center text-muted">No conversations yet</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-teal text-white">
                    <h5 class="card-title mb-0">
                        <?php
                        if ($selected_user) {
                            $stmt = $db->getConnection()->prepare('SELECT email FROM users WHERE id = ?');
                            $stmt->bindValue(1, $selected_user, SQLITE3_INTEGER);
                            $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
                            echo htmlspecialchars($user['email']);
                        } else {
                            echo 'Select a conversation';
                        }
                        ?>
                    </h5>
                </div>
                <div class="card-body" id="messageContainer" style="height: 400px; overflow-y: auto;">
                    <?php
                    if ($selected_user) {
                        // Mark messages as seen
                        $update = $db->getConnection()->prepare('
                            UPDATE messages SET seen = 1 
                            WHERE sender_id = ? AND receiver_id = ? AND seen = 0
                        ');
                        $update->bindValue(1, $selected_user, SQLITE3_INTEGER);
                        $update->bindValue(2, $_SESSION['user_id'], SQLITE3_INTEGER);
                        $update->execute();

                        // Get messages
                        $stmt = $db->getConnection()->prepare('
                            SELECT m.*, u.email as sender_email 
                            FROM messages m
                            JOIN users u ON m.sender_id = u.id
                            WHERE (sender_id = ? AND receiver_id = ?) 
                               OR (sender_id = ? AND receiver_id = ?)
                            ORDER BY created_at ASC
                        ');
                        $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
                        $stmt->bindValue(2, $selected_user, SQLITE3_INTEGER);
                        $stmt->bindValue(3, $selected_user, SQLITE3_INTEGER);
                        $stmt->bindValue(4, $_SESSION['user_id'], SQLITE3_INTEGER);
                        $messages = $stmt->execute();

                        while ($message = $messages->fetchArray(SQLITE3_ASSOC)) {
                            $is_sender = $message['sender_id'] == $_SESSION['user_id'];
                            ?>
                            <div class="mb-3 d-flex <?php echo $is_sender ? 'justify-content-end' : 'justify-content-start'; ?>">
                                <div class="card <?php echo $is_sender ? 'bg-teal text-white' : 'bg-light'; ?>" style="max-width: 70%;">
                                    <div class="card-body py-2 px-3">
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                        <small class="<?php echo $is_sender ? 'text-white-50' : 'text-muted'; ?>">
                                            <?php echo date('g:i a', strtotime($message['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="text-center text-muted">Select a conversation to view messages</div>';
                    }
                    ?>
                </div>
                <?php if ($selected_user): ?>
                <div class="card-footer">
                    <form id="messageForm" class="d-flex gap-2">
                        <input type="hidden" name="receiver_id" value="<?php echo $selected_user; ?>">
                        <textarea class="form-control" name="message" rows="1" placeholder="Type your message..." required></textarea>
                        <button type="submit" class="btn btn-teal">Send</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');
    const messageForm = document.getElementById('messageForm');

    // Scroll to bottom of messages
    messageContainer.scrollTop = messageContainer.scrollHeight;

    if (messageForm) {
        messageForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'send_message');

            try {
                const response = await fetch('ajax/messages.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    toastr.error(result.message || 'Failed to send message');
                }
            } catch (error) {
                toastr.error('An error occurred');
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>