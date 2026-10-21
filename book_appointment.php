<?php
require_once 'auth_check.php';
requirePatientLogin();

// Set timezone to prevent date shifting issues
date_default_timezone_set('Asia/Manila');

// Database configuration
$host = 'localhost';           // or '127.0.0.1'
$port = '3307';               // MySQL port (usually 3306)
$dbname = 'dermatology_clinic';
$username = 'root';           // your MySQL username
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$patient_id = $_SESSION['user_id'];

// Get available services
$stmt = $pdo->prepare("SELECT * FROM service WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$services = $stmt->fetchAll();

// Get the single medical staff account (assuming staff_id = 1)
$stmt = $pdo->prepare("SELECT * FROM medical_staff LIMIT 1");
$stmt->execute();
$staff = $stmt->fetch();

if (!$staff) {
    die("No medical staff account found. Please contact administrator.");
}

// Handle appointment booking
if ($_POST['action'] ?? '' == 'book') {
    $service_id = $_POST['service_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $staff_id = $staff['staff_id']; // Use the single staff account

    // Get service price
    $stmt = $pdo->prepare("SELECT price FROM service WHERE service_id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();
    $price = $service['price'];

    // Check if time slot is available
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM appointment
        WHERE date = ? AND time = ? AND status != 'cancelled'
    ");
    $stmt->execute([$date, $time]);
    $existing = $stmt->fetch();

    if ($existing['count'] > 0) {
        $error = "This time slot is already booked. Please select another time.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO appointment (patient_id, staff_id, service_id, date, time, price, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')
            ");
            $stmt->execute([$patient_id, $staff_id, $service_id, $date, $time, $price, $notes]);
            $success = "Appointment booked successfully!";

            // Clear form data
            unset($_POST);
        } catch(PDOException $e) {
            $error = "Booking failed: " . $e->getMessage();
        }
    }
}

// Get patient's upcoming appointments for reference
$stmt = $pdo->prepare("
    SELECT a.*, s.name as service_name
    FROM appointment a
    JOIN service s ON a.service_id = s.service_id
    WHERE a.patient_id = ? AND a.date >= CURDATE() AND a.status = 'scheduled'
    ORDER BY a.date ASC, a.time ASC
");
$stmt->execute([$patient_id]);
$upcoming_appointments = $stmt->fetchAll();

// Get patient info for header
$stmt = $pdo->prepare("SELECT full_name FROM patient WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Dermatology Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c5f6f;
            --secondary-color: #8ab4a7;
            --accent-color: #d4a574;
            --text-dark: #1a1a1a;
            --text-light: #f5f5f5;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        /* Modern Header - Liquid Glass */
        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 20px 40px;
            border-radius: 20px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 600;
            color: white;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .user-name {
            font-weight: 500;
            color: white;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Navigation */
        nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            margin-bottom: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        nav a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            padding: 10px 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        nav a:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: translateY(-2px);
        }

        nav a.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        /* Messages */
        .message {
            padding: 18px 24px;
            border-radius: 15px;
            margin-bottom: 24px;
            font-size: 16px;
            text-align: center;
            backdrop-filter: blur(10px);
            font-weight: 500;
        }

        .error {
            background: rgba(255, 59, 48, 0.15);
            color: #ff3b30;
            border: 2px solid rgba(255, 59, 48, 0.3);
        }

        .success {
            background: rgba(52, 199, 89, 0.15);
            color: #34c759;
            border: 2px solid rgba(52, 199, 89, 0.3);
        }

        .success a {
            color: #34c759;
            font-weight: 600;
            text-decoration: underline;
        }

        /* Cards and Sections */
        .card-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg, var(--primary-color), var(--accent-color)) 1;
        }

        /* Services Table */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(44, 95, 111, 0.2);
            border-color: var(--secondary-color);
        }

        .service-name {
            font-weight: 600;
            font-size: 18px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .service-desc {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .service-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid rgba(44, 95, 111, 0.1);
        }

        .service-duration {
            font-size: 13px;
            color: #888;
        }

        .service-price {
            font-weight: 700;
            font-size: 20px;
            color: var(--accent-color);
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 15px;
        }

        input, select, textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(44, 95, 111, 0.2);
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(44, 95, 111, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 4px 15px rgba(44, 95, 111, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 95, 111, 0.4);
        }

        .btn-secondary {
            background: rgba(44, 95, 111, 0.1);
            color: var(--primary-color);
        }

        .btn-secondary:hover {
            background: rgba(44, 95, 111, 0.2);
        }

        .staff-info {
            background: linear-gradient(135deg, rgba(44, 95, 111, 0.1), rgba(138, 180, 167, 0.1));
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 16px;
            font-weight: 500;
            border-left: 4px solid var(--primary-color);
        }

        /* Appointments Table */
        .appointments-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .appointments-table thead tr {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .appointments-table th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .appointments-table th:first-child {
            border-radius: 12px 0 0 12px;
        }

        .appointments-table th:last-child {
            border-radius: 0 12px 12px 0;
        }

        .appointments-table td {
            padding: 18px 20px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 15px;
        }

        .appointments-table tbody tr {
            transition: all 0.3s ease;
        }

        .appointments-table tbody tr:hover td {
            background: rgba(44, 95, 111, 0.05);
        }

        .appointments-table td:first-child {
            border-radius: 12px 0 0 12px;
        }

        .appointments-table td:last-child {
            border-radius: 0 12px 12px 0;
        }

        .cancel-link {
            color: #ff3b30;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .cancel-link:hover {
            text-decoration: underline;
        }

        /* Card Shuffle Date Picker Styles - PRESERVED FROM ORIGINAL */
        .date-picker-container {
            position: relative;
            margin: 20px 0;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            perspective: 1000px;
        }

        .card-stack {
            position: relative;
            width: 600px;
            height: 180px;
            margin: 0 auto;
        }

        .date-card {
            position: absolute;
            width: 280px;
            height: 100%;
            left: 50%;
            margin-left: -140px;
            background: linear-gradient(135deg, #007aff 0%, #0051d5 100%);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 122, 255, 0.3);
            user-select: none;
            color: white;
            transform-origin: center;
        }

        .date-card.disabled {
            background: linear-gradient(135deg, #ff3b30 0%, #cc0000 100%);
            box-shadow: 0 10px 30px rgba(255, 59, 48, 0.3);
            cursor: not-allowed;
        }

        .date-card.disabled:hover.card-0 {
            transform: translateX(0) translateY(0) scale(1) rotateY(0deg);
            box-shadow: 0 10px 30px rgba(255, 59, 48, 0.3);
        }

        .date-card .availability-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .date-card.disabled .availability-badge {
            background: rgba(255, 255, 255, 0.95);
            color: #ff3b30;
        }

        /* Three-card layout: Past (left), Current (center), Future (right) */
        .date-card.card-left {
            transform: translateX(-160px) translateY(0) scale(0.8);
            z-index: 5;
            opacity: 0.4;
            filter: grayscale(0.3) brightness(0.9);
        }

        .date-card.card-0 {
            transform: translateX(0) translateY(0) scale(1) rotateY(0deg);
            z-index: 10;
            opacity: 1;
            filter: none;
        }

        .date-card.card-right {
            transform: translateX(160px) translateY(0) scale(0.8);
            z-index: 5;
            opacity: 0.4;
            filter: grayscale(0.3) brightness(0.9);
        }

        .date-card.card-hidden {
            transform: translateX(-400px) scale(0.5);
            z-index: 1;
            opacity: 0;
        }

        .date-card:hover.card-0 {
            transform: translateX(0) translateY(-8px) scale(1.02) rotateY(0deg);
            box-shadow: 0 15px 40px rgba(0, 122, 255, 0.4);
        }

        .date-card .day-name {
            font-size: 14px;
            font-weight: 600;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        .date-card .day-number {
            font-size: 56px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .date-card .month-year {
            font-size: 15px;
            font-weight: 500;
            opacity: 0.9;
        }

        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #007aff;
            transition: all 0.3s ease;
            z-index: 20;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-btn:hover {
            background: #007aff;
            color: white;
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 122, 255, 0.3);
        }

        .nav-btn.prev {
            left: -70px;
        }

        .nav-btn.next {
            right: -70px;
        }

        .nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .nav-btn:disabled:hover {
            transform: translateY(-50%) scale(1);
            background: white;
            color: #007aff;
        }

        .picker-mode-toggle {
            text-align: center;
            margin-top: 20px;
        }

        .mode-btn {
            background: #f2f2f7;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            color: #007aff;
            cursor: pointer;
            margin: 0 4px;
            transition: all 0.2s;
            font-weight: 500;
        }

        .mode-btn.active {
            background: #007aff;
            color: white;
        }

        .mode-btn:hover {
            background: #e5e5ea;
        }

        .mode-btn.active:hover {
            background: #0051d5;
        }

        /* Scroll Mode Styles - PRESERVED FROM ORIGINAL */
        .scroll-cards-container {
            display: none;
            overflow-x: auto;
            padding: 20px 10px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        .scroll-cards-container.active {
            display: block;
        }

        .scroll-cards {
            display: flex;
            gap: 15px;
            padding: 0 10px;
        }

        .scroll-card {
            flex-shrink: 0;
            width: 140px;
            height: 110px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 2px solid rgba(0, 122, 255, 0.15);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 122, 255, 0.08);
            user-select: none;
            position: relative;
        }

        .scroll-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 20px rgba(0, 122, 255, 0.18);
            border-color: rgba(0, 122, 255, 0.4);
        }

        .scroll-card.selected {
            background: linear-gradient(135deg, #007aff 0%, #0051d5 100%);
            color: white;
            border-color: #007aff;
            transform: translateY(-6px) scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 122, 255, 0.35);
        }

        .scroll-card.disabled {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            border: 2px solid rgba(255, 59, 48, 0.3);
            color: #ff3b30;
            cursor: not-allowed;
        }

        .scroll-card.disabled:hover {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(255, 59, 48, 0.15);
            border-color: rgba(255, 59, 48, 0.3);
        }

        .scroll-card.disabled.selected {
            background: linear-gradient(135deg, #ff3b30 0%, #cc0000 100%);
            color: white;
            border-color: #ff3b30;
        }

        .scroll-card .availability-indicator {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #34c759;
        }

        .scroll-card.disabled .availability-indicator {
            background: #ff3b30;
        }

        .scroll-card .day-name {
            font-size: 12px;
            font-weight: 600;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 6px;
        }

        .scroll-card.selected .day-name {
            opacity: 0.95;
        }

        .scroll-card .day-number {
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 4px;
        }

        .scroll-card .month-year {
            font-size: 11px;
            font-weight: 500;
            opacity: 0.7;
        }

        .scroll-card.selected .month-year {
            opacity: 0.95;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .container {
                padding: 16px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                padding: 20px;
            }

            .header-left, .header-right {
                width: 100%;
                justify-content: center;
            }

            .logo {
                font-size: 24px;
            }

            .page-title {
                font-size: 20px;
            }

            nav {
                flex-direction: column;
                padding: 10px;
            }

            nav a {
                width: 100%;
                justify-content: center;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .card-section {
                padding: 20px;
            }

            .section-title {
                font-size: 22px;
            }

            .date-picker-container {
                padding: 0 70px;
            }

            .card-stack {
                width: 100%;
                max-width: 500px;
                height: 150px;
            }

            .date-card {
                width: 180px;
                margin-left: -90px;
            }

            .date-card.card-left {
                transform: translateX(-120px) scale(0.75);
            }

            .date-card.card-right {
                transform: translateX(120px) scale(0.75);
            }

            .date-card .day-number {
                font-size: 42px;
            }

            .date-card .day-name {
                font-size: 12px;
            }

            .date-card .month-year {
                font-size: 13px;
            }

            .nav-btn {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }

            .nav-btn.prev {
                left: 10px;
            }

            .nav-btn.next {
                right: 10px;
            }

            .scroll-card {
                width: 120px;
                height: 100px;
            }

            .scroll-card .day-number {
                font-size: 28px;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .appointments-table {
                font-size: 13px;
            }

            .appointments-table th,
            .appointments-table td {
                padding: 12px;
            }
        }

        @media (max-width: 640px) {
            .user-info {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="animated-background"></div>

    <div class="container">
        <!-- Modern Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo">SkinCare</div>
                <div class="page-title">Book Appointment</div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($patient['full_name'], 0, 1)); ?>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($patient['full_name']); ?></span>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav>
            <a href="patient_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="book_appointment.php" class="nav-link active"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
            <a href="medical_records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
            <a href="patient_messages.php"><i class="fas fa-envelope"></i> Messages</a>
            <a href="patient_feedback_list.php"><i class="fas fa-comment-dots"></i> My Feedback</a>
            <a href="edit_profilePatient.php"><i class="fas fa-user-edit"></i><span>Edit Profile</span></a>
        </nav>

        <?php if (isset($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                <br><a href="patient_dashboard.php">Back to Dashboard</a>
            </div>
        <?php endif; ?>

        <!-- Available Services -->
        <div class="card-section">
            <h2 class="section-title">Available Services</h2>
            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                    <div class="service-card">
                        <div class="service-name"><?= htmlspecialchars($service['name']) ?></div>
                        <div class="service-desc"><?= htmlspecialchars($service['description']) ?></div>
                        <div class="service-meta">
                            <span class="service-duration"><i class="far fa-clock"></i> <?= htmlspecialchars($service['duration']) ?> min</span>
                            <span class="service-price">RM<?= htmlspecialchars($service['price']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Booking Form -->
        <div class="card-section">
            <h2 class="section-title">Schedule Your Appointment</h2>
            <div class="staff-info">
                <i class="fas fa-user-md"></i> <strong>Medical Staff:</strong> <?= htmlspecialchars($staff['full_name']) ?>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="book">

                <div class="form-group">
                    <label><i class="fas fa-stethoscope"></i> Select Service:</label>
                    <select name="service_id" required>
                        <option value="">Choose a service...</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= $service['service_id'] ?>" <?= ($_POST['service_id'] ?? '') == $service['service_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($service['name']) ?> - RM<?= htmlspecialchars($service['price']) ?> (<?= htmlspecialchars($service['duration']) ?> min)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; justify-content:space-between;">
                        <span><i class="fas fa-calendar-alt"></i> Appointment Date:</span>
                        <button type="button" id="showCalendarBtn" title="Show full calendar" style="background:none; border:none; cursor:pointer; padding:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24">
                                <rect x="3" y="5" width="18" height="16" rx="3" fill="#2c5f6f" opacity="0.12"/>
                                <rect x="3" y="5" width="18" height="16" rx="3" stroke="#2c5f6f" stroke-width="2"/>
                                <path d="M8 3v4M16 3v4" stroke="#2c5f6f" stroke-width="2" stroke-linecap="round"/>
                                <rect x="7" y="13" width="2" height="2" rx="1" fill="#2c5f6f"/>
                                <rect x="11" y="13" width="2" height="2" rx="1" fill="#2c5f6f"/>
                                <rect x="15" y="13" width="2" height="2" rx="1" fill="#2c5f6f"/>
                            </svg>
                        </button>
                    </label>

                    <!-- Mode Toggle -->
                    <div class="picker-mode-toggle">
                        <button type="button" class="mode-btn active" id="shuffleMode">Card Shuffle</button>
                        <button type="button" class="mode-btn" id="scrollMode">Scroll View</button>
                    </div>

                    <!-- Card Shuffle Mode -->
                    <div class="date-picker-container" id="cardShuffleContainer">
                        <button class="nav-btn prev" type="button" id="prevBtn">‹</button>
                        <div class="card-stack" id="cardStack">
                            <!-- Shuffle cards will be inserted here by JavaScript -->
                        </div>
                        <button class="nav-btn next" type="button" id="nextBtn">›</button>
                    </div>

                    <!-- Scroll Mode -->
                    <div class="scroll-cards-container" id="scrollContainer">
                        <div class="scroll-cards" id="scrollCards">
                            <!-- Scroll cards will be inserted here by JavaScript -->
                        </div>
                    </div>

                    <input type="hidden" name="date" id="selected-date" value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" required>
                    <div style="font-size: 14px; color: #8E8E93; margin-top: 8px; text-align: center;" id="selected-date-display">
                        Please select a date
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Appointment Time:</label>
                    <select name="time" id="time-select" required>
                        <option value="">Select a date first...</option>
                    </select>
                    <div id="time-loading" style="display:none; font-size:13px; color:#64748b; margin-top:6px;"><i class="fas fa-spinner fa-spin"></i> Loading available times...</div>
                    <div id="time-error" style="display:none; font-size:13px; color:#ff3b30; margin-top:6px;"></div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-notes-medical"></i> Additional Notes:</label>
                    <textarea name="notes" rows="4" placeholder="Any specific concerns or requests..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>

                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Book Appointment
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Clear Form
                    </button>
                </div>
            </form>
        </div>

        <!-- Upcoming Appointments -->
        <?php if ($upcoming_appointments): ?>
            <div class="card-section">
                <h2 class="section-title">Your Upcoming Appointments</h2>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Service</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_appointments as $apt): ?>
                            <tr>
                                <td><i class="far fa-calendar"></i> <?= htmlspecialchars($apt['date']) ?></td>
                                <td><i class="far fa-clock"></i> <?= htmlspecialchars($apt['time']) ?></td>
                                <td><?= htmlspecialchars($apt['service_name']) ?></td>
                                <td><strong>$<?= htmlspecialchars($apt['price']) ?></strong></td>
                                <td>
                                    <a href="cancel_appointment.php?id=<?= $apt['appointment_id'] ?>"
                                       onclick="return confirm('Are you sure you want to cancel this appointment?')"
                                       class="cancel-link">
                                       <i class="fas fa-times-circle"></i> Cancel
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Popup Calendar Modal -->
    <div id="calendarModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
        <div style="background:white; padding:32px 24px; border-radius:18px; box-shadow:0 8px 32px rgba(44,95,111,0.2); max-width:350px; width:100%; position:relative;">
            <button onclick="hideCalendar()" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:1.5rem; color:#2c5f6f; cursor:pointer; font-weight:bold;">&times;</button>
            <h3 style="color:#2c5f6f; margin-bottom:10px; text-align:center; font-family: 'Playfair Display', serif;"><i class="fas fa-calendar-alt"></i> Select Date</h3>
            <input type="date" id="calendarInput" style="width:100%; font-size:1.1rem; padding:12px; border-radius:8px; border:2px solid rgba(44, 95, 111, 0.2);">
            <div style="text-align:center; margin-top:18px;">
                <button type="button" class="btn btn-primary" onclick="chooseCalendarDate()">Choose</button>
            </div>
        </div>
    </div>

    <script>
        class DatePicker {
            constructor() {
                this.dates = [];
                this.currentIndex = 0;
                this.selectedDate = null;
                this.maxDates = 30;
                this.mode = 'shuffle'; // 'shuffle' or 'scroll'
                this.availability = {}; // Store availability data

                this.init();
            }

            init() {
                this.generateDates();
                this.fetchAvailability().then(() => {
                    this.renderShuffleMode();
                    this.renderScrollMode();
                    this.bindEvents();

                    // Check for pre-selected date from PHP
                    const hiddenInput = document.getElementById('selected-date');
                    if (hiddenInput && hiddenInput.value) {
                        this.setSelectedDate(hiddenInput.value);
                    }
                });
            }

            async fetchAvailability() {
                try {
                    const today = new Date();
                    const startDate = today.toISOString().split('T')[0];
                    const response = await fetch(`get_date_availability.php?start_date=${startDate}&days=${this.maxDates}`);
                    const data = await response.json();

                    if (data.success && data.availability) {
                        this.availability = data.availability;
                    }
                } catch (error) {
                    console.error('Error fetching date availability:', error);
                    // Continue without availability data - all dates will be treated as available
                }
            }

            isDateAvailable(date) {
                const dateStr = this.formatDateValue(date);
                // If no data for this date, assume it's available (backward compatibility)
                if (!this.availability[dateStr]) {
                    return true;
                }
                return this.availability[dateStr].available === true;
            }

            generateDates() {
                const today = new Date();
                for (let i = 0; i < this.maxDates; i++) {
                    const date = new Date(today);
                    date.setDate(today.getDate() + i);
                    this.dates.push(date);
                }
            }

            formatDateValue(date) {
                // Use local timezone instead of UTC to prevent date shifting
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            formatDateDisplay(date) {
                return date.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }

            createShuffleCard(date, index) {
                const card = document.createElement('div');
                card.className = 'date-card';
                card.setAttribute('data-index', index);
                card.setAttribute('data-date', this.formatDateValue(date));

                const isAvailable = this.isDateAvailable(date);
                if (!isAvailable) {
                    card.classList.add('disabled');
                }

                const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
                const dayNumber = date.getDate();
                const monthYear = date.toLocaleDateString('en-US', {
                    month: 'long',
                    year: 'numeric'
                });

                const badgeText = isAvailable ? 'Available' : 'Not Available';
                const badgeClass = isAvailable ? '' : ' disabled';

                card.innerHTML = `
                    <div class="availability-badge${badgeClass}">${badgeText}</div>
                    <div class="day-name">${dayName.toUpperCase()}</div>
                    <div class="day-number">${dayNumber}</div>
                    <div class="month-year">${monthYear}</div>
                `;

                // Only allow clicking if available
                if (isAvailable) {
                    card.addEventListener('click', () => {
                        this.selectDate(date);
                    });
                }

                return card;
            }

            createScrollCard(date, index) {
                const card = document.createElement('div');
                card.className = 'scroll-card';
                card.setAttribute('data-index', index);
                card.setAttribute('data-date', this.formatDateValue(date));

                const isAvailable = this.isDateAvailable(date);
                if (!isAvailable) {
                    card.classList.add('disabled');
                }

                const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
                const dayNumber = date.getDate();
                const monthYear = date.toLocaleDateString('en-US', {
                    month: 'short',
                    year: 'numeric'
                });

                card.innerHTML = `
                    <div class="availability-indicator"></div>
                    <div class="day-name">${dayName.toUpperCase()}</div>
                    <div class="day-number">${dayNumber}</div>
                    <div class="month-year">${monthYear}</div>
                `;

                // Only allow clicking if available
                if (isAvailable) {
                    card.addEventListener('click', () => {
                        this.selectDate(date);
                        this.updateScrollSelection();
                    });
                }

                return card;
            }

            renderShuffleMode() {
                const container = document.getElementById('cardStack');
                container.innerHTML = '';

                // Show three cards: previous (left), current (center), next (right)
                const positions = [
                    { index: this.currentIndex - 1, class: 'card-left' },
                    { index: this.currentIndex, class: 'card-0' },
                    { index: this.currentIndex + 1, class: 'card-right' }
                ];

                positions.forEach(pos => {
                    if (pos.index >= 0 && pos.index < this.dates.length) {
                        const card = this.createShuffleCard(this.dates[pos.index], pos.index);
                        card.classList.add(pos.class);
                        container.appendChild(card);
                    }
                });

                this.updateNavigationButtons();
            }

            renderScrollMode() {
                const container = document.getElementById('scrollCards');
                container.innerHTML = '';

                this.dates.forEach((date, index) => {
                    const card = this.createScrollCard(date, index);
                    container.appendChild(card);
                });
            }

            updateNavigationButtons() {
                const prevBtn = document.getElementById('prevBtn');
                const nextBtn = document.getElementById('nextBtn');

                prevBtn.disabled = this.currentIndex <= 0;
                nextBtn.disabled = this.currentIndex >= this.dates.length - 1;
            }

            updateScrollSelection() {
                const cards = document.querySelectorAll('.scroll-card');
                cards.forEach(card => {
                    card.classList.remove('selected');
                });

                if (this.selectedDate) {
                    const selectedDateStr = this.formatDateValue(this.selectedDate);
                    const selectedCard = document.querySelector(`.scroll-card[data-date="${selectedDateStr}"]`);
                    if (selectedCard) {
                        selectedCard.classList.add('selected');
                        selectedCard.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    }
                }
            }

            selectDate(date) {
                this.selectedDate = date;
                const dateValue = this.formatDateValue(date);

                // Update hidden input
                document.getElementById('selected-date').value = dateValue;

                // Update display text with availability status
                const displayElement = document.getElementById('selected-date-display');
                displayElement.textContent = this.formatDateDisplay(date);
                displayElement.style.color = '#34c759';
                displayElement.style.fontWeight = '500';

                // Update current index for shuffle mode
                const dateIndex = this.dates.findIndex(d => this.formatDateValue(d) === dateValue);
                if (dateIndex !== -1) {
                    this.currentIndex = dateIndex;
                    this.renderShuffleMode();
                }

                this.updateScrollSelection();

                // Fetch available times for the selected date
                if (typeof fetchAvailableTimes === 'function') {
                    fetchAvailableTimes(dateValue);
                }
            }

            setSelectedDate(dateString) {
                const date = new Date(dateString + 'T00:00:00');
                const dateIndex = this.dates.findIndex(d =>
                    this.formatDateValue(d) === dateString
                );

                if (dateIndex !== -1) {
                    this.selectedDate = date;
                    this.currentIndex = dateIndex;
                    this.renderShuffleMode();
                    this.updateScrollSelection();

                    // Update display
                    document.getElementById('selected-date-display').textContent =
                        this.formatDateDisplay(date);
                }
            }

            navigate(direction) {
                const newIndex = this.currentIndex + direction;

                if (newIndex >= 0 && newIndex < this.dates.length) {
                    this.currentIndex = newIndex;
                    this.selectDate(this.dates[this.currentIndex]);
                }
            }

            switchMode(newMode) {
                this.mode = newMode;
                const shuffleContainer = document.getElementById('cardShuffleContainer');
                const scrollContainer = document.getElementById('scrollContainer');
                const shuffleBtn = document.getElementById('shuffleMode');
                const scrollBtn = document.getElementById('scrollMode');

                if (newMode === 'shuffle') {
                    shuffleContainer.style.display = 'flex';
                    scrollContainer.classList.remove('active');
                    shuffleBtn.classList.add('active');
                    scrollBtn.classList.remove('active');
                } else {
                    shuffleContainer.style.display = 'none';
                    scrollContainer.classList.add('active');
                    scrollBtn.classList.add('active');
                    shuffleBtn.classList.remove('active');
                    this.updateScrollSelection();
                }
            }

            bindEvents() {
                // Navigation buttons
                document.getElementById('prevBtn').addEventListener('click', () => {
                    this.navigate(-1);
                });

                document.getElementById('nextBtn').addEventListener('click', () => {
                    this.navigate(1);
                });

                // Mode toggle buttons
                document.getElementById('shuffleMode').addEventListener('click', () => {
                    this.switchMode('shuffle');
                });

                document.getElementById('scrollMode').addEventListener('click', () => {
                    this.switchMode('scroll');
                });

                // Keyboard support - arrow keys for navigation
                document.addEventListener('keydown', (e) => {
                    // Only work if not typing in an input field
                    if (document.activeElement.tagName !== 'INPUT' &&
                        document.activeElement.tagName !== 'TEXTAREA' &&
                        document.activeElement.tagName !== 'SELECT') {

                        if (this.mode === 'shuffle') {
                            if (e.key === 'ArrowLeft') {
                                this.navigate(-1);
                                e.preventDefault();
                            } else if (e.key === 'ArrowRight') {
                                this.navigate(1);
                                e.preventDefault();
                            } else if (e.key === 'Enter' || e.key === ' ') {
                                // Select current card
                                const currentDate = this.dates[this.currentIndex];
                                if (currentDate && this.isDateAvailable(currentDate)) {
                                    this.selectDate(currentDate);
                                    e.preventDefault();
                                }
                            }
                        }
                    }
                });

                // Swipe support for shuffle mode
                let startX = 0;
                const cardStack = document.getElementById('cardStack');

                cardStack.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                });

                cardStack.addEventListener('touchend', (e) => {
                    const endX = e.changedTouches[0].clientX;
                    const diffX = startX - endX;

                    if (Math.abs(diffX) > 50) {
                        if (diffX > 0) {
                            this.navigate(1); // Swipe left = next
                        } else {
                            this.navigate(-1); // Swipe right = previous
                        }
                    }
                });
            }
        }

        // Fetch available times when date is selected
        function fetchAvailableTimes(selectedDate) {
            const timeSelect = document.getElementById('time-select');
            const timeLoading = document.getElementById('time-loading');
            const timeError = document.getElementById('time-error');

            // Check if the date is disabled using the date string directly
            if (window.datePicker && window.datePicker.availability) {
                const isAvailable = window.datePicker.availability[selectedDate];

                // Debug log
                console.log('Checking availability for:', selectedDate);
                console.log('Availability data:', isAvailable);

                if (isAvailable && isAvailable.available === false) {
                    // Date is disabled
                    timeLoading.style.display = 'none';
                    timeError.textContent = '⚠ This date has been disabled by the administrator. No appointments can be booked on this date. Please select another available date.';
                    timeError.style.display = 'block';
                    timeError.style.color = '#ff3b30';
                    timeError.style.fontWeight = '600';
                    timeSelect.innerHTML = '<option value="">Date Not Available</option>';
                    timeSelect.disabled = true;
                    timeSelect.style.backgroundColor = '#ffebee';
                    timeSelect.style.borderColor = '#ff3b30';
                    timeSelect.style.color = '#ff3b30';
                    return;
                }
            }

            // Reset styling for available dates
            timeSelect.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
            timeSelect.style.borderColor = 'rgba(44, 95, 111, 0.2)';
            timeSelect.style.color = '#000';

            // Show loading state
            timeLoading.style.display = 'block';
            timeError.style.display = 'none';
            timeSelect.disabled = true;
            timeSelect.innerHTML = '<option value="">Loading...</option>';

            // Fetch available times
            fetch('get_available_times.php?date=' + encodeURIComponent(selectedDate))
                .then(response => response.json())
                .then(data => {
                    timeLoading.style.display = 'none';

                    if (data.error) {
                        timeError.textContent = data.error;
                        timeError.style.display = 'block';
                        timeSelect.innerHTML = '<option value="">No times available</option>';
                        return;
                    }

                    if (data.times && data.times.length > 0) {
                        timeSelect.innerHTML = '<option value="">Select Time...</option>';
                        data.times.forEach(timeSlot => {
                            const option = document.createElement('option');
                            option.value = timeSlot.value;
                            option.textContent = timeSlot.label;
                            timeSelect.appendChild(option);
                        });
                        timeSelect.disabled = false;
                    } else {
                        timeSelect.innerHTML = '<option value="">No times available for this date</option>';
                        timeError.textContent = 'No available time slots for the selected date. Please choose another date.';
                        timeError.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error fetching available times:', error);
                    timeLoading.style.display = 'none';
                    timeError.textContent = 'Failed to load available times. Please try again.';
                    timeError.style.display = 'block';
                    timeSelect.innerHTML = '<option value="">Error loading times</option>';
                });
        }

        // Global variable to store datePicker instance
        let datePicker;

        // Initialize date picker when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            datePicker = new DatePicker();
            window.datePicker = datePicker;

            // Form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const dateInput = document.getElementById('selected-date');
                if (!dateInput.value) {
                    alert('Please select an appointment date.');
                    e.preventDefault();
                    return false;
                }

                const selectedDate = new Date(dateInput.value + 'T00:00:00');
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (selectedDate < today) {
                    alert('Please select a future date.');
                    e.preventDefault();
                    return false;
                }

                // Check if the selected date is available
                if (window.datePicker && !window.datePicker.isDateAvailable(selectedDate)) {
                    alert('The selected date is not available for appointments.\n\nThis date has been disabled by the clinic administrator. Please select another available date from the card picker.');
                    e.preventDefault();
                    return false;
                }

                // Check if it's a weekend
                const dayOfWeek = selectedDate.getDay();
                if (dayOfWeek === 0 || dayOfWeek === 6) {
                    if (!confirm('You selected a weekend day. Are you sure the clinic is open?')) {
                        e.preventDefault();
                        return false;
                    }
                }
            });

            // Calendar modal button
            document.getElementById('showCalendarBtn').onclick = function() {
                document.getElementById('calendarModal').style.display = 'flex';
                const today = new Date();
                // Use local timezone instead of UTC to prevent date shifting
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const minDate = `${year}-${month}-${day}`;
                const calendarInput = document.getElementById('calendarInput');
                calendarInput.min = minDate;
                calendarInput.value = document.getElementById('selected-date').value || minDate;

                // Add change listener to validate date selection
                calendarInput.onchange = function() {
                    const selectedDate = this.value;
                    if (selectedDate && window.datePicker) {
                        const date = new Date(selectedDate + 'T00:00:00');
                        if (!window.datePicker.isDateAvailable(date)) {
                            // Show visual feedback
                            this.style.borderColor = '#ff3b30';
                            this.style.backgroundColor = '#ffebee';

                            // Show warning message
                            let warning = document.getElementById('calendar-warning');
                            if (!warning) {
                                warning = document.createElement('div');
                                warning.id = 'calendar-warning';
                                warning.style.cssText = 'color: #ff3b30; font-size: 13px; margin-top: 10px; text-align: center; font-weight: 500;';
                                this.parentElement.appendChild(warning);
                            }
                            warning.textContent = 'This date is not available. Please select another date.';
                        } else {
                            // Reset styling if date is valid
                            this.style.borderColor = 'rgba(44, 95, 111, 0.2)';
                            this.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
                            const warning = document.getElementById('calendar-warning');
                            if (warning) {
                                warning.remove();
                            }
                        }
                    }
                };
            };
        });

        // Calendar modal functions
        function hideCalendar() {
            document.getElementById('calendarModal').style.display = 'none';
        }

        function chooseCalendarDate() {
            const calendarInput = document.getElementById('calendarInput');
            const selectedDate = calendarInput.value;
            if (selectedDate) {
                // Check if the date is available
                if (window.datePicker) {
                    const date = new Date(selectedDate + 'T00:00:00');
                    if (!window.datePicker.isDateAvailable(date)) {
                        // Prevent choosing disabled date
                        calendarInput.style.borderColor = '#ff3b30';
                        calendarInput.style.backgroundColor = '#ffebee';

                        let warning = document.getElementById('calendar-warning');
                        if (!warning) {
                            warning = document.createElement('div');
                            warning.id = 'calendar-warning';
                            warning.style.cssText = 'color: #ff3b30; font-size: 13px; margin-top: 10px; text-align: center; font-weight: 500;';
                            calendarInput.parentElement.appendChild(warning);
                        }
                        warning.textContent = '⚠ This date is not available. Please select another date.';
                        return;
                    }
                    hideCalendar();
                    if (typeof window.datePicker.setSelectedDate === 'function') {
                        window.datePicker.setSelectedDate(selectedDate);
                    }
                } else {
                    hideCalendar();
                }
            }
        }
    </script>
</body>
</html>
