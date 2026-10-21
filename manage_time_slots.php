<?php
// Admin page for managing available appointment time slots
require_once 'auth_check.php';
requireStaffLogin();

// --- Configuration ---
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'dermatology_clinic';

// Set timezone to prevent date shifting issues
date_default_timezone_set('Asia/Manila');

// --- Connect ---
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    http_response_code(500);
    die("Database connection failed: (" . $mysqli->connect_errno . ") " . htmlspecialchars($mysqli->connect_error));
}

$message = '';
$error = '';

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'get_slots') {
    $date = $_GET['date'] ?? '';

    if (empty($date)) {
        echo json_encode(['error' => 'Date required']);
        exit;
    }

    $sql = "
        SELECT slot_id, time, is_available,
               (SELECT COUNT(*) FROM appointment WHERE date = ? AND time = available_time_slots.time AND status != 'cancelled') as booked_count
        FROM available_time_slots
        WHERE date = ?
        ORDER BY time ASC
    ";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ss', $date, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $slots = [];
    while ($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }

    echo json_encode(['slots' => $slots]);
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'disable_day') {
        $date = $_POST['date'] ?? '';

        if (empty($date)) {
            $error = 'Date is required.';
        } else {
            $stmt = $mysqli->prepare("UPDATE available_time_slots SET is_available = 0 WHERE date = ?");
            $stmt->bind_param('s', $date);

            if ($stmt->execute()) {
                $affected = $stmt->affected_rows;
                $message = "Disabled entire day ({$affected} slots updated).";
            } else {
                $error = 'Failed to disable day.';
            }
            $stmt->close();
        }
    }
    elseif ($action === 'enable_day') {
        $date = $_POST['date'] ?? '';

        if (empty($date)) {
            $error = 'Date is required.';
        } else {
            $stmt = $mysqli->prepare("UPDATE available_time_slots SET is_available = 1 WHERE date = ?");
            $stmt->bind_param('s', $date);

            if ($stmt->execute()) {
                $affected = $stmt->affected_rows;
                $message = "Enabled entire day ({$affected} slots updated).";
            } else {
                $error = 'Failed to enable day.';
            }
            $stmt->close();
        }
    }
    elseif ($action === 'add_day_slots') {
        $date = $_POST['date'] ?? '';

        if (empty($date)) {
            $error = 'Date is required.';
        } else {
            $default_times = [
                '09:00:00', '09:30:00', '10:00:00', '10:30:00', '11:00:00', '11:30:00',
                '14:00:00', '14:30:00', '15:00:00', '15:30:00', '16:00:00', '16:30:00'
            ];

            $success_count = 0;
            foreach ($default_times as $time) {
                $stmt = $mysqli->prepare("INSERT INTO available_time_slots (date, time, is_available) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE is_available = 1");
                $stmt->bind_param('ss', $date, $time);
                if ($stmt->execute()) {
                    $success_count++;
                }
                $stmt->close();
            }
            $message = "Added {$success_count} time slots for the selected day.";
        }
    }
    elseif ($action === 'toggle_slot') {
        $slot_id = $_POST['slot_id'] ?? 0;
        $is_available = $_POST['is_available'] ?? 0;

        $stmt = $mysqli->prepare("UPDATE available_time_slots SET is_available = ? WHERE slot_id = ?");
        $stmt->bind_param('ii', $is_available, $slot_id);

        if ($stmt->execute()) {
            $message = 'Time slot updated successfully.';
        } else {
            $error = 'Failed to update time slot.';
        }
        $stmt->close();
    }
    elseif ($action === 'delete_slot') {
        $slot_id = $_POST['slot_id'] ?? 0;

        $stmt = $mysqli->prepare("DELETE FROM available_time_slots WHERE slot_id = ?");
        $stmt->bind_param('i', $slot_id);

        if ($stmt->execute()) {
            $message = 'Time slot deleted successfully.';
        } else {
            $error = 'Failed to delete time slot.';
        }
        $stmt->close();
    }
    elseif ($action === 'add_custom_slots') {
        $date = $_POST['date'] ?? '';
        $times = $_POST['times'] ?? [];

        if (empty($date) || empty($times)) {
            $error = 'Date and at least one time slot required.';
        } else {
            $success_count = 0;
            foreach ($times as $time) {
                $stmt = $mysqli->prepare("INSERT INTO available_time_slots (date, time, is_available) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE is_available = 1");
                $stmt->bind_param('ss', $date, $time);
                if ($stmt->execute()) {
                    $success_count++;
                }
                $stmt->close();
            }
            $message = "Added {$success_count} custom time slot(s).";
        }
    }

    // Return JSON for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => !empty($message), 'message' => $message ?: $error]);
        exit;
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Available Time Slots - True Beauty Concept</title>
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
    color: var(--text-dark);
    line-height: 1.6;
    min-height: 100vh;
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

