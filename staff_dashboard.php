<?php
require_once 'auth_check.php';
requireStaffLogin();
require_once 'db_connect.php';

$staff_id = $_SESSION['user_id'];
$setup_mode = $_SESSION['setup_mode'] ?? false;

// Handle first-time setup
if ($setup_mode && $_POST['action'] ?? '' == 'create_account') {
    $username = $_POST['username'] ?? '';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO medical_staff (username, password, full_name, email, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $full_name, $email, $phone]);

        $new_staff_id = $pdo->lastInsertId();

        // Update session with real account details
        $_SESSION['user_id'] = $new_staff_id;
        $_SESSION['user_name'] = $full_name;
        unset($_SESSION['setup_mode']);

        $setup_success = "Account created successfully! You can now use your new credentials to login.";

        // Get the new staff data
        $stmt = $pdo->prepare("SELECT * FROM medical_staff WHERE staff_id = ?");
        $stmt->execute([$new_staff_id]);
        $staff = $stmt->fetch();

    } catch(PDOException $e) {
        $setup_error = "Account creation failed: " . $e->getMessage();
    }
}

// Get staff info (only if not in setup mode)
if (!$setup_mode) {
    $stmt = $pdo->prepare("SELECT * FROM medical_staff WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch();
}

// Check injection stock for low stock notification
$stock_alert = null;
$current_stock = 0;
try {
    $stmt = $pdo->query("SELECT quantity, low_stock_threshold FROM stock WHERE item_name = 'Injection' LIMIT 1");
    $stock_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($stock_row) {
        $current_stock = intval($stock_row['quantity']);
        $threshold = intval($stock_row['low_stock_threshold']);

        if ($current_stock <= 0) {
            $stock_alert = [
                'level' => 'danger',
                'icon' => 'exclamation-triangle',
                'message' => 'Out of Stock! No injections available. Please add stock immediately.',
                'link' => 'manage_stock.php'
            ];
        } elseif ($current_stock <= $threshold) {
            $stock_alert = [
                'level' => 'warning',
                'icon' => 'exclamation-triangle',
                'message' => "Low Stock Warning! Only $current_stock injection(s) remaining.",
                'link' => 'manage_stock.php'
            ];
        }
    }
} catch (PDOException $e) {
    // Stock table might not exist yet
}

// Handle profile update
if ($_POST['action'] ?? '' == 'update_profile') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // Verify current password if trying to change password
    if ($new_password && !password_verify($current_password, $staff['password'])) {
        $profile_error = "Current password is incorrect.";
    } else {
        try {
            if ($new_password) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE medical_staff SET full_name = ?, email = ?, phone = ?, password = ? WHERE staff_id = ?");
                $stmt->execute([$full_name, $email, $phone, $hashed_password, $staff_id]);
            } else {
                // Update without password change
                $stmt = $pdo->prepare("UPDATE medical_staff SET full_name = ?, email = ?, phone = ? WHERE staff_id = ?");
                $stmt->execute([$full_name, $email, $phone, $staff_id]);
            }

            $profile_success = "Profile updated successfully!";

            // Refresh staff data
            $stmt = $pdo->prepare("SELECT * FROM medical_staff WHERE staff_id = ?");
            $stmt->execute([$staff_id]);
            $staff = $stmt->fetch();
            $_SESSION['user_name'] = $staff['full_name'];

        } catch(PDOException $e) {
            $profile_error = "Update failed: " . $e->getMessage();
        }
    }
}

