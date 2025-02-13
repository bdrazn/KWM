<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/notifications_handler.php';

$db = new Database();
$auth = new Auth($db);
$notifications_handler = new NotificationsHandler($db);

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// At the top, after session check
// Handle show all parameter
if (isset($_GET['show_all']) && $_GET['show_all'] === 'true') {
    $search = '';
    $category = '';
    $min_price = null;
    $max_price = null;
}

$unseen_counts = $notifications_handler->getUnseenCounts($_SESSION['user_id']);

// Handle search and filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
// Update these lines
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : PHP_FLOAT_MAX;

// Update the SQL query part
if ($min_price > 0) {
    $sql .= " AND s.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}
if ($max_price < PHP_FLOAT_MAX) {
    $sql .= " AND s.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}



// Remove these lines
// $params = array();
// $types = "";

// Build the SQL query
$sql = "SELECT s.*, u.email as vendor_email 
        FROM services s 
        JOIN users u ON s.vendor_id = u.id 
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (s.title LIKE ? OR s.description LIKE ?)";
}

if (!empty($category)) {
    $sql .= " AND s.category = ?";
}

if ($min_price !== null && $min_price > 0) {
    $sql .= " AND s.price >= ?";
}

if ($max_price !== null && $max_price < PHP_FLOAT_MAX) {
    $sql .= " AND s.price <= ?";
}

$sql .= " ORDER BY s.created_at DESC";

// Remove all the code related to $params and $types arrays
if (!empty($search)) {
    $sql .= " AND (s.title LIKE ? OR s.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category)) {
    $sql .= " AND s.category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " AND s.price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$types .= "dd";

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Search Filters -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Filters</h5>
                    <form id="filterForm" method="GET" action="search.php">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <option value="tutoring" <?php echo $category === 'tutoring' ? 'selected' : ''; ?>>Tutoring</option>
                                <option value="design" <?php echo $category === 'design' ? 'selected' : ''; ?>>Design</option>
                                <option value="programming" <?php echo $category === 'programming' ? 'selected' : ''; ?>>Programming</option>
                                <option value="writing" <?php echo $category === 'writing' ? 'selected' : ''; ?>>Writing</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price Range</label>
                            <div class="d-flex gap-2">
                                <input type="number" class="form-control" name="min_price" placeholder="Min" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                                <input type="number" class="form-control" name="max_price" placeholder="Max" value="<?php echo $max_price < PHP_FLOAT_MAX ? $max_price : ''; ?>">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-teal flex-grow-1">Apply Filters</button>
                            <a href="search.php?show_all=true" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Available Services</h5>
                        <form class="d-flex" method="GET" action="search.php">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                            <input type="hidden" name="min_price" value="<?php echo $min_price; ?>">
                            <input type="hidden" name="max_price" value="<?php echo $max_price; ?>">
                            <input type="search" class="form-control me-2" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search services...">
                            <button type="submit" class="btn btn-teal">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Service Cards -->
                    <div class="row g-4" id="servicesContainer">
                        <?php
                        $stmt = $db->getConnection()->prepare($sql);
                        
                        // Bind parameters for SQLite3
                        if (!empty($search)) {
                            $stmt->bindValue(1, "%$search%", SQLITE3_TEXT);
                            $stmt->bindValue(2, "%$search%", SQLITE3_TEXT);
                            $param_index = 3;
                        } else {
                            $param_index = 1;
                        }

                        if (!empty($category)) {
                            $stmt->bindValue($param_index, $category, SQLITE3_TEXT);
                            $param_index++;
                        }

                        // Update the parameter binding section
                        if ($min_price > 0) {
                            $stmt->bindValue($param_index++, $min_price, SQLITE3_FLOAT);
                        }
                        if ($max_price < PHP_FLOAT_MAX) {
                            $stmt->bindValue($param_index++, $max_price, SQLITE3_FLOAT);
                        }

                        $result = $stmt->execute();

                        $has_rows = false;
                        while ($service = $result->fetchArray(SQLITE3_ASSOC)) {
                            $has_rows = true;
                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($service['title']); ?></h5>
                                        <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($service['category']); ?></span>
                                        <p class="card-text text-muted mb-3"><?php echo htmlspecialchars($service['description']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="h5 mb-0">$<?php echo number_format($service['price'], 2); ?></span>
                                                <small class="text-muted">/ <?php echo $service['duration']; ?> min</small>
                                            </div>
                                            <a href="book_service.php?id=<?php echo $service['id']; ?>" class="btn btn-teal">
                                                Book Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                        
                        if (!$has_rows) {
                            echo '<div class="col-12"><p class="text-center text-muted py-5">No services found matching your criteria.</p></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>