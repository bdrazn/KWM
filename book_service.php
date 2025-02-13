<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$db = new Database();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: search.php');
    exit();
}

$service_id = (int)$_GET['id'];

// Verify bookings table structure
$table_check = $db->getConnection()->query("PRAGMA table_info(bookings)");
$columns = [];
while ($col = $table_check->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $col['name'];
}

// Required columns
$required_columns = ['student_id', 'service_id', 'booking_date', 'booking_time', 'status', 'created_at'];
$missing_columns = array_diff($required_columns, $columns);

if (!empty($missing_columns)) {
    error_log("Missing columns in bookings table: " . implode(', ', $missing_columns));
    die("Database structure error. Please contact administrator.");
}

// Fetch service details
$stmt = $db->getConnection()->prepare('
    SELECT s.*
    FROM services s 
    WHERE s.id = ?
');
$stmt->bindValue(1, $service_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$service = $result->fetchArray(SQLITE3_ASSOC);

if (!$service) {
    header('Location: search.php');
    exit();
}

include 'includes/header.php';
?>

<div class="container py-4" style="max-width: 800px;">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="card-title mb-4">Book Service</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo htmlspecialchars($service['title']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($service['description']); ?></p>
                    <div class="mb-3">
                        <strong>Provider ID:</strong> <?php echo htmlspecialchars($service['vendor_id']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Duration:</strong> <?php echo $service['duration']; ?> minutes
                    </div>
                    <div class="mb-3">
                        <strong>Price:</strong> $<?php echo number_format($service['price'], 2); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <form method="POST" action="process_booking.php" id="bookingForm">
                        <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
                        <div class="mb-3">
                            <label class="form-label" for="booking-date">
                                <i class="fas fa-calendar me-2"></i>Select Date
                            </label>
                            <input type="date" 
                                   id="booking-date" 
                                   name="date" 
                                   class="form-control" 
                                   required 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   aria-label="Booking date">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="booking-time">
                                <i class="fas fa-clock me-2"></i>Select Time
                            </label>
                            <select id="booking-time" 
                                    name="time" 
                                    class="form-select" 
                                    required
                                    aria-label="Booking time">
                                <option value="">Choose a time...</option>
                                <?php
                                for ($hour = 9; $hour <= 17; $hour++) {
                                    $time = sprintf("%02d:00", $hour);
                                    echo "<option value=\"$time\">$time</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-teal w-100" id="submitBtn">
                            <span class="button-text">Confirm Booking</span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                        </button>
                    </form>
                </div>

                // Remove the inline script and add this before closing body tag
                ?>
                <!-- Add this before closing body tag -->
                <script>
                // Add error event listener for global errors
                window.onerror = function(msg, url, lineNo, columnNo, error) {
                    console.error('Error: ' + msg);
                    console.error('URL: ' + url);
                    console.error('Line: ' + lineNo);
                    return false;
                };
                
                document.getElementById('bookingForm').addEventListener('submit', function(e) {
                    console.log('Form submission started'); // Debug log
                    
                    const submitBtn = document.getElementById('submitBtn');
                    const buttonText = submitBtn.querySelector('.button-text');
                    const spinner = submitBtn.querySelector('.spinner-border');
                    
                    // Log form data
                    const formData = new FormData(this);
                    formData.forEach((value, key) => {
                        console.log(`${key}: ${value}`);
                    });
                    
                    // Disable button and show spinner
                    submitBtn.disabled = true;
                    buttonText.textContent = 'Processing...';
                    spinner.classList.remove('d-none');
                    
                    console.log('Form submission in progress'); // Debug log
                });
                
                // Display error message if it exists in URL
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('error')) {
                    console.log('Error parameter found:', urlParams.get('error')); // Debug log
                    const errorType = urlParams.get('error');
                    let errorMessage = '';
                    
                    switch(errorType) {
                        case 'missing_fields':
                            errorMessage = 'Please fill in all required fields.';
                            break;
                        case 'invalid_date':
                            errorMessage = 'Please select a future date and time.';
                            break;
                        case 'time_taken':
                            errorMessage = 'This time slot is already booked. Please select another time.';
                            break;
                        case 'system':
                            errorMessage = 'A system error occurred. Please try again later.';
                            break;
                        case 'service_unavailable':
                            errorMessage = 'This service is currently unavailable.';
                            break;
                        case 'booking_limit':
                            errorMessage = 'You have reached the maximum number of bookings for this service.';
                            break;
                        default:
                            errorMessage = 'An error occurred. Please contact support if the problem persists.';
                    }
                    
                    console.log('Error message:', errorMessage); // Debug log
                    if (typeof toastr !== 'undefined') {
                        console.log('Using toastr for notification'); // Debug log
                        toastr.error(errorMessage);
                    } else {
                        console.log('Falling back to alert'); // Debug log
                        alert(errorMessage);
                    }
                }
                </script>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>