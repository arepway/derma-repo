<?php
require_once 'auth_check.php';
requirePatientLogin();

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

$patient_id = $_SESSION['user_id'];

// Get patient info
$stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

// Get patient's appointments with counts
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointment WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$total_appointments = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointment WHERE patient_id = ? AND status = 'scheduled'");
$stmt->execute([$patient_id]);
$upcoming_appointments = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointment WHERE patient_id = ? AND status = 'Completed'");
$stmt->execute([$patient_id]);
$completed_appointments = $stmt->fetch()['total'];

// Get patient's appointments
$stmt = $pdo->prepare("
    SELECT a.*, s.name as service_name
    FROM appointment a
    JOIN service s ON a.service_id = s.service_id
    WHERE a.patient_id = ?
    ORDER BY a.date DESC, a.time DESC
    LIMIT 5
");
$stmt->execute([$patient_id]);
$recent_appointments = $stmt->fetchAll();

// Get unread messages count (only messages sent TO the patient by staff)
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM messages WHERE patient_id = ? AND is_read = 0 AND sender_type != 'patient'");
$stmt->execute([$patient_id]);
$unread_messages_count = $stmt->fetch()['total'];

// Get unread messages (only messages sent TO the patient by staff)
$stmt = $pdo->prepare("
    SELECT m.*
    FROM messages m
    WHERE m.patient_id = ? AND m.is_read = 0 AND m.sender_type != 'patient'
    ORDER BY m.timestamp DESC
    LIMIT 5
");
$stmt->execute([$patient_id]);
$unread_messages = $stmt->fetchAll();

// Handle logout
if (isset($_GET['logout'])) {
    header("Location: logout.php?type=patient");
    exit;
}

// Handle account deletion
if ($_POST['action'] ?? '' == 'delete_account') {
    $entered_password = $_POST['confirm_password'] ?? '';

    // Verify password before deletion
    if (password_verify($entered_password, $patient['password'])) {
        try {
            $pdo->beginTransaction();

            // Delete all related records in proper order (child tables first)

            // Delete medication records
            $stmt = $pdo->prepare("DELETE FROM medication_records WHERE appointment_id IN (SELECT appointment_id FROM appointment WHERE patient_id = ?)");
            $stmt->execute([$patient_id]);

            // Delete medical records
            $stmt = $pdo->prepare("DELETE FROM medical_record WHERE appointment_id IN (SELECT appointment_id FROM appointment WHERE patient_id = ?)");
            $stmt->execute([$patient_id]);

            // Delete appointments
            $stmt = $pdo->prepare("DELETE FROM appointment WHERE patient_id = ?");
            $stmt->execute([$patient_id]);

            // Delete messages
            $stmt = $pdo->prepare("DELETE FROM messages WHERE patient_id = ?");
            $stmt->execute([$patient_id]);

            // Finally delete patient account
            $stmt = $pdo->prepare("DELETE FROM patient WHERE patient_id = ?");
            $stmt->execute([$patient_id]);

            $pdo->commit();

            // Destroy session and redirect
            session_destroy();
            header("Location: index_patient.php?deleted=1");
            exit;

        } catch(PDOException $e) {
            $pdo->rollback();
            $delete_error = "Failed to delete account: " . $e->getMessage();
        }
    } else {
        $delete_error = "Incorrect password. Account not deleted.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - True Beauty Concept</title>
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
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
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

        /* Header */
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
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .user-details h3 {
            font-size: 1rem;
            color: white;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .user-details p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }


        /* Navigation */
        .nav-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-radius: 15px;
            padding: 1rem;
            margin: 0 auto 2rem;
            max-width: 1400px;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .nav-link {
            padding: 0.8rem 1.5rem;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .nav-link i {
            font-size: 1.1rem;
        }

        .notification-badge {
            position: relative;
            top: -2px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 0.3rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.6);
            }
        }

        .nav-link:hover .notification-badge {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            animation: none;
            transform: scale(1.1);
        }

        .nav-link.logout {
            color: var(--danger);
        }

        .nav-link.logout:hover {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .nav-link.delete-account {
            color: #dc2626;
        }

        .nav-link.delete-account:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stat-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(45deg);
            transition: all 0.5s ease;
        }

        .stat-card:hover .stat-icon::before {
            top: 150%;
            right: 150%;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
            position: relative;
            display: inline-block;
        }

        .stat-number::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 60%;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
            border-radius: 2px;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-number::after {
            opacity: 1;
            width: 100%;
        }

        .stat-label {
            color: var(--text-light);
            font-weight: 500;
            font-size: 1rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .stat-label::before {
            content: '\f0c8';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 0.6rem;
            color: var(--accent-color);
        }

        /* Weather Widget */
        .weather-widget {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 25px;
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 15px 50px rgba(102, 126, 234, 0.3);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .weather-widget::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float-bubble 15s ease-in-out infinite;
        }

        @keyframes float-bubble {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-30px, 30px) scale(1.1); }
        }

        .weather-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            position: relative;
            z-index: 1;
        }

        .weather-main {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .weather-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .weather-temp-display {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .weather-icon-large {
            font-size: 4rem;
            filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.2));
        }

        .weather-temp {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .weather-description {
            font-size: 1.2rem;
            text-transform: capitalize;
            opacity: 0.9;
        }

        .weather-details {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .uv-alert {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1rem;
            border-left: 4px solid #fbbf24;
        }

        .uv-alert.high {
            border-left-color: #f59e0b;
        }

        .uv-alert.very-high {
            border-left-color: #ef4444;
        }

        .uv-alert.extreme {
            border-left-color: #dc2626;
            animation: pulse-alert 2s ease-in-out infinite;
        }

        @keyframes pulse-alert {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        }

        .uv-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .uv-level {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .skincare-tip {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1rem;
            display: flex;
            align-items: start;
            gap: 0.75rem;
        }

        .tip-icon {
            font-size: 1.2rem;
            margin-top: 0.2rem;
        }

        .weather-extra {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .weather-stat {
            text-align: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .weather-stat-value {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .weather-stat-label {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .weather-loading {
            text-align: center;
            padding: 2rem;
            font-size: 1.1rem;
        }

        /* Section */
        .section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--bg-light);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .section-title i {
            color: var(--accent-color);
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, rgba(138, 180, 167, 0.1), rgba(212, 165, 116, 0.1));
            color: var(--primary-color);
            padding: 1rem;
            font-weight: 600;
            text-align: left;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 1.2rem 1rem;
            border-bottom: 1px solid var(--bg-light);
        }

        tr:hover {
            background: rgba(138, 180, 167, 0.05);
        }

        .status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status.scheduled {
            background: #fef3c7;
            color: #d97706;
        }

        .status.Completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status.cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
            padding: 2rem 0;
        }

        .action-card {
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .action-card:hover .action-icon {
            transform: scale(1.15) translateY(-5px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }

        .action-card:hover .action-title {
            color: var(--accent-color);
            transform: translateY(-3px);
        }

        .action-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: all 0.4s ease;
            position: relative;
        }

        .action-icon::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 50%;
            background: inherit;
            opacity: 0.3;
            filter: blur(10px);
            z-index: -1;
        }

        .action-icon::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            height: 90%;
            border-radius: 50%;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .action-title {
            font-weight: 600;
            font-size: 1rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .action-title span {
            display: block;
            text-align: center;
        }

        .action-title i {
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .action-card:hover .action-title i {
            opacity: 1;
        }

        /* Buttons */
        .btn {
            padding: 0.9rem 1.8rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 8px 20px rgba(44, 95, 111, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(44, 95, 111, 0.35);
        }

        .btn-link {
            color: var(--info);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-link:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--bg-light);
            margin-bottom: 1rem;
        }

        /* Alert */
        .alert {
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Messages */
        .messages-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .message-item {
            padding: 1.2rem;
            border-bottom: 1px solid var(--bg-light);
            display: flex;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .message-item:hover {
            background: rgba(138, 180, 167, 0.05);
        }

        .message-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
            font-size: 1.2rem;
        }

        .message-content {
            flex: 1;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .message-name {
            font-weight: 600;
            color: var(--primary-color);
        }

        .message-time {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .message-preview {
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .badge-new {
            background: var(--danger);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .nav-links {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .weather-content {
                grid-template-columns: 1fr;
            }

            .weather-extra {
                grid-template-columns: repeat(3, 1fr);
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .container {
                padding: 0 1rem 1rem;
            }
        }

        @media (max-width: 640px) {
            .logo {
                font-size: 1.4rem;
            }

            .section-title {
                font-size: 1.4rem;
            }

            .stat-number {
                font-size: 2rem;
            }
        }

        /* Delete Account Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
            position: relative;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--danger);
        }

        .modal-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--text-dark);
            font-weight: 700;
        }

        .modal-body {
            margin-bottom: 2rem;
        }

        .modal-warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .modal-warning p {
            margin: 0;
            color: #92400e;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .modal-info {
            margin-bottom: 1.5rem;
            color: var(--text-dark);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .modal-form-group {
            margin-bottom: 1.5rem;
        }

        .modal-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .modal-input {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 2px solid rgba(44, 95, 111, 0.2);
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .modal-input:focus {
            outline: none;
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .modal-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: none;
        }

        .modal-error.show {
            display: block;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 0.9rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .modal-btn-cancel {
            background: #f3f4f6;
            color: var(--text-dark);
        }

        .modal-btn-cancel:hover {
            background: #e5e7eb;
        }

        .modal-btn-delete {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .modal-btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-background"></div>

    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <div class="logo">
                    <i class="fas fa-spa"></i>
                    <span>True Beauty Concept</span>
                </div>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <h3><?= htmlspecialchars($patient['full_name']) ?></h3>
                    <p>Patient</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="container">
        <div class="nav-container">
            <div class="nav-links">
                <a href="patient_dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="book_appointment.php" class="nav-link">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Book Appointment</span>
                </a>
                <a href="medical_records.php" class="nav-link">
                    <i class="fas fa-file-medical"></i>
                    <span>Medical Records</span>
                </a>
                <a href="patient_messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php if ($unread_messages_count > 0): ?>
                        <span class="notification-badge"><?= $unread_messages_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="patient_feedback_list.php" class="nav-link">
                    <i class="fas fa-comment-dots"></i>
                    <span>My Feedback</span>
                </a>
                <a href="edit_profilePatient.php" class="nav-link">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="?logout=1" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Dashboard Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon blue">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $total_appointments ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon green">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $upcoming_appointments ?></div>
                <div class="stat-label">Upcoming Appointments</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon orange">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $unread_messages_count ?></div>
                <div class="stat-label">Unread Messages</div>
            </div>
        </div>

        <!-- Weather Widget with UV Index & Skincare Tips -->
        <div class="weather-widget" id="weatherWidget">
            <div class="weather-loading">
                <i class="fas fa-spinner fa-spin"></i> Loading weather data...
            </div>
        </div>

        <!-- Recent Appointments -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Appointments
                </h2>
            </div>

            <?php if ($recent_appointments): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_appointments as $apt): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($apt['date']) ?></strong></td>
                                    <td><?= htmlspecialchars($apt['time']) ?></td>
                                    <td><?= htmlspecialchars($apt['service_name']) ?></td>
                                    <td><span class="status <?= htmlspecialchars($apt['status']) ?>"><?= htmlspecialchars($apt['status']) ?></span></td>
                                    <td><strong>RM<?= htmlspecialchars($apt['price']) ?></strong></td>
                                    <td>
                                        <a href="appointment_details_patient.php?id=<?= $apt['appointment_id'] ?>" class="btn-link">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>No appointments yet. Book your first appointment!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Messages -->
        <?php if ($unread_messages): ?>
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-comments"></i>
                    Unread Messages
                </h2>
                <a href="patient_messages.php" class="btn btn-primary">
                    <span>View All</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="messages-list">
                <?php foreach ($unread_messages as $msg): ?>
                    <div class="message-item">
                        <div class="message-avatar">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-name">
                                    Staff Message
                                    <span class="badge-new">NEW</span>
                                </span>
                                <span class="message-time"><?= htmlspecialchars($msg['timestamp']) ?></span>
                            </div>
                            <p class="message-preview">
                                <?= htmlspecialchars(substr($msg['message'], 0, 100)) ?>...
                            </p>
                        </div>
                        <a href="patient_messages.php?id=<?= $msg['message_id'] ?>" class="btn-link">
                            <i class="fas fa-reply"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h2>
            </div>

            <div class="quick-actions">
                <a href="book_appointment.php" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="action-title">
                        <span>Book Appointment</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <a href="medical_records.php" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <div class="action-title">
                        <span>Medical Records</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <a href="patient_messages.php" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="action-title">
                        <span>Messages</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <a href="edit_profilePatient.php" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="action-title">
                        <span>Edit Profile</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <a href="#" class="action-card" onclick="openDeleteModal(); return false;">
                    <div class="action-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="action-title">
                        <span>Delete Account</span>
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2 class="modal-title">Delete Account</h2>
            </div>

            <div class="modal-body">
                <div class="modal-warning">
                    <p><strong>Warning:</strong> This action cannot be undone. All your data including appointments, medical records, and messages will be permanently deleted.</p>
                </div>

                <div class="modal-info">
                    <p>To confirm deletion, please enter your password:</p>
                </div>

                <?php if (isset($delete_error)): ?>
                <div class="modal-error show">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($delete_error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="deleteAccountForm">
                    <input type="hidden" name="action" value="delete_account">
                    <div class="modal-form-group">
                        <label class="modal-label" for="confirm_password">
                            <i class="fas fa-lock"></i> Your Password
                        </label>
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            class="modal-input"
                            placeholder="Enter your password to confirm"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">
                            Cancel
                        </button>
                        <button type="submit" class="modal-btn modal-btn-delete">
                            <i class="fas fa-trash-alt"></i> Delete My Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Delete Account Modal Functions
        function openDeleteModal() {
            document.getElementById('deleteModal').classList.add('active');
            document.getElementById('confirm_password').value = '';
            document.getElementById('confirm_password').focus();
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            document.getElementById('confirm_password').value = '';
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });

        // Auto-open modal if there's a delete error
        <?php if (isset($delete_error)): ?>
        window.addEventListener('load', function() {
            openDeleteModal();
        });
        <?php endif; ?>

        // Weather Widget with UV Index and Skincare Tips - Using WeatherAPI.com
        // Get your FREE API key at: https://www.weatherapi.com/
        const WEATHER_API_KEY = '0612c88d5171419aa10102503252011'; // Replace with your WeatherAPI.com key

        async function fetchWeatherData() {
            const weatherWidget = document.getElementById('weatherWidget');

            try {
                if (!navigator.geolocation) {
                    throw new Error('Geolocation is not supported');
                }

                navigator.geolocation.getCurrentPosition(async (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;

                    try {
                        const response = await fetch(
                            `https://api.weatherapi.com/v1/current.json?key=${WEATHER_API_KEY}&q=${lat},${lon}&aqi=yes`
                        );
                        const data = await response.json();

                        if (data.error) {
                            throw new Error(data.error.message || 'Weather API error');
                        }

                        displayWeather(data);
                    } catch (err) {
                        console.error('API error:', err);
                        fetchWeatherByCity('Manila');
                    }
                }, (error) => {
                    console.log('Geolocation denied, using default city');
                    fetchWeatherByCity('Manila');
                }, {
                    timeout: 10000,
                    enableHighAccuracy: false
                });

            } catch (error) {
                console.error('Weather fetch error:', error);
                weatherWidget.innerHTML = `
                    <div class="weather-loading">
                        <i class="fas fa-exclamation-triangle"></i>
                        Unable to load weather data. Using default location...
                    </div>
                `;
                setTimeout(() => fetchWeatherByCity('Manila'), 1000);
            }
        }

        async function fetchWeatherByCity(city) {
            try {
                const response = await fetch(
                    `https://api.weatherapi.com/v1/current.json?key=${WEATHER_API_KEY}&q=${city}&aqi=yes`
                );
                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error.message || 'City not found');
                }

                displayWeather(data);
            } catch (error) {
                console.error('City weather fetch error:', error);
                document.getElementById('weatherWidget').innerHTML = `
                    <div class="weather-loading">
                        <i class="fas fa-cloud-sun"></i> Weather data unavailable. Please check your API key or internet connection.
                    </div>
                `;
            }
        }

        function displayWeather(data) {
            const weatherWidget = document.getElementById('weatherWidget');
            const current = data.current;
            const location = data.location;

            const temp = Math.round(current.temp_c);
            const feelsLike = Math.round(current.feelslike_c);
            const humidity = current.humidity;
            const windSpeed = (current.wind_kph / 3.6).toFixed(1); // Convert to m/s
            const description = current.condition.text;
            const uvIndex = current.uv;
            const isDay = current.is_day;

            // Get weather icon
            const weatherIcon = getWeatherIcon(current.condition.code, isDay);

            // Get UV level and tips
            const uvLevel = getUVLevel(uvIndex);
            const skincareTip = getSkincareTip(temp, humidity, uvIndex, description, current.precip_mm);

            weatherWidget.innerHTML = `
                <div class="weather-content">
                    <div class="weather-main">
                        <div class="weather-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${location.name}, ${location.country}</span>
                        </div>
                        <div class="weather-temp-display">
                            <div class="weather-icon-large">${weatherIcon}</div>
                            <div>
                                <div class="weather-temp">${temp}°C</div>
                                <div class="weather-description">${description}</div>
                            </div>
                        </div>
                        <div class="weather-extra">
                            <div class="weather-stat">
                                <div class="weather-stat-value">${feelsLike}°C</div>
                                <div class="weather-stat-label">Feels Like</div>
                            </div>
                            <div class="weather-stat">
                                <div class="weather-stat-value">${humidity}%</div>
                                <div class="weather-stat-label">Humidity</div>
                            </div>
                            <div class="weather-stat">
                                <div class="weather-stat-value">${windSpeed} m/s</div>
                                <div class="weather-stat-label">Wind</div>
                            </div>
                        </div>
                    </div>
                    <div class="weather-details">
                        <div class="uv-alert ${uvLevel.class}">
                            <div class="uv-header">
                                <div>
                                    <i class="fas fa-sun"></i> UV Index
                                </div>
                                <div class="uv-level">${uvIndex.toFixed(1)}</div>
                            </div>
                            <div><strong>${uvLevel.label}</strong></div>
                            <div style="font-size: 0.9rem; margin-top: 0.5rem;">${uvLevel.message}</div>
                        </div>
                        <div class="skincare-tip">
                            <div class="tip-icon">${skincareTip.icon}</div>
                            <div>
                                <div style="font-weight: 600; margin-bottom: 0.25rem;">Skincare Tip</div>
                                <div style="font-size: 0.9rem;">${skincareTip.message}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function getWeatherIcon(code, isDay) {
            // WeatherAPI.com condition codes
            const dayIcons = {
                1000: '☀️', // Sunny
                1003: '⛅', // Partly cloudy
                1006: '☁️', // Cloudy
                1009: '☁️', // Overcast
                1030: '🌫️', // Mist
                1063: '🌦️', // Patchy rain
                1066: '🌨️', // Patchy snow
                1069: '🌧️', // Patchy sleet
                1072: '🌧️', // Patchy freezing drizzle
                1087: '⛈️', // Thundery outbreaks
                1114: '❄️', // Blowing snow
                1117: '❄️', // Blizzard
                1135: '🌫️', // Fog
                1147: '🌫️', // Freezing fog
                1150: '🌧️', // Light drizzle
                1153: '🌧️', // Light drizzle
                1168: '🌧️', // Freezing drizzle
                1171: '🌧️', // Heavy freezing drizzle
                1180: '🌦️', // Light rain
                1183: '🌧️', // Light rain
                1186: '🌧️', // Moderate rain
                1189: '🌧️', // Moderate rain
                1192: '🌧️', // Heavy rain
                1195: '🌧️', // Heavy rain
                1198: '🌧️', // Light freezing rain
                1201: '🌧️', // Moderate freezing rain
                1204: '🌨️', // Light sleet
                1207: '🌨️', // Moderate sleet
                1210: '🌨️', // Light snow
                1213: '❄️', // Light snow
                1216: '❄️', // Moderate snow
                1219: '❄️', // Moderate snow
                1222: '❄️', // Heavy snow
                1225: '❄️', // Heavy snow
                1237: '🌨️', // Ice pellets
                1240: '🌦️', // Light rain shower
                1243: '🌧️', // Moderate rain shower
                1246: '🌧️', // Torrential rain
                1249: '🌨️', // Light sleet showers
                1252: '🌨️', // Moderate sleet showers
                1255: '🌨️', // Light snow showers
                1258: '❄️', // Moderate snow showers
                1261: '🌨️', // Light ice pellets
                1264: '🌨️', // Moderate ice pellets
                1273: '⛈️', // Light rain with thunder
                1276: '⛈️', // Moderate rain with thunder
                1279: '⛈️', // Light snow with thunder
                1282: '⛈️'  // Moderate snow with thunder
            };

            const nightIcons = {
                1000: '🌙', // Clear
                1003: '☁️', // Partly cloudy
            };

            if (!isDay && nightIcons[code]) {
                return nightIcons[code];
            }
            return dayIcons[code] || '🌤️';
        }

        function getUVLevel(uvIndex) {
            if (uvIndex < 3) {
                return {
                    label: 'Low',
                    class: '',
                    message: 'Minimal sun protection needed. Safe for outdoor activities!'
                };
            } else if (uvIndex < 6) {
                return {
                    label: 'Moderate',
                    class: '',
                    message: 'Apply SPF 30+ sunscreen. Seek shade during peak hours (10 AM - 2 PM).'
                };
            } else if (uvIndex < 8) {
                return {
                    label: 'High',
                    class: 'high',
                    message: 'SPF 50+ essential! Wear protective clothing, hat, and UV-blocking sunglasses.'
                };
            } else if (uvIndex < 11) {
                return {
                    label: 'Very High',
                    class: 'very-high',
                    message: 'Avoid outdoor exposure 10 AM - 4 PM. Maximum protection required!'
                };
            } else {
                return {
                    label: 'Extreme',
                    class: 'extreme',
                    message: 'DANGER! Stay indoors if possible. Unprotected skin can burn in minutes!'
                };
            }
        }

        function getSkincareTip(temp, humidity, uvIndex, description, precip) {
            const descLower = description.toLowerCase();

            // Extreme UV - highest priority
            if (uvIndex >= 8) {
                const tips = [
                    { icon: '🧴', message: 'Extreme UV alert! Use SPF 50+ with PA++++ protection. Reapply every 90 minutes and avoid direct sunlight.' },
                    { icon: '🕶️', message: 'Very high UV detected! Protect your eyes with UV400 sunglasses and apply sunscreen around eye area.' },
                    { icon: '👒', message: 'Dangerous UV levels! Wide-brim hat, long sleeves, and mineral sunscreen recommended today.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // High UV
            if (uvIndex >= 6) {
                const tips = [
                    { icon: '🧴', message: 'High UV today! Apply broad-spectrum SPF 50+ sunscreen 15 mins before going out. Don\'t forget ears and neck!' },
                    { icon: '☂️', message: 'UV index is elevated. Carry a UV-protection umbrella and reapply sunscreen every 2 hours.' },
                    { icon: '🧥', message: 'Protect your skin with UPF clothing today. Apply antioxidant serum (Vitamin C) before sunscreen.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // Hot and humid (tropical conditions)
            if (temp > 30 && humidity > 70) {
                const tips = [
                    { icon: '💧', message: 'Hot & humid! Use oil-free, non-comedogenic products. Blotting papers and mattifying primer will help.' },
                    { icon: '🧊', message: 'Tropical heat alert! Double cleanse tonight and use a cooling gel mask. Stay in AC when possible.' },
                    { icon: '🍃', message: 'Humidity high! Swap cream moisturizer for water-based gel. Try products with niacinamide for oil control.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // Hot weather
            if (temp > 30) {
                const tips = [
                    { icon: '💧', message: 'Stay hydrated! Drink 8+ glasses of water and use a hydrating mist spray throughout the day.' },
                    { icon: '🥒', message: 'Hot weather tip: Store your toner in the fridge for a cooling effect. Use light, breathable products.' },
                    { icon: '🍉', message: 'Beat the heat! Eat water-rich fruits and apply aloe vera gel to soothe sun-exposed skin.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // Cold and dry
            if (temp < 15 && humidity < 50) {
                const tips = [
                    { icon: '🧴', message: 'Cold & dry conditions! Layer hydration: essence → serum → rich cream. Don\'t skip SPF even in winter!' },
                    { icon: '💋', message: 'Protect from cold! Use a heavier occlusive moisturizer and apply lip balm with SPF frequently.' },
                    { icon: '🛁', message: 'Winter skin care: Avoid hot showers, use lukewarm water. Apply body oil right after bathing.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // Cold weather
            if (temp < 15) {
                const tips = [
                    { icon: '🧴', message: 'Cold weather dries skin. Use a ceramide-rich moisturizer and consider adding facial oil to your routine.' },
                    { icon: '🧣', message: 'Protect exposed skin from cold wind. Apply petroleum jelly on lips and dry patches before going out.' },
                    { icon: '🏠', message: 'Indoor heating dehydrates skin. Use a humidifier and spritz hydrating toner throughout the day.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // Rainy weather
            if (descLower.includes('rain') || descLower.includes('drizzle') || precip > 0) {
                const tips = [
                    { icon: '🌧️', message: 'Rainy day! Humidity is high. Use lightweight products and skip heavy foundations to let skin breathe.' },
                    { icon: '☔', message: 'Wet weather tip: Waterproof your makeup! Use setting spray and waterproof mascara if going out.' },
                    { icon: '🏠', message: 'Perfect indoor skincare day! Try a sheet mask or do a double-masking routine for extra glow.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // Very high humidity
            if (humidity > 80) {
                const tips = [
                    { icon: '💨', message: 'Very humid today! Use gel-based products and salicylic acid cleanser to prevent breakouts.' },
                    { icon: '🧪', message: 'High humidity = oily skin. Use a clay mask tonight and switch to a lighter moisturizer.' },
                    { icon: '✨', message: 'Humid conditions! Skip heavy creams, use niacinamide serum for pore control and oil balance.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // Very low humidity
            if (humidity < 30) {
                const tips = [
                    { icon: '💦', message: 'Very dry air! Layer hyaluronic acid serum while skin is damp, then seal with moisturizer.' },
                    { icon: '🥛', message: 'Low humidity alert! Use a sleeping mask tonight and drink extra water to combat dehydration.' },
                    { icon: '🌿', message: 'Dry conditions! Add a few drops of squalane or rosehip oil to your moisturizer for extra nourishment.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // Moderate humidity (40-60%) - ideal range
            if (humidity >= 40 && humidity <= 60) {
                const tips = [
                    { icon: '✨', message: 'Perfect humidity for skin! Great day for your regular routine. Consider trying a new serum or treatment.' },
                    { icon: '🌸', message: 'Ideal weather conditions! Your skin barrier is happy. Focus on antioxidant protection today.' },
                    { icon: '💆', message: 'Optimal humidity! Perfect time for gentle exfoliation or a vitamin C treatment for brightening.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // Cloudy/overcast
            if (descLower.includes('cloud') || descLower.includes('overcast')) {
                const tips = [
                    { icon: '☁️', message: 'Cloudy doesn\'t mean no UV! Apply SPF 30+ - up to 80% of UV rays penetrate clouds.' },
                    { icon: '🧴', message: 'Overcast sky? Perfect for using retinol or AHA products - less sun sensitivity risk.' },
                    { icon: '📸', message: 'Great lighting for skincare selfies! Diffused light shows your skin\'s true texture.' }
                ];
                return tips[Math.floor(Math.random() * tips.length)];
            }

            // Default tips for pleasant weather
            const defaultTips = [
                { icon: '✨', message: 'Beautiful day! Stick to your AM routine: cleanser → toner → serum → moisturizer → SPF.' },
                { icon: '🌿', message: 'Great weather! Consider adding antioxidants like green tea or vitamin E to protect from pollution.' },
                { icon: '💆', message: 'Nice conditions! Tonight, treat yourself to a gentle exfoliation and hydrating mask.' },
                { icon: '🌞', message: 'Lovely weather! Remember: SPF is non-negotiable. Reapply every 2 hours if outdoors.' },
                { icon: '🥗', message: 'Healthy skin starts within! Eat colorful fruits and veggies rich in vitamins A, C, and E today.' }
            ];
            return defaultTips[Math.floor(Math.random() * defaultTips.length)];
        }

        // Load weather data when page loads
        window.addEventListener('load', fetchWeatherData);

        // Refresh weather data every 30 minutes
        setInterval(fetchWeatherData, 30 * 60 * 1000);
    </script>
</body>
</html>
