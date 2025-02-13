<footer class="bg-light mt-auto py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="text-teal mb-3">SVMarketplace</h5>
                <p class="text-muted">Connecting students with talented service providers on campus. Find the help you need or offer your skills to others.</p>
                <div class="social-links">
                    <a href="#" class="text-muted me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-muted me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-muted me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-muted"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="col-md-2">
                <h6 class="text-dark mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'vendor'): ?>
                        <li class="mb-2"><a href="/vendor/dashboard.php" class="text-muted text-decoration-none">Dashboard</a></li>
                        <li class="mb-2"><a href="/vendor/services.php" class="text-muted text-decoration-none">My Services</a></li>
                    <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student'): ?>
                        <li class="mb-2"><a href="/student/dashboard.php" class="text-muted text-decoration-none">Dashboard</a></li>
                        <li class="mb-2"><a href="/search.php" class="text-muted text-decoration-none">Find Services</a></li>
                    <?php endif; ?>
                    <li class="mb-2"><a href="/profile.php" class="text-muted text-decoration-none">Profile</a></li>
                    <li class="mb-2"><a href="/bookings.php" class="text-muted text-decoration-none">Bookings</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="text-dark mb-3">Popular Services</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/student/search.php?category=tutoring" class="text-muted text-decoration-none">Tutoring</a></li>
                    <li class="mb-2"><a href="/student/search.php?category=design" class="text-muted text-decoration-none">Design Services</a></li>
                    <li class="mb-2"><a href="/student/search.php?category=programming" class="text-muted text-decoration-none">Programming</a></li>
                    <li class="mb-2"><a href="/student/search.php?category=writing" class="text-muted text-decoration-none">Writing & Editing</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="text-dark mb-3">Contact Us</h6>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2"><i class="fas fa-envelope me-2"></i> support@svmarketplace.com</li>
                    <li class="mb-2"><i class="fas fa-phone me-2"></i> (555) 123-4567</li>
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> University Campus, Building A</li>
                </ul>
            </div>
        </div>
        <hr class="my-4">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> SVMarketplace. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item">
                        <a href="/legal/terms.php" class="text-muted text-decoration-none">Terms of Use</a>
                    </li>
                    <li class="list-inline-item mx-3">
                        <a href="/legal/privacy.php" class="text-muted text-decoration-none">Privacy Policy</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="/legal/cookies.php" class="text-muted text-decoration-none">Cookie Policy</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>