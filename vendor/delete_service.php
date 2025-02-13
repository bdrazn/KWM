<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $auth = new Auth($db);

    if (!$auth->isLoggedIn() || $_SESSION['user_type'] !== 'vendor') {
        throw new Exception('Unauthorized access');
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception('Invalid service ID');
    }

    $stmt = $db->getConnection()->prepare('
        DELETE FROM services 
        WHERE id = ? AND vendor_id = ?
    ');
    
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $_SESSION['user_id'], SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Service deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete service');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}