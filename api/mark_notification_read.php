<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$db = new Database();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

$stmt = $db->getConnection()->prepare('
    UPDATE notifications 
    SET seen = 1 
    WHERE id = ? AND user_id = ?
');

$stmt->bindValue(1, $id, SQLITE3_INTEGER);
$stmt->bindValue(2, $_SESSION['user_id'], SQLITE3_INTEGER);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
}