<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$db = new Database();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Left Sidebar -->
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Quick Links</h5>
                    <div class="list-group list-group-flush">
                        <a href="search.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-search"></i> Find Services
                        </a>
                        <?php if ($auth->isLoggedIn() && $_SESSION['user_type'] === 'vendor'): ?>
                        <a href="vendor/services.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus"></i> Add New Service
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Available Services</h5>
                    <!-- Add service listing here -->
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Upcoming Bookings</h5>
                    <!-- Add upcoming bookings here -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>