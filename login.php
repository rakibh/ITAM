<?php
// File: login.php
// Purpose: User login page with validation and security features

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: pages/dashboard_admin.php');
    } else {
        header('Location: pages/dashboard_user.php');
    }
    exit();
}

preventBackButton();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 16px;
            color: #1B1C1D;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 15px;
        }
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
        }
        .company-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-logo h3 {
            color: #667eea;
            font-weight: 600;
            margin: 0;
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #dee2e6;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 500;
            transition: transform 0.2s;
        }
        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .position-relative {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="company-logo">
                <h3><?php echo SITE_NAME; ?></h3>
                <p class="text-muted mb-0">Sign in to continue</p>
            </div>

            <div id="alert-container"></div>

            <form id="loginForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

                <div class="mb-3">
                    <label for="login_id" class="form-label">Employee ID / Username</label>
                    <input type="text" class="form-control" id="login_id" name="login_id" 
                           placeholder="e.g., E-101 or rakib" autocomplete="username" required>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="position-relative">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="••••••••" autocomplete="current-password" required>
                        <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>

                <button type="submit" class="btn btn-login" id="loginBtn">
                    <span id="btnText">Sign In</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </span>
                </button>
            </form>
        </div>
    </div>

    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Password toggle
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $(this).toggleClass('bi-eye bi-eye-slash');
            });

            // Form validation
            function validateForm() {
                let isValid = true;
                
                // Clear previous errors
                $('.form-control').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                // Validate login ID
                const loginId = $('#login_id').val().trim();
                if (loginId === '') {
                    $('#login_id').addClass('is-invalid');
                    $('#login_id').siblings('.invalid-feedback').text('Login ID is required');
                    isValid = false;
                }

                // Validate password
                const password = $('#password').val();
                if (password === '') {
                    $('#password').addClass('is-invalid');
                    $('#password').siblings('.invalid-feedback').text('Password is required');
                    isValid = false;
                } else if (password.length < 6) {
                    $('#password').addClass('is-invalid');
                    $('#password').siblings('.invalid-feedback').text('Password must be at least 6 characters');
                    isValid = false;
                } else {
                    // Check password policy
                    const hasLetter = /[A-Za-z]/.test(password);
                    const hasNumber = /\d/.test(password);
                    const hasSpecial = /[!@#$%^&*]/.test(password);
                    
                    if (!hasLetter || !hasNumber || !hasSpecial) {
                        $('#password').addClass('is-invalid');
                        $('#password').siblings('.invalid-feedback').text('Password must include a letter, a number, and a special character (!@#$%^&*)');
                        isValid = false;
                    }
                }

                // Enable/disable button based on validation
                $('#loginBtn').prop('disabled', !isValid);
                return isValid;
            }

            // Real-time validation
            $('#login_id, #password').on('input', function() {
                validateForm();
            });

            // Prevent Enter key submission in inputs
            $('#login_id, #password').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    if (validateForm()) {
                        $('#loginForm').submit();
                    }
                }
            });

            // Form submission
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();

                if (!validateForm()) {
                    return false;
                }

                // Disable button and show loading
                $('#loginBtn').prop('disabled', true);
                $('#btnText').text('Signing in...');
                $('#btnSpinner').removeClass('d-none');

                $.ajax({
                    url: 'ajax/login_handler.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', response.message);
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 500);
                        } else {
                            showAlert('danger', response.message);
                            resetButton();
                        }
                    },
                    error: function(xhr) {
                        let message = 'Something went wrong. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        showAlert('danger', message);
                        resetButton();
                    }
                });
            });

            function showAlert(type, message) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert" aria-live="polite">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                $('#alert-container').html(alertHtml);
            }

            function resetButton() {
                $('#loginBtn').prop('disabled', false);
                $('#btnText').text('Sign In');
                $('#btnSpinner').addClass('d-none');
            }

            // Initial button state
            validateForm();
        });
    </script>
</body>
</html>