.main-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem 2rem;
}

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

.back-link {
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

.back-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(44, 95, 111, 0.35);
}
.message {
    padding: 1.2rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    font-weight: 500;
    animation: slideIn 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    backdrop-filter: blur(10px);
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message.success {
    background: rgba(16, 185, 129, 0.15);
    color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-left: 4px solid var(--success);
}

.message.error {
    background: rgba(239, 68, 68, 0.15);
    color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-left: 4px solid var(--danger);
}

.main-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

/* Bento Grid Layout */
.bento-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1.5rem;
}

.calendar-section {
    grid-column: span 4;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    padding: 1.5rem;
    border-radius: 16px;
    color: white;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.calendar-section h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    margin-bottom: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.calendar-section h2 i {
    color: var(--accent-color);
    font-size: 1.1rem;
}

.date-input-wrapper {
    margin-bottom: 1rem;
}

.date-input-wrapper label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.7);
}

.date-input-wrapper input[type="date"] {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    font-size: 0.9rem;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    backdrop-filter: blur(5px);
    transition: all 0.3s ease;
}

.date-input-wrapper input[type="date"]:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.2);
}

.date-input-wrapper input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}

.view-slots-btn {
    width: 100%;
    margin-bottom: 1rem;
}

/* Quick Actions Panel */
.quick-actions-panel {
    grid-column: span 8;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    padding: 1.5rem;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.quick-actions-panel h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    color: white;
    margin-bottom: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quick-actions-panel h2 i {
    color: var(--accent-color);
    font-size: 1.1rem;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.action-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.action-card:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.action-card i {
    font-size: 1.8rem;
    margin-bottom: 0.8rem;
    display: block;
}

.action-card.add-slots i { color: var(--success); }
.action-card.enable-day i { color: var(--info); }
.action-card.disable-day i { color: var(--danger); }

.action-card span {
    font-size: 0.85rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9);
    display: block;
}

.action-card p {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.5);
    margin-top: 0.3rem;
}

.btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    box-shadow: 0 4px 12px rgba(44, 95, 111, 0.3);
}

.btn-primary:hover {
    box-shadow: 0 6px 20px rgba(44, 95, 111, 0.4);
}

.btn-success {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
    background: rgba(16, 185, 129, 0.3);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.3);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 6px 20px rgba(255, 255, 255, 0.1);
}

