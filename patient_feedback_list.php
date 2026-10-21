<?php
require_once 'auth_check.php';
requirePatientLogin();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index_patient.php");
    exit;
}

require_once 'db_connect.php';

$patient_id = $_SESSION['user_id'];

// Handle feedback submission
$feedback_success = '';
$feedback_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $rating = intval($_POST['rating'] ?? 0);
    $message = trim($_POST['feedback_message'] ?? '');

    if ($rating > 0 && !empty($message)) {
        try {
            // Create feedback table if it doesn't exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS patient_feedback (
                feedback_id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                rating INT NOT NULL,
                message TEXT NOT NULL,
                is_anonymous TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                FOREIGN KEY (patient_id) REFERENCES patient(patient_id) ON DELETE CASCADE,
                INDEX idx_created_at (created_at),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Add is_anonymous column if it doesn't exist (for existing tables)
            try {
                $pdo->exec("ALTER TABLE patient_feedback ADD COLUMN is_anonymous TINYINT(1) DEFAULT 0 AFTER message");
            } catch (PDOException $e) {
                // Column already exists, ignore error
            }

            $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO patient_feedback (patient_id, rating, message, is_anonymous) VALUES (?, ?, ?, ?)");
            $stmt->execute([$patient_id, $rating, $message, $is_anonymous]);

            $privacy_text = $is_anonymous ? "Your feedback will be displayed anonymously." : "Your name will be displayed with your feedback.";
            $feedback_success = "Thank you for your feedback! It will be reviewed and published on our testimonials page shortly. " . $privacy_text;
        } catch (PDOException $e) {
            $feedback_error = "Error submitting feedback. Please try again.";
        }
    } else {
        $feedback_error = "Please select a rating and provide your feedback.";
    }
}

// Get patient info
$stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

