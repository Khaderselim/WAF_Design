<?php
// Initialize WAF - detects and blocks attacks
require_once 'waf_init.php';

include 'db_connection.php';

$message = "";
$toastClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $checkEmailStmt = $conn->prepare("SELECT email FROM userdata WHERE email = ?");
    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();
    $checkEmailStmt->store_result();

    if ($checkEmailStmt->num_rows > 0) {
        $message = "Email ID already exists";
        $toastClass = "#007bff"; // Primary color
    } else {
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO userdata (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);

        if ($stmt->execute()) {
            $message = "Account created successfully";
            $toastClass = "#28a745"; // Success color
        } else {
            $message = "Error: " . $stmt->error;
            $toastClass = "#dc3545"; // Danger color
        }

        $stmt->close();
    }

    $checkEmailStmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Login/Signup - Kaiadmin Bootstrap 5 Admin Dashboard</title>
    <meta
            content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
            name="viewport"
    />
    <link
            rel="icon"
            href="assets/img/kaiadmin/favicon.ico"
            type="image/x-icon"
    />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons",
                ],
                urls: ["assets/css/fonts.min.css"],
            },
            active: function () {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css" />

    <style>
        /* Hide floating labels but keep for accessibility */
        .form-floating-label label {
            display: none;
        }

        /* Enhanced form styling */
        .login-form .form-group {
            margin-bottom: 20px;
        }

        .login-form .form-control {
            background-color: transparent;
            border: none;
            border-bottom: 2px solid #e0e0e0;
            border-radius: 0;
            padding: 10px 0;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .login-form .form-control:focus {
            background-color: transparent;
            border-bottom: 2px solid #1572e8;
            box-shadow: none;
        }

        .login-form .form-control::placeholder {
            color: #999;
            font-size: 13px;
        }

        /* Password toggle icon styling */
        .toggle-password {
            position: absolute;
            right: 0;
            top: 12px;
            cursor: pointer;
            color: #999;
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: #1572e8;
        }

        /* Form group positioning for icon */
        .form-group {
            position: relative;
        }

        /* Button styling */
        .btn-login {
            width: 100%;
            padding: 12px 0;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Container styling */
        .container-signup {
            width: 380px !important;
        }

        /* Account link section */
        .login-account {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
        }

        .login-account .link {
            font-weight: 600;
            margin-left: 5px;
        }

        /* Checkbox styling */
        .custom-control-label {
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body class="login">
<div class="wrapper wrapper-login">
    <!-- Toast Message -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php 
            if ($toastClass === "#28a745") echo "success";
            elseif ($toastClass === "#dc3545") echo "danger";
            else echo "warning";
        ?> alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <strong>
                <?php 
                if ($toastClass === "#28a745") echo "✓ Success! ";
                elseif ($toastClass === "#dc3545") echo "✗ Error! ";
                else echo "⚠ Warning! ";
                ?>
            </strong>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Signup Container -->
    <div class="container container-login animated fadeIn">
        <h3 class="text-center">Sign Up</h3>
        <form class="login-form" id="signupForm" method="post">
            <div class="form-group form-floating-label">
                <input
                        id="fullname"
                        name="username"
                        type="text"
                        class="form-control input-border-bottom"
                        placeholder="Full Name"
                        required
                />
                <label for="fullname" class="placeholder">Full Name</label>
            </div>
            <div class="form-group form-floating-label">
                <input
                        id="email"
                        name="email"
                        type="email"
                        class="form-control input-border-bottom"
                        placeholder="Email"
                        required
                />
                <label for="email" class="placeholder">Email</label>
            </div>
            <div class="form-group form-floating-label">
                <input
                        id="passwordsignup"
                        name="password"
                        type="password"
                        class="form-control input-border-bottom"
                        placeholder="Password"
                        required
                />
                <label for="passwordsignup" class="placeholder">Password</label>
                <span toggle="#passwordsignup" class="fa fa-fw fa-eye field-icon toggle-password"></span>
            </div>
            <div class="form-group form-floating-label">
                <input
                        id="confirmpassword"
                        name="confirmpassword"
                        type="password"
                        class="form-control input-border-bottom"
                        placeholder="Confirm Password"
                        required
                />
                <label for="confirmpassword" class="placeholder">Confirm Password</label>
                <span toggle="#confirmpassword" class="fa fa-fw fa-eye field-icon toggle-password"></span>
            </div>

            <div class="form-action">
                <button type="submit" class="btn btn-primary btn-rounded btn-login">Sign Up</button>
            </div>
            <div class="login-account">
                <span class="msg">Already have an account?</span>
                <a href="login.php" class="link">Sign In</a>
            </div>
        </form>
    </div>
</div>
<script>
    // Toggle Password Visibility
    Array.from(document.getElementsByClassName('toggle-password')).forEach(function(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('toggle');
            var input = document.querySelector(targetId);

            if (input) {
                if (input.getAttribute('type') === 'password') {
                    input.setAttribute('type', 'text');
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    input.setAttribute('type', 'password');
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            }
        });
    });

    // Validate Password Match
    function validatePasswordMatch() {
        var password = document.getElementById('passwordsignup');
        var confirmPassword = document.getElementById('confirmpassword');

        if (password && confirmPassword) {
            if (password.value !== confirmPassword.value) {
                confirmPassword.style.borderBottomColor = '#f25961';
                return false;
            } else {
                confirmPassword.style.borderBottomColor = '#31ce36';
                return true;
            }
        }
        return true;
    }

    // Real-time password confirmation check
    var confirmPasswordField = document.getElementById('confirmpassword');
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            validatePasswordMatch();
        });
    }

    // Signup Form Submission with validation
    var signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            if (!validatePasswordMatch()) {
                e.preventDefault();
                alert('Passwords do not match!');
                document.getElementById('confirmpassword').focus();
                return false;
            }
            // Allow form to submit to PHP if passwords match
        });
    }

    // Auto-dismiss alerts after 5 seconds
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.classList.remove('show');
            setTimeout(function() {
                alert.remove();
            }, 150);
        }, 5000);
    });

    // Redirect on success
    <?php if ($toastClass === "#28a745"): ?>
            window.location.href = 'login.php';
    <?php endif; ?>
</script>
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>

<!-- jQuery Scrollbar -->
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

<!-- Kaiadmin JS -->
<script src="assets/js/kaiadmin.min.js"></script>

<!-- Kaiadmin DEMO methods, don't include it in your project! -->
<script src="assets/js/setting-demo.js"></script>
<script src="assets/js/demo.js"></script>
</body>
</html>



