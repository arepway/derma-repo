<?php
session_start();

// Database configuration
$host = 'localhost';
$port = '3307';
$dbname = 'dermatology_clinic';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "<br>Please check your database settings.");
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $dob = $_POST['date_of_birth'] ?? '';
    $sex = $_POST['sex'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO patient (full_name, username, password, email, phone, date_of_birth, sex) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $username, $password, $email, $phone, $dob, $sex]);
        $success = "Registration successful! Please <a href='index_patient.php'>login</a>.";
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Username already exists. Please choose a different username.";
        } else {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.75, maximum-scale=1.0, user-scalable=yes">
    <title>Patient Registration - True Beauty Concept</title>
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
            display: flex;
            overflow-x: hidden;
            position: relative;
            padding: 15px;
            align-items: center;
            justify-content: center;
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
        .registration-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 600px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .registration-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .registration-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin: 0 auto 15px;
            box-shadow: 0 10px 30px rgba(138, 180, 167, 0.3);
        }

        .registration-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .registration-subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .progress-line {
            position: absolute;
            top: 20px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .progress-line-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
            width: 0%;
            transition: width 0.5s ease;
        }

        .step {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--text-light);
            transition: all 0.3s ease;
        }

        .step.active .step-circle {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            border-color: var(--accent-color);
            color: white;
            box-shadow: 0 5px 15px rgba(138, 180, 167, 0.4);
        }

        .step.completed .step-circle {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
            color: white;
        }

        .step-label {
            font-size: 11px;
            color: var(--text-light);
            font-weight: 500;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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

        .required {
            color: #ef4444;
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

        .form-input,
        .form-select {
            width: 100%;
            padding: 16px 20px 16px 55px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(138, 180, 167, 0.1);
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
            color: var(--secondary-color);
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .strength-weak { width: 33%; background: #ef4444; }
        .strength-medium { width: 66%; background: #f59e0b; }
        .strength-strong { width: 100%; background: #34c759; }

        .btn-register {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(138, 180, 167, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(138, 180, 167, 0.4);
        }

        .btn-register:active {
            transform: translateY(-1px);
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 1px;
            background: #e0e0e0;
        }

        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 15px;
            color: var(--text-light);
            font-size: 14px;
            position: relative;
            z-index: 1;
        }

        .back-login {
            text-align: center;
        }

        .back-login a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-login a:hover {
            color: var(--accent-color);
            gap: 12px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }

            .registration-container {
                padding: 30px 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .registration-title {
                font-size: 1.6rem;
            }

            .registration-icon {
                width: 60px;
                height: 60px;
                font-size: 28px;
                margin-bottom: 12px;
            }

            .progress-steps {
                margin-bottom: 25px;
            }

            .step-label {
                font-size: 10px;
            }

            form {
                gap: 18px;
            }

            .form-row {
                gap: 16px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .registration-container {
                padding: 25px 20px;
                border-radius: 20px;
            }

            .registration-title {
                font-size: 1.4rem;
            }

            .registration-icon {
                width: 55px;
                height: 55px;
                font-size: 24px;
            }

            .registration-header {
                margin-bottom: 25px;
            }

            .progress-steps {
                margin-bottom: 20px;
            }

            .step-circle {
                width: 35px;
                height: 35px;
                font-size: 13px;
            }

            .form-input,
            .form-select {
                padding: 14px 18px 14px 50px;
                font-size: 15px;
            }

            .input-icon {
                font-size: 16px;
                left: 16px;
            }

            .btn-register {
                padding: 16px;
                font-size: 15px;
            }

            form {
                gap: 16px;
            }

            .form-row {
                gap: 14px;
            }

            .alert {
                padding: 12px 16px;
                font-size: 13px;
                margin-bottom: 20px;
            }

            .divider {
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background - Starry Night -->
    <div class="animated-background"></div>

    <!-- Registration Container -->
    <div class="registration-container">
        <div class="registration-header">
            <div class="registration-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="registration-title">Create Account</h1>
            <p class="registration-subtitle">Join True Beauty Concept family today</p>
        </div>

        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="progress-line">
                <div class="progress-line-fill" id="progressFill"></div>
            </div>
            <div class="step active" id="step1">
                <div class="step-circle">1</div>
                <div class="step-label">Personal</div>
            </div>
            <div class="step" id="step2">
                <div class="step-circle">2</div>
                <div class="step-label">Account</div>
            </div>
            <div class="step" id="step3">
                <div class="step-circle">3</div>
                <div class="step-label">Details</div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="registrationForm">
            <div class="form-group full-width">
                <label class="form-label">
                    <i class="fas fa-user"></i>
                    Full Name
                    <span class="required">*</span>
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input
                        type="text"
                        id="reg-fullname"
                        name="full_name"
                        class="form-input"
                        placeholder="Enter your full name"
                        required
                    >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user-circle"></i>
                        Username
                        <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user-circle input-icon"></i>
                        <input
                            type="text"
                            id="reg-username"
                            name="username"
                            class="form-input"
                            placeholder="Choose a username"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email
                        <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input
                            type="email"
                            id="reg-email"
                            name="email"
                            class="form-input"
                            placeholder="your@email.com"
                            required
                        >
                    </div>
                </div>
            </div>

            <div class="form-group full-width">
                <label class="form-label">
                    <i class="fas fa-lock"></i>
                    Password
                    <span class="required">*</span>
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input
                        type="password"
                        id="reg-password"
                        name="password"
                        class="form-input"
                        placeholder="Create a strong password"
                        required
                        oninput="checkPasswordStrength()"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('reg-password', 'toggleIcon1')">
                        <i class="fas fa-eye" id="toggleIcon1"></i>
                    </button>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-phone"></i>
                        Phone Number
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input
                            type="text"
                            id="reg-phone"
                            name="phone"
                            class="form-input"
                            placeholder="+1 (555) 000-0000"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i>
                        Date of Birth
                        <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-calendar input-icon"></i>
                        <input
                            type="date"
                            id="reg-dob"
                            name="date_of_birth"
                            class="form-input"
                            required
                        >
                    </div>
                </div>
            </div>

            <div class="form-group full-width">
                <label class="form-label">
                    <i class="fas fa-venus-mars"></i>
                    Sex
                    <span class="required">*</span>
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-venus-mars input-icon"></i>
                    <select id="reg-sex" name="sex" class="form-select" required>
                        <option value="">Select your sex</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-register">
                <i class="fas fa-check"></i>
                <span>Create Account</span>
            </button>
        </form>

        <div class="divider">
            <span>Already have an account?</span>
        </div>

        <div class="back-login">
            <a href="index_patient.php">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Login</span>
            </a>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

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

        function checkPasswordStrength() {
            const password = document.getElementById('reg-password').value;
            const strengthBar = document.getElementById('strengthBar');

            // Remove all strength classes
            strengthBar.className = 'password-strength-bar';

            if (password.length === 0) {
                return;
            }

            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength === 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        }

        // Progress animation based on scroll or input focus
        const formInputs = document.querySelectorAll('.form-input, .form-select');
        const progressFill = document.getElementById('progressFill');
        const steps = document.querySelectorAll('.step');

        formInputs.forEach((input, index) => {
            input.addEventListener('focus', () => {
                let progress = 0;
                if (index < 3) {
                    progress = 0;
                    setActiveStep(0);
                } else if (index < 5) {
                    progress = 50;
                    setActiveStep(1);
                } else {
                    progress = 100;
                    setActiveStep(2);
                }
                progressFill.style.width = progress + '%';
            });
        });

        function setActiveStep(activeIndex) {
            steps.forEach((step, index) => {
                step.classList.remove('active', 'completed');
                if (index < activeIndex) {
                    step.classList.add('completed');
                } else if (index === activeIndex) {
                    step.classList.add('active');
                }
            });
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