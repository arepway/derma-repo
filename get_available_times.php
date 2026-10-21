<?php
// API endpoint to fetch available time slots for a given date
require_once 'auth_check.php';
requirePatientLogin(false); // Don't redirect, just check

// Set timezone to prevent date shifting issues
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

// Use centralized database connection
require_once 'db_connect.php';

// Convert PDO to mysqli for this API (or keep using PDO)
$mysqli = new mysqli("localhost", "root", "", "dermatology_clinic", 3307);
if ($mysqli->connect_errno) {
    echo json_encode(['error' => 'Database connection failed: ' . $mysqli->connect_error]);
    exit;
}

$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['error' => 'Date parameter is required']);
    exit;
}

// Validate date format
$dateObj = DateTime::createFromFormat('Y-m-d', $date, new DateTimeZone('Asia/Manila'));
if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

// Check if the date is in the past (compare dates at midnight in local timezone)
$today = new DateTime('today', new DateTimeZone('Asia/Manila'));
if ($dateObj < $today) {
    echo json_encode(['error' => 'Cannot book appointments in the past']);
    exit;
}

// Get available time slots from the database
// Join with appointments to check if slots are already booked
$sql = "
    SELECT
        ats.time,
        ats.is_available,
        COUNT(a.appointment_id) as booked_count,
        ats.max_appointments
    FROM available_time_slots ats
    LEFT JOIN appointment a ON a.date = ats.date AND a.time = ats.time AND a.status != 'cancelled'
    WHERE ats.date = ?
    GROUP BY ats.slot_id, ats.time, ats.is_available, ats.max_appointments
    HAVING ats.is_available = 1 AND booked_count < ats.max_appointments
    ORDER BY ats.time ASC
";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Database query failed']);
    exit;
}

$stmt->bind_param('s', $date);
$stmt->execute();
$result = $stmt->get_result();

$available_times = [];
while ($row = $result->fetch_assoc()) {
    $time = substr($row['time'], 0, 5); // Convert HH:MM:SS to HH:MM
    $available_times[] = [
        'value' => $time,
        'label' => date('h:i A', strtotime($row['time']))
    ];
}

$stmt->close();

// If no slots found in database, return default time slots (backward compatibility)
if (empty($available_times)) {
    // Check if any appointments exist for this date
    $checkSql = "SELECT time FROM appointment WHERE date = ? AND status != 'cancelled'";
    $checkStmt = $mysqli->prepare($checkSql);
    $checkStmt->bind_param('s', $date);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    $booked_times = [];
    while ($row = $checkResult->fetch_assoc()) {
        $booked_times[] = substr($row['time'], 0, 5);
    }
    $checkStmt->close();

    // Default time slots
    $default_times = [
        '09:00' => '09:00 AM',
        '09:30' => '09:30 AM',
        '10:00' => '10:00 AM',
        '10:30' => '10:30 AM',
        '11:00' => '11:00 AM',
        '11:30' => '11:30 AM',
        '14:00' => '02:00 PM',
        '14:30' => '02:30 PM',
        '15:00' => '03:00 PM',
        '15:30' => '03:30 PM',
        '16:00' => '04:00 PM',
        '16:30' => '04:30 PM'
    ];

    foreach ($default_times as $time => $label) {
        if (!in_array($time, $booked_times)) {
            $available_times[] = [
                'value' => $time,
                'label' => $label
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'times' => $available_times
]);
?>