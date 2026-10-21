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
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$staff_id = $_SESSION['user_id'];

// Get unread messages count BEFORE marking as read (for navigation badge)
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM messages WHERE is_read = 0 AND sender_type = 'patient'");
$stmt->execute();
$unread_messages = $stmt->fetch()['total'];

// Mark all unread messages from patients as read when staff accesses this page
$stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE is_read = 0 AND sender_type = 'patient'");
$stmt->execute();

// Get staff info
$stmt = $pdo->prepare("SELECT full_name FROM medical_staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

// Get selected patient ID from URL
$selected_patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['patient_id'])) {
    $message = trim($_POST['message']);
    $patient_id = (int)$_POST['patient_id'];

    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (patient_id, staff_id, sender_type, message, timestamp) VALUES (?, ?, 'staff', ?, NOW())");
        $stmt->execute([$patient_id, $staff_id, $message]);
    }

    // Redirect to prevent form resubmission
    header("Location: staff_messages.php?patient_id=" . $patient_id);
    exit;
}

// Get all patients who have sent messages
$stmt = $pdo->prepare("
    SELECT DISTINCT p.patient_id, p.full_name,
           (SELECT message FROM messages WHERE patient_id = p.patient_id ORDER BY timestamp DESC LIMIT 1) as last_message,
           (SELECT timestamp FROM messages WHERE patient_id = p.patient_id ORDER BY timestamp DESC LIMIT 1) as last_message_time,
           (SELECT COUNT(*) FROM messages WHERE patient_id = p.patient_id AND sender_type = 'patient' AND is_read = 0) as unread_count
    FROM patient p
    WHERE EXISTS (SELECT 1 FROM messages WHERE patient_id = p.patient_id)
    ORDER BY last_message_time DESC
");
$stmt->execute();
$patients = $stmt->fetchAll();

// If no patient selected but patients exist, select first one
if (!$selected_patient_id && !empty($patients)) {
    $selected_patient_id = $patients[0]['patient_id'];
}

// Get messages for selected patient
$messages = [];
$selected_patient = null;
if ($selected_patient_id) {
    $stmt = $pdo->prepare("
        SELECT m.*,
               p.full_name as patient_name,
               ms.full_name as staff_name
        FROM messages m
        LEFT JOIN patient p ON m.patient_id = p.patient_id
        LEFT JOIN medical_staff ms ON m.staff_id = ms.staff_id
        WHERE m.patient_id = ?
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([$selected_patient_id]);
    $messages = $stmt->fetchAll();

    // Get selected patient info
    $stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
    $stmt->execute([$selected_patient_id]);
    $selected_patient = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Medical Staff</title>
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
            overflow-y: auto;
            color: var(--text-dark);
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

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
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

        .user-details h3 {
            font-size: 1rem;
            font-weight: 600;
            color: white;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            margin: 0;
        }

        .user-details p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            margin: 0;
        }


        /* Navigation - Minimal */
        .page-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem 2rem 1.5rem;
            min-height: calc(100vh - 120px);
        }

        .nav-container-wrapper {
            margin-bottom: 1.5rem;
        }

        .nav-container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 0.5rem;
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
            display: flex;
            gap: 1rem;
            min-height: calc(100vh - 200px);
        }

        /* Sidebar - Minimal */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: fit-content;
            max-height: calc(100vh - 200px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-title {
            font-size: 1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
        }

        .back-link {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        .patients-list {
            flex: 1;
            overflow-y: auto;
        }

        .patients-list::-webkit-scrollbar {
            width: 4px;
        }

        .patients-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .patients-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
        }

        .patient-item {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            gap: 0.75rem;
            text-decoration: none;
            color: inherit;
        }

        .patient-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .patient-item.active {
            background: rgba(255, 255, 255, 0.1);
        }

        .patient-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 500;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .patient-info {
            flex: 1;
            min-width: 0;
        }

        .patient-name {
            font-weight: 500;
            font-size: 0.85rem;
            color: white;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .patient-last-message {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.5);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .unread-badge {
            background: var(--accent-color);
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 0.6rem;
            font-weight: 600;
            align-self: center;
        }

        /* Chat Area - Minimal */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            overflow: hidden;
            height: fit-content;
            max-height: calc(100vh - 200px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .patient-avatar-large {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .patient-header-info {
            flex: 1;
        }

        .patient-header-name {
            font-weight: 500;
            font-size: 0.95rem;
            color: white;
            margin-bottom: 0;
        }

        .patient-header-details {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .messages-area {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .messages-area::-webkit-scrollbar {
            width: 4px;
        }

        .messages-area::-webkit-scrollbar-track {
            background: transparent;
        }

        .messages-area::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
        }

        .message {
            display: flex;
            gap: 0.5rem;
            max-width: 75%;
        }

        .message.sent {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message.received {
            align-self: flex-start;
        }

        .message-avatar-small {
            display: none;
        }

        .message-content {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .message-bubble {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            word-wrap: break-word;
            line-height: 1.5;
            font-size: 0.85rem;
        }

        .message.sent .message-bubble {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.received .message-bubble {
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.9);
            border-bottom-left-radius: 4px;
        }

        .message-time {
            font-size: 0.6rem;
            color: rgba(255, 255, 255, 0.4);
            padding: 0 4px;
        }

        .message.sent .message-time {
            text-align: right;
        }

        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.4);
            gap: 0.75rem;
        }

        .empty-state i {
            font-size: 2.5rem;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 0.85rem;
        }

        .input-area {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .input-form {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .input-wrapper {
            flex: 1;
            position: relative;
        }

        .message-input {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            font-size: 0.85rem;
            font-family: 'Poppins', sans-serif;
            background: rgba(255, 255, 255, 0.08);
            color: white;
            transition: all 0.2s ease;
            resize: none;
            max-height: 100px;
            min-height: 40px;
        }

        .message-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .message-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
        }

        .emoji-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .emoji-btn:hover {
            color: white;
        }

        .send-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .send-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .header-content {
                padding: 0 1rem;
            }

            .page-wrapper {
                padding: 0 1rem 1.5rem 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .main-container {
                flex-direction: column;
                gap: 1rem;
                min-height: auto;
            }

            .sidebar {
                display: <?php echo $selected_patient_id ? 'none' : 'flex'; ?>;
                width: 100%;
                max-height: 50vh;
            }

            .chat-area {
                display: <?php echo $selected_patient_id ? 'flex' : 'none'; ?>;
                max-height: none;
                min-height: calc(100vh - 180px);
            }

            .message {
                max-width: 85%;
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
            <div class="header-left">
                <div class="logo">
                    <i class="fas fa-spa"></i>
                    <span>True Beauty Concept</span>
                </div>
            </div>
        </div>
    </div>

    <div class="page-wrapper">
        <!-- Navigation - Minimal -->
        <div class="nav-container-wrapper">
            <div class="nav-container">
                <div class="nav-links">
                    <a href="staff_dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="manage_appointments.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Appointments</span>
                    </a>
                    <a href="manage_patients.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Patients</span>
                    </a>
                    <a href="staff_messages.php" class="nav-link active">
                        <i class="fas fa-envelope"></i>
                        <span>Messages</span>
                        <?php if ($unread_messages > 0): ?>
                            <span class="notification-badge"><?= $unread_messages ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php?type=staff" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">Messages</h2>
                <a href="staff_dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="patients-list">
                <?php if (empty($patients)): ?>
                    <div style="padding: 40px 20px; text-align: center; color: #888;">
                        <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; margin-bottom: 15px;"></i>
                        <p>No messages yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($patients as $patient): ?>
                        <a href="staff_messages.php?patient_id=<?php echo $patient['patient_id']; ?>"
                           class="patient-item <?php echo $selected_patient_id == $patient['patient_id'] ? 'active' : ''; ?>">
                            <div class="patient-avatar">
                                <?php echo strtoupper(substr($patient['full_name'], 0, 1)); ?>
                            </div>
                            <div class="patient-info">
                                <div class="patient-name"><?php echo htmlspecialchars($patient['full_name']); ?></div>
                                <div class="patient-last-message">
                                    <?php echo htmlspecialchars(substr($patient['last_message'], 0, 40)); ?>...
                                </div>
                            </div>
                            <?php if ($patient['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo $patient['unread_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($selected_patient): ?>
                <div class="chat-header">
                    <div class="patient-avatar-large">
                        <?php echo strtoupper(substr($selected_patient['full_name'], 0, 1)); ?>
                    </div>
                    <div class="patient-header-info">
                        <div class="patient-header-name"><?php echo htmlspecialchars($selected_patient['full_name']); ?></div>
                        <div class="patient-header-details">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($selected_patient['email']); ?>
                            <?php if ($selected_patient['phone']): ?>
                                • <i class="fas fa-phone"></i> <?php echo htmlspecialchars($selected_patient['phone']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="messages-area" id="messagesArea">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <p>No messages yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?php echo $msg['sender_type'] === 'staff' ? 'sent' : 'received'; ?>">
                                <div class="message-avatar-small">
                                    <?php
                                    if ($msg['sender_type'] === 'staff') {
                                        echo strtoupper(substr($staff['full_name'], 0, 1));
                                    } else {
                                        echo strtoupper(substr($selected_patient['full_name'], 0, 1));
                                    }
                                    ?>
                                </div>
                                <div class="message-content">
                                    <div class="message-bubble">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    </div>
                                    <div class="message-time">
                                        <?php
                                        $time = new DateTime($msg['timestamp']);
                                        echo $time->format('g:i A');
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="input-area">
                    <form method="POST" class="input-form" id="messageForm">
                        <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                        <div class="input-wrapper">
                            <textarea
                                name="message"
                                id="messageInput"
                                class="message-input"
                                placeholder="Type your message..."
                                rows="1"
                                required
                            ></textarea>
                            <button type="button" class="emoji-btn" onclick="insertEmoji()">
                                <i class="far fa-smile"></i>
                            </button>
                        </div>
                        <button type="submit" class="send-btn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comment-dots"></i>
                    <p>Select a patient to view messages</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <script>
        // Auto-resize textarea
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });

            // Submit on Enter (but allow Shift+Enter for new line)
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    document.getElementById('messageForm').submit();
                }
            });
        }

        // Scroll to bottom of messages
        function scrollToBottom() {
            const messagesArea = document.getElementById('messagesArea');
            if (messagesArea) {
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }
        }

        // Scroll to bottom on load
        window.addEventListener('load', scrollToBottom);

        // Simple emoji insertion
        function insertEmoji() {
            const emojis = ['😊', '👍', '❤️', '😄', '🎉', '👏', '🙏', '💪', '✨', '🌟'];
            const emoji = emojis[Math.floor(Math.random() * emojis.length)];
            messageInput.value += emoji;
            messageInput.focus();
        }

        // Auto-refresh messages every 5 seconds only when input is not focused
        <?php if ($selected_patient_id): ?>
        let isTyping = false;

        messageInput.addEventListener('focus', function() {
            isTyping = true;
        });

        messageInput.addEventListener('blur', function() {
            isTyping = false;
        });

        setInterval(function() {
            // Only reload if user is not typing
            if (!isTyping && document.activeElement !== messageInput) {
                location.reload();
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
