<?php
require_once 'auth_check.php';
requirePatientLogin();

require_once 'db_connect.php';

$patient_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    header('Location: patient_dashboard.php');
    exit;
}

// Detect which datetime columns exist in appointment table
$cols = [];
$colRes = $pdo->query("SHOW COLUMNS FROM `appointment`");
if ($colRes) {
    while ($c = $colRes->fetch(PDO::FETCH_ASSOC)) {
        $cols[] = $c['Field'];
    }
}

// Build datetime expression based on available columns
if (in_array('appointment_datetime', $cols)) {
    $datetime_expr = 'a.appointment_datetime';
} elseif (in_array('appointment_date', $cols) && in_array('appointment_time', $cols)) {
    $datetime_expr = "CONCAT(a.appointment_date, ' ', a.appointment_time)";
} elseif (in_array('date', $cols) && in_array('time', $cols)) {
    $datetime_expr = "CONCAT(a.date, ' ', a.time)";
} elseif (in_array('appointment_date', $cols)) {
    $datetime_expr = 'a.appointment_date';
} elseif (in_array('date', $cols)) {
    $datetime_expr = 'a.date';
} else {
    $datetime_expr = 'NULL';
}

// Fetch appointment details - only for this patient
$stmt = $pdo->prepare("
    SELECT
        a.*,
        s.name as service_name,
        s.description as service_description,
        s.price as service_price,
        s.duration as service_duration,
        ms.full_name as staff_name,
        ms.email as staff_email,
        ms.phone as staff_phone,
        {$datetime_expr} as full_datetime
    FROM appointment a
    LEFT JOIN service s ON a.service_id = s.service_id
    LEFT JOIN medical_staff ms ON a.staff_id = ms.staff_id
    WHERE a.appointment_id = ? AND a.patient_id = ?
");
$stmt->execute([$appointment_id, $patient_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    header('Location: patient_dashboard.php');
    exit;
}

// Fetch medication records for this appointment
$stmt = $pdo->prepare("
    SELECT
        mr.*,
        ms.full_name as staff_name
    FROM medication_records mr
    LEFT JOIN medical_staff ms ON mr.recorded_by = ms.staff_id
    WHERE mr.appointment_id = ?
    ORDER BY mr.recorded_at DESC
");
$stmt->execute([$appointment_id]);
$medication_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format date and time
function formatDateTime($datetime) {
    if (!$datetime) return 'N/A';
    $dt = new DateTime($datetime);
    return $dt->format('F j, Y \a\t g:i A');
}

function formatDate($date) {
    if (!$date) return 'N/A';
    $dt = new DateTime($date);
    return $dt->format('F j, Y');
}

function formatDateShort($date) {
    if (!$date) return 'N/A';
    $dt = new DateTime($date);
    return $dt->format('M j, Y');
}

function formatTime($time) {
    if (!$time) return 'N/A';
    $dt = new DateTime($time);
    return $dt->format('g:i A');
}

function formatDayName($date) {
    if (!$date) return '';
    $dt = new DateTime($date);
    return $dt->format('l');
}

// Calculate days until/since appointment
function getDaysText($date) {
    if (!$date) return '';
    $apt_date = new DateTime($date);
    $today = new DateTime('today');
    $diff = $today->diff($apt_date);
    $days = $diff->days;

    if ($apt_date->format('Y-m-d') === $today->format('Y-m-d')) {
        return 'Today';
    } elseif ($diff->invert === 0) {
        // Future
        if ($days === 1) return 'Tomorrow';
        return "In {$days} days";
    } else {
        // Past
        if ($days === 1) return 'Yesterday';
        return "{$days} days ago";
    }
}

$days_text = getDaysText($appointment['date'] ?? null);
$is_upcoming = false;
$is_past = false;
if (isset($appointment['date'])) {
    $apt_date = new DateTime($appointment['date']);
    $today = new DateTime('today');
    $is_upcoming = $apt_date >= $today;
    $is_past = $apt_date < $today;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - True Beauty Concept</title>
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

        /* Deep Midnight Starry Sky Background */
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
                radial-gradient(circle at 92% 55%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 48% 72%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 8% 92%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 58% 28%, rgba(255, 255, 255, 0.6) 1px, transparent 1px);
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
            flex-wrap: wrap;
            gap: 1rem;
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
            text-decoration: none;
        }

        .logo i {
            color: var(--accent-color);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

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

        .btn-outline {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
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

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.25);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(239, 68, 68, 0.35);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }

        /* Hero Section */
        .hero-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .hero-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .hero-title-section {
            flex: 1;
        }

        .appointment-id {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.7rem;
            margin-bottom: 0.5rem;
        }

        .page-title i {
            color: var(--accent-color);
        }

        .days-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .days-badge.upcoming {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }

        .days-badge.past {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            color: #6b7280;
        }

        .days-badge.today {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        /* Status Badge */
        .status {
            padding: 0.8rem 1.8rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status.scheduled {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }

        .status.completed {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }

        .status.cancelled {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .summary-card {
            background: linear-gradient(135deg, rgba(44, 95, 111, 0.05), rgba(138, 180, 167, 0.05));
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(44, 95, 111, 0.1);
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .summary-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .summary-label {
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }

        .summary-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .summary-sub {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 0.3rem;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card */
        .card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(44, 95, 111, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
        }

        .card.full-width {
            grid-column: 1 / -1;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--bg-light);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            box-shadow: 0 8px 20px rgba(44, 95, 111, 0.3);
        }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Detail List */
        .detail-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(138, 180, 167, 0.05), rgba(212, 165, 116, 0.05));
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            background: linear-gradient(135deg, rgba(138, 180, 167, 0.1), rgba(212, 165, 116, 0.1));
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--accent-color);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }

        .detail-value {
            font-size: 1rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .detail-value strong {
            color: var(--primary-color);
        }

        /* Medical Records */
        .medical-record {
            background: linear-gradient(135deg, rgba(138, 180, 167, 0.08), rgba(212, 165, 116, 0.08));
            padding: 1.8rem;
            border-radius: 15px;
            border-left: 4px solid var(--secondary-color);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .medical-record:last-child {
            margin-bottom: 0;
        }

        .medical-record:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .record-title {
            font-size: 1.3rem;
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .record-title i {
            color: var(--accent-color);
        }

        .record-meta {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            font-size: 0.85rem;
            color: var(--text-light);
            text-align: right;
        }

        .record-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .record-meta-item i {
            color: var(--accent-color);
            width: 14px;
            text-align: center;
        }

        .record-details {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .record-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 1rem;
            border-radius: 10px;
        }

        .record-item-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .record-item-label i {
            color: var(--accent-color);
            width: 16px;
            text-align: center;
        }

        .record-item-value {
            color: var(--text-dark);
            line-height: 1.7;
        }

        .injection-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.2rem;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .injection-badge i {
            color: #3b82f6;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 1.05rem;
        }

        /* Actions */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .hero-top {
                flex-direction: column;
                text-align: center;
            }

            .page-title {
                font-size: 1.8rem;
                justify-content: center;
            }

            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .container {
                padding: 0 1rem 1rem;
            }

            .record-meta {
                text-align: left;
            }

            .record-meta-item {
                justify-content: flex-start;
            }
        }

        @media (max-width: 640px) {
            .logo {
                font-size: 1.4rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .btn {
                padding: 0.7rem 1.4rem;
                font-size: 0.9rem;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }

            .hero-section {
                padding: 1.5rem;
            }

            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-background"></div>

    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <a href="patient_dashboard.php" class="logo">
                <i class="fas fa-spa"></i>
                <span>True Beauty Concept</span>
            </a>
            <a href="patient_dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-top">
                <div class="hero-title-section">
                    <div class="appointment-id">
                        <i class="fas fa-hashtag"></i> Appointment #<?= htmlspecialchars($appointment_id) ?>
                    </div>
                    <h1 class="page-title">
                        <i class="fas fa-calendar-check"></i>
                        <span><?= htmlspecialchars($appointment['service_name'] ?? 'Appointment Details') ?></span>
                    </h1>
                    <?php if ($days_text): ?>
                        <span class="days-badge <?= $is_upcoming ? ($days_text === 'Today' ? 'today' : 'upcoming') : 'past' ?>">
                            <i class="fas fa-<?= $is_upcoming ? 'hourglass-half' : 'history' ?>"></i>
                            <?= $days_text ?>
                        </span>
                    <?php endif; ?>
                </div>
                <span class="status <?= strtolower(htmlspecialchars($appointment['status'])) ?>">
                    <i class="fas fa-<?= strtolower($appointment['status']) === 'completed' ? 'check-circle' : (strtolower($appointment['status']) === 'cancelled' ? 'times-circle' : 'clock') ?>"></i>
                    <?= htmlspecialchars(ucfirst($appointment['status'])) ?>
                </span>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="summary-label">Date</div>
                    <div class="summary-value"><?= htmlspecialchars(isset($appointment['date']) ? formatDateShort($appointment['date']) : 'N/A') ?></div>
                    <div class="summary-sub"><?= htmlspecialchars(isset($appointment['date']) ? formatDayName($appointment['date']) : '') ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="summary-label">Time</div>
                    <div class="summary-value"><?= htmlspecialchars(isset($appointment['time']) ? formatTime($appointment['time']) : 'N/A') ?></div>
                    <div class="summary-sub"><?= htmlspecialchars($appointment['service_duration'] ?? '30') ?> mins</div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="summary-label">Price</div>
                    <div class="summary-value">RM<?= htmlspecialchars($appointment['price'] ?? $appointment['service_price'] ?? '0.00') ?></div>
                    <div class="summary-sub">Total Amount</div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="summary-label">Staff</div>
                    <div class="summary-value"><?= htmlspecialchars($appointment['staff_name'] ?? 'Assigned Staff') ?></div>
                    <div class="summary-sub">Medical Professional</div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Service Details -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <h2 class="card-title">Service Details</h2>
                </div>

                <div class="detail-list">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Service Name</div>
                            <div class="detail-value"><strong><?= htmlspecialchars($appointment['service_name'] ?? 'N/A') ?></strong></div>
                        </div>
                    </div>

                    <?php if (!empty($appointment['service_description'])): ?>
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Description</div>
                            <div class="detail-value"><?= htmlspecialchars($appointment['service_description']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value"><?= htmlspecialchars($appointment['service_duration'] ?? '30') ?> minutes</div>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Price</div>
                            <div class="detail-value"><strong>RM<?= htmlspecialchars($appointment['price'] ?? $appointment['service_price'] ?? '0.00') ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointment Info -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h2 class="card-title">Appointment Info</h2>
                </div>

                <div class="detail-list">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Scheduled Date</div>
                            <div class="detail-value"><strong><?= htmlspecialchars(isset($appointment['date']) ? formatDate($appointment['date']) : 'N/A') ?></strong></div>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Scheduled Time</div>
                            <div class="detail-value"><strong><?= htmlspecialchars(isset($appointment['time']) ? formatTime($appointment['time']) : 'N/A') ?></strong></div>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Attending Staff</div>
                            <div class="detail-value"><?= htmlspecialchars($appointment['staff_name'] ?? 'Assigned Staff') ?></div>
                        </div>
                    </div>

                    <?php if (!empty($appointment['notes'])): ?>
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-sticky-note"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Your Notes</div>
                            <div class="detail-value"><?= nl2br(htmlspecialchars($appointment['notes'])) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_upcoming && strtolower($appointment['status']) === 'scheduled'): ?>
                <div class="action-buttons">
                    <a href="cancel_appointment.php?id=<?= $appointment_id ?>"
                       onclick="return confirm('Are you sure you want to cancel this appointment?')"
                       class="btn btn-danger">
                        <i class="fas fa-times-circle"></i>
                        Cancel Appointment
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Medical Records - Full Width -->
            <div class="card full-width">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                    <h2 class="card-title">Medical Records</h2>
                </div>

                <?php if (!empty($medication_records)): ?>
                    <?php foreach ($medication_records as $record): ?>
                        <div class="medical-record">
                            <div class="record-header">
                                <div class="record-title">
                                    <i class="fas fa-pills"></i>
                                    <?= htmlspecialchars($record['medication_name']) ?>
                                </div>
                                <div class="record-meta">
                                    <div class="record-meta-item">
                                        <i class="fas fa-user-md"></i>
                                        <span><?= htmlspecialchars($record['staff_name'] ?? 'Staff') ?></span>
                                    </div>
                                    <div class="record-meta-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span><?= formatDateTime($record['recorded_at']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="record-details">
                                <?php if (!empty($record['medication_steps'])): ?>
                                <div class="record-item">
                                    <div class="record-item-label">
                                        <i class="fas fa-list-ol"></i>
                                        <span>Treatment Instructions</span>
                                    </div>
                                    <div class="record-item-value">
                                        <?= nl2br(htmlspecialchars($record['medication_steps'])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($record['used_injection']): ?>
                                <div class="record-item">
                                    <div class="record-item-label">
                                        <i class="fas fa-syringe"></i>
                                        <span>Injection Used</span>
                                    </div>
                                    <div class="record-item-value">
                                        <span class="injection-badge">
                                            <i class="fas fa-syringe"></i>
                                            Quantity: <?= htmlspecialchars($record['injection_quantity']) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($record['notes'])): ?>
                                <div class="record-item">
                                    <div class="record-item-label">
                                        <i class="fas fa-comment-medical"></i>
                                        <span>Additional Notes</span>
                                    </div>
                                    <div class="record-item-value">
                                        <?= nl2br(htmlspecialchars($record['notes'])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-medical"></i>
                        <p>No medical records available for this appointment yet.</p>
                        <?php if (strtolower($appointment['status']) !== 'completed'): ?>
                            <p style="margin-top: 0.5rem; font-size: 0.95rem; color: var(--text-light);">
                                Medical records will be added after your appointment is completed.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
