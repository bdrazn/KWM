<?php
class NotificationsHandler {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getUnseenCounts($user_id) {
        $notifications = $this->db->getConnection()->prepare('
            SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = ? AND seen = 0
        ');
        $notifications->bindValue(1, $user_id, SQLITE3_INTEGER);
        $notif_count = $notifications->execute()->fetchArray(SQLITE3_ASSOC)['count'];

        $messages = $this->db->getConnection()->prepare('
            SELECT COUNT(*) as count FROM messages 
            WHERE receiver_id = ? AND seen = 0
        ');
        $messages->bindValue(1, $user_id, SQLITE3_INTEGER);
        $msg_count = $messages->execute()->fetchArray(SQLITE3_ASSOC)['count'];

        return [
            'notifications' => $notif_count,
            'messages' => $msg_count
        ];
    }

    public function addNotification($user_id, $title, $message, $type = 'info') {
        $stmt = $this->db->getConnection()->prepare('
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $title, SQLITE3_TEXT);
        $stmt->bindValue(3, $message, SQLITE3_TEXT);
        $stmt->bindValue(4, $type, SQLITE3_TEXT);
        return $stmt->execute();
    }

    public function sendMessage($sender_id, $receiver_id, $message) {
        $stmt = $this->db->getConnection()->prepare('
            INSERT INTO messages (sender_id, receiver_id, message)
            VALUES (?, ?, ?)
        ');
        $stmt->bindValue(1, $sender_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $receiver_id, SQLITE3_INTEGER);
        $stmt->bindValue(3, $message, SQLITE3_TEXT);
        return $stmt->execute();
    }
}
?>