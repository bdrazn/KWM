<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$db = new Database();
$auth = new Auth($db);

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get the current status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Prepare the SQL query based on user type and status
$user_id = $_SESSION['user_id'];

// First, verify the table structure
$table_check = $db->getConnection()->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='bookings'");
$table_info = $table_check->fetchArray(SQLITE3_ASSOC);

if (!$table_info) {
    die("Error: Bookings table not found");
}

// Get column names from the bookings table
$columns_check = $db->getConnection()->query("PRAGMA table_info(bookings)");
$columns = [];
while ($col = $columns_check->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $col['name'];
}

// Build the SQL query based on existing columns
$sql = "SELECT b.*, s.title as service_title, s.price, s.duration 
        FROM bookings b 
        JOIN services s ON b.service_id = s.id 
        WHERE ";

// Check which user ID column exists
if (in_array('user_id', $columns)) {
    $sql .= "b.user_id = ?";
} elseif (in_array('student_id', $columns)) {
    $sql .= "b.student_id = ?";
} else {
    die("Error: No valid user ID column found in bookings table");
}

if ($status_filter !== 'all') {
    $sql .= " AND b.status = ?";
}
$sql .= " ORDER BY b.created_at DESC";

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Booking Status</h5>
                    <div class="list-group list-group-flush">
                        <a href="?status=all" class="list-group-item list-group-item-action <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Bookings</a>
                        <a href="?status=pending" class="list-group-item list-group-item-action <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                        <a href="?status=confirmed" class="list-group-item list-group-item-action <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                        <a href="?status=completed" class="list-group-item list-group-item-action <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed</a>
                        <a href="?status=cancelled" class="list-group-item list-group-item-action <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">My Bookings</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $db->getConnection()->prepare($sql);
                                
                                // Bind parameters for SQLite3
                                $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
                                if ($status_filter !== 'all') {
                                    $stmt->bindValue(2, $status_filter, SQLITE3_TEXT);
                                }
                                
                                $result = $stmt->execute();
                                $has_rows = false;

                                while ($booking = $result->fetchArray(SQLITE3_ASSOC)) {
                                    $has_rows = true;
                                    $status_class = [
                                        'pending' => 'bg-warning',
                                        'confirmed' => 'bg-success',
                                        'completed' => 'bg-info',
                                        'cancelled' => 'bg-danger'
                                    ][$booking['status']] ?? 'bg-secondary';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['booking_time']); ?></td>
                                        <td><span class="badge <?php echo $status_class; ?>"><?php echo ucfirst(htmlspecialchars($booking['status'])); ?></span></td>
                                        <td>$<?php echo number_format($booking['price'], 2); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-teal" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">View Details</button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                
                                if (!$has_rows) {
                                    echo '<tr><td colspan="6" class="text-center">No bookings found</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingDetailsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Booking details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>