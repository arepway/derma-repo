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

// Check if any patients exist
$stmt = $pdo->prepare("SELECT COUNT(*) FROM patient");
$stmt->execute();
$patient_count = $stmt->fetchColumn();
$no_users_message = ($patient_count == 0) ? "Welcome! No accounts exist yet. Please register to get started." : '';

// Handle login
if ($_POST['action'] ?? '' == 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM patient WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "No account found with that username. Please register first.";
    } elseif (!password_verify($password, $user['password'])) {
        $error = "Invalid password.";
    } else {
        setLoginSession($user['patient_id'], 'patient', ['user_name' => $user['full_name']]);
        header("Location: patient_dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - True Beauty Concept Dermatology</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --bg-light: #f8f9fa;
            --white: #ffffff;
        }

        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
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
            z-index: -1;
        }

        /* Stars Layer 1 - Small stars */
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
                radial-gradient(circle at 12% 92%, rgba(255, 255, 255, 0.5) 1px, transparent 1px);
            animation: twinkle1 3s ease-in-out infinite;
        }

        /* Stars Layer 2 - Medium stars with different timing */
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
                radial-gradient(circle at 18% 48%, rgba(255, 255, 255, 0.6) 2px, transparent 2px),
                radial-gradient(circle at 48% 8%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 78% 58%, rgba(255, 255, 255, 0.7) 2px, transparent 2px);
            animation: twinkle2 4s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes twinkle1 {
            0%, 100% {
                opacity: 0.4;
            }
            50% {
                opacity: 1;
            }
        }

        @keyframes twinkle2 {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.3;
            }
        }

        /* Navigation Bar */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: transparent;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.3);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo i {
            color: var(--accent-color);
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-btn {
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-btn-outline {
            color: #ffffff;
            border: 2px solid rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .nav-btn-outline:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.8);
            color: white;
        }

        .nav-btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            box-shadow: 0 5px 15px rgba(44, 95, 111, 0.3);
        }

        .nav-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44, 95, 111, 0.4);
        }

        /* Hero Section */
        .hero {
            margin-top: 80px;
            min-height: 90vh;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 4rem 2rem;
        }

        .hero-content {
            max-width: 1400px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-text {
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

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero-badge i {
            color: var(--accent-color);
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.4);
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-highlight {
            color: var(--accent-color);
            position: relative;
            display: inline-block;
        }

        .hero p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.3);
            margin-bottom: 2.5rem;
            line-height: 1.8;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 10px 30px rgba(44, 95, 111, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(44, 95, 111, 0.35);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary-color);
            border: 2px solid white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        /* Login Card */
        .login-card-wrapper {
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

        .login-card {
            background: white;
            padding: 2.5rem;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(138, 180, 167, 0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 30px rgba(138, 180, 167, 0.3);
        }

        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #1a1a1a;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
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

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 0.6rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(138, 180, 167, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 0.3rem;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--secondary-color);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(138, 180, 167, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(138, 180, 167, 0.35);
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
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
            background: white;
            padding: 0 1rem;
            color: var(--text-light);
            font-size: 0.85rem;
            position: relative;
            z-index: 1;
        }

        .links {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .link-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            padding: 0.9rem 1.2rem;
            border: 2px solid var(--secondary-color);
            border-radius: 12px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .link-btn:hover {
            background: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(138, 180, 167, 0.25);
        }

        /* Specialties Section */
        .specialties {
            padding: 6rem 2rem;
            background: transparent;
        }

        .section-header {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 4rem;
        }

        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            color: #ffffff;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.4);
            margin-bottom: 1rem;
        }

        .section-description {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.3);
            line-height: 1.8;
        }

        .specialties-grid {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }

        .specialty-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .specialty-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .specialty-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
            border-color: rgba(138, 180, 167, 0.3);
        }

        .specialty-card:hover::before {
            transform: scaleX(1);
        }

        .specialty-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(138, 180, 167, 0.2), rgba(212, 165, 116, 0.2));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.4s ease;
        }

        .specialty-card:hover .specialty-icon {
            transform: scale(1.1) rotate(5deg);
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
        }

        .specialty-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .specialty-description {
            color: var(--text-light);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .specialty-features {
            list-style: none;
            padding: 0;
            margin-bottom: 1.5rem;
        }

        .specialty-features li {
            padding: 0.5rem 0;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-size: 0.95rem;
        }

        .specialty-features li i {
            color: var(--accent-color);
            font-size: 0.8rem;
        }

        .specialty-link {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .specialty-link:hover {
            color: var(--accent-color);
            gap: 0.8rem;
        }

        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: transparent;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2.5rem;
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .feature-icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(138, 180, 167, 0.2), rgba(212, 165, 116, 0.2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon-circle {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
            transform: scale(1.1);
        }

        .feature-title {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 0.8rem;
            font-weight: 600;
        }

        .feature-description {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background: transparent;
            color: white;
            padding: 3rem 2rem 1.5rem;
            text-align: center;
        }

        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
        }

        .footer-logo i {
            color: var(--accent-color);
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--accent-color);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .social-links a {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--accent-color);
            transform: translateY(-5px);
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 3rem;
                text-align: center;
            }

            .hero h1 {
                font-size: 2.8rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .login-card-wrapper {
                max-width: 500px;
                margin: 0 auto;
            }

            .section-title {
                font-size: 2.2rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .nav-btn {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 640px) {
            .hero {
                padding: 2rem 1rem;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .login-card {
                padding: 1.5rem;
            }

            .specialties-grid {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .nav-links {
                flex-direction: column;
                gap: 0.5rem;
            }

            .logo {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-background"></div>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-spa"></i>
                <span>True Beauty Concept</span>
            </a>
            <div class="nav-links">
                <a href="index.php" class="nav-btn nav-btn-outline">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="services_public.php" class="nav-btn nav-btn-primary">
                    <i class="fas fa-list"></i>
                    <span>Services</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <!-- Left Side - Content -->
            <div class="hero-text">
                <div class="hero-badge">
                    <i class="fas fa-award"></i>
                    <span>Expert Dermatology Care</span>
                </div>
                <h1>
                    Your Journey to<br>
                    <span class="hero-highlight">Radiant Skin</span><br>
                    Starts Here
                </h1>
                <p>
                    Access your personalized patient portal to manage appointments,
                    view treatment history, and track your skincare journey with our
                    expert dermatologists.
                </p>
                <div class="hero-buttons">
                    <a href="registration.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        <span>Register Now</span>
                    </a>
                    <a href="#specialties" class="btn btn-secondary">
                        <i class="fas fa-star"></i>
                        <span>Our Specialties</span>
                    </a>
                </div>
            </div>

            <!-- Right Side - Login Card -->
            <div class="login-card-wrapper">
                <div class="login-card">
                    <div class="login-header">
                        <div class="login-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h2 class="login-title">Patient Login</h2>
                        <p class="login-subtitle">Sign in to access your portal</p>
                    </div>

                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>Account Deleted</strong><br>
                                Your account has been removed successfully.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($no_users_message): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <span><?= htmlspecialchars($no_users_message) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span><?= htmlspecialchars($success) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($patient_count > 0): ?>
                    <form method="POST" id="loginForm">
                        <input type="hidden" name="action" value="login">

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user"></i>
                                Username
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input
                                    type="text"
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
                                    id="login-password"
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
                            <span>Sign In to Portal</span>
                        </button>
                    </form>

                    <div class="divider">
                        <span>OR</span>
                    </div>
                    <?php endif; ?>

                    <div class="links">
                        <a href="registration.php" class="link-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Create New Account</span>
                        </a>
                        <a href="services_public.php" class="link-btn">
                            <i class="fas fa-list"></i>
                            <span>Browse Services</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Specialties Section -->
    <section class="specialties" id="specialties">
        <div class="section-header">
            <div class="section-badge">
                <i class="fas fa-star"></i>
                <span>Our Expertise</span>
            </div>
            <h2 class="section-title">Dermatology Specialties</h2>
            <p class="section-description">
                Comprehensive skin care solutions backed by expertise,
                advanced technology, and a commitment to your skin health.
            </p>
        </div>

        <div class="specialties-grid">
            <!-- Medical Dermatology -->
            <div class="specialty-card">
                <div class="specialty-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <h3 class="specialty-title">Medical Dermatology</h3>
                <p class="specialty-description">
                    Expert diagnosis and treatment of skin conditions with
                    evidence-based medical care.
                </p>
                <ul class="specialty-features">
                    <li><i class="fas fa-check-circle"></i> Acne Treatment</li>
                    <li><i class="fas fa-check-circle"></i> Eczema & Psoriasis Care</li>
                    <li><i class="fas fa-check-circle"></i> Skin Infection Treatment</li>
                    <li><i class="fas fa-check-circle"></i> Rash Diagnosis</li>
                </ul>
                <a href="services_public.php" class="specialty-link">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <!-- Cosmetic Dermatology -->
            <div class="specialty-card">
                <div class="specialty-icon">
                    <i class="fas fa-magic"></i>
                </div>
                <h3 class="specialty-title">Cosmetic Dermatology</h3>
                <p class="specialty-description">
                    Advanced aesthetic treatments to enhance your natural
                    beauty and boost confidence.
                </p>
                <ul class="specialty-features">
                    <li><i class="fas fa-check-circle"></i> Anti-Aging Treatments</li>
                    <li><i class="fas fa-check-circle"></i> Chemical Peels</li>
                    <li><i class="fas fa-check-circle"></i> Laser Therapy</li>
                    <li><i class="fas fa-check-circle"></i> Skin Rejuvenation</li>
                </ul>
                <a href="services_public.php" class="specialty-link">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <!-- Surgical Dermatology -->
            <div class="specialty-card">
                <div class="specialty-icon">
                    <i class="fas fa-syringe"></i>
                </div>
                <h3 class="specialty-title">Surgical Procedures</h3>
                <p class="specialty-description">
                    Safe, minimally invasive procedures performed by
                    experienced dermatological surgeons.
                </p>
                <ul class="specialty-features">
                    <li><i class="fas fa-check-circle"></i> Mole Removal</li>
                    <li><i class="fas fa-check-circle"></i> Cyst Excision</li>
                    <li><i class="fas fa-check-circle"></i> Skin Biopsy</li>
                    <li><i class="fas fa-check-circle"></i> Scar Revision</li>
                </ul>
                <a href="services_public.php" class="specialty-link">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <!-- Pediatric Dermatology -->
            <div class="specialty-card">
                <div class="specialty-icon">
                    <i class="fas fa-child"></i>
                </div>
                <h3 class="specialty-title">Pediatric Dermatology</h3>
                <p class="specialty-description">
                    Specialized care for children's unique skin conditions
                    with gentle, effective treatments.
                </p>
                <ul class="specialty-features">
                    <li><i class="fas fa-check-circle"></i> Childhood Eczema</li>
                    <li><i class="fas fa-check-circle"></i> Birthmark Treatment</li>
                    <li><i class="fas fa-check-circle"></i> Pediatric Acne</li>
                    <li><i class="fas fa-check-circle"></i> Wart Removal</li>
                </ul>
                <a href="services_public.php" class="specialty-link">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <!-- Hair & Nail Care -->
            <div class="specialty-card">
                <div class="specialty-icon">
                    <i class="fas fa-cut"></i>
                </div>
                <h3 class="specialty-title">Hair & Nail Care</h3>
                <p class="specialty-description">
                    Comprehensive treatment for hair loss and nail disorders
                    to restore health and appearance.
                </p>
                <ul class="specialty-features">
                    <li><i class="fas fa-check-circle"></i> Hair Loss Treatment</li>
                    <li><i class="fas fa-check-circle"></i> Scalp Conditions</li>
                    <li><i class="fas fa-check-circle"></i> Nail Fungus Treatment</li>
                    <li><i class="fas fa-check-circle"></i> Nail Disorders</li>
                </ul>
                <a href="services_public.php" class="specialty-link">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <!-- Skin Cancer Screening -->
            <div class="specialty-card">
                <div class="specialty-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="specialty-title">Skin Cancer Screening</h3>
                <p class="specialty-description">
                    Early detection and treatment of skin cancer with
                    comprehensive screening programs.
                </p>
                <ul class="specialty-features">
                    <li><i class="fas fa-check-circle"></i> Full Body Screening</li>
                    <li><i class="fas fa-check-circle"></i> Mole Mapping</li>
                    <li><i class="fas fa-check-circle"></i> Dermoscopy Analysis</li>
                    <li><i class="fas fa-check-circle"></i> Risk Assessment</li>
                </ul>
                <a href="services_public.php" class="specialty-link">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="section-header">
            <div class="section-badge">
                <i class="fas fa-heart"></i>
                <span>Why Choose Us</span>
            </div>
            <h2 class="section-title">Your Skin Health, Our Priority</h2>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon-circle">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3 class="feature-title">Easy Booking</h3>
                <p class="feature-description">
                    Schedule appointments online 24/7 with instant confirmation
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-circle">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3 class="feature-title">Expert Doctors</h3>
                <p class="feature-description">
                    Board-certified dermatologists with years of experience
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-circle">
                    <i class="fas fa-file-medical-alt"></i>
                </div>
                <h3 class="feature-title">Digital Records</h3>
                <p class="feature-description">
                    Access your medical history and treatment plans anytime
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-circle">
                    <i class="fas fa-microscope"></i>
                </div>
                <h3 class="feature-title">Advanced Technology</h3>
                <p class="feature-description">
                    State-of-the-art equipment for accurate diagnosis
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <i class="fas fa-spa"></i>
                <span>True Beauty Concept</span>
            </div>
            <p class="footer-text">
                Your trusted partner in comprehensive dermatological care.
                We're committed to helping you achieve and maintain healthy, beautiful skin.
            </p>
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="services_public.php">Services</a>
                <a href="registration.php">Register</a>
                <a href="index_patient.php">Patient Portal</a>
            </div>
                <div class="social-links">
                    <a href="https://www.facebook.com/sheyreen.ling" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/truebeautyconcept_kk/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@truebeautyconcept" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            <div class="footer-bottom">
                <p>&copy; 2025 True Beauty Concept Dermatology Clinic. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('login-password');
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
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Scroll animation for specialty cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 100);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.specialty-card, .feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
