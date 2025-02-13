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

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception('Invalid service ID');
    }

    $stmt = $db->getConnection()->prepare('
        SELECT * FROM services 
        WHERE id = ? AND vendor_id = ?
    ');
    
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $_SESSION['user_id'], SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $service = $result->fetchArray(SQLITE3_ASSOC);

    if (!$service) {
        throw new Exception('Service not found');
    }

    echo json_encode($service);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}