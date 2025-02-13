<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/notifications_handler.php';

$db = new Database();
$auth = new Auth($db);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_POST['action'] === 'send_message') {
    $receiver_id = intval($_POST['receiver_id']);
    $message = trim($_POST['message']);

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }

    $notifications_handler = new NotificationsHandler($db);
    $result = $notifications_handler->sendMessage($_SESSION['user_id'], $receiver_id, $message);

    echo json_encode([
        'success' => $result !== false,
        'message' => $result !== false ? 'Message sent' : 'Failed to send message'
    ]);
    exit;
}