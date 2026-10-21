<?php
require_once 'auth_check.php';
requireStaffLogin();

require_once 'db_connect.php';

$staff_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    $_SESSION['error_msg'] = "Invalid appointment ID.";
    header('Location: manage_appointments.php');
    exit;
}

// Handle form submission to complete appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_appointment'])) {
    $medication_name = trim($_POST['medication_name'] ?? '');
    $medication_steps = trim($_POST['medication_steps'] ?? '');
    $used_injection = isset($_POST['used_injection']) ? 1 : 0;
    $injection_quantity = intval($_POST['injection_quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');

    // Validation
    $errors = [];
    if (empty($medication_name)) {
        $errors[] = "Medication/Treatment name is required.";
    }
    if (empty($medication_steps)) {
        $errors[] = "Medication steps/instructions are required.";
    }

    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // 1. Insert medication record
            $stmt = $pdo->prepare("
                INSERT INTO medication_records
                (appointment_id, medication_name, medication_steps, used_injection, injection_quantity, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $appointment_id,
                $medication_name,
                $medication_steps,
                $used_injection,
                $injection_quantity,
                $notes,
                $staff_id
            ]);

            // 2. Update appointment status to 'Completed'
            $stmt = $pdo->prepare("
                UPDATE appointment
                SET status = 'Completed',
                    notes = CONCAT(COALESCE(notes, ''), '\n\nDiagnosis: ', ?)
                WHERE appointment_id = ?
            ");
            $stmt->execute([$diagnosis, $appointment_id]);

            // 3. If injection was used, update inventory (if table exists)
            if ($used_injection && $injection_quantity > 0) {
                $check_table = $pdo->query("SHOW TABLES LIKE 'inventory'");
                if ($check_table->rowCount() > 0) {
                    $update_inventory = $pdo->prepare("
                        UPDATE inventory
                        SET quantity = quantity - ?,
                            last_updated = NOW()
                        WHERE item_name LIKE ?
                        AND quantity >= ?
                    ");
                    $update_inventory->execute([
                        $injection_quantity,
                        '%' . $medication_name . '%',
                        $injection_quantity
                    ]);
                }
            }

            // Commit transaction
            $pdo->commit();

            $_SESSION['success_msg'] = "Appointment completed successfully! Medical record has been saved.";
            header('Location: manage_appointments.php');
            exit;

        } catch (PDOException $e) {
            // Rollback on error
            $pdo->rollBack();
            $error_message = "Error completing appointment: " . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
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

// Fetch appointment details
$stmt = $pdo->prepare("
    SELECT
        a.*,
        p.full_name as patient_name,
        p.email as patient_email,
        p.phone as patient_phone,
        p.date_of_birth,
        p.sex,
        s.name as service_name,
        s.description as service_description,
        s.price as service_price,
        s.duration as service_duration,
        {$datetime_expr} as full_datetime
    FROM appointment a
    LEFT JOIN patient p ON a.patient_id = p.patient_id
    LEFT JOIN service s ON a.service_id = s.service_id
    WHERE a.appointment_id = ?
");
$stmt->execute([$appointment_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    $_SESSION['error_msg'] = "Appointment not found.";
    header('Location: manage_appointments.php');
    exit;
}

// Check if appointment is already completed
$already_completed = (strtolower($appointment['status']) === 'completed');

// Fetch existing medication records for this appointment
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Appointment - Dermatology Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f2f2f7 0%, #e5e5ea 100%);
            min-height: 100vh;
            color: #1c1c1e;
            line-height: 1.6;
        }

        /* Header */
        .page-header {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding: 20px 40px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            flex-direction: column;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #34c759;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .subtitle {
            color: #64748b;
            font-size: 0.95rem;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            color: #007aff;
            text-decoration: none;
            border-radius: 12px;
            border: 2px solid #007aff;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .back-btn:hover {
            background: #007aff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 122, 255, 0.25);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px 40px;
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #34d399;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #f87171;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fbbf24;
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

        /* Grid Layout */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 30px rgba(52, 199, 89, 0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f2f2f7;
        }

        .card-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(52, 199, 89, 0.2);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1c1c1e;
        }

        /* Info Items */
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: #f2f2f7;
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .info-icon {
            font-size: 18px;
            margin-top: 2px;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 12px;
            color: #8e8e93;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 15px;
            color: #1c1c1e;
            font-weight: 500;
        }

        .info-value.highlight {
            color: #34c759;
            font-weight: 600;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.scheduled {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }

        .status-badge.completed {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .status-badge.cancelled {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }

        /* Form Styles */
        .form-section {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 6px 24px rgba(52, 199, 89, 0.15);
            border: 2px solid #34c759;
            margin-bottom: 24px;
        }

        .form-section-disabled {
            opacity: 0.6;
            pointer-events: none;
        }

        .form-grid {
            display: grid;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #1c1c1e;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label .required {
            color: #ff3b30;
        }

        .form-input,
        .form-textarea,
        .form-select {
            padding: 12px 16px;
            border: 2px solid #e5e5ea;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: #f2f2f7;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #34c759;
            background: white;
            box-shadow: 0 0 0 4px rgba(52, 199, 89, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #f2f2f7;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-checkbox:hover {
            background: #e5e5ea;
        }

        .form-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #34c759;
        }

        .form-checkbox label {
            cursor: pointer;
            font-weight: 500;
            flex: 1;
        }

        .injection-quantity {
            margin-top: 12px;
            padding: 16px;
            background: #fff7ed;
            border: 2px solid #fed7aa;
            border-radius: 12px;
        }

        /* Buttons */
        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-success {
            background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3);
        }

        .btn-success:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(52, 199, 89, 0.4);
        }

        .btn-success:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007aff 0%, #0051d5 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 122, 255, 0.4);
        }

        .btn-large {
            padding: 18px 36px;
            font-size: 17px;
        }

        /* Medication Records */
        .medication-records {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .record-item {
            padding: 20px;
            background: #f2f2f7;
            border-radius: 16px;
            margin-bottom: 16px;
            border-left: 4px solid #34c759;
            transition: all 0.3s ease;
        }

        .record-item:hover {
            background: #e5e5ea;
            transform: translateX(4px);
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .record-title {
            font-size: 16px;
            font-weight: 700;
            color: #1c1c1e;
            margin-bottom: 4px;
        }

        .record-meta {
            font-size: 13px;
            color: #8e8e93;
        }

        .record-content {
            margin: 12px 0;
        }

        .record-section {
            margin-bottom: 8px;
        }

        .record-section-title {
            font-size: 12px;
            color: #8e8e93;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .record-section-value {
            font-size: 14px;
            color: #1c1c1e;
            white-space: pre-wrap;
        }

        .injection-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
            color: #9a3412;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #8e8e93;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-text {
            font-size: 16px;
            font-weight: 500;
        }

        /* Completion Banner */
        .completion-banner {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #34c759;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .completion-banner-icon {
            font-size: 48px;
        }

        .completion-banner-content h3 {
            color: #065f46;
            font-size: 1.3rem;
            margin-bottom: 8px;
        }

        .completion-banner-content p {
            color: #047857;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 16px 20px;
            }

            .header-container {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }

            .header-left {
                text-align: center;
            }

            h1 {
                font-size: 1.75rem;
                justify-content: center;
            }

            .back-btn {
                justify-content: center;
            }

            .container {
                padding: 0 20px 20px;
            }

            .card,
            .form-section,
            .medication-records {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="header-container">
            <div class="header-left">
                <h1>
                    <span>✓</span>
                    <span>Complete Appointment</span>
                </h1>
                <p class="subtitle">Fill in treatment details and mark appointment as completed</p>
            </div>
            <a href="manage_appointments.php" class="back-btn">
                <span>←</span>
                <span>Back to Appointments</span>
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Alert Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <span>✓</span>
                <span><?= htmlspecialchars($success_message) ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <span>⚠</span>
                <span><?= $error_message ?></span>
            </div>
        <?php endif; ?>

        <!-- Completion Banner (if already completed) -->
        <?php if ($already_completed): ?>
            <div class="completion-banner">
                <div class="completion-banner-icon">✓</div>
                <div class="completion-banner-content">
                    <h3>This Appointment is Already Completed</h3>
                    <p>This appointment has been marked as completed. You can still view the details and add additional medication records below if needed.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Appointment Information Grid -->
        <div class="content-grid">
            <!-- Patient Information -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">👤</div>
                    <h2 class="card-title">Patient Information</h2>
                </div>

                <div class="info-item">
                    <span class="info-icon">👨‍⚕️</span>
                    <div class="info-content">
                        <div class="info-label">Full Name</div>
                        <div class="info-value highlight"><?= htmlspecialchars($appointment['patient_name']) ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icon">📧</span>
                    <div class="info-content">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($appointment['patient_email']) ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icon">📞</span>
                    <div class="info-content">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?= htmlspecialchars($appointment['patient_phone']) ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icon">🎂</span>
                    <div class="info-content">
                        <div class="info-label">Date of Birth / Age</div>
                        <div class="info-value"><?= formatDate($appointment['date_of_birth']) ?> (<?= calculateAge($appointment['date_of_birth']) ?>)</div>
                    </div>
                </div>

                <?php if (!empty($appointment['sex'])): ?>
                <div class="info-item">
                    <span class="info-icon">⚧</span>
                    <div class="info-content">
                        <div class="info-label">Sex</div>
                        <div class="info-value"><?= htmlspecialchars(ucfirst($appointment['sex'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Appointment Information -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">📅</div>
                    <h2 class="card-title">Appointment Information</h2>
                </div>

                <div class="info-item">
                    <span class="info-icon">🕒</span>
                    <div class="info-content">
                        <div class="info-label">Date & Time</div>
                        <div class="info-value highlight"><?= formatDateTime($appointment['full_datetime']) ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icon">📋</span>
                    <div class="info-content">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge <?= strtolower($appointment['status']) ?>">
                                <?= htmlspecialchars(ucfirst($appointment['status'])) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($appointment['service_name'])): ?>
                <div class="info-item">
                    <span class="info-icon">🏥</span>
                    <div class="info-content">
                        <div class="info-label">Service</div>
                        <div class="info-value"><?= htmlspecialchars($appointment['service_name']) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($appointment['notes'])): ?>
                <div class="info-item">
                    <span class="info-icon">📝</span>
                    <div class="info-content">
                        <div class="info-label">Notes</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($appointment['notes'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Complete Appointment Form -->
        <div class="form-section <?= $already_completed ? 'form-section-disabled' : '' ?>">
            <div class="card-header">
                <div class="card-icon">💊</div>
                <h2 class="card-title">
                    <?= $already_completed ? 'Appointment Already Completed' : 'Complete Appointment & Add Medical Record' ?>
                </h2>
            </div>

            <?php if ($already_completed): ?>
                <div class="alert alert-warning">
                    <span>ℹ</span>
                    <span>This appointment is already completed. Use the "Add Medication Record" section in the Appointment Details page if you need to add additional records.</span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <span>💊 Medication / Treatment Name</span>
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            name="medication_name"
                            class="form-input"
                            placeholder="e.g., Hydrocortisone Cream, Botox Injection, Acne Treatment"
                            required
                            <?= $already_completed ? 'disabled' : '' ?>
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>📝 Medication Steps / Instructions</span>
                            <span class="required">*</span>
                        </label>
                        <textarea
                            name="medication_steps"
                            class="form-textarea"
                            placeholder="Enter step-by-step instructions for the patient:&#10;1. Clean the affected area with mild soap&#10;2. Pat dry gently&#10;3. Apply thin layer of cream twice daily&#10;4. Avoid direct sunlight&#10;5. Return for follow-up in 2 weeks"
                            required
                            <?= $already_completed ? 'disabled' : '' ?>
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>🩺 Diagnosis / Condition</span>
                        </label>
                        <textarea
                            name="diagnosis"
                            class="form-textarea"
                            placeholder="Enter the diagnosis or condition (this will be added to appointment notes)"
                            <?= $already_completed ? 'disabled' : '' ?>
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">📌 Additional Notes</label>
                        <textarea
                            name="notes"
                            class="form-textarea"
                            placeholder="Any additional observations, warnings, or follow-up instructions..."
                            <?= $already_completed ? 'disabled' : '' ?>
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <div class="form-checkbox" onclick="toggleInjectionQuantity()">
                            <input
                                type="checkbox"
                                name="used_injection"
                                id="used_injection"
                                onchange="toggleInjectionQuantity()"
                                <?= $already_completed ? 'disabled' : '' ?>
                            >
                            <label for="used_injection">
                                💉 This treatment used an injection
                                <div style="font-size: 13px; color: #8e8e93; margin-top: 4px;">
                                    Check this if the treatment involved an injection (will update inventory if available)
                                </div>
                            </label>
                        </div>

                        <div id="injection-quantity-section" class="injection-quantity" style="display: none;">
                            <label class="form-label" style="margin-bottom: 8px;">
                                💉 Injection Quantity Used
                            </label>
                            <input
                                type="number"
                                name="injection_quantity"
                                class="form-input"
                                min="1"
                                value="1"
                                placeholder="Enter quantity"
                                <?= $already_completed ? 'disabled' : '' ?>
                            >
                            <div style="font-size: 12px; color: #9a3412; margin-top: 8px;">
                                ⚠ This will decrease the inventory stock by the specified quantity (if inventory system is enabled)
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button
                            type="submit"
                            name="complete_appointment"
                            class="btn btn-success btn-large"
                            <?= $already_completed ? 'disabled' : '' ?>
                        >
                            <span>✓</span>
                            <span>Complete Appointment & Save Record</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Existing Medication Records -->
        <?php if (count($medication_records) > 0): ?>
        <div class="medication-records">
            <div class="card-header">
                <div class="card-icon">📋</div>
                <h2 class="card-title">Medication Records History (<?= count($medication_records) ?>)</h2>
            </div>

            <?php foreach ($medication_records as $record): ?>
                <div class="record-item">
                    <div class="record-header">
                        <div>
                            <div class="record-title">💊 <?= htmlspecialchars($record['medication_name']) ?></div>
                            <div class="record-meta">
                                Recorded by <?= htmlspecialchars($record['staff_name'] ?? 'Unknown') ?>
                                on <?= formatDateTime($record['recorded_at']) ?>
                            </div>
                        </div>
                    </div>

                    <div class="record-content">
                        <?php if (!empty($record['medication_steps'])): ?>
                        <div class="record-section">
                            <div class="record-section-title">📝 Instructions</div>
                            <div class="record-section-value"><?= nl2br(htmlspecialchars($record['medication_steps'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($record['notes'])): ?>
                        <div class="record-section">
                            <div class="record-section-title">📌 Notes</div>
                            <div class="record-section-value"><?= nl2br(htmlspecialchars($record['notes'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($record['used_injection']): ?>
                        <div class="record-section">
                            <span class="injection-badge">
                                💉 Injection Used (Qty: <?= $record['injection_quantity'] ?>)
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 16px; margin-top: 24px; flex-wrap: wrap;">
            <a href="manage_appointments.php" class="btn btn-primary">
                <span>←</span>
                <span>Back to Manage Appointments</span>
            </a>
            <a href="appointment_details.php?id=<?= $appointment_id ?>" class="btn btn-primary">
                <span>📋</span>
                <span>View Full Appointment Details</span>
            </a>
        </div>
    </div>

    <script>
        function toggleInjectionQuantity() {
            const checkbox = document.getElementById('used_injection');
            const section = document.getElementById('injection-quantity-section');
            if (checkbox && section) {
                section.style.display = checkbox.checked ? 'block' : 'none';
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

        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const medicationName = document.querySelector('input[name="medication_name"]');
            const medicationSteps = document.querySelector('textarea[name="medication_steps"]');

            if (!medicationName.value.trim()) {
                e.preventDefault();
                alert('Please enter the medication/treatment name.');
                medicationName.focus();
                return false;
            }

            if (!medicationSteps.value.trim()) {
                e.preventDefault();
                alert('Please enter the medication steps/instructions.');
                medicationSteps.focus();
                return false;
            }

            // Confirm before submission
            if (!confirm('Are you sure you want to complete this appointment? This will update the appointment status to "Completed" and save the medical record.')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
