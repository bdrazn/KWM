<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$db = new Database();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: search.php');
    exit();
}

// Get form data
$service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
$date = isset($_POST['date']) ? $_POST['date'] : '';
$time = isset($_POST['time']) ? $_POST['time'] : '';

// Validate inputs
if (!$service_id || !$date || !$time) {
    header('Location: book_service.php?id=' . $service_id . '&error=missing_fields');
    exit();
}

// Validate date is in the future
$booking_datetime = new DateTime($date . ' ' . $time);
$now = new DateTime();
if ($booking_datetime <= $now) {
    header('Location: book_service.php?id=' . $service_id . '&error=invalid_date');
    exit();
}

try {
    // First, verify the table structure
    $table_check = $db->getConnection()->query("PRAGMA table_info(bookings)");
    $columns = [];
    while ($col = $table_check->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $col['name'];
    }

    // Check if service exists
    $stmt = $db->getConnection()->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->bindValue(1, $service_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $service = $result->fetchArray(SQLITE3_ASSOC);

    if (!$service) {
        throw new Exception('Service not found');
    }

    // Check for existing bookings at the same time
    $stmt = $db->getConnection()->prepare('
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE service_id = ? AND booking_date = ? AND booking_time = ?
    ');
    $stmt->bindValue(1, $service_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $date, SQLITE3_TEXT);
    $stmt->bindValue(3, $time, SQLITE3_TEXT);
    $result = $stmt->execute();
    $existing = $result->fetchArray(SQLITE3_ASSOC);

    if ($existing['count'] > 0) {
        header('Location: book_service.php?id=' . $service_id . '&error=time_taken');
        exit();
    }

    // Create the booking
    $stmt = $db->getConnection()->prepare('
        INSERT INTO bookings (student_id, service_id, booking_date, booking_time, status, created_at)
        VALUES (?, ?, ?, ?, ?, datetime("now"))
    ');
    
    $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $service_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, $date, SQLITE3_TEXT);
    $stmt->bindValue(4, $time, SQLITE3_TEXT);
    $stmt->bindValue(5, 'pending', SQLITE3_TEXT);
    
    $stmt->execute();

    // Store the success message in session
    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => 'Booking confirmed successfully!'
    ];
    session_write_close(); // Ensure session is written before redirect

    // Redirect to bookings page
    header('Location: bookings.php');
    exit();

} catch (Exception $e) {
    error_log("Booking error: " . $e->getMessage()); // Add error logging
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'An error occurred while processing your booking.'
    ];
    session_write_close(); // Ensure session is written before redirect
    
    header('Location: book_service.php?id=' . $service_id);
    exit();
}