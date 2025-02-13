<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$db = new Database();
$auth = new Auth($db);

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Dashboard Menu</h5>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home me-2"></i> Overview
                        </a>
                        <a href="search.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : ''; ?>">
                            <i class="fas fa-search me-2"></i> Find Services
                        </a>
                        <a href="bookings.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar me-2"></i> My Bookings
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?></h4>
                    <p class="text-muted">Find and book services from our talented vendors</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Active Bookings</h5>
                            <h2 class="mb-0">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Completed Bookings</h5>
                            <h2 class="mb-0">0</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Services -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Featured Services</h5>
                    <div class="row">
                        <div class="col-12">
                            <p class="text-center text-muted my-4">No featured services available yet</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>