// Get dashboard statistics (only if not in setup mode)
if (!$setup_mode) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointment");
    $stmt->execute();
    $total_appointments = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointment WHERE date = CURDATE()");
    $stmt->execute();
    $today_appointments = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM patient");
    $stmt->execute();
    $total_patients = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM messages WHERE is_read = 0 AND sender_type = 'patient'");
    $stmt->execute();
    $unread_messages = $stmt->fetch()['total'];

    // Get today's appointments
    $stmt = $pdo->prepare("
        SELECT a.*, p.full_name as patient_name, s.name as service_name
        FROM appointment a
        JOIN patient p ON a.patient_id = p.patient_id
        JOIN service s ON a.service_id = s.service_id
        WHERE a.date = CURDATE()
        ORDER BY a.time ASC
    ");
    $stmt->execute();
    $todays_appointments = $stmt->fetchAll();

    // Get upcoming appointments (future appointments only, excluding past ones)
    $stmt = $pdo->prepare("
        SELECT a.*, p.full_name as patient_name, s.name as service_name
        FROM appointment a
        JOIN patient p ON a.patient_id = p.patient_id
        JOIN service s ON a.service_id = s.service_id
        WHERE a.status = 'Scheduled'
        AND (
            a.date > CURDATE()
            OR (a.date = CURDATE() AND a.time > CURTIME())
        )
        ORDER BY a.date ASC, a.time ASC
        LIMIT 10
    ");
    $stmt->execute();
    $upcoming_appointments = $stmt->fetchAll();

    // Get recent messages
    $stmt = $pdo->prepare("
        SELECT m.*, p.full_name as patient_name
        FROM messages m
        JOIN patient p ON m.patient_id = p.patient_id
        WHERE m.is_read = 0 AND m.sender_type = 'patient'
        ORDER BY m.timestamp DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_messages = $stmt->fetchAll();
} else {
    // Setup mode - initialize empty values
    $total_appointments = 0;
    $today_appointments = 0;
    $total_patients = 0;
    $unread_messages = 0;
    $todays_appointments = [];
    $upcoming_appointments = [];
    $recent_messages = [];
}

// Handle logout
if (isset($_GET['logout'])) {
    header("Location: logout.php?type=staff");
    exit;
}

// Handle account deletion
if ($_POST['action'] ?? '' == 'delete_account') {
    $entered_password = $_POST['confirm_password'] ?? '';

    // Verify password before deletion
    if (password_verify($entered_password, $staff['password'])) {
        try {
            $pdo->beginTransaction();

            // Check if staff has any appointments
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointment WHERE staff_id = ?");
            $stmt->execute([$staff_id]);
            $appointment_count = $stmt->fetch()['count'];

            if ($appointment_count > 0) {
                $pdo->rollback();
                $delete_error = "Cannot delete account. This staff member has $appointment_count appointment(s) in the system. For data integrity, staff accounts with appointments cannot be deleted.";
            } else {
                // Delete all related records created by this staff member

                // Delete medication records recorded by this staff
                $stmt = $pdo->prepare("DELETE FROM medication_records WHERE recorded_by = ?");
                $stmt->execute([$staff_id]);

                // Delete messages sent by this staff
                $stmt = $pdo->prepare("DELETE FROM messages WHERE staff_id = ? AND sender_type = 'staff'");
                $stmt->execute([$staff_id]);

                // Finally delete staff account
                $stmt = $pdo->prepare("DELETE FROM medical_staff WHERE staff_id = ?");
                $stmt->execute([$staff_id]);

                $pdo->commit();

                // Destroy session and redirect
                session_destroy();
                header("Location: index_staff.php?deleted=1");
                exit;
            }

        } catch(PDOException $e) {
            $pdo->rollback();
            $delete_error = "Failed to delete account: " . $e->getMessage();
        }
    } else {
        $delete_error = "Incorrect password. Account not deleted.";
    }
}

// Handle add medical staff
if ($_POST['action'] ?? '' == 'add_staff') {
    $new_full_name = $_POST['new_full_name'] ?? '';
    $new_username = $_POST['new_username'] ?? '';
    $new_password = password_hash($_POST['new_password'] ?? '', PASSWORD_DEFAULT);
    $new_email = $_POST['new_email'] ?? '';
    $new_phone = $_POST['new_phone'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO medical_staff (full_name, username, password, email, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$new_full_name, $new_username, $new_password, $new_email, $new_phone]);
        $add_staff_success = "Medical staff account created successfully!";
    } catch(PDOException $e) {
        $add_staff_error = "Failed to create staff: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard — True Beauty Concept</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Swiss Modernist Editorial Design System */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Neutral Matte Palette - Warm Grays */
            --background: #fafaf9;
            --surface: #ffffff;
            --border: #e7e5e4;
            --border-strong: #d6d3d1;

            --text-primary: #1c1917;
            --text-secondary: #57534e;
            --text-tertiary: #78716c;
            --text-muted: #a8a29e;

            /* Editorial Accent - Precise Red */
            --accent: #dc2626;
            --accent-light: #fee2e2;

            /* Status Colors - Muted */
            --success: #15803d;
            --warning: #ca8a04;
            --danger: #dc2626;

            /* Grid System */
            --grid-gap: 24px;
            --grid-margin: 48px;

            /* Typography Scale */
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-size-sm: 0.8125rem;
            --font-size-base: 0.9375rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.5rem;
            --font-size-2xl: 2rem;
            --font-size-3xl: 3rem;

            /* Line Heights */
            --line-tight: 1.2;
            --line-normal: 1.5;
            --line-relaxed: 1.75;

            /* Spacing Scale */
            --space-1: 4px;
            --space-2: 8px;
            --space-3: 12px;
            --space-4: 16px;
            --space-5: 20px;
            --space-6: 24px;
            --space-8: 32px;
            --space-10: 40px;
            --space-12: 48px;
            --space-16: 64px;
        }

        body {
            font-family: var(--font-family);
            background: var(--background);
            color: var(--text-primary);
            font-size: var(--font-size-base);
            line-height: var(--line-normal);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Utility Classes */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 var(--grid-margin);
        }

        /* Layout Structure */
        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Left Sidebar Navigation */
        .sidebar {
            width: 280px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: var(--space-8) var(--space-6);
            border-bottom: 1px solid var(--border);
        }

        .brand-name {
            font-size: var(--font-size-lg);
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }

        .brand-role {
            font-size: var(--font-size-sm);
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        .sidebar-section {
            padding: var(--space-6) 0;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-section-title {
            font-size: 0.6875rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            padding: 0 var(--space-6);
            margin-bottom: var(--space-3);
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav-item {
            display: block;
            padding: var(--space-3) var(--space-6);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: var(--font-size-sm);
            font-weight: 500;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-nav-item:hover {
            background: var(--background);
            color: var(--text-primary);
        }

        .sidebar-nav-item.active {
            background: var(--background);
            color: var(--text-primary);
            border-left-color: var(--accent);
        }

        .nav-badge {
            background: var(--accent);
            color: white;
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 2px 6px;
        }

        .sidebar-footer {
            padding: var(--space-6);
            border-top: 1px solid var(--border);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--text-primary);
            color: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: var(--font-size-sm);
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-title {
            font-size: var(--font-size-sm);
            color: var(--text-tertiary);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 280px;
            background: var(--background);
        }

        /* Header in main content */
        .masthead {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: var(--space-6) var(--grid-margin);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .masthead-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: var(--font-size-xl);
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--text-primary);
        }

        .page-meta {
            font-size: var(--font-size-sm);
            color: var(--text-tertiary);
        }

        /* Content Container */
        .content-wrapper {
            padding: var(--grid-margin);
        }

        /* Grid Layout System */
        .layout-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--grid-gap);
            margin-bottom: var(--space-12);
        }

        /* Analytical Overview - Stats Bar */
        .stats-overview {
            background: var(--surface);
            border: 1px solid var(--border);
            margin-bottom: var(--space-12);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-bottom: 1px solid var(--border);
        }

        .stat-cell {
            padding: var(--space-8);
            border-right: 1px solid var(--border);
        }

        .stat-cell:last-child {
            border-right: none;
        }

        .stat-label {
            font-size: var(--font-size-sm);
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
            margin-bottom: var(--space-2);
        }

        .stat-value {
            font-size: var(--font-size-3xl);
            font-weight: 300;
            letter-spacing: -0.03em;
            line-height: var(--line-tight);
            color: var(--text-primary);
        }

        .stat-meta {
            font-size: var(--font-size-sm);
            color: var(--text-muted);
            margin-top: var(--space-2);
        }

        /* Module - Standard Content Block */
        .module {
            background: var(--surface);
            border: 1px solid var(--border);
            margin-bottom: var(--space-6);
        }

        .module-header {
            padding: var(--space-6);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .module-title {
            font-size: var(--font-size-base);
            font-weight: 600;
            letter-spacing: -0.01em;
            color: var(--text-primary);
        }

        .module-action {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.15s ease;
        }

        .module-action:hover {
            color: var(--accent);
        }

        .module-body {
            padding: var(--space-6);
        }

        /* Timeline Strip */
        .timeline-strip {
            background: var(--surface);
            border: 1px solid var(--border);
            margin-bottom: var(--space-12);
            overflow-x: auto;
        }

        .timeline-header {
            padding: var(--space-6);
            border-bottom: 1px solid var(--border);
        }

        .timeline-title {
            font-size: var(--font-size-base);
            font-weight: 600;
            color: var(--text-primary);
        }

        .timeline-content {
            padding: var(--space-6);
        }

        .timeline-items {
            display: flex;
            gap: var(--space-6);
            min-width: max-content;
        }

        .timeline-item {
            flex: 0 0 280px;
            padding: var(--space-5);
            border: 1px solid var(--border);
            background: var(--background);
            transition: border-color 0.15s ease;
        }

        .timeline-item:hover {
            border-color: var(--border-strong);
        }

        .timeline-time {
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }

        .timeline-patient {
            font-size: var(--font-size-base);
            color: var(--text-secondary);
            margin-bottom: var(--space-1);
        }

        .timeline-service {
            font-size: var(--font-size-sm);
            color: var(--text-tertiary);
        }

        /* Stacked List Module */
        .list-stack {
            list-style: none;
        }

        .list-item {
            padding: var(--space-5);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: var(--space-4);
            text-decoration: none;
            transition: background 0.15s ease;
        }

        .list-item:hover {
            background: var(--background);
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-initial {
            width: 40px;
            height: 40px;
            background: var(--text-primary);
            color: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: var(--font-size-sm);
            flex-shrink: 0;
        }

        .list-content {
            flex: 1;
            min-width: 0;
        }

        .list-primary {
            font-size: var(--font-size-base);
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: var(--space-1);
        }

        .list-secondary {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .list-badge {
            background: var(--accent);
            color: white;
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 2px 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Clean Table Design */
        .table-wrapper {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--font-size-sm);
        }

        .data-table thead {
            background: var(--background);
        }

        .data-table th {
            padding: var(--space-3) var(--space-5);
            text-align: left;
            font-weight: 600;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
            border-bottom: 1px solid var(--border-strong);
        }

        .data-table td {
            padding: var(--space-4) var(--space-5);
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }

        .data-table tbody tr:hover {
            background: var(--background);
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            font-size: var(--font-size-sm);
            font-weight: 500;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .status-scheduled .status-dot {
            background: var(--warning);
        }

        .status-completed .status-dot {
            background: var(--success);
        }

        .status-cancelled .status-dot {
            background: var(--danger);
        }

        /* Time Badge */
        .time-badge {
            background: var(--text-primary);
            color: var(--surface);
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 2px 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-left: var(--space-2);
        }

        /* Alert Banner */
        .alert-banner {
            background: var(--accent-light);
            border: 1px solid var(--accent);
            border-left-width: 3px;
            padding: var(--space-5) var(--space-6);
            margin-bottom: var(--space-12);
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }

        .alert-icon {
            color: var(--accent);
            font-size: 1.25rem;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-size: var(--font-size-base);
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-1);
        }

        .alert-message {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-3) var(--space-5);
            font-size: var(--font-size-sm);
            font-weight: 600;
            text-decoration: none;
            border: 1px solid;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--text-primary);
            color: var(--surface);
            border-color: var(--text-primary);
        }

        .btn-primary:hover {
            background: var(--text-secondary);
            border-color: var(--text-secondary);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border-color: var(--border-strong);
        }

        .btn-secondary:hover {
            border-color: var(--text-primary);
            color: var(--text-primary);
        }

        .btn-accent {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .btn-accent:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }

        /* Empty State */
        .empty-state {
            padding: var(--space-16) var(--space-6);
            text-align: center;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            background: var(--border);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-4);
            font-size: 1.5rem;
        }

        .empty-message {
            font-size: var(--font-size-sm);
            color: var(--text-tertiary);
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-dialog {
            background: var(--surface);
            border: 1px solid var(--border);
            max-width: 480px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: var(--space-6);
            border-bottom: 1px solid var(--border);
        }

        .modal-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-body {
            padding: var(--space-6);
        }

        .modal-footer {
            padding: var(--space-6);
            border-top: 1px solid var(--border);
            display: flex;
            gap: var(--space-3);
            justify-content: flex-end;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: var(--space-5);
        }

        .form-label {
            display: block;
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }

        .form-input {
            width: 100%;
            padding: var(--space-3) var(--space-4);
            font-size: var(--font-size-base);
            font-family: var(--font-family);
            color: var(--text-primary);
            background: var(--surface);
            border: 1px solid var(--border-strong);
            transition: border-color 0.15s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--text-primary);
        }

        .form-warning {
            background: var(--accent-light);
            border: 1px solid var(--accent);
            padding: var(--space-4);
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            margin-bottom: var(--space-5);
        }

        /* Responsive */
        @media (max-width: 968px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .masthead {
                padding: var(--space-4) var(--space-6);
            }

            .content-wrapper {
                padding: var(--space-6);
            }
        }

        @media (max-width: 768px) {
            :root {
                --grid-margin: 16px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-cell {
                border-right: 1px solid var(--border);
            }

            .stat-cell:nth-child(2n) {
                border-right: none;
            }

            .stat-cell:nth-child(-n+2) {
                border-bottom: 1px solid var(--border);
            }
        }

        /* Print-like Texture (Subtle) */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.01) 2px, rgba(0,0,0,0.01) 4px);
            pointer-events: none;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <?php if ($setup_mode): ?>
        <!-- Setup Mode -->
        <div class="container" style="margin-top: var(--space-16);">
            <div class="module">
                <div class="module-header">
                    <h1 class="module-title">Account Setup</h1>
                </div>
                <div class="module-body">
                    <?php if (isset($setup_error)): ?>
                        <div class="alert-banner">
                            <i class="fas fa-exclamation-circle alert-icon"></i>
                            <div class="alert-content">
                                <div class="alert-message"><?= htmlspecialchars($setup_error) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($setup_success)): ?>
                        <div class="alert-banner" style="background: #f0fdf4; border-color: #15803d;">
                            <i class="fas fa-check-circle alert-icon" style="color: #15803d;"></i>
                            <div class="alert-content">
                                <div class="alert-title"><?= htmlspecialchars($setup_success) ?></div>
                                <div class="alert-message">Username: <strong><?= htmlspecialchars($_POST['username'] ?? '') ?></strong></div>
                                <a href="?logout=1" class="btn btn-primary" style="margin-top: var(--space-4);">
                                    Logout & Login
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="create_account">

                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-input">
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                                Create Account
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="app-layout">
            <!-- Left Sidebar Navigation -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <h1 class="brand-name">True Beauty Concept</h1>
                    <div class="brand-role">Medical Staff</div>
                </div>

                <!-- Main Navigation -->
                <nav class="sidebar-section">
                    <div class="sidebar-section-title">Navigation</div>
                    <ul class="sidebar-nav">
                        <a href="staff_dashboard.php" class="sidebar-nav-item active">
                            <span>Dashboard</span>
                        </a>
                        <a href="manage_appointments.php" class="sidebar-nav-item">
                            <span>Appointments</span>
                        </a>
                        <a href="manage_patients.php" class="sidebar-nav-item">
                            <span>Patients</span>
                        </a>
                        <a href="staff_messages.php" class="sidebar-nav-item">
                            <span>Messages</span>
                            <?php if ($unread_messages > 0): ?>
                                <span class="nav-badge"><?= $unread_messages ?></span>
                            <?php endif; ?>
                        </a>
                    </ul>
                </nav>

                <!-- Management Section -->
                <nav class="sidebar-section">
                    <div class="sidebar-section-title">Management</div>
                    <ul class="sidebar-nav">
                        <a href="manage_services.php" class="sidebar-nav-item">
                            <span>Services</span>
                        </a>
                        <a href="manage_feedback.php" class="sidebar-nav-item">
                            <span>Feedback</span>
                        </a>
                        <a href="manage_stock.php" class="sidebar-nav-item">
                            <span>Stock</span>
                        </a>
                    </ul>
                </nav>

                <!-- Account Section -->
                <nav class="sidebar-section">
                    <div class="sidebar-section-title">Account</div>
                    <ul class="sidebar-nav">
                        <a href="staff_register.php" class="sidebar-nav-item">
                            <span>Add Staff Member</span>
                        </a>
                        <a href="edit_profileStaff.php" class="sidebar-nav-item">
                            <span>Edit Profile</span>
                        </a>
                        <a href="#" class="sidebar-nav-item" onclick="openDeleteModal(); return false;">
                            <span>Delete Account</span>
                        </a>
                        <a href="?logout=1" class="sidebar-nav-item">
                            <span>Logout</span>
                        </a>
                    </ul>
                </nav>

                <!-- User Info Footer -->
                <div class="sidebar-footer">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= strtoupper(substr($staff['full_name'], 0, 1)) ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?= htmlspecialchars($staff['full_name']) ?></div>
                            <div class="user-title">Medical Staff</div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="main-content">
                <header class="masthead">
                    <div class="masthead-inner">
                        <h2 class="page-title">Dashboard</h2>
                        <div class="page-meta"><?= date('l, F j, Y') ?></div>
                    </div>
                </header>

                <main class="content-wrapper">
            <!-- Stock Alert -->
            <?php if ($stock_alert): ?>
                <div class="alert-banner">
                    <i class="fas fa-<?= $stock_alert['icon'] ?> alert-icon"></i>
                    <div class="alert-content">
                        <div class="alert-title">Injection Stock Alert</div>
                        <div class="alert-message"><?= $stock_alert['message'] ?></div>
                    </div>
                    <a href="<?= $stock_alert['link'] ?>" class="btn btn-accent">
                        Manage Stock
                    </a>
                </div>
            <?php endif; ?>

            <!-- Analytical Overview -->
            <section class="stats-overview">
                <div class="stats-grid">
                    <div class="stat-cell">
                        <div class="stat-label">Today</div>
                        <div class="stat-value"><?= $today_appointments ?></div>
                        <div class="stat-meta">Appointments</div>
                    </div>
                    <div class="stat-cell">
                        <div class="stat-label">Total</div>
                        <div class="stat-value"><?= $total_patients ?></div>
                        <div class="stat-meta">Patients</div>
                    </div>
                    <div class="stat-cell">
                        <div class="stat-label">Unread</div>
                        <div class="stat-value"><?= $unread_messages ?></div>
                        <div class="stat-meta">Messages</div>
                    </div>
                    <div class="stat-cell">
                        <div class="stat-label">All Time</div>
                        <div class="stat-value"><?= $total_appointments ?></div>
                        <div class="stat-meta">Appointments</div>
                    </div>
                </div>
            </section>

            <!-- Timeline Strip -->
            <?php if ($todays_appointments): ?>
                <section class="timeline-strip">
                    <div class="timeline-header">
                        <h2 class="timeline-title">Today's Schedule</h2>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-items">
                            <?php foreach ($todays_appointments as $apt): ?>
                                <div class="timeline-item">
                                    <div class="timeline-time"><?= htmlspecialchars($apt['time']) ?></div>
                                    <div class="timeline-patient"><?= htmlspecialchars($apt['patient_name']) ?></div>
                                    <div class="timeline-service"><?= htmlspecialchars($apt['service_name']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Upcoming Appointments Module -->
            <section class="module">
                <div class="module-header">
                    <h2 class="module-title">Upcoming Appointments</h2>
                    <a href="manage_appointments.php" class="module-action">View All →</a>
                </div>
                <?php if ($upcoming_appointments): ?>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Service</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_appointments as $apt): ?>
                                    <tr>
                                        <td>
                                            <strong><?= date('M d', strtotime($apt['date'])) ?></strong>
                                            <?php
                                            $apt_date = date('Y-m-d', strtotime($apt['date']));
                                            $today_date = date('Y-m-d');
                                            if ($apt_date === $today_date): ?>
                                                <span class="time-badge">Today</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($apt['time']) ?></td>
                                        <td><?= htmlspecialchars($apt['patient_name']) ?></td>
                                        <td><?= htmlspecialchars($apt['service_name']) ?></td>
                                        <td><strong>RM<?= htmlspecialchars($apt['price']) ?></strong></td>
                                        <td>
                                            <a href="appointment_details.php?id=<?= $apt['appointment_id'] ?>" class="module-action">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="empty-message">No upcoming appointments</div>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Messages Section -->
            <section class="module">
                <div class="module-header">
                    <h2 class="module-title">Unread Messages</h2>
                    <?php if ($recent_messages): ?>
                        <a href="staff_messages.php" class="module-action">View All →</a>
                    <?php endif; ?>
                </div>
                <?php if ($recent_messages): ?>
                    <ul class="list-stack">
                        <?php foreach ($recent_messages as $msg): ?>
                            <a href="staff_messages.php?patient_id=<?= $msg['patient_id'] ?>" class="list-item">
                                <div class="list-initial">
                                    <?= strtoupper(substr($msg['patient_name'], 0, 1)) ?>
                                </div>
                                <div class="list-content">
                                    <div class="list-primary"><?= htmlspecialchars($msg['patient_name']) ?></div>
                                    <div class="list-secondary">
                                        <?= htmlspecialchars(substr($msg['message'], 0, 50)) ?>...
                                    </div>
                                </div>
                                <span class="list-badge">New</span>
                            </a>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="empty-message">No unread messages</div>
                    </div>
                <?php endif; ?>
            </section>
                </main>
            </div>
        </div>
    <?php endif; ?>

    <!-- Delete Account Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2 class="modal-title">Delete Account</h2>
            </div>
            <div class="modal-body">
                <div class="form-warning">
                    <strong>Warning:</strong> This action cannot be undone. Your account and associated data will be permanently deleted.
                </div>

                <?php if (isset($delete_error)): ?>
                    <div class="alert-banner" style="margin-bottom: var(--space-5);">
                        <i class="fas fa-exclamation-circle alert-icon"></i>
                        <div class="alert-content">
                            <div class="alert-message"><?= htmlspecialchars($delete_error) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" id="deleteAccountForm">
                    <input type="hidden" name="action" value="delete_account">
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            class="form-input"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    Cancel
                </button>
                <button type="submit" form="deleteAccountForm" class="btn btn-accent">
                    Delete Account
                </button>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal() {
            document.getElementById('deleteModal').classList.add('active');
            document.getElementById('confirm_password').value = '';
            document.getElementById('confirm_password').focus();
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            document.getElementById('confirm_password').value = '';
        }

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDeleteModal();
        });

        <?php if (isset($delete_error)): ?>
        window.addEventListener('load', function() {
            openDeleteModal();
        });
        <?php endif; ?>
    </script>
</body>
</html>
