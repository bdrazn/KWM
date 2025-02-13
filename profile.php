<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$db = new Database();
$auth = new Auth($db);

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user profile data
$user = null;
$stmt = $db->getConnection()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();

if ($result) {
    $user = $result->fetchArray(SQLITE3_ASSOC);
}

if (!$user) {
    header('Location: logout.php');
    exit();
}

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="https://via.placeholder.com/150" class="rounded-circle" alt="Profile Picture">
                    </div>
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($user['email']); ?></h5>
                    <p class="text-muted"><?php echo ucfirst($user['user_type']); ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Account Settings</h5>
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-2"></i> Change Password
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit me-2"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Profile Information</h5>
                    <form id="profileForm" class="mt-4">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" value="">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <button type="submit" class="btn btn-teal">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <input type="password" class="form-control" name="current_password" placeholder="Current Password" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" name="new_password" placeholder="New Password" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm New Password" required>
                    </div>
                    <button type="submit" class="btn btn-teal w-100">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>