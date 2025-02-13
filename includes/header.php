<?php
if (!isset($_SESSION)) {
    session_start();
}

// Error handling
function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    if (!isset($_SESSION['php_errors'])) {
        $_SESSION['php_errors'] = [];
    }
    
    $_SESSION['php_errors'][] = "$errstr in $errfile on line $errline";
    return true;
}

set_error_handler('handleError');

// Transfer PHP errors to variable and clear session
$php_errors = $_SESSION['php_errors'] ?? [];
unset($_SESSION['php_errors']);

// Existing notification handling
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    unset($_SESSION['notification']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SVMarketplace</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --teal-primary: #008080;
            --teal-dark: #006666;
            --teal-light: #e6ffff;
        }
        .bg-teal { background-color: var(--teal-primary); }
        .btn-teal {
            background-color: var(--teal-primary);
            color: white;
        }
        .btn-teal:hover {
            background-color: var(--teal-dark);
            color: white;
        }
        .text-teal { color: var(--teal-primary); }
        
        /* Add these new styles */
        .notification-badge {
            position: absolute;
            top: 0;
            right: -5px;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        .nav-icon {
            position: relative;
            padding: 0.5rem 1rem;
            color: rgba(255,255,255,0.85);
            cursor: pointer;
        }
        .nav-icon:hover {
            color: #fff;
        }
        
        /* Loading Spinner */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--teal-primary);
        }
    </style>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading-overlay">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
    // Initialize toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000",
        "extendedTimeOut": "1000",
        "preventDuplicates": true
    };

    // Global error handling
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        const message = error ? `${msg}\nLine: ${lineNo}` : msg;
        toastr.error(message, 'JavaScript Error');
        return false;
    };

    // Handle Promise rejections
    window.addEventListener('unhandledrejection', function(event) {
        toastr.error(event.reason, 'Promise Error');
    });

    // Handle AJAX errors
    $(document).ajaxError(function(event, jqXHR, settings, error) {
        const message = jqXHR.responseJSON?.message || error || 'An error occurred';
        toastr.error(message, 'Ajax Error');
    });

    // Add this after toastr initialization
    <?php if (isset($notification)): ?>
        toastr['<?php echo $notification['type']; ?>']('<?php echo addslashes($notification['message']); ?>');
    <?php endif; ?>

    <?php if (isset($php_errors)): ?>
        <?php foreach($php_errors as $error): ?>
            toastr.error('<?php echo addslashes($error); ?>', 'PHP Error');
        <?php endforeach; ?>
    <?php endif; ?>
    </script>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-teal">
        <div class="container">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a class="navbar-brand" href="/index.php">SVMarketplace</a>
            <?php else: ?>
                <a class="navbar-brand" href="/<?php echo $_SESSION['user_type']; ?>/dashboard.php">SVMarketplace</a>
            <?php endif; ?>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <ul class="navbar-nav">
                        <?php if ($_SESSION['user_type'] === 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/student/search.php">Find Services</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/student/bookings.php">My Bookings</a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] === 'vendor'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/vendor/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/vendor/services.php">My Services</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/vendor/bookings.php">Bookings</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item d-flex align-items-center me-2">
                            <a href="/<?php echo $_SESSION['user_type']; ?>/messages.php" class="nav-icon" data-bs-toggle="tooltip" title="Messages">
                                <i class="fas fa-envelope"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge <?php echo (isset($unseen_counts) && $unseen_counts['messages'] > 0) ? '' : 'd-none'; ?>">
                                    <?php echo isset($unseen_counts) ? $unseen_counts['messages'] : '0'; ?>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item d-flex align-items-center me-3">
                            <a href="/<?php echo $_SESSION['user_type']; ?>/notifications.php" class="nav-icon" data-bs-toggle="tooltip" title="Notifications">
                                <i class="fas fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge <?php echo (isset($unseen_counts) && $unseen_counts['notifications'] > 0) ? '' : 'd-none'; ?>">
                                    <?php echo isset($unseen_counts) ? $unseen_counts['notifications'] : '0'; ?>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo htmlspecialchars($_SESSION['email']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/<?php echo $_SESSION['user_type']; ?>/dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="/profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Toast container -->
    <?php if (isset($_SESSION['toast'])): ?>
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header <?php echo $_SESSION['toast']['type'] === 'success' ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                <strong class="me-auto"><?php echo $_SESSION['toast']['type'] === 'success' ? 'Success' : 'Error'; ?></strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php echo htmlspecialchars($_SESSION['toast']['message']); ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastElList = [].slice.call(document.querySelectorAll('.toast'));
        var toastList = toastElList.map(function(toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 5000 });
        });
        toastList.forEach(toast => toast.show());
    });
    </script>
    <?php unset($_SESSION['toast']); endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Existing loading overlay code
        const loadingOverlay = document.querySelector('.loading-overlay');
        
        // Update link click handler to exclude tooltips
        document.querySelectorAll('a:not([href^="#"]):not([data-bs-toggle])').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href && !href.includes('javascript:') && !this.closest('[data-bs-toggle="tooltip"]')) {
                    e.preventDefault();
                    loadingOverlay.style.display = 'flex';
                    setTimeout(() => {
                        window.location.href = href;
                    }, 500);
                }
            });
        });

        // Existing form submission code
        document.querySelectorAll('form:not([data-no-loading])').forEach(form => {
            form.addEventListener('submit', function() {
                loadingOverlay.style.display = 'flex';
            });
        });
    });

    <?php
    if (file_exists(__DIR__ . '/notification.php')) {
        require_once __DIR__ . '/notification.php';
        $notification = Notification::display();
        if ($notification !== 'null') {
            echo "const notification = " . $notification . ";";
            echo "toastr[notification.type](notification.message);";
        }
    }
    ?>
    </script>
</body>