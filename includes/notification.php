<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the root path
define('ROOT_PATH', dirname(__DIR__));

class Notification {
    private static $db;
    private static $initialized = false;

    public static function init($database) {
        if (!$database) {
            throw new Exception('Database connection required');
        }
        self::$db = $database;
        self::$initialized = true;
    }

    private static function checkInit() {
        if (!self::$initialized) {
            throw new Exception('Notification system not initialized. Call init() first.');
        }
    }

    public static function setError($message) {
        if (empty($message)) return;
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => $message
        ];
    }

    public static function setSuccess($message) {
        if (empty($message)) return;
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => $message
        ];
    }

    public static function setWarning($message) {
        $_SESSION['notification'] = [
            'type' => 'warning',
            'message' => $message
        ];
    }

    public static function setInfo($message) {
        $_SESSION['notification'] = [
            'type' => 'info',
            'message' => $message
        ];
    }

    public static function create($userId, $title, $message, $type = 'info') {
        self::checkInit();
        try {
            $stmt = self::$db->getConnection()->prepare('
                INSERT INTO notifications (user_id, title, message, type)
                VALUES (?, ?, ?, ?)
            ');
            
            $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
            $stmt->bindValue(2, $title, SQLITE3_TEXT);
            $stmt->bindValue(3, $message, SQLITE3_TEXT);
            $stmt->bindValue(4, $type, SQLITE3_TEXT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Failed to create notification: ' . $e->getMessage());
            return false;
        }
    }

    public static function getUnread($userId) {
        try {
            $stmt = self::$db->getConnection()->prepare('
                SELECT * FROM notifications 
                WHERE user_id = ? AND seen = 0 
                ORDER BY created_at DESC
            ');
            
            $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            $notifications = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $notifications[] = $row;
            }
            
            return $notifications;
        } catch (Exception $e) {
            error_log('Failed to get notifications: ' . $e->getMessage());
            return [];
        }
    }

    public static function markAsRead($notificationId) {
        try {
            $stmt = self::$db->getConnection()->prepare('
                UPDATE notifications SET seen = 1 
                WHERE id = ?
            ');
            
            $stmt->bindValue(1, $notificationId, SQLITE3_INTEGER);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Failed to mark notification as read: ' . $e->getMessage());
            return false;
        }
    }

    public static function display() {
        if (isset($_SESSION['notification'])) {
            $notification = $_SESSION['notification'];
            unset($_SESSION['notification']);
            return json_encode($notification);
        }
        return 'null';
    }
}
?>