.slots-section {
    grid-column: span 12;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    min-height: 300px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.slots-section h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    color: white;
    margin-bottom: 0.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.slots-section h2 i {
    color: var(--accent-color);
    font-size: 1.1rem;
}
.date-display {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.85rem;
    margin-bottom: 1rem;
}

.slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.slot-card {
    background: rgba(255, 255, 255, 0.05);
    padding: 1rem;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.slot-card:hover {
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-2px);
}

.slot-card.available {
    border-color: rgba(110, 231, 183, 0.4);
    background: rgba(16, 185, 129, 0.1);
}

.slot-card.available:hover {
    background: rgba(16, 185, 129, 0.15);
}

.slot-card.unavailable {
    border-color: rgba(252, 165, 165, 0.4);
    background: rgba(239, 68, 68, 0.1);
    opacity: 0.8;
}

.slot-card.booked {
    border-color: rgba(251, 191, 36, 0.4);
    background: rgba(245, 158, 11, 0.1);
}

.slot-info {
    text-align: center;
}

.slot-time {
    font-size: 1.1rem;
    font-weight: 700;
    color: white;
}

.slot-status {
    font-size: 0.7rem;
    margin-top: 0.3rem;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.slot-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.icon-btn {
    width: 30px;
    height: 30px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-size: 0.8rem;
}

.icon-btn:hover {
    transform: scale(1.1);
}

.icon-btn.toggle {
    background: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.icon-btn.toggle:hover {
    background: rgba(59, 130, 246, 0.3);
}

.icon-btn.delete {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.icon-btn.delete:hover {
    background: rgba(239, 68, 68, 0.3);
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: rgba(255, 255, 255, 0.5);
}
.empty-state svg {
    width: 80px;
    height: 80px;
    margin-bottom: 20px;
    opacity: 0.3;
    stroke: rgba(255, 255, 255, 0.5);
}
.empty-state h3 {
    font-size: 18px;
    margin-bottom: 8px;
    color: rgba(255, 255, 255, 0.7);
}
.empty-state p {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.5);
}
.add-slots-section {
    background: rgba(255, 255, 255, 0.03);
    padding: 1.2rem;
    border-radius: 12px;
    margin-top: 1.5rem;
    border: 1px dashed rgba(255, 255, 255, 0.15);
}

.add-slots-section h3 {
    font-size: 0.95rem;
    margin-bottom: 1rem;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.add-slots-section h3::before {
    content: '\f067';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    color: var(--accent-color);
    font-size: 0.8rem;
}

.time-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.time-checkbox {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 0.8rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
}

.time-checkbox:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

.time-checkbox input {
    margin-right: 0.5rem;
    accent-color: var(--info);
}

.time-checkbox.checked {
    background: rgba(59, 130, 246, 0.15);
    border-color: rgba(59, 130, 246, 0.4);
    color: #93c5fd;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .bento-grid {
        grid-template-columns: repeat(6, 1fr);
    }

    .calendar-section {
        grid-column: span 6;
    }

    .quick-actions-panel {
        grid-column: span 6;
    }

    .slots-section {
        grid-column: span 6;
    }

    .quick-actions {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .bento-grid {
        grid-template-columns: 1fr;
    }

    .calendar-section,
    .quick-actions-panel,
    .slots-section {
        grid-column: span 1;
    }

    .quick-actions {
        grid-template-columns: 1fr;
    }

    .slots-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .time-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 480px) {
    .slots-grid {
        grid-template-columns: 1fr;
    }

    .time-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
</head>
<body>
<!-- Animated Nebula Background -->
<div class="animated-background"></div>
<div class="nebula-particles"></div>

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
        <h1><i class="fas fa-clock"></i> Manage Available Time Slots</h1>
        <div class="subtitle">Select a date to view and manage time slots</div>
        <a href="manage_appointments.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Appointments</span>
        </a>
    </div>

    <?php if ($message): ?>
        <div class="message success" id="message">
            <i class="fas fa-check-circle"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error" id="message">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="main-grid">
        <div class="bento-grid">
            <!-- Calendar Section -->
            <div class="calendar-section">
                <h2><i class="fas fa-calendar-alt"></i> Select Date</h2>

                <div class="date-input-wrapper">
                    <label>Choose a date:</label>
                    <input type="date" id="selectedDate" min="<?= date('Y-m-d') ?>">
                </div>

                <button class="btn btn-primary view-slots-btn" onclick="viewSlots()">
                    <i class="fas fa-search"></i> View Slots
                </button>
            </div>

            <!-- Quick Actions Panel -->
            <div class="quick-actions-panel">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="quick-actions">
                    <div class="action-card add-slots" onclick="addDefaultSlots()">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Default Slots</span>
                        <p>9 AM - 4:30 PM</p>
                    </div>
                    <div class="action-card enable-day" onclick="enableDay()">
                        <i class="fas fa-check-circle"></i>
                        <span>Enable All</span>
                        <p>Enable entire day</p>
                    </div>
                    <div class="action-card disable-day" onclick="disableDay()">
                        <i class="fas fa-ban"></i>
                        <span>Disable All</span>
                        <p>Disable entire day</p>
                    </div>
                </div>
            </div>

            <!-- Slots Display -->
            <div class="slots-section">
                <h2><i class="fas fa-clock"></i> Time Slots</h2>
                <div class="date-display" id="dateDisplay">Select a date to view slots</div>

            <div id="slotsContainer">
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h3>No date selected</h3>
                    <p>Choose a date from the calendar to manage time slots</p>
                </div>
            </div>

            <!-- Add Custom Slots Section -->
            <div class="add-slots-section" id="addSlotsSection" style="display: none;">
                <h3>Add Custom Time Slots</h3>
                <form id="addCustomSlotsForm" onsubmit="addCustomSlots(event)">
                    <div class="time-grid">
                        <?php
                        $all_times = [
                            '09:00:00' => '09:00 AM', '09:30:00' => '09:30 AM',
                            '10:00:00' => '10:00 AM', '10:30:00' => '10:30 AM',
                            '11:00:00' => '11:00 AM', '11:30:00' => '11:30 AM',
                            '12:00:00' => '12:00 PM', '12:30:00' => '12:30 PM',
                            '13:00:00' => '01:00 PM', '13:30:00' => '01:30 PM',
                            '14:00:00' => '02:00 PM', '14:30:00' => '02:30 PM',
                            '15:00:00' => '03:00 PM', '15:30:00' => '03:30 PM',
                            '16:00:00' => '04:00 PM', '16:30:00' => '04:30 PM',
                            '17:00:00' => '05:00 PM', '17:30:00' => '05:30 PM'
                        ];
                        foreach ($all_times as $value => $label):
                        ?>
                        <label class="time-checkbox">
                            <input type="checkbox" name="times[]" value="<?= $value ?>" onchange="this.parentElement.classList.toggle('checked', this.checked)">
                            <?= $label ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 12px;">Add Selected Slots</button>
                </form>
            </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentDate = '';

// Set today as default
document.getElementById('selectedDate').value = new Date().toISOString().split('T')[0];

function viewSlots() {
    const dateInput = document.getElementById('selectedDate');
    const date = dateInput.value;

    if (!date) {
        alert('Please select a date');
        return;
    }

    currentDate = date;
    const dateObj = new Date(date + 'T00:00:00');
    document.getElementById('dateDisplay').textContent = dateObj.toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });

    // Fetch slots
    fetch(`?action=get_slots&date=${encodeURIComponent(date)}`)
        .then(response => response.json())
        .then(data => {
            renderSlots(data.slots || []);
            document.getElementById('addSlotsSection').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load slots');
        });
}

function renderSlots(slots) {
    const container = document.getElementById('slotsContainer');

    if (slots.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3>No time slots configured</h3>
                <p>Click "Add Default Slots" to create standard clinic hours</p>
            </div>
        `;
        return;
    }

    let html = '<div class="slots-grid">';
    slots.forEach(slot => {
        const time12 = formatTime(slot.time);
        const statusClass = slot.booked_count > 0 ? 'booked' : (slot.is_available ? 'available' : 'unavailable');
        const statusText = slot.booked_count > 0 ? `${slot.booked_count} booked` : (slot.is_available ? 'Available' : 'Disabled');
        const toggleIcon = slot.is_available ? '<i class="fas fa-pause"></i>' : '<i class="fas fa-play"></i>';
        const toggleAction = slot.is_available ? 0 : 1;

        html += `
            <div class="slot-card ${statusClass}">
                <div class="slot-info">
                    <div class="slot-time">${time12}</div>
                    <div class="slot-status">${statusText}</div>
                </div>
                <div class="slot-actions">
                    <button class="icon-btn toggle" onclick="toggleSlot(${slot.slot_id}, ${toggleAction})" title="${slot.is_available ? 'Disable' : 'Enable'}">
                        ${toggleIcon}
                    </button>
                    <button class="icon-btn delete" onclick="deleteSlot(${slot.slot_id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    html += '</div>';

    container.innerHTML = html;
}

function formatTime(time24) {
    const [hours, minutes] = time24.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

function addDefaultSlots() {
    if (!currentDate) {
        alert('Please select a date first');
        return;
    }

    if (!confirm('Add default time slots (9 AM - 4:30 PM, excluding lunch)?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_day_slots');
    formData.append('date', currentDate);

    fetch('', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) viewSlots();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add slots');
    });
}

function enableDay() {
    if (!currentDate) {
        alert('Please select a date first');
        return;
    }

    if (!confirm('Enable all time slots for this day?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'enable_day');
    formData.append('date', currentDate);

    fetch('', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) viewSlots();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to enable day');
    });
}

function disableDay() {
    if (!currentDate) {
        alert('Please select a date first');
        return;
    }

    if (!confirm('Disable all time slots for this day? Existing appointments will not be cancelled.')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'disable_day');
    formData.append('date', currentDate);

    fetch('', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) viewSlots();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to disable day');
    });
}

function toggleSlot(slotId, isAvailable) {
    const formData = new FormData();
    formData.append('action', 'toggle_slot');
    formData.append('slot_id', slotId);
    formData.append('is_available', isAvailable);

    fetch('', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) viewSlots();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to toggle slot');
    });
}

function deleteSlot(slotId) {
    if (!confirm('Delete this time slot permanently?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_slot');
    formData.append('slot_id', slotId);

    fetch('', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) viewSlots();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete slot');
    });
}

function addCustomSlots(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    formData.append('action', 'add_custom_slots');
    formData.append('date', currentDate);

    fetch('', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) {
            viewSlots();
            event.target.reset();
            document.querySelectorAll('.time-checkbox').forEach(cb => cb.classList.remove('checked'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add slots');
    });
}

function showMessage(text, isSuccess) {
    const existing = document.getElementById('message');
    if (existing) existing.remove();

    const msg = document.createElement('div');
    msg.id = 'message';
    msg.className = 'message ' + (isSuccess ? 'success' : 'error');
    msg.innerHTML = `<i class="fas fa-${isSuccess ? 'check' : 'exclamation'}-circle"></i><span>${text}</span>`;

    document.querySelector('.main-container').insertBefore(msg, document.querySelector('.main-grid'));

    setTimeout(() => msg.remove(), 5000);
}

// Auto-hide message after 5 seconds
setTimeout(() => {
    const msg = document.getElementById('message');
    if (msg) msg.remove();
}, 5000);
</script>
</body>
</html>
