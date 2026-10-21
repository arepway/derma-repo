<?php
require_once 'auth_check.php';
preventLoggedInAccess();

// Database configuration - Update these settings to match your setup
$host = 'localhost';           // or '127.0.0.1'
$port = '3307';               // MySQL port (usually 3306)
$dbname = 'dermatology_clinic';
$username = 'root';           // your MySQL username
$password = '';               // your MySQL password (empty for XAMPP/WAMP default)

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "<br>Please check your database settings.");
}

// Check if any staff accounts exist
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medical_staff");
$stmt->execute();
$staff_count = $stmt->fetch()['count'];
$no_staff_exists = ($staff_count == 0);

// Show setup notification if requested
$setup_info = isset($_GET['setup_disabled']) ? "Setup is disabled. Staff accounts already exist in the system." : '';

// Handle login
if ($_POST['action'] ?? '' == 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check for hardcoded admin credentials
    if ($username === 'admin' && $password === 'admin123') {
        setLoginSession(0, 'staff', ['user_name' => 'Administrator']);
        header("Location: staff_dashboard.php");
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM medical_staff WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        setLoginSession($user['staff_id'], 'staff', ['user_name' => $user['full_name']]);
        header("Location: staff_dashboard.php");
        exit;
    }

    $error = "Invalid login credentials";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.75, maximum-scale=1.0, user-scalable=yes">
    <title>Staff Login - True Beauty Concept</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #2c5f6f;
            --secondary-color: #8ab4a7;
            --accent-color: #d4a574;
            --text-dark: #2d3436;
            --text-light: #636e72;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            height: 100vh;
            display: flex;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background - Starry Night */
        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1a2a3a 0%, #2c3e50 50%, #1a2a3a 100%);
            z-index: 0;
        }

        /* Stars Layer 1 - Small twinkling stars */
        .animated-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 25% 45%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 40% 15%, rgba(255, 255, 255, 0.9) 1px, transparent 1px),
                radial-gradient(circle at 55% 70%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 70% 35%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 85% 60%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 15% 75%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 30% 90%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 45% 5%, rgba(255, 255, 255, 0.9) 1px, transparent 1px),
                radial-gradient(circle at 60% 25%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 75% 80%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 90% 10%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 5% 50%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 35% 65%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 50% 40%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 65% 85%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 80% 50%, rgba(255, 255, 255, 0.9) 1px, transparent 1px),
                radial-gradient(circle at 95% 75%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 12% 92%, rgba(255, 255, 255, 0.8) 1px, transparent 1px);
            animation: twinkle1 3s ease-in-out infinite;
        }

        /* Stars Layer 2 - Larger stars with different timing */
        .animated-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 8% 35%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 22% 68%, rgba(255, 255, 255, 0.7) 2px, transparent 2px),
                radial-gradient(circle at 38% 22%, rgba(255, 255, 255, 0.8) 2px, transparent 2px),
                radial-gradient(circle at 52% 88%, rgba(255, 255, 255, 0.6) 2px, transparent 2px),
                radial-gradient(circle at 68% 42%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 82% 72%, rgba(255, 255, 255, 0.7) 2px, transparent 2px),
                radial-gradient(circle at 18% 12%, rgba(255, 255, 255, 0.8) 2px, transparent 2px),
                radial-gradient(circle at 48% 55%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 72% 18%, rgba(255, 255, 255, 0.6) 2px, transparent 2px),
                radial-gradient(circle at 92% 38%, rgba(255, 255, 255, 0.8) 2px, transparent 2px);
            animation: twinkle2 4s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes twinkle1 {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }

        @keyframes twinkle2 {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Main Container */
        .container {
            display: flex;
            width: 100%;
            height: 100vh;
            position: relative;
            z-index: 2;
        }

        /* Left Side - Branding */
        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            color: white;
            position: relative;
        }

        .brand-content {
            max-width: 500px;
            animation: fadeInLeft 1s ease;
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .logo-large {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .welcome-text {
            font-size: 1.2rem;
            line-height: 1.8;
            margin-bottom: 40px;
            color: rgba(255, 255, 255, 0.95);
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(10px);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Right Side - Login Form */
        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
            position: relative;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInRight 1s ease;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin: 0 auto 15px;
            box-shadow: 0 10px 30px rgba(44, 95, 111, 0.3);
        }

        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 4px solid #34c759;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Form Styles */
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            position: relative;
        }

        .form-label {
            display: block;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 18px;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px 16px 55px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(44, 95, 111, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 5px;
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(44, 95, 111, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(44, 95, 111, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .back-home {
            text-align: center;
            margin-top: 25px;
        }

        .back-home a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-home a:hover {
            color: var(--accent-color);
            gap: 12px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
                overflow-y: auto;
            }

            .left-panel {
                padding: 30px 25px;
                min-height: auto;
            }

            .logo-text {
                font-size: 1.8rem;
            }

            .welcome-text {
                font-size: 0.95rem;
                margin-bottom: 20px;
            }

            .features {
                display: none;
            }

            .right-panel {
                padding: 30px 25px;
            }

            .login-card {
                padding: 30px 25px;
            }

            .login-icon {
                width: 60px;
                height: 60px;
                font-size: 28px;
                margin-bottom: 12px;
            }

            .login-title {
                font-size: 1.6rem;
            }

            form {
                gap: 18px;
            }
        }

        @media (max-width: 480px) {
            body {
                height: auto;
                min-height: 100vh;
            }

            .left-panel,
            .right-panel {
                padding: 20px 15px;
            }

            .login-card {
                padding: 25px 20px;
                border-radius: 20px;
            }

            .logo-text {
                font-size: 1.4rem;
            }

            .login-title {
                font-size: 1.4rem;
            }

            .login-icon {
                width: 55px;
                height: 55px;
                font-size: 24px;
            }

            .form-input {
                padding: 14px 18px 14px 50px;
                font-size: 15px;
            }

            .input-icon {
                font-size: 16px;
                left: 16px;
            }

            .btn-login {
                padding: 16px;
                font-size: 15px;
            }

            form {
                gap: 16px;
            }

            .back-home {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background - Starry Night -->
    <div class="animated-background"></div>

    <!-- Main Container -->
    <div class="container">
        <!-- Left Panel - Branding -->
        <div class="left-panel">
            <div class="brand-content">
                <div class="logo-large">
                    <div class="logo-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="logo-text">
                        True Beauty<br>Concept
                    </div>
                </div>

                <p class="welcome-text">
                    Welcome to the staff portal. Manage appointments, patient records, and clinic operations efficiently.
                </p>

                <div class="features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span>Manage Appointments</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span>Patient Records</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-notes-medical"></i>
                        </div>
                        <span>Medical Documentation</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="right-panel">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h1 class="login-title">Staff Portal</h1>
                    <p class="login-subtitle">Sign in to access your dashboard</p>
                </div>

                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Account Successfully Deleted</strong><br>
                            Your account and all data have been permanently removed.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['success_msg']) ?></span>
                    </div>
                    <?php unset($_SESSION['success_msg']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['error_msg']) ?></span>
                    </div>
                    <?php unset($_SESSION['error_msg']); ?>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($setup_info): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i>
                        <span><?= htmlspecialchars($setup_info) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($no_staff_exists): ?>
                    <div class="alert" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; border-left: 4px solid #3b82f6;">
                        <i class="fas fa-tools"></i>
                        <div>
                            <strong>Initial Setup Required</strong><br>
                            <span style="font-size: 0.9rem;">No staff accounts exist. Please complete the initial setup to create the first administrator account.</span>
                            <div style="margin-top: 10px;">
                                <a href="setup_staff.php" style="display: inline-block; background: #3b82f6; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">
                                    <i class="fas fa-arrow-right"></i> Go to Setup
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="user_type" value="staff">

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i>
                            Username
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-input"
                                placeholder="Enter your username"
                                required
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Sign In</span>
                    </button>
                </form>

                <div class="back-home">
                    <a href="index.php">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Home</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>