// Fetch all feedback from this patient
try {
    $stmt = $pdo->prepare("
        SELECT *, COALESCE(is_anonymous, 0) as is_anonymous
        FROM patient_feedback
        WHERE patient_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$patient_id]);
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $feedbacks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Feedback - True Beauty Concept</title>
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
            background: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Starry Night Background */
        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1a2a3a 0%, #2c3e50 50%, #1a2a3a 100%);
            z-index: -1;
        }

        .animated-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 30% 40%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 40% 10%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 50% 60%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 60% 30%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 70% 90%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 80% 50%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 90% 70%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 15% 55%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 25% 15%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 35% 85%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 45% 35%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 55% 95%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 65% 5%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 75% 65%, rgba(255, 255, 255, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 85% 25%, rgba(255, 255, 255, 0.8) 1px, transparent 1px),
                radial-gradient(circle at 95% 45%, rgba(255, 255, 255, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 5% 75%, rgba(255, 255, 255, 0.7) 1px, transparent 1px),
                radial-gradient(circle at 12% 42%, rgba(255, 255, 255, 0.5) 1px, transparent 1px);
            animation: twinkle1 3s ease-in-out infinite;
        }

        .animated-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 8% 35%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 22% 65%, rgba(255, 255, 255, 0.7) 2px, transparent 2px),
                radial-gradient(circle at 38% 22%, rgba(255, 255, 255, 0.8) 2px, transparent 2px),
                radial-gradient(circle at 52% 78%, rgba(255, 255, 255, 0.6) 2px, transparent 2px),
                radial-gradient(circle at 68% 42%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 82% 88%, rgba(255, 255, 255, 0.7) 2px, transparent 2px),
                radial-gradient(circle at 92% 12%, rgba(255, 255, 255, 0.8) 2px, transparent 2px),
                radial-gradient(circle at 18% 92%, rgba(255, 255, 255, 0.6) 2px, transparent 2px),
                radial-gradient(circle at 48% 8%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 78% 58%, rgba(255, 255, 255, 0.7) 2px, transparent 2px);
            animation: twinkle2 4s ease-in-out infinite;
        }

        @keyframes twinkle1 {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }

        @keyframes twinkle2 {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
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
            flex-wrap: wrap;
            gap: 1rem;
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
            text-decoration: none;
        }

        .logo i {
            color: var(--accent-color);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .user-details h3 {
            font-size: 1rem;
            color: white;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .user-details p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* Navigation */
        .nav-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .nav-link {
            padding: 0.8rem 1.5rem;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
        }

        .page-title i {
            color: var(--accent-color);
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1.05rem;
            margin-bottom: 1.5rem;
        }

        .btn {
            padding: 0.9rem 1.8rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 8px 20px rgba(44, 95, 111, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(44, 95, 111, 0.35);
        }

        /* Feedback Grid */
        .feedback-grid {
            display: grid;
            gap: 2rem;
        }

        .feedback-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feedback-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .feedback-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
        }

        .feedback-card:hover::before {
            transform: scaleX(1);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--bg-light);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .feedback-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .feedback-date {
            font-size: 0.9rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feedback-date i {
            color: var(--secondary-color);
        }

        .status-badge {
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-badge.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.rejected {
            background: #fee2e2;
            color: #dc2626;
        }

        .privacy-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }

        .privacy-badge.anonymous {
            background: #e0e7ff;
            color: #4338ca;
        }

        .privacy-badge.public {
            background: #d1fae5;
            color: #065f46;
        }

        .rating {
            display: flex;
            gap: 0.3rem;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .rating i {
            color: var(--accent-color);
        }

        .rating i.empty {
            color: #ddd;
        }

        .feedback-message {
            color: var(--text-dark);
            line-height: 1.7;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .status-info {
            font-size: 0.9rem;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-info.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-info.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-info.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h2 {
            font-family: 'Playfair Display', serif;
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-light);
            font-size: 1.05rem;
            margin-bottom: 2rem;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--bg-light);
        }

        .modal-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .modal-title i {
            color: var(--accent-color);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: var(--bg-light);
            color: var(--danger);
        }

        /* Form Styles */
        .feedback-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--accent-color);
        }

        .form-textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid rgba(138, 180, 167, 0.2);
            border-radius: 12px;
            font-family: 'Inter', 'Poppins', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            min-height: 120px;
            resize: vertical;
        }

        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(44, 95, 111, 0.1);
        }

        /* Star Rating */
        .star-rating {
            display: flex;
            gap: 0.5rem;
            font-size: 2rem;
            margin-top: 0.5rem;
        }

        .star {
            cursor: pointer;
            color: #ddd;
            transition: all 0.3s ease;
        }

        .star:hover,
        .star.active {
            color: var(--accent-color);
            transform: scale(1.2);
        }

        /* Privacy Toggle */
        .privacy-toggle {
            margin-top: 0.5rem;
        }

        .privacy-option {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: pointer;
            user-select: none;
        }

        .privacy-option input[type="checkbox"] {
            display: none;
        }

        .toggle-switch {
            width: 50px;
            height: 26px;
            background: #ddd;
            border-radius: 13px;
            position: relative;
            transition: all 0.3s ease;
        }

        .toggle-switch::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            top: 3px;
            left: 3px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .privacy-option input:checked + .toggle-switch {
            background: var(--primary-color);
        }

        .privacy-option input:checked + .toggle-switch::before {
            transform: translateX(24px);
        }

        .toggle-label {
            font-weight: 500;
            color: var(--text-dark);
        }

        .privacy-hint {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-light);
            font-style: italic;
        }

        /* Alert Messages */
        .alert {
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .container {
                padding: 0 1rem 1rem;
            }

            .page-title {
                font-size: 2rem;
                flex-direction: column;
            }

            .feedback-header {
                flex-direction: column;
            }

            .modal-content {
                padding: 1.5rem;
                margin: 1rem;
            }

            .modal-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-background"></div>

    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <a href="patient_dashboard.php" class="logo">
                <i class="fas fa-spa"></i>
                <span>True Beauty Concept</span>
            </a>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <h3><?= htmlspecialchars($patient['full_name']) ?></h3>
                    <p>Patient</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navigation -->
        <div class="nav-container">
            <div class="nav-links">
                <a href="patient_dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="book_appointment.php" class="nav-link">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Book Appointment</span>
                </a>
                <a href="medical_records.php" class="nav-link">
                    <i class="fas fa-file-medical"></i>
                    <span>Medical Records</span>
                </a>
                <a href="patient_messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
                <a href="patient_feedback_list.php" class="nav-link active">
                    <i class="fas fa-comment-dots"></i>
                    <span>My Feedback</span>
                </a>
                <a href="edit_profilePatient.php" class="nav-link">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-list-alt"></i>
                <span>My Feedback Submissions</span>
            </h1>
            <p class="page-subtitle">
                View all your feedback submissions and their status
            </p>

            <?php if ($feedback_success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($feedback_success) ?>
                </div>
            <?php endif; ?>

            <?php if ($feedback_error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($feedback_error) ?>
                </div>
            <?php endif; ?>

            <button type="button" class="btn btn-primary" onclick="openFeedbackModal()">
                <i class="fas fa-plus"></i>
                <span>Submit New Feedback</span>
            </button>
        </div>

        <!-- Feedback Grid -->
        <?php if (!empty($feedbacks)): ?>
            <div class="feedback-grid">
                <?php foreach ($feedbacks as $feedback): ?>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <div class="feedback-meta">
                                <div class="feedback-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    Submitted on <?= date('F j, Y \a\t g:i A', strtotime($feedback['created_at'])) ?>
                                </div>
                                <div class="privacy-badge <?= $feedback['is_anonymous'] ? 'anonymous' : 'public' ?>">
                                    <i class="fas fa-<?= $feedback['is_anonymous'] ? 'user-secret' : 'user' ?>"></i>
                                    <?= $feedback['is_anonymous'] ? 'Anonymous' : 'Public (Name Shown)' ?>
                                </div>
                            </div>
                            <span class="status-badge <?= htmlspecialchars($feedback['status']) ?>">
                                <i class="fas fa-circle"></i>
                                <?= htmlspecialchars($feedback['status']) ?>
                            </span>
                        </div>

                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $feedback['rating'] ? '' : 'empty' ?>"></i>
                            <?php endfor; ?>
                            <span style="font-size: 1rem; color: var(--text-light); margin-left: 0.5rem;">
                                (<?= $feedback['rating'] ?>/5)
                            </span>
                        </div>

                        <div class="feedback-message">
                            <?= nl2br(htmlspecialchars($feedback['message'])) ?>
                        </div>

                        <?php if ($feedback['status'] === 'pending'): ?>
                            <div class="status-info pending">
                                <i class="fas fa-clock"></i>
                                Your feedback is under review. It will be published once approved by our staff.
                            </div>
                        <?php elseif ($feedback['status'] === 'approved'): ?>
                            <div class="status-info approved">
                                <i class="fas fa-check-circle"></i>
                                This feedback has been approved and is now publicly visible!
                                <a href="feedback.php" style="margin-left: 0.5rem; color: inherit; text-decoration: underline;">View Public Page</a>
                            </div>
                        <?php else: ?>
                            <div class="status-info rejected">
                                <i class="fas fa-times-circle"></i>
                                This feedback was not approved for public display.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Feedback Modal -->
    <div class="modal-overlay" id="feedbackModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-star"></i>
                    Share Your Feedback
                </h2>
                <button type="button" class="modal-close" onclick="closeFeedbackModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="" class="feedback-form">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-star"></i>
                        Rate Your Experience
                    </label>
                    <div class="star-rating" id="starRating">
                        <span class="star" data-rating="1"><i class="fas fa-star"></i></span>
                        <span class="star" data-rating="2"><i class="fas fa-star"></i></span>
                        <span class="star" data-rating="3"><i class="fas fa-star"></i></span>
                        <span class="star" data-rating="4"><i class="fas fa-star"></i></span>
                        <span class="star" data-rating="5"><i class="fas fa-star"></i></span>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="0">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-comment"></i>
                        Your Feedback
                    </label>
                    <textarea name="feedback_message" class="form-textarea" required placeholder="Tell us about your experience with our clinic..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user-secret"></i>
                        Privacy Setting
                    </label>
                    <div class="privacy-toggle">
                        <label class="privacy-option">
                            <input type="checkbox" name="is_anonymous" id="anonymousToggle">
                            <span class="toggle-switch"></span>
                            <span class="toggle-label">Post Anonymously</span>
                        </label>
                        <p class="privacy-hint" id="privacyHint">Your name will be displayed with your feedback</p>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="submit_feedback" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-paper-plane"></i>
                        <span>Submit Feedback</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openFeedbackModal() {
            document.getElementById('feedbackModal').classList.add('active');
            // Reset form
            document.getElementById('ratingInput').value = '0';
            document.querySelectorAll('.star').forEach(s => s.classList.remove('active'));
            document.querySelector('.form-textarea').value = '';
            document.getElementById('anonymousToggle').checked = false;
            document.getElementById('privacyHint').textContent = 'Your name will be displayed with your feedback';
            document.getElementById('privacyHint').style.color = 'var(--text-light)';
        }

        function closeFeedbackModal() {
            document.getElementById('feedbackModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('feedbackModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeFeedbackModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeFeedbackModal();
            }
        });

        // Star rating functionality
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');

        if (stars.length > 0 && ratingInput) {
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    ratingInput.value = rating;

                    stars.forEach(s => {
                        const starRating = s.getAttribute('data-rating');
                        if (starRating <= rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });

                star.addEventListener('mouseenter', function() {
                    const rating = this.getAttribute('data-rating');
                    stars.forEach(s => {
                        const starRating = s.getAttribute('data-rating');
                        if (starRating <= rating) {
                            s.style.color = 'var(--accent-color)';
                        }
                    });
                });
            });

            document.getElementById('starRating').addEventListener('mouseleave', function() {
                const currentRating = ratingInput.value;
                stars.forEach(s => {
                    const starRating = s.getAttribute('data-rating');
                    if (starRating <= currentRating) {
                        s.style.color = 'var(--accent-color)';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        }

        // Privacy toggle functionality
        const anonymousToggle = document.getElementById('anonymousToggle');
        const privacyHint = document.getElementById('privacyHint');

        if (anonymousToggle && privacyHint) {
            anonymousToggle.addEventListener('change', function() {
                if (this.checked) {
                    privacyHint.textContent = 'Your feedback will be displayed anonymously';
                    privacyHint.style.color = 'var(--primary-color)';
                } else {
                    privacyHint.textContent = 'Your name will be displayed with your feedback';
                    privacyHint.style.color = 'var(--text-light)';
                }
            });
        }

        // Auto-hide alert messages
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                alert.style.transition = 'all 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
