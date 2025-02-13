<?php
// Start output buffering at the very beginning
ob_start();

require_once '../config/database.php';
require_once '../includes/auth.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

try {
    $db = new Database();
    $auth = new Auth($db);

    // Check authentication and vendor status
    if (!$auth->isLoggedIn() || $_SESSION['user_type'] !== 'vendor') {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate inputs
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $duration = intval($_POST['duration'] ?? 0);
    $category = trim($_POST['category'] ?? '');

    // Input validation
    if (empty($title)) {
        throw new Exception('Service title is required');
    }
    if (empty($description)) {
        throw new Exception('Service description is required');
    }
    if ($price <= 0) {
        throw new Exception('Price must be greater than zero');
    }
    if (!in_array($duration, [30, 60, 90, 120])) {
        throw new Exception('Invalid duration selected');
    }
    if (empty($category)) {
        throw new Exception('Category is required');
    }

    // Insert service
    $stmt = $db->getConnection()->prepare('
        INSERT INTO services (vendor_id, title, description, price, duration, category)
        VALUES (?, ?, ?, ?, ?, ?)
    ');

    $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $title, SQLITE3_TEXT);
    $stmt->bindValue(3, $description, SQLITE3_TEXT);
    $stmt->bindValue(4, $price, SQLITE3_FLOAT);
    $stmt->bindValue(5, $duration, SQLITE3_INTEGER);
    $stmt->bindValue(6, $category, SQLITE3_TEXT);

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Service added successfully!',
            'service_id' => $db->getConnection()->lastInsertRowID()
        ];
    } else {
        throw new Exception('Failed to add service. Please try again.');
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
} finally {
    // Clear any output buffered so far
    ob_clean();
    
    // Ensure we're only outputting the JSON
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    
    echo json_encode($response);
    exit();
}