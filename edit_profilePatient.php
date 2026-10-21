<?php
require_once 'auth_check.php';
requirePatientLogin();
require_once 'db_connect.php';

$patient_id = $_SESSION['user_id'];

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

// Get patient info
$stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient not found.");
}

$profile_error = '';
$profile_success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') == 'update_profile') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $profile_error = "Invalid security token. Please try again.";
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $date_of_birth = trim($_POST['date_of_birth'] ?? '');
        $sex = trim($_POST['sex'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        $errors = [];

        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        if (!empty($phone) && !preg_match('/^[0-9+\s\-()]{6,30}$/', $phone)) {
            $errors[] = "Invalid phone number format.";
        }

        if (!empty($date_of_birth) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
            $errors[] = "Invalid date of birth format.";
        }

        // Password validation if changing password
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = "Current password is required to change password.";
            } elseif (!password_verify($current_password, $patient['password'])) {
                $errors[] = "Current password is incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $errors[] = "New passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $errors[] = "New password must be at least 6 characters long.";
            }
        }

        if (!empty($errors)) {
            $profile_error = implode("<br>", $errors);
        } else {
            try {
                if (!empty($new_password)) {
                    // Update with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE patient SET full_name = ?, email = ?, phone = ?, date_of_birth = ?, sex = ?, password = ? WHERE patient_id = ?");
                    $stmt->execute([$full_name, $email, $phone, $date_of_birth, $sex, $hashed_password, $patient_id]);
                } else {
                    // Update without password change
                    $stmt = $pdo->prepare("UPDATE patient SET full_name = ?, email = ?, phone = ?, date_of_birth = ?, sex = ? WHERE patient_id = ?");
                    $stmt->execute([$full_name, $email, $phone, $date_of_birth, $sex, $patient_id]);
                }

                $profile_success = "Profile updated successfully!";

                // Refresh patient data
                $stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
                $stmt->execute([$patient_id]);
                $patient = $stmt->fetch();
                $_SESSION['user_name'] = $patient['full_name'];

                // Regenerate CSRF token after successful update
                $_SESSION['csrf_token'] = bin2hex(random_bytes(24));

            } catch(PDOException $e) {
                $profile_error = "Update failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Patient Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c5f6f;
            --secondary-color: #8ab4a7;
            --accent-color: #d4a574;
            --text-dark: #1a1a1a;
            --text-light: #f5f5f5;
            --danger: #ef4444;
            --success: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Starry Night Background */
        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1a2a3a 0%, #2c3e50 50%, #1a2a3a 100%);
            z-index: -1;
        }

        .animated-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 30% 40%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 40% 10%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 50% 60%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 60% 30%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 70% 90%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 80% 50%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 90% 70%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 15% 55%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 25% 15%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 35% 85%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 45% 35%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 55% 95%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 65% 5%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 75% 65%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 85% 25%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 95% 45%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 5% 75%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 12% 42%, rgba(255, 255, 255, 0.5) 1px, transparent 1px);
            animation: twinkle1 3s ease-in-out infinite;
        }

        .animated-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 8% 35%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 22% 65%, rgba(255, 255, 255, 0.7) 2px, transparent 2px),
                radial-gradient(circle at 38% 22%, rgba(255, 255, 255, 0.8) 2px, transparent 2px),
                radial-gradient(circle at 52% 78%, rgba(255, 255, 255, 0.6) 2px, transparent 2px),
                radial-gradient(circle at 68% 42%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 82% 88%, rgba(255, 255, 255, 0.7) 2px, transparent 2px),
                radial-gradient(circle at 92% 12%, rgba(255, 255, 255, 0.8) 2px, transparent 2px),
                radial-gradient(circle at 18% 92%, rgba(255, 255, 255, 0.6) 2px, transparent 2px),
                radial-gradient(circle at 48% 8%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 78% 58%, rgba(255, 255, 255, 0.7) 2px, transparent 2px);
            animation: twinkle2 4s ease-in-out infinite;
        }

        @keyframes twinkle1 {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }

        @keyframes twinkle2 {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Floating Shapes */
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 80px;
            height: 80px;
            top: 70%;
            left: 15%;
            animation-delay: 3s;
        }

        .shape:nth-child(3) {
            width: 120px;
            height: 120px;
            top: 30%;
            right: 10%;
            animation-delay: 5s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-40px) rotate(180deg); }
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 700px;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.6s ease;
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

        .profile-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .profile-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 42px;
            margin: 0 auto 20px;
            box-shadow: 0 15px 35px rgba(138, 180, 167, 0.4);
            border: 4px solid rgba(255, 255, 255, 0.9);
        }

        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .subtitle {
            color: #666;
            font-size: 15px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(44, 95, 111, 0.1);
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(-5px);
        }

        .section-divider {
            margin: 30px 0;
            border: none;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--secondary-color), transparent);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--secondary-color);
        }

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
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        label i {
            color: var(--secondary-color);
            font-size: 16px;
        }

        .required {
            color: var(--danger);
            margin-left: 4px;
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
            color: #999;
        }

        input, select {
            width: 100%;
            padding: 14px 20px 14px 55px;
            border: 2px solid rgba(44, 95, 111, 0.2);
            border-radius: 15px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(138, 180, 167, 0.1);
        }

        input:disabled {
            background: rgba(44, 95, 111, 0.05);
            cursor: not-allowed;
            opacity: 0.7;
        }

        input[readonly] {
            background: rgba(44, 95, 111, 0.05);
            cursor: not-allowed;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px;
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--secondary-color);
        }

        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            padding-left: 55px;
        }

        .btn-update {
            width: 100%;
            padding: 16px;
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
            margin-top: 30px;
        }

        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(138, 180, 167, 0.4);
        }

        .btn-update:active {
            transform: translateY(-1px);
        }

        .password-section {
            background: rgba(212, 165, 116, 0.05);
            padding: 25px;
            border-radius: 20px;
            border: 2px dashed rgba(212, 165, 116, 0.3);
        }

        .info-text {
            font-size: 13px;
            color: #666;
            padding: 12px 20px;
            background: rgba(138, 180, 167, 0.1);
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-text i {
            color: var(--secondary-color);
            font-size: 18px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .main-container {
                padding: 30px 25px;
                border-radius: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            h2 {
                font-size: 26px;
            }

            .profile-avatar-large {
                width: 80px;
                height: 80px;
                font-size: 36px;
            }

            input, select {
                padding: 12px 18px 12px 50px;
                font-size: 14px;
            }

            .input-icon {
                left: 16px;
                font-size: 16px;
            }

            .password-hint {
                padding-left: 50px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .main-container {
                padding: 25px 20px;
            }

            h2 {
                font-size: 24px;
            }

            .section-title {
                font-size: 18px;
            }

            .btn-update {
                padding: 14px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-background"></div>

    <!-- Floating Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <a href="patient_dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Dashboard</span>
        </a>

        <div class="profile-header">
            <div class="profile-avatar-large">
                <?php echo strtoupper(substr($patient['full_name'] ?? 'P', 0, 1)); ?>
            </div>
            <h2>Edit Your Profile</h2>
            <p class="subtitle">Update your personal information</p>
        </div>

        <?php if ($profile_success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($profile_success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($profile_error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $profile_error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <!-- Personal Information Section -->
            <div class="section-title">
                <i class="fas fa-user-circle"></i>
                Personal Information
            </div>

            <div class="form-group full-width">
                <label>
                    <i class="fas fa-user"></i>
                    Full Name
                    <span class="required">*</span>
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input
                        type="text"
                        name="full_name"
                        value="<?php echo htmlspecialchars($patient['full_name'] ?? ''); ?>"
                        required
                        placeholder="Enter your full name"
                    >
                </div>
            </div>

            <div class="form-group full-width">
                <label>
                    <i class="fas fa-user-tag"></i>
                    Username
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-user-tag input-icon"></i>
                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($patient['username'] ?? ''); ?>"
                        readonly
                        title="Username cannot be changed"
                    >
                </div>
                <div class="password-hint">
                    <i class="fas fa-info-circle"></i> Username cannot be changed
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input
                            type="email"
                            name="email"
                            value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>"
                            placeholder="your@email.com"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-phone"></i>
                        Phone Number
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input
                            type="text"
                            name="phone"
                            value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>"
                            placeholder="+1 (555) 000-0000"
                        >
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        <i class="fas fa-calendar"></i>
                        Date of Birth
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-calendar input-icon"></i>
                        <input
                            type="date"
                            name="date_of_birth"
                            value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-venus-mars"></i>
                        Sex
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-venus-mars input-icon"></i>
                        <select name="sex">
                            <option value="">Select your sex</option>
                            <option value="Male" <?php echo ($patient['sex'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($patient['sex'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($patient['sex'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <hr class="section-divider">

            <!-- Password Change Section -->
            <div class="password-section">
                <div class="section-title">
                    <i class="fas fa-lock"></i>
                    Change Password
                </div>

                <div class="info-text">
                    <i class="fas fa-info-circle"></i>
                    Leave password fields empty if you don't want to change your password
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-key"></i>
                        Current Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-key input-icon"></i>
                        <input
                            type="password"
                            id="current-password"
                            name="current_password"
                            placeholder="Enter current password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('current-password', 'toggle-current')">
                            <i class="fas fa-eye" id="toggle-current"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-lock"></i>
                        New Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input
                            type="password"
                            id="new-password"
                            name="new_password"
                            placeholder="Enter new password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('new-password', 'toggle-new')">
                            <i class="fas fa-eye" id="toggle-new"></i>
                        </button>
                    </div>
                    <div class="password-hint">
                        <i class="fas fa-info-circle"></i> Password must be at least 6 characters long
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-lock"></i>
                        Confirm New Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input
                            type="password"
                            id="confirm-password"
                            name="confirm_password"
                            placeholder="Confirm new password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm-password', 'toggle-confirm')">
                            <i class="fas fa-eye" id="toggle-confirm"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-update">
                <i class="fas fa-save"></i>
                <span>Save Changes</span>
            </button>
        </form>
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
