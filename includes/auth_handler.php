<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/database.php';
require_once 'auth.php';

header('Content-Type: application/json');

$db = new Database();

// Add table check
try {
    $tableCheck = $db->getConnection()->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if (!$tableCheck->fetchArray()) {
        // Create users table if it doesn't exist
        $db->getConnection()->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                user_type TEXT CHECK(user_type IN ("student", "vendor")) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database initialization error: ' . $e->getMessage()
    ]);
    exit;
}

$auth = new Auth($db);

try {
    if (isset($_POST['login'])) {
        $result = $auth->login($_POST['email'], $_POST['password']);
        if ($result['success']) {
            // Redirect based on user type
            $redirect = $result['user_type'] === 'vendor' ? 'vendor/services.php' : 'home.php';
            echo json_encode([
                'success' => true,
                'redirect' => $redirect
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
    } elseif (isset($_POST['register'])) {
        if (empty($_POST['email']) || empty($_POST['password']) || empty($_POST['user_type'])) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        $result = $auth->register($_POST['email'], $_POST['password'], $_POST['user_type']);
        
        // Create vendor profile if user type is vendor
        if ($result['success'] && $_POST['user_type'] === 'vendor') {
            try {
                $stmt = $db->getConnection()->prepare('
                    INSERT INTO vendor_profiles (user_id)
                    VALUES (?)
                ');
                $stmt->bindValue(1, $result['user_id'], SQLITE3_INTEGER);
                $stmt->execute();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to create vendor profile']);
                exit;
            }
        }
        
        echo json_encode($result);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
exit;