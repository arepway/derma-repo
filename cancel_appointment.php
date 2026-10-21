<?php
require_once 'auth_check.php';
requirePatientLogin();

include('db_connect.php');

// Ensure we have a PDO connection from db_connect.php
if (!isset($pdo) || !$pdo) {
    die('Database connection not available.');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = (int) $_POST['appointment_id'];
    $user_id = (int) $_SESSION['user_id'];

    if ($appointment_id <= 0) {
        $message = "Invalid appointment id.";
    } else {
        // Verify the appointment belongs to the logged-in user
        $stmt = $pdo->prepare("SELECT * FROM appointment WHERE appointment_id = ? AND patient_id = ?");
        $stmt->execute([$appointment_id, $user_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($appointment) {
            // Delete the appointment
            $delete_stmt = $pdo->prepare("DELETE FROM appointment WHERE appointment_id = ?");
            
            if ($delete_stmt->execute([$appointment_id])) {
                $message = "Appointment cancelled successfully.";
            } else {
                $message = "Error cancelling appointment.";
            }
        } else {
            $message = "Invalid appointment or unauthorized access.";
        }
    }
}

// Fetch user's upcoming appointments
$user_id = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM appointment WHERE patient_id = ? AND date >= CURDATE() ORDER BY date, time");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Appointment</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Minimal inline styles in case styles.css is missing */
        .container { max-width:780px; margin:40px auto; padding:20px; }
        table { width:100%; border-collapse:collapse; margin-top:16px; }
        th,td { padding:10px 12px; border-bottom:1px solid #eee; text-align:left; }
        .cancel-btn { background:#ef4444; color:#fff; padding:8px 12px; border:none; border-radius:6px; cursor:pointer; }
        .message { padding:10px 12px; background:#e6ffef; color:#065f46; border-radius:6px; margin-bottom:12px; }
        .back-link { display:inline-block; margin-top:14px; color:#0366d6; text-decoration:none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cancel Appointment</h2>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (count($appointments) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['date']))); ?></td>
                            <td><?php echo htmlspecialchars(date('H:i', strtotime($row['time']))); ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?');" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars((int) ($row['appointment_id'] ?? 0)); ?>">
                                    <button type="submit" class="cancel-btn">Cancel</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No upcoming appointments found.</p>
        <?php endif; ?>
        
        <a href="patient_dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>