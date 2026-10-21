<?php
require_once 'auth_check.php';
requireStaffLogin();

require_once 'db_connect.php';

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    $_SESSION['error_msg'] = "Invalid patient ID.";
    header('Location: manage_patients.php');
    exit;
}

// Fetch patient details
$stmt = $pdo->prepare("
    SELECT patient_id, full_name, email, phone, date_of_birth, sex
    FROM patient
    WHERE patient_id = ?
");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    $_SESSION['error_msg'] = "Patient not found.";
    header('Location: manage_patients.php');
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

// Fetch appointments for this patient
$stmt = $pdo->prepare("
    SELECT
        a.appointment_id,
        a.status,
        a.notes,
        s.name as service_name,
        s.description as service_description,
        s.price as service_price,
        s.duration as service_duration,
        {$datetime_expr} as full_datetime
    FROM appointment a
    LEFT JOIN service s ON a.service_id = s.service_id
    WHERE a.patient_id = ?
    ORDER BY {$datetime_expr} DESC
");
$stmt->execute([$patient_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format date and time
function formatDateTime($datetime) {
    if (empty($datetime)) return 'N/A';
    try {
        $dt = new DateTime($datetime);
        return $dt->format('F j, Y • g:i A');
    } catch (Exception $e) {
        return htmlspecialchars($datetime);
    }
}

function formatDate($date) {
    if (empty($date)) return 'N/A';
    try {
        $dt = new DateTime($date);
        return $dt->format('F j, Y');
    } catch (Exception $e) {
        return htmlspecialchars($date);
    }
}

function calculateAge($dob) {
    if (empty($dob)) return 'N/A';
    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        return $age . ' years';
    } catch (Exception $e) {
        return 'N/A';
    }
}

function categorizeAppointment($dtStr) {
    if (empty($dtStr)) return 'past';
    $now = new DateTime();
    try {
        $dt = new DateTime($dtStr);
    } catch (Exception $e) {
        return 'past';
    }
    if ($dt < $now) return 'past';
    $today = $now->format('Y-m-d');
    if ($dt->format('Y-m-d') === $today) return 'current';
    return 'future';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment History - <?= htmlspecialchars($patient['full_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c5f6f;
            --secondary-color: #8ab4a7;
            --accent-color: #d4a574;
            --text-dark: #1a1a1a;
            --text-light: #636e72;
            --bg-light: #f8f9fa;
            --success: #34c759;
            --warning: #ff9500;
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
            position: relative;
            overflow-x: hidden;
            color: var(--text-dark);
        }

        /* Animated Nebula Glow Background */
        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #050508 0%, #0a0510 50%, #05080a 100%);
            z-index: -1;
            overflow: hidden;
        }

        .animated-background::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(ellipse at 20% 30%, rgba(138, 43, 226, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(0, 191, 255, 0.12) 0%, transparent 50%),
                radial-gradient(ellipse at 40% 80%, rgba(255, 105, 180, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 90% 70%, rgba(50, 205, 50, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 10% 90%, rgba(255, 165, 0, 0.1) 0%, transparent 50%);
            animation: nebulaFloat 20s ease-in-out infinite;
        }

        .animated-background::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(ellipse at 60% 50%, rgba(147, 112, 219, 0.12) 0%, transparent 45%),
                radial-gradient(ellipse at 30% 60%, rgba(64, 224, 208, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 30%, rgba(255, 20, 147, 0.08) 0%, transparent 45%);
            animation: nebulaFloat 25s ease-in-out infinite reverse;
        }

        @keyframes nebulaFloat {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(2%, 3%) rotate(1deg); }
            50% { transform: translate(-1%, 2%) rotate(-1deg); }
            75% { transform: translate(1%, -2%) rotate(0.5deg); }
        }

        /* Nebula Particles */
        .nebula-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .nebula-particles::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(2px 2px at 20px 30px, rgba(255, 255, 255, 0.4), transparent),
                radial-gradient(2px 2px at 40px 70px, rgba(255, 255, 255, 0.3), transparent),
                radial-gradient(1px 1px at 90px 40px, rgba(255, 255, 255, 0.4), transparent),
                radial-gradient(2px 2px at 130px 80px, rgba(255, 255, 255, 0.3), transparent),
                radial-gradient(1px 1px at 160px 120px, rgba(255, 255, 255, 0.5), transparent);
            background-size: 200px 200px;
            animation: twinkle 4s ease-in-out infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.8; }
        }

        /* Header - Liquid Glass */
        .page-header {
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
            gap: 20px;
            flex-wrap: wrap;
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

        .page-title-section {
            border-left: 3px solid var(--accent-color);
            padding-left: 20px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            margin-bottom: 4px;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.8);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            font-size: 0.85rem;
            font-weight: 400;
        }

        .back-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(44, 95, 111, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44, 95, 111, 0.4);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
            position: relative;
            z-index: 1;
        }

        /* Patient Info Card - Liquid Glass */
        .patient-info-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .patient-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .patient-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(44, 95, 111, 0.3);
        }

        .patient-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }

        .patient-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .detail-label {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .detail-label i {
            color: var(--accent-color);
        }

        .detail-value {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        /* Stats Cards - Liquid Glass */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        .stat-card.current {
            border-left: 5px solid var(--warning);
        }

        .stat-card.future {
            border-left: 5px solid var(--success);
        }

        .stat-card.past {
            border-left: 5px solid rgba(255, 255, 255, 0.3);
        }

        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Appointments List - Liquid Glass */
        .appointments-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .section-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            box-shadow: 0 4px 15px rgba(44, 95, 111, 0.3);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 600;
            color: white;
        }

        .appointments-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .appointment-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-left: 5px solid;
        }

        .appointment-card:hover {
            transform: translateX(6px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .appointment-card.current {
            border-left-color: var(--warning);
            background: rgba(245, 158, 11, 0.1);
        }

        .appointment-card.future {
            border-left-color: var(--success);
            background: rgba(16, 185, 129, 0.1);
        }

        .appointment-card.past {
            border-left-color: rgba(255, 255, 255, 0.3);
        }

        .appointment-info {
            flex: 1;
        }

        .appointment-datetime {
            font-size: 16px;
            font-weight: 600;
            color: white;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .appointment-datetime i {
            color: var(--accent-color);
        }

        .appointment-service {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .appointment-service i {
            color: var(--accent-color);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.scheduled {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
        }

        .status-badge.completed {
            background: linear-gradient(135deg, var(--success), #2ba84a);
            color: white;
        }

        .status-badge.cancelled {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .appointment-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 4px 12px rgba(44, 95, 111, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(44, 95, 111, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
            color: rgba(255, 255, 255, 0.5);
        }

        .empty-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
        }

        .empty-text {
            font-size: 16px;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Responsive */
        @media (max-width: 968px) {
            .header-content {
                flex-direction: column;
                align-items: stretch;
            }

            .header-left {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .page-title-section {
                border-left: none;
                border-top: 3px solid var(--accent-color);
                padding-left: 0;
                padding-top: 15px;
            }

            .back-btn {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .patient-details {
                grid-template-columns: 1fr;
            }

            .appointment-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .appointment-actions {
                width: 100%;
            }

            .btn {
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .container {
                padding: 0 1rem 1rem;
            }

            .page-header {
                padding: 1rem 0;
            }

            .header-content {
                padding: 0 1rem;
            }

            h1 {
                font-size: 1.3rem;
            }

            .logo {
                font-size: 1.5rem;
            }

            .patient-info-card, .appointments-section {
                padding: 20px;
            }

            .stat-number {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="animated-background"></div>
    <div class="nebula-particles"></div>

    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-left">
                <div class="logo">
                    <i class="fas fa-spa"></i>
                    <span>True Beauty Concept</span>
                </div>
                <div class="page-title-section">
                    <h1><i class="fas fa-history"></i> Appointment History</h1>
                    <p class="subtitle">View all appointments for <?= htmlspecialchars($patient['full_name']) ?></p>
                </div>
            </div>
            <a href="manage_patients.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Patients</span>
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Patient Information Card -->
        <div class="patient-info-card">
            <div class="patient-header">
                <div class="patient-avatar">
                    <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                </div>
                <h2 class="patient-title"><?= htmlspecialchars($patient['full_name']) ?></h2>
            </div>

            <div class="patient-details">
                <div class="detail-item">
                    <span class="detail-label">
                        <i class="fas fa-envelope"></i> Email
                    </span>
                    <span class="detail-value"><?= htmlspecialchars($patient['email']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">
                        <i class="fas fa-phone"></i> Phone
                    </span>
                    <span class="detail-value"><?= htmlspecialchars($patient['phone']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">
                        <i class="fas fa-birthday-cake"></i> Date of Birth
                    </span>
                    <span class="detail-value"><?= formatDate($patient['date_of_birth']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">
                        <i class="fas fa-calendar-alt"></i> Age
                    </span>
                    <span class="detail-value"><?= calculateAge($patient['date_of_birth']) ?></span>
                </div>
                <?php if (!empty($patient['sex'])): ?>
                <div class="detail-item">
                    <span class="detail-label">
                        <i class="fas fa-venus-mars"></i> Sex
                    </span>
                    <span class="detail-value"><?= htmlspecialchars(ucfirst($patient['sex'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics -->
        <?php
            $current_count = 0;
            $future_count = 0;
            $past_count = 0;
            foreach ($appointments as $app) {
                $cat = categorizeAppointment($app['full_datetime']);
                if ($cat === 'current') $current_count++;
                elseif ($cat === 'future') $future_count++;
                else $past_count++;
            }
        ?>
        <div class="stats-grid">
            <div class="stat-card current">
                <div class="stat-number" style="color: var(--warning);"><?= $current_count ?></div>
                <div class="stat-label"><i class="fas fa-clock"></i> Today's Appointments</div>
            </div>
            <div class="stat-card future">
                <div class="stat-number" style="color: var(--success);"><?= $future_count ?></div>
                <div class="stat-label"><i class="fas fa-calendar-plus"></i> Upcoming Appointments</div>
            </div>
            <div class="stat-card past">
                <div class="stat-number" style="color: #8e8e93;"><?= $past_count ?></div>
                <div class="stat-label"><i class="fas fa-calendar-check"></i> Past Appointments</div>
            </div>
        </div>

        <!-- Appointments List -->
        <div class="appointments-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h2 class="section-title">All Appointments (<?= count($appointments) ?>)</h2>
            </div>

            <?php if (empty($appointments)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
                    <h3 class="empty-title">No Appointments Found</h3>
                    <p class="empty-text">This patient has no appointments yet.</p>
                </div>
            <?php else: ?>
                <div class="appointments-list">
                    <?php foreach ($appointments as $app): ?>
                        <?php $category = categorizeAppointment($app['full_datetime']); ?>
                        <div class="appointment-card <?= $category ?>">
                            <div class="appointment-info">
                                <div class="appointment-datetime">
                                    <i class="fas fa-calendar"></i>
                                    <?= formatDateTime($app['full_datetime']) ?>
                                </div>
                                <?php if (!empty($app['service_name'])): ?>
                                    <div class="appointment-service">
                                        <i class="fas fa-spa"></i>
                                        <?= htmlspecialchars($app['service_name']) ?>
                                        <?php if (!empty($app['service_duration'])): ?>
                                            <span style="color: var(--accent-color); font-weight: 600;">
                                                • <?= htmlspecialchars($app['service_duration']) ?> min
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <div style="margin-top: 8px;">
                                    <span class="status-badge <?= strtolower($app['status']) ?>">
                                        <?php
                                        $status = strtolower($app['status']);
                                        if ($status === 'completed') echo '<i class="fas fa-check-circle"></i> ';
                                        elseif ($status === 'scheduled') echo '<i class="fas fa-clock"></i> ';
                                        elseif ($status === 'cancelled') echo '<i class="fas fa-times-circle"></i> ';
                                        ?>
                                        <?= htmlspecialchars(ucfirst($app['status'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="appointment-actions">
                                <a href="appointment_details.php?id=<?= $app['appointment_id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i>
                                    <span>View Details</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
