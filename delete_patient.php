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

// Get patient ID from POST
$patient_id = $_POST['patient_id'] ?? null;

if (!$patient_id) {
    header("Location: manage_patients.php?error=missing_id");
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get patient info before deletion for logging
    $stmt = $pdo->prepare("SELECT full_name, email FROM patient WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();

    if (!$patient) {
        $pdo->rollback();
        header("Location: manage_patients.php?error=not_found");
        exit;
    }

    // Delete all related records in proper order (child tables first)

    // 1. Delete medication records
    $stmt = $pdo->prepare("DELETE FROM medication_records WHERE appointment_id IN (SELECT appointment_id FROM appointment WHERE patient_id = ?)");
    $stmt->execute([$patient_id]);

    // 2. Delete medical records
    $stmt = $pdo->prepare("DELETE FROM medical_record WHERE appointment_id IN (SELECT appointment_id FROM appointment WHERE patient_id = ?)");
    $stmt->execute([$patient_id]);

    // 3. Delete appointments
    $stmt = $pdo->prepare("DELETE FROM appointment WHERE patient_id = ?");
    $stmt->execute([$patient_id]);

    // 4. Delete messages
    $stmt = $pdo->prepare("DELETE FROM messages WHERE patient_id = ?");
    $stmt->execute([$patient_id]);

    // 5. Delete patient feedback (if exists)
    $stmt = $pdo->prepare("DELETE FROM patient_feedback WHERE patient_id = ?");
    $stmt->execute([$patient_id]);

    // 6. Finally delete the patient record
    $stmt = $pdo->prepare("DELETE FROM patient WHERE patient_id = ?");
    $stmt->execute([$patient_id]);

    // Commit transaction
    $pdo->commit();

    // Redirect with success message
    header("Location: manage_patients.php?success=deleted&name=" . urlencode($patient['full_name']));
    exit;

} catch(PDOException $e) {
    // Rollback on error
    $pdo->rollback();

    // Redirect with error message
    header("Location: manage_patients.php?error=delete_failed&message=" . urlencode($e->getMessage()));
    exit;
}
?>
