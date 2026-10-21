<?php
// Setup page for creating the first staff account
// This page is only accessible when no staff accounts exist

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
    die("Connection failed: " . $e->getMessage());
}

// Check if any staff accounts exist
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medical_staff");
$stmt->execute();
$staff_count = $stmt->fetch()['count'];

// If staff accounts exist, redirect to login
if ($staff_count > 0) {
    header("Location: index_staff.php?setup_disabled=1");
    exit;
}

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($new_password)) {
        $error = "All required fields must be filled.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            // Hash the password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Insert the first staff account
            $stmt = $pdo->prepare("INSERT INTO medical_staff (full_name, username, password, email, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $username, $hashed_password, $email, $phone]);

            $success = "Initial staff account created successfully! You can now log in.";

            // Redirect to login after 2 seconds
            header("refresh:2;url=index_staff.php");
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Username or email already exists.";
            } else {
                $error = "Error creating account: " . $e->getMessage();
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
    <title>Initial Setup - Dermatology Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c5f6f;
            --secondary-color: #8ab4a7;
            --accent-color: #d4a574;
            --text-dark: #1a1a1a;
            --text-light: #636e72;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2c5f6f 0%, #8ab4a7 25%, #d4a574 50%, #8ab4a7 75%, #2c5f6f 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            padding: 2rem;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .setup-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 3rem;
            max-width: 500px;
            width: 100%;
        }

        .setup-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .logo i {
            color: var(--accent-color);
            font-size: 2.5rem;
        }

        .setup-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .setup-subtitle {
            font-size: 0.95rem;
            color: var(--text-light);
            line-height: 1.5;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert i {
            font-size: 1.25rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .required {
            color: var(--danger);
        }

        .password-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid rgba(44, 95, 111, 0.2);
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .password-wrapper .form-input {
            padding-right: 3rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(44, 95, 111, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .toggle-password:hover {
            color: var(--primary-color);
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44, 95, 111, 0.3);
        }

        .info-box {
            background: rgba(44, 95, 111, 0.05);
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .info-box h4 {
            color: var(--primary-color);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .info-box ul {
            margin-left: 1.25rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .info-box li {
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <div class="logo">
                <i class="fas fa-spa"></i>
                <span>True Beauty Concept</span>
            </div>
            <h1 class="setup-title">Initial Setup</h1>
            <p class="setup-subtitle">No staff accounts exist in the system. Create the first administrator account to get started.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h4><i class="fas fa-info-circle"></i> Important</h4>
            <ul>
                <li>This account will have full administrative access</li>
                <li>Keep your credentials secure</li>
                <li>You can create additional staff accounts later</li>
            </ul>
        </div>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" class="form-input" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Username <span class="required">*</span></label>
                <input type="text" name="username" class="form-input" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-input" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-input" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" class="form-input" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password-icon"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-input" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye" id="confirm_password-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus"></i> Create Administrator Account
            </button>
        </form>
    </div>

    <script>
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
