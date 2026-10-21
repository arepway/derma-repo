<?php
require_once 'auth_check.php';
requireStaffLogin();

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

// Get staff information for header
$staff = null;
$staff_id = $_SESSION['user_id'] ?? null;
if ($staff_id) {
    try {
        $stmt = $pdo->prepare("SELECT staff_id, full_name, role FROM medical_staff WHERE staff_id = ?");
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Continue without staff info
    }
}

// Handle add stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_stock') {
    $quantity_to_add = intval($_POST['quantity'] ?? 0);

    if ($quantity_to_add > 0) {
        try {
            // Get current stock
            $stmt = $pdo->query("SELECT stock_id, quantity FROM stock WHERE item_name = 'Injection' LIMIT 1");
            $current_stock = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($current_stock) {
                $new_quantity = $current_stock['quantity'] + $quantity_to_add;
                $stmt = $pdo->prepare("UPDATE stock SET quantity = ?, updated_by = ? WHERE stock_id = ?");
                $stmt->execute([$new_quantity, $staff_id, $current_stock['stock_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO stock (item_name, quantity, updated_by) VALUES ('Injection', ?, ?)");
                $stmt->execute([$quantity_to_add, $staff_id]);
            }

            $success_message = "Successfully added $quantity_to_add injection(s) to stock!";
        } catch (PDOException $e) {
            $error_message = "Error adding stock: " . $e->getMessage();
        }
    } else {
        $error_message = "Please enter a valid quantity.";
    }
}

// Get current stock
try {
    $stmt = $pdo->query("SELECT * FROM stock WHERE item_name = 'Injection' LIMIT 1");
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stock) {
        // Create initial stock entry
        $pdo->exec("INSERT INTO stock (item_name, quantity, low_stock_threshold) VALUES ('Injection', 0, 5)");
        $stmt = $pdo->query("SELECT * FROM stock WHERE item_name = 'Injection' LIMIT 1");
        $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "Error fetching stock: " . $e->getMessage();
    $stock = ['quantity' => 0, 'low_stock_threshold' => 5];
}

// Get injection usage history
try {
    $stmt = $pdo->query("
        SELECT mr.*, a.appointment_id, p.full_name as patient_name, ms.full_name as staff_name,
               COALESCE(a.appointment_datetime, CONCAT(a.appointment_date, ' ', a.appointment_time)) as appointment_dt
        FROM medical_record mr
        JOIN appointment a ON mr.appointment_id = a.appointment_id
        JOIN patient p ON a.patient_id = p.patient_id
        LEFT JOIN medical_staff ms ON mr.staff_id = ms.staff_id
        WHERE mr.injection_used = 1
        ORDER BY mr.record_date DESC
        LIMIT 50
    ");
    $usage_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usage_history = [];
}

$is_low_stock = $stock['quantity'] <= $stock['low_stock_threshold'];
$is_out_of_stock = $stock['quantity'] <= 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Stock - Dermatology Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
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
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        /* User Info & Profile Dropdown */
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .user-details h3 {
            font-size: 1rem;
            font-weight: 600;
            color: white;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            margin: 0;
        }

        .user-details p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            margin: 0;
        }


        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Animated Starry Night with Moon Background */
        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #020111 0%, #050520 25%, #0a0a2e 50%, #060618 75%, #020111 100%);
            z-index: -1;
            overflow: hidden;
        }

        /* Primary stars layer */
        .animated-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 1) 1px, transparent 1px),
                radial-gradient(circle at 30% 65%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 50% 10%, rgba(255, 255, 255, 0.9) 1px, transparent 1px),
                radial-gradient(circle at 70% 80%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 90% 40%, rgba(255, 255, 255, 1) 1px, transparent 1px),
                radial-gradient(circle at 15% 85%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 45% 35%, rgba(255, 255, 255, 0.9) 1px, transparent 1px),
                radial-gradient(circle at 75% 15%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 25% 50%, rgba(255, 255, 255, 1) 1px, transparent 1px),
                radial-gradient(circle at 85% 60%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 5% 45%, rgba(255, 255, 255, 0.9) 1px, transparent 1px),
                radial-gradient(circle at 55% 90%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 35% 25%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 65% 55%, rgba(255, 255, 255, 1) 1px, transparent 1px),
                radial-gradient(circle at 95% 85%, rgba(255, 255, 255, 0.9) 1px, transparent 1px);
            animation: twinkle 3s ease-in-out infinite;
        }

        /* Secondary stars layer with offset animation */
        .animated-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 20% 70%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 80% 30%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 40% 60%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 60% 40%, rgba(255, 255, 255, 0.9) 1px, transparent 1px),
                radial-gradient(circle at 12% 33%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 88% 77%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 42% 88%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 78% 12%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 33% 44%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 67% 66%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 22% 15%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 92% 55%, rgba(255, 255, 255, 0.6) 1px, transparent 1px);
            animation: twinkle2 4s ease-in-out infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        @keyframes twinkle2 {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Moon */
        .moon {
            position: fixed;
            top: 8%;
            right: 10%;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, #fffde7, #ffd54f 40%, #ffb300 100%);
            box-shadow:
                0 0 40px rgba(255, 235, 59, 0.4),
                0 0 80px rgba(255, 235, 59, 0.2),
                0 0 120px rgba(255, 235, 59, 0.1),
                inset -10px -10px 20px rgba(255, 152, 0, 0.3);
            z-index: -1;
            animation: moonGlow 6s ease-in-out infinite;
        }

        .moon::before {
            content: '';
            position: absolute;
            top: 15%;
            left: 20%;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: rgba(255, 152, 0, 0.2);
        }

        .moon::after {
            content: '';
            position: absolute;
            top: 45%;
            left: 55%;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(255, 152, 0, 0.15);
        }

        @keyframes moonGlow {
            0%, 100% {
                box-shadow:
                    0 0 40px rgba(255, 235, 59, 0.4),
                    0 0 80px rgba(255, 235, 59, 0.2),
                    0 0 120px rgba(255, 235, 59, 0.1),
                    inset -10px -10px 20px rgba(255, 152, 0, 0.3);
            }
            50% {
                box-shadow:
                    0 0 60px rgba(255, 235, 59, 0.5),
                    0 0 100px rgba(255, 235, 59, 0.3),
                    0 0 140px rgba(255, 235, 59, 0.15),
                    inset -10px -10px 20px rgba(255, 152, 0, 0.3);
            }
        }

        /* Shooting stars */
        .shooting-star {
            position: fixed;
            width: 100px;
            height: 2px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.8), transparent);
            z-index: -1;
            animation: shoot 3s ease-in-out infinite;
            opacity: 0;
        }

        .shooting-star:nth-child(2) {
            top: 20%;
            left: 70%;
            animation-delay: 0s;
        }

        .shooting-star:nth-child(3) {
            top: 40%;
            left: 30%;
            animation-delay: 2s;
        }

        .shooting-star:nth-child(4) {
            top: 60%;
            left: 80%;
            animation-delay: 4s;
        }

        @keyframes shoot {
            0% {
                transform: translateX(0) translateY(0) rotate(-45deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            30% {
                transform: translateX(-200px) translateY(200px) rotate(-45deg);
                opacity: 0;
            }
            100% {
                opacity: 0;
            }
        }

        /* Header - Liquid Glass */
        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo i {
            color: var(--accent-color);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        /* Navigation - Minimal */
        .nav-container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 0.5rem;
            margin: 0 auto 1.5rem;
            max-width: 1200px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 0.25rem;
        }

        .nav-link {
            padding: 0.6rem 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .nav-link i {
            font-size: 0.9rem;
        }

        .nav-link span {
            display: none;
        }

        @media (min-width: 768px) {
            .nav-link span {
                display: inline;
            }
        }

        .notification-badge {
            background: var(--accent-color);
            color: white;
            padding: 0.15rem 0.4rem;
            border-radius: 6px;
            font-size: 0.65rem;
            font-weight: 600;
            margin-left: 0.2rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }

        /* Page Header */
        .page-header {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .page-header h1 i {
            color: var(--accent-color);
        }

        .page-header p {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-left: 4px solid var(--danger);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.15);
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-left: 4px solid var(--warning);
        }

        .alert i {
            font-size: 1.2rem;
        }

        /* Stock Cards Grid */
        .stock-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stock-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stock-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        .stock-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stock-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stock-icon.normal {
            background: linear-gradient(135deg, var(--success), #059669);
        }

        .stock-icon.low {
            background: linear-gradient(135deg, var(--warning), #d97706);
        }

        .stock-icon.out {
            background: linear-gradient(135deg, var(--danger), #dc2626);
        }

        .stock-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: white;
            margin-bottom: 0.2rem;
        }

        .stock-quantity {
            font-size: 3rem;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
            margin: 1rem 0;
        }

        .stock-quantity.normal {
            color: #6ee7b7;
        }

        .stock-quantity.low {
            color: #fbbf24;
        }

        .stock-quantity.out {
            color: #fca5a5;
        }

        .stock-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Add Stock Form */
        .add-stock-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .add-stock-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-stock-card h3 i {
            color: var(--accent-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .form-group label i {
            color: var(--accent-color);
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            color: white;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(138, 180, 167, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 10px 30px rgba(44, 95, 111, 0.3);
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(44, 95, 111, 0.4);
        }

        /* Usage History */
        .history-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-top: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .history-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .history-card h3 i {
            color: var(--accent-color);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .history-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
        }

        .history-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .history-table a {
            color: var(--secondary-color);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 968px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-direction: column;
            }

            .container {
                padding: 0 1rem 1rem;
            }

            .stock-grid {
                grid-template-columns: 1fr;
            }

            .history-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Starry Night Background -->
    <div class="animated-background"></div>
    <div class="moon"></div>
    <div class="shooting-star"></div>
    <div class="shooting-star"></div>
    <div class="shooting-star"></div>

    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-spa"></i>
                <span>True Beauty Concept</span>
            </div>
            <?php if ($staff): ?>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($staff['full_name'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <h3><?= htmlspecialchars($staff['full_name']) ?></h3>
                    <p><?= htmlspecialchars($staff['role'] ?? 'Medical Staff') ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation -->
    <div class="container">
        <div class="nav-container">
            <div class="nav-links">
                <a href="staff_dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_services.php" class="nav-link">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Services</span>
                </a>
                <a href="manage_feedback.php" class="nav-link">
                    <i class="fas fa-star"></i>
                    <span>Feedback</span>
                </a>
                <a href="manage_stock.php" class="nav-link active">
                    <i class="fas fa-boxes"></i>
                    <span>Stock</span>
                </a>
                <a href="logout.php?type=staff" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-boxes"></i>
                Injection Stock Management
            </h1>
            <p>Monitor and manage injection inventory</p>
        </div>

        <!-- Alerts -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($success_message) ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($is_out_of_stock): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><strong>Out of Stock!</strong> No injections available. Please add stock immediately.</span>
            </div>
        <?php elseif ($is_low_stock): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span><strong>Low Stock Warning!</strong> Injection stock is running low (<?= $stock['quantity'] ?> remaining).</span>
            </div>
        <?php endif; ?>

        <!-- Stock Grid -->
        <div class="stock-grid">
            <!-- Current Stock Card -->
            <div class="stock-card">
                <div class="stock-card-header">
                    <div class="stock-icon <?= $is_out_of_stock ? 'out' : ($is_low_stock ? 'low' : 'normal') ?>">
                        <i class="fas fa-syringe"></i>
                    </div>
                    <div>
                        <h3>Current Stock</h3>
                        <p class="stock-label">Injections Available</p>
                    </div>
                </div>
                <div class="stock-quantity <?= $is_out_of_stock ? 'out' : ($is_low_stock ? 'low' : 'normal') ?>">
                    <?= $stock['quantity'] ?>
                </div>
                <p class="stock-label">Last Updated: <?= date('M j, Y g:i A', strtotime($stock['last_updated'])) ?></p>
            </div>

            <!-- Add Stock Card -->
            <div class="add-stock-card">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Add Stock
                </h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_stock">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-box"></i>
                            Quantity to Add
                        </label>
                        <input type="number" name="quantity" min="1" required placeholder="Enter quantity">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add to Stock
                    </button>
                </form>
            </div>
        </div>

        <!-- Usage History -->
        <div class="history-card">
            <h3>
                <i class="fas fa-history"></i>
                Injection Usage History (Last 50)
            </h3>
            <?php if (empty($usage_history)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No injection usage recorded yet</p>
                </div>
            <?php else: ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Doctor/Staff</th>
                            <th>Appointment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usage_history as $record): ?>
                            <tr>
                                <td><?= date('M j, Y g:i A', strtotime($record['record_date'])) ?></td>
                                <td><?= htmlspecialchars($record['patient_name']) ?></td>
                                <td><?= htmlspecialchars($record['staff_name'] ?? 'N/A') ?></td>
                                <td>
                                    <a href="appointment_details.php?id=<?= $record['appointment_id'] ?>" style="color: var(--primary-color); text-decoration: none;">
                                        View Appointment
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
