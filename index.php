<?php
// Start output buffering and disable error display
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config/database.php';
require_once 'includes/auth.php';

$db = new Database();
$auth = new Auth($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any previous output
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        if (isset($_POST['login'])) {
            $result = $auth->login($_POST['email'], $_POST['password']);
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'redirect' => $result['user_type'] === 'vendor' ? 'home.php' : 'home.php'
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
            if (!is_array($result)) {
                echo json_encode(['success' => false, 'message' => 'Invalid registration response']);
                exit;
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
}

// For non-POST requests, continue with HTML output
?>

<!DOCTYPE html>
<html>
<head>
    <title>SVMarketplace - Connect with Student Vendors</title>
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
        .hero-section {
            background: linear-gradient(135deg, var(--teal-primary), var(--teal-dark));
            color: white;
            padding: 100px 0;
        }
    </style>
</head>
<body>
    <!-- Add jQuery and toastr before other scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-teal">
        <div class="container">
            <a class="navbar-brand" href="#">SVMarketplace</a>
            <div class="ms-auto">
                <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Connect with Student Vendors</h1>
            <p class="lead mb-4">Find and book services from talented students in your campus</p>
            <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                Get Started
            </button>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose SVMarketplace?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-user-graduate fa-3x text-teal mb-3"></i>
                            <h4>Student Vendors</h4>
                            <p>Connect with talented students offering various services</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-check fa-3x text-teal mb-3"></i>
                            <h4>Easy Booking</h4>
                            <p>Schedule services with just a few clicks</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt fa-3x text-teal mb-3"></i>
                            <h4>Secure Platform</h4>
                            <p>Safe and secure transactions within campus</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Login to SVMarketplace</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="loginForm">
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="student@example.com" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-teal w-100">Login</button>
                        <div class="alert alert-danger mt-3 d-none" id="loginError"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create an Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="registerForm">
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="student@example.com" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Choose a strong password" required>
                        </div>
                        <div class="mb-3">
                            <select name="user_type" class="form-select" required>
                                <option value="" disabled selected>I want to...</option>
                                <option value="student">Find Services</option>
                                <option value="vendor">Offer Services</option>
                            </select>
                        </div>
                        <button type="submit" name="register" class="btn btn-teal w-100">Create Account</button>
                        <div class="alert mt-3 d-none" id="registerMessage"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/toast.js"></script>
    <!-- Update the script section -->
    <script>
    // Configure toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Login form handling
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const errorDiv = document.getElementById('loginError');
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(this);
                formData.append('login', '1');
                
                const response = await fetch('includes/auth_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    toastr.success('Login successful!');
                    window.location.href = result.redirect;
                } else {
                    toastr.error(result.message || 'Login failed');
                    errorDiv.textContent = result.message;
                    errorDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Login error:', error);
                toastr.error('An unexpected error occurred');
                errorDiv.textContent = 'An unexpected error occurred. Please try again.';
                errorDiv.classList.remove('d-none');
            } finally {
                submitBtn.disabled = false;
            }
        });

        // Registration form handling
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const messageDiv = document.getElementById('registerMessage');
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(this);
                formData.append('register', '1');
                
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    toastr.success('Registration successful!');
                    messageDiv.classList.remove('alert-danger');
                    messageDiv.classList.add('alert-success');
                    messageDiv.textContent = 'Registration successful! Logging you in...';
                    messageDiv.classList.remove('d-none');
                    
                    // Automatically login after successful registration
                    const loginData = new FormData();
                    loginData.append('login', '1');
                    loginData.append('email', formData.get('email'));
                    loginData.append('password', formData.get('password'));
                    
                    const loginResponse = await fetch('index.php', {
                        method: 'POST',
                        body: loginData
                    });
                    
                    const loginResult = await loginResponse.json();
                    
                    if (loginResult.success) {
                        window.location.href = loginResult.redirect;
                    }
                } else {
                    toastr.error(result.message || 'Registration failed');
                    messageDiv.classList.remove('alert-success');
                    messageDiv.classList.add('alert-danger');
                    messageDiv.textContent = result.message;
                    messageDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Registration error:', error);
                toastr.error('An unexpected error occurred');
                messageDiv.classList.remove('alert-success');
                messageDiv.classList.add('alert-danger');
                messageDiv.textContent = 'An unexpected error occurred. Please try again.';
                messageDiv.classList.remove('d-none');
            } finally {
                submitBtn.disabled = false;
            }
        });
    });
    </script>
</body>
</html>