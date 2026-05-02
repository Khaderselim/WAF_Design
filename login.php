<?php
// Initialize WAF - detects and blocks attacks
require_once 'waf_init.php';

include 'db_connection.php';
$message = "";
$toastClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];


    // Prepare and execute
    $stmt = $conn->prepare("SELECT password_hash, username,role_id FROM user WHERE email = ? and is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_password, $username, $role_id);
        $stmt->fetch();

        if (md5($password) === $db_password) {
            $message = "Login successful";
            $toastClass = "bg-success";
            // Start the session and redirect to the dashboard or home page
            session_start();
            $_SESSION['email'] = $email;
            $_SESSION['username']=$username;
            $_SESSION['role_id']=$role_id;
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Incorrect password";
            $toastClass = "bg-danger";
        }
    } else {
        $message = "Email not found";
        $toastClass = "bg-warning";
    }

    $stmt->close();
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
        .container-login, .container-signup {
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
            <div class="alert alert-<?php echo ($toastClass === 'bg-success') ? 'success' : (($toastClass === 'bg-danger') ? 'danger' : 'warning'); ?> alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Login Container -->
        <div class="container container-login animated fadeIn">
            <h3 class="text-center">Sign In</h3>
            <form class="login-form" method="POST" action="login.php">
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
                        id="password"
                        name="password"
                        type="password"
                        class="form-control input-border-bottom"
                        placeholder="Password"
                        required
                    />
                    <label for="password" class="placeholder">Password</label>
                    <span toggle="#password" class="fa fa-fw fa-eye field-icon toggle-password"></span>
                </div>
                <div class="form-action mb-3">
                    <button type="submit" class="btn btn-primary btn-rounded btn-login">Sign In</button>
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
