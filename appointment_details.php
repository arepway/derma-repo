<?php
require_once 'auth_check.php';
requireStaffLogin();

require_once 'db_connect.php';

$staff_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    header('Location: manage_appointments.php');
    exit;
}

// Get current stock for injection availability check
$current_stock = 0;
try {
    $stmt = $pdo->query("SELECT quantity FROM stock WHERE item_name = 'Injection' LIMIT 1");
    $stock_row = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_stock = $stock_row ? intval($stock_row['quantity']) : 0;
} catch (PDOException $e) {
    // Stock table might not exist yet
    $current_stock = 0;
}

// Handle appointment completion with medication record
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

    // Check stock availability if injection is being used
    if ($used_injection) {
        if ($current_stock <= 0) {
            $errors[] = "Cannot use injection: Stock is empty. Please add stock first.";
        }
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

            // 3. If injection was used, update stock and create medical record
            if ($used_injection) {
                // Update stock table - reduce quantity by 1
                $check_stock = $pdo->query("SHOW TABLES LIKE 'stock'");
                if ($check_stock->rowCount() > 0) {
                    $update_stock = $pdo->prepare("
                        UPDATE stock
                        SET quantity = quantity - 1,
                            updated_by = ?
                        WHERE item_name = 'Injection'
                        AND quantity > 0
                    ");
                    $update_stock->execute([$staff_id]);

                    // Also check if medical_record table exists and update it
                    $check_mr = $pdo->query("SHOW TABLES LIKE 'medical_record'");
                    if ($check_mr->rowCount() > 0) {
                        // First check which columns exist in the medical_record table
                        $columns_result = $pdo->query("SHOW COLUMNS FROM medical_record");
                        $available_columns = [];
                        while ($col = $columns_result->fetch(PDO::FETCH_ASSOC)) {
                            $available_columns[] = $col['Field'];
                        }

                        // Check if a medical record exists for this appointment
                        $check_existing = $pdo->prepare("SELECT record_id FROM medical_record WHERE appointment_id = ?");
                        $check_existing->execute([$appointment_id]);

                        if ($check_existing->rowCount() > 0) {
                            // Update existing record - only use columns that exist
                            $update_fields = [];
                            $update_params = [];

                            if (in_array('injection_used', $available_columns)) {
                                $update_fields[] = "injection_used = ?";
                                $update_params[] = 1;
                            }
                            if (in_array('diagnosis', $available_columns)) {
                                $update_fields[] = "diagnosis = ?";
                                $update_params[] = $diagnosis;
                            }
                            if (in_array('staff_id', $available_columns)) {
                                $update_fields[] = "staff_id = ?";
                                $update_params[] = $staff_id;
                            }

                            if (!empty($update_fields)) {
                                $update_params[] = $appointment_id;
                                $update_sql = "UPDATE medical_record SET " . implode(', ', $update_fields) . " WHERE appointment_id = ?";
                                $update_mr = $pdo->prepare($update_sql);
                                $update_mr->execute($update_params);
                            }
                        } else {
                            // Insert new record - only use columns that exist
                            $insert_fields = ['appointment_id'];
                            $insert_placeholders = ['?'];
                            $insert_params = [$appointment_id];

                            if (in_array('diagnosis', $available_columns)) {
                                $insert_fields[] = 'diagnosis';
                                $insert_placeholders[] = '?';
                                $insert_params[] = $diagnosis;
                            }
                            if (in_array('injection_used', $available_columns)) {
                                $insert_fields[] = 'injection_used';
                                $insert_placeholders[] = '?';
                                $insert_params[] = 1;
                            }
                            if (in_array('staff_id', $available_columns)) {
                                $insert_fields[] = 'staff_id';
                                $insert_placeholders[] = '?';
                                $insert_params[] = $staff_id;
                            }

                            $insert_sql = "INSERT INTO medical_record (" . implode(', ', $insert_fields) . ") VALUES (" . implode(', ', $insert_placeholders) . ")";
                            $insert_mr = $pdo->prepare($insert_sql);
                            $insert_mr->execute($insert_params);
                        }
                    }
                }
            }

            // Commit transaction
            $pdo->commit();

            $success_message = "Appointment completed successfully! Medical record has been saved.";

            // Refresh appointment data
            header("Location: appointment_details.php?id=$appointment_id&completed=1");
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

// Handle medication record submission (without completion)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_medication'])) {
    $medication_name = trim($_POST['medication_name'] ?? '');
    $medication_steps = trim($_POST['medication_steps'] ?? '');
    $used_injection = isset($_POST['used_injection']) ? 1 : 0;
    $injection_quantity = intval($_POST['injection_quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($medication_name)) {
        try {
            // Insert medication record
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

            // If injection was used, update inventory (assuming there's an inventory/stock table)
            if ($used_injection && $injection_quantity > 0) {
                // Check if inventory table exists and update
                $check_table = $pdo->query("SHOW TABLES LIKE 'inventory'");
                if ($check_table->rowCount() > 0) {
                    // Update inventory - decrease stock
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

            $success_message = "Medication record saved successfully!";
        } catch (PDOException $e) {
            $error_message = "Error saving medication record: " . $e->getMessage();
        }
    } else {
        $error_message = "Medication name is required.";
    }
}

// Handle medication record deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record'])) {
    $record_id = intval($_POST['record_id'] ?? 0);

    if ($record_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM medication_records WHERE record_id = ?");
            $stmt->execute([$record_id]);
            $success_message = "Medication record deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting record: " . $e->getMessage();
        }
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
    header('Location: manage_appointments.php');
    exit;
}

// Check if appointment is already completed
$already_completed = (strtolower($appointment['status']) === 'completed');

// Check if just completed
if (isset($_GET['completed']) && $_GET['completed'] == '1') {
    $success_message = "Appointment completed successfully! Medical record has been saved.";
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

        /* Navigation - Liquid Glass */
        .nav-container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 0.25rem;
            flex-wrap: wrap;
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

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }

        /* Page Title Section - Liquid Glass */
        .page-title-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: white;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        h1 i {
            color: var(--accent-color);
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.8rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(44, 95, 111, 0.25);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(44, 95, 111, 0.35);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }

        /* Alert Messages - Liquid Glass */
        .alert {
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
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

        /* Card Styles - Liquid Glass */
        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.08);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            font-size: 1.4rem;
            font-weight: 700;
            color: white;
        }

        /* Info Items - Liquid Glass */
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-icon {
            font-size: 18px;
            margin-top: 2px;
            color: var(--accent-color);
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .info-value.highlight {
            color: var(--accent-color);
            font-weight: 600;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.scheduled {
            background: linear-gradient(135deg, rgba(44, 95, 111, 0.1), rgba(44, 95, 111, 0.2));
            color: var(--primary-color);
        }

        .status-badge.completed {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.2));
            color: var(--success);
        }

        .status-badge.cancelled {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.2));
            color: var(--danger);
        }

        /* Form Styles - Liquid Glass */
        .form-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 24px;
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
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label i {
            color: var(--accent-color);
        }

        .form-label .required {
            color: #ff6b6b;
        }

        .form-input,
        .form-textarea,
        .form-select {
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(138, 180, 167, 0.2);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
        }

        .form-checkbox:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .form-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--accent-color);
        }

        .form-checkbox label {
            cursor: pointer;
            font-weight: 500;
            flex: 1;
        }

        .injection-quantity {
            margin-top: 12px;
            padding: 16px;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 12px;
        }

        /* Buttons */
        .btn {
            padding: 0.9rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 8px 20px rgba(44, 95, 111, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(44, 95, 111, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(239, 68, 68, 0.4);
        }

        .btn-small {
            padding: 0.6rem 1.2rem;
            font-size: 0.85rem;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
        }

        .btn-success:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-large {
            padding: 1.2rem 2.5rem;
            font-size: 1.1rem;
        }

        .form-section-disabled {
            opacity: 0.6;
            pointer-events: none;
        }

        .completion-banner {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.2));
            border: 2px solid var(--success);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .completion-banner-icon {
            font-size: 3rem;
            color: var(--success);
        }

        .completion-banner-content h3 {
            font-family: 'Playfair Display', serif;
            color: var(--success);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .completion-banner-content p {
            color: #047857;
            font-size: 0.95rem;
        }

        .complete-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.15);
            border: 2px solid var(--success);
            margin-bottom: 2rem;
        }

        .complete-section .card-icon {
            background: linear-gradient(135deg, var(--success), #059669);
        }

        /* Medication Records - Liquid Glass */
        .medication-records {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .record-item {
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            margin-bottom: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-left: 4px solid var(--accent-color);
            transition: all 0.3s ease;
        }

        .record-item:hover {
            background: rgba(255, 255, 255, 0.08);
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
            color: white;
            margin-bottom: 4px;
        }

        .record-meta {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }

        .record-content {
            margin: 12px 0;
        }

        .record-section {
            margin-bottom: 8px;
        }

        .record-section-title {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .record-section-value {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
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
            color: rgba(255, 255, 255, 0.5);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
            color: rgba(255, 255, 255, 0.3);
        }

        .empty-text {
            font-size: 16px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.6);
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
        </div>
    </div>

    <!-- Navigation -->
    <div class="main-container">
        <!-- Page Title Section -->
        <div class="page-title-section">
            <h1><i class="fas fa-calendar-check"></i> Appointment Details</h1>
            <p class="subtitle">View and manage patient appointment information</p>
            <a href="manage_appointments.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Appointments</span>
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Alert Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($success_message) ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= $error_message ?></span>
            </div>
        <?php endif; ?>

        <!-- Completion Banner (if already completed) -->
        <?php if ($already_completed): ?>
            <div class="completion-banner">
                <div class="completion-banner-icon"><i class="fas fa-check-circle"></i></div>
                <div class="completion-banner-content">
                    <h3>This Appointment is Completed</h3>
                    <p>This appointment has been marked as completed. You can still add additional medication records below if needed.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Appointment Information Grid -->
        <div class="content-grid">
            <!-- Patient Information -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-user"></i></div>
                    <h2 class="card-title">Patient Information</h2>
                </div>

                <div class="info-item">
                    <span class="info-icon"><i class="fas fa-user-circle"></i></span>
                    <div class="info-content">
                        <div class="info-label">Full Name</div>
                        <div class="info-value highlight"><?= htmlspecialchars($appointment['patient_name']) ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icon"><i class="fas fa-envelope"></i></span>
                    <div class="info-content">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($appointment['patient_email']) ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icon"><i class="fas fa-phone"></i></span>
                    <div class="info-content">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?= htmlspecialchars($appointment['patient_phone']) ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icon"><i class="fas fa-birthday-cake"></i></span>
                    <div class="info-content">
                        <div class="info-label">Date of Birth / Age</div>
                        <div class="info-value"><?= formatDate($appointment['date_of_birth']) ?> (<?= calculateAge($appointment['date_of_birth']) ?>)</div>
                    </div>
                </div>

                <?php if (!empty($appointment['sex'])): ?>
                <div class="info-item">
                    <span class="info-icon"><i class="fas fa-venus-mars"></i></span>
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
                    <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
                    <h2 class="card-title">Appointment Information</h2>
                </div>

                <div class="info-item">
                    <span class="info-icon"><i class="fas fa-clock"></i></span>
                    <div class="info-content">
                        <div class="info-label">Date & Time</div>
                        <div class="info-value highlight"><?= formatDateTime($appointment['full_datetime']) ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icon"><i class="fas fa-clipboard-list"></i></span>
                    <div class="info-content">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge <?= strtolower($appointment['status']) ?>">
                                <?= htmlspecialchars(ucfirst($appointment['status'])) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($appointment['notes'])): ?>
                <div class="info-item">
                    <span class="info-icon"><i class="fas fa-sticky-note"></i></span>
                    <div class="info-content">
                        <div class="info-label">Notes</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($appointment['notes'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Service Information -->
        <?php if (!empty($appointment['service_name'])): ?>
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-capsules"></i></div>
                <h2 class="card-title">Service / Treatment Information</h2>
            </div>

            <div class="info-item">
                <span class="info-icon"><i class="fas fa-hospital"></i></span>
                <div class="info-content">
                    <div class="info-label">Service Name</div>
                    <div class="info-value highlight"><?= htmlspecialchars($appointment['service_name']) ?></div>
                </div>
            </div>

            <?php if (!empty($appointment['service_description'])): ?>
            <div class="info-item">
                <span class="info-icon"><i class="fas fa-book-open"></i></span>
                <div class="info-content">
                    <div class="info-label">Description</div>
                    <div class="info-value"><?= htmlspecialchars($appointment['service_description']) ?></div>
                </div>
            </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="info-item">
                    <span class="info-icon"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-content">
                        <div class="info-label">Price</div>
                        <div class="info-value">RM <?= number_format($appointment['service_price'], 2) ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icon"><i class="fas fa-stopwatch"></i></span>
                    <div class="info-content">
                        <div class="info-label">Duration</div>
                        <div class="info-value"><?= htmlspecialchars($appointment['service_duration']) ?> minutes</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Complete Appointment Section -->
        <?php if (!$already_completed): ?>
        <div class="complete-section">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-check"></i></div>
                <h2 class="card-title">Complete Appointment & Add Medical Record</h2>
            </div>

            <form method="POST" action="" id="complete-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <span><i class="fas fa-pills"></i> Medication / Treatment Name</span>
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            name="medication_name"
                            class="form-input"
                            placeholder="e.g., Hydrocortisone Cream, Botox Injection, Acne Treatment"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span><i class="fas fa-list-ol"></i> Medication Steps / Instructions</span>
                            <span class="required">*</span>
                        </label>
                        <textarea
                            name="medication_steps"
                            class="form-textarea"
                            placeholder="Enter step-by-step instructions for the patient:&#10;1. Clean the affected area with mild soap&#10;2. Pat dry gently&#10;3. Apply thin layer of cream twice daily&#10;4. Avoid direct sunlight&#10;5. Return for follow-up in 2 weeks"
                            required
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span><i class="fas fa-stethoscope"></i> Diagnosis / Condition</span>
                        </label>
                        <textarea
                            name="diagnosis"
                            class="form-textarea"
                            placeholder="Enter the diagnosis or condition (this will be added to appointment notes)"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-paperclip"></i> Additional Notes</label>
                        <textarea
                            name="notes"
                            class="form-textarea"
                            placeholder="Any additional observations, warnings, or follow-up instructions..."
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <?php if ($current_stock <= 0): ?>
                            <div class="alert alert-error" style="padding: 1rem; background: #fee2e2; border: 1px solid #fecaca; border-radius: 10px; margin-bottom: 1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Out of Stock!</strong> No injections available. Please <a href="manage_stock.php" style="color: #991b1b; text-decoration: underline;">add stock</a> to use this option.
                            </div>
                        <?php elseif ($current_stock <= 5): ?>
                            <div class="alert alert-warning" style="padding: 1rem; background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; margin-bottom: 1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Low Stock Warning!</strong> Only <?= $current_stock ?> injection(s) remaining.
                            </div>
                        <?php endif; ?>
                        <div class="form-checkbox" onclick="<?= $current_stock > 0 ? 'toggleInjectionQuantity(\'complete\')' : 'event.preventDefault(); alert(\'No stock available!\')' ?>">
                            <input
                                type="checkbox"
                                name="used_injection"
                                id="used_injection_complete"
                                onchange="toggleInjectionQuantity('complete')"
                                <?= $current_stock <= 0 ? 'disabled' : '' ?>
                            >
                            <label for="used_injection_complete" style="<?= $current_stock <= 0 ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>">
                                <i class="fas fa-syringe"></i> This treatment used an injection
                                <div style="font-size: 13px; color: #8e8e93; margin-top: 4px;">
                                    Check this if the treatment involved an injection (Current stock: <?= $current_stock ?>)
                                </div>
                            </label>
                        </div>

                        <div id="injection-quantity-section-complete" class="injection-quantity" style="display: none;">
                            <label class="form-label" style="margin-bottom: 8px;">
                                <i class="fas fa-syringe"></i> Injection Quantity Used
                            </label>
                            <input
                                type="number"
                                name="injection_quantity"
                                class="form-input"
                                min="1"
                                value="1"
                                placeholder="Enter quantity"
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
                        >
                            <span>✓</span>
                            <span>Complete Appointment & Save Record</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Add Medication Record Form -->
        <div class="form-section">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-syringe"></i></div>
                <h2 class="card-title"><?= $already_completed ? 'Add Additional Medication Record' : 'Add Medication Record (Without Completing)' ?></h2>
            </div>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            Medication / Treatment Name
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            name="medication_name"
                            class="form-input"
                            placeholder="e.g., Hydrocortisone Cream, Botox Injection"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Medication Steps / Instructions</label>
                        <textarea
                            name="medication_steps"
                            class="form-textarea"
                            placeholder="Enter step-by-step instructions for the patient:&#10;1. Clean the affected area&#10;2. Apply thin layer of cream&#10;3. Avoid direct sunlight"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Additional Notes</label>
                        <textarea
                            name="notes"
                            class="form-textarea"
                            placeholder="Any additional notes or observations..."
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <?php if ($current_stock <= 0): ?>
                            <div class="alert alert-error" style="padding: 1rem; background: #fee2e2; border: 1px solid #fecaca; border-radius: 10px; margin-bottom: 1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Out of Stock!</strong> No injections available. Please <a href="manage_stock.php" style="color: #991b1b; text-decoration: underline;">add stock</a> to use this option.
                            </div>
                        <?php elseif ($current_stock <= 5): ?>
                            <div class="alert alert-warning" style="padding: 1rem; background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; margin-bottom: 1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Low Stock Warning!</strong> Only <?= $current_stock ?> injection(s) remaining.
                            </div>
                        <?php endif; ?>
                        <div class="form-checkbox" onclick="<?= $current_stock > 0 ? 'toggleInjectionQuantity(\'add\')' : 'event.preventDefault(); alert(\'No stock available!\')' ?>">
                            <input
                                type="checkbox"
                                name="used_injection"
                                id="used_injection"
                                onchange="toggleInjectionQuantity('add')"
                                <?= $current_stock <= 0 ? 'disabled' : '' ?>
                            >
                            <label for="used_injection" style="<?= $current_stock <= 0 ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>">
                                <i class="fas fa-syringe"></i> This treatment used an injection
                                <div style="font-size: 13px; color: #8e8e93; margin-top: 4px;">
                                    Check this if the treatment involved an injection (Current stock: <?= $current_stock ?>)
                                </div>
                            </label>
                        </div>

                        <div id="injection-quantity-section" class="injection-quantity" style="display: none;">
                            <label class="form-label" style="margin-bottom: 8px;">
                                Injection Quantity Used
                            </label>
                            <input
                                type="number"
                                name="injection_quantity"
                                class="form-input"
                                min="1"
                                value="1"
                                placeholder="Enter quantity"
                            >
                            <div style="font-size: 12px; color: #9a3412; margin-top: 8px;">
                                ⚠ This will decrease the inventory stock by the specified quantity
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="save_medication" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span>Save Medication Record</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Medication Records History -->
        <div class="medication-records">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-clipboard-list"></i></div>
                <h2 class="card-title">Medication Records (<?= count($medication_records) ?>)</h2>
            </div>

            <?php if (count($medication_records) > 0): ?>
                <?php foreach ($medication_records as $record): ?>
                    <div class="record-item">
                        <div class="record-header">
                            <div>
                                <div class="record-title"><?= htmlspecialchars($record['medication_name']) ?></div>
                                <div class="record-meta">
                                    Recorded by <?= htmlspecialchars($record['staff_name']) ?>
                                    on <?= formatDateTime($record['recorded_at']) ?>
                                </div>
                            </div>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this medication record?');">
                                <input type="hidden" name="record_id" value="<?= $record['record_id'] ?>">
                                <button type="submit" name="delete_record" class="btn btn-danger btn-small">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>

                        <div class="record-content">
                            <?php if (!empty($record['medication_steps'])): ?>
                            <div class="record-section">
                                <div class="record-section-title">Instructions</div>
                                <div class="record-section-value"><?= nl2br(htmlspecialchars($record['medication_steps'])) ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($record['notes'])): ?>
                            <div class="record-section">
                                <div class="record-section-title">Notes</div>
                                <div class="record-section-value"><?= nl2br(htmlspecialchars($record['notes'])) ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if ($record['used_injection']): ?>
                            <div class="record-section">
                                <span class="injection-badge">
                                    <i class="fas fa-syringe"></i> Injection Used (Qty: <?= $record['injection_quantity'] ?>)
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-file-medical"></i></div>
                    <div class="empty-text">No medication records yet. Add the first one above.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleInjectionQuantity(type) {
            if (type === 'complete') {
                const checkbox = document.getElementById('used_injection_complete');
                const section = document.getElementById('injection-quantity-section-complete');
                if (checkbox && section) {
                    section.style.display = checkbox.checked ? 'block' : 'none';
                }
            } else {
                const checkbox = document.getElementById('used_injection');
                const section = document.getElementById('injection-quantity-section');
                if (checkbox && section) {
                    section.style.display = checkbox.checked ? 'block' : 'none';
                }
            }
        }

        // Form validation for complete appointment form
        document.getElementById('complete-form')?.addEventListener('submit', function(e) {
            const medicationName = this.querySelector('input[name="medication_name"]');
            const medicationSteps = this.querySelector('textarea[name="medication_steps"]');

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
