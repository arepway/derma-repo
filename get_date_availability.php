<?php
// API endpoint to check date availability status for multiple dates
require_once 'auth_check.php';
requirePatientLogin(false); // Don't redirect, just check

// Set timezone to prevent date shifting issues
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

// Use centralized database connection
require_once 'db_connect.php';

// Database configuration for mysqli
$host = 'localhost';
$port = '3307';
$dbname = 'dermatology_clinic';
$username = 'root';
$password = '';

try {
    $mysqli = new mysqli($host, $username, $password, $dbname, $port);

    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }

    // Get date range from request
    $start_date = $_GET['start_date'] ?? date('Y-m-d');
    $days = intval($_GET['days'] ?? 30);

    // Calculate end date
    $end_date = date('Y-m-d', strtotime($start_date . " + $days days"));

    // Query to check availability for each date
    // A date is considered UNAVAILABLE if:
    // 1. All time slots for that day have is_available = 0, OR
    // 2. No time slots exist for that day (and it's not a default day)

    $sql = "SELECT
                date,
                COUNT(*) as total_slots,
                SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available_slots
            FROM available_time_slots
            WHERE date >= ? AND date <= ?
            GROUP BY date
            ORDER BY date ASC";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $availability = [];

    // Store dates that exist in the database
    $db_dates = [];
    while ($row = $result->fetch_assoc()) {
        $date = $row['date'];
        $total_slots = intval($row['total_slots']);
        $available_slots = intval($row['available_slots']);

        $db_dates[] = $date;

        // Date is available if at least one slot is available
        $availability[$date] = [
            'available' => $available_slots > 0,
            'total_slots' => $total_slots,
            'available_slots' => $available_slots
        ];
    }

    // Generate all dates in the range
    $current = new DateTime($start_date, new DateTimeZone('Asia/Manila'));
    $end = new DateTime($end_date, new DateTimeZone('Asia/Manila'));

    while ($current <= $end) {
        $dateStr = $current->format('Y-m-d');

        // If date doesn't exist in database, make it available by default
        if (!in_array($dateStr, $db_dates)) {
            $availability[$dateStr] = [
                'available' => true,
                'total_slots' => 0,
                'available_slots' => 0
            ];
        }

        $current->modify('+1 day');
    }

    // Sort by date
    ksort($availability);

    $stmt->close();
    $mysqli->close();

    echo json_encode([
        'success' => true,
        'availability' => $availability,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
