<?php
require_once 'auth_check.php';
requireStaffLogin();

require_once 'db_connect.php';

$message = '';
$error = '';

// Handle feedback approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_id = intval($_POST['feedback_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($feedback_id > 0 && in_array($action, ['approve', 'reject', 'delete'])) {
        try {
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM patient_feedback WHERE feedback_id = ?");
                $stmt->execute([$feedback_id]);
                $message = "Feedback deleted successfully.";
            } else {
                $status = ($action === 'approve') ? 'approved' : 'rejected';
                $stmt = $pdo->prepare("UPDATE patient_feedback SET status = ? WHERE feedback_id = ?");
                $stmt->execute([$status, $feedback_id]);
                $message = "Feedback " . $status . " successfully.";
            }
        } catch (PDOException $e) {
            $error = "Error processing feedback: " . $e->getMessage();
        }
    }
}

// Get staff information for header
$staff = null;
$staff_id = $_SESSION['user_id'] ?? null;
if ($staff_id) {
    try {
        $stmt = $pdo->prepare("SELECT staff_id, full_name, role FROM medical_staff WHERE staff_id = ?");
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Continue without staff info
    }
}

// Fetch all feedback
try {
    $stmt = $pdo->query("
        SELECT pf.*, p.full_name, p.email
        FROM patient_feedback pf
        JOIN patient p ON pf.patient_id = p.patient_id
        ORDER BY pf.created_at DESC
    ");
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $feedbacks = [];
    $error = "Error fetching feedback: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback - True Beauty Concept</title>
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

        /* User Info & Profile Dropdown */
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }

        .page-header {
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
        }

        h1 i {
            color: var(--accent-color);
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
        }

        .alert {
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
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

        .feedback-grid {
            display: grid;
            gap: 1.5rem;
        }

        .feedback-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .feedback-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .feedback-info h3 {
            font-size: 1.2rem;
            color: white;
            margin-bottom: 0.3rem;
        }

        .feedback-meta {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-badge.approved {
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-badge.rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .rating {
            display: flex;
            gap: 0.3rem;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .rating i {
            color: var(--accent-color);
        }

        .rating i.empty {
            color: rgba(255, 255, 255, 0.2);
        }

        .feedback-message {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-approve {
            background: var(--success);
            color: white;
        }

        .btn-approve:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: var(--warning);
            color: white;
        }

        .btn-reject:hover {
            background: #d97706;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .empty-state i {
            font-size: 5rem;
            color: rgba(255, 255, 255, 0.3);
            margin-bottom: 1.5rem;
        }

        .empty-state h2 {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: rgba(255, 255, 255, 0.6);
        }

        @media (max-width: 968px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-direction: column;
            }

            .container {
                padding: 0 1rem 1rem;
            }

            .feedback-header {
                flex-direction: column;
                gap: 1rem;
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
            <?php if ($staff): ?>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($staff['full_name'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <h3><?= htmlspecialchars($staff['full_name']) ?></h3>
                    <p><?= htmlspecialchars($staff['role'] ?? 'Medical Staff') ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <!-- Navigation -->
        <div class="nav-container">
            <div class="nav-links">
                <a href="staff_dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_services.php" class="nav-link">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Services</span>
                </a>
                <a href="manage_feedback.php" class="nav-link active">
                    <i class="fas fa-star"></i>
                    <span>Feedback</span>
                </a>
                <a href="manage_stock.php" class="nav-link">
                    <i class="fas fa-boxes"></i>
                    <span>Stock</span>
                </a>
                <a href="logout.php?type=staff" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-comments"></i> Manage Patient Feedback</h1>
            <p class="subtitle">Review and approve patient testimonials</p>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Feedback Grid -->
        <?php if (!empty($feedbacks)): ?>
            <div class="feedback-grid">
                <?php foreach ($feedbacks as $feedback): ?>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <div class="feedback-info">
                                <h3><?= htmlspecialchars($feedback['full_name']) ?></h3>
                                <div class="feedback-meta">
                                    <?= htmlspecialchars($feedback['email']) ?> •
                                    <?= date('F j, Y \a\t g:i A', strtotime($feedback['created_at'])) ?>
                                </div>
                            </div>
                            <span class="status-badge <?= htmlspecialchars($feedback['status']) ?>">
                                <?= htmlspecialchars($feedback['status']) ?>
                            </span>
                        </div>

                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $feedback['rating'] ? '' : 'empty' ?>"></i>
                            <?php endfor; ?>
                        </div>

                        <div class="feedback-message">
                            <?= nl2br(htmlspecialchars($feedback['message'])) ?>
                        </div>

                        <div class="action-buttons">
                            <?php if ($feedback['status'] !== 'approved'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="feedback_id" value="<?= $feedback['feedback_id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-approve">
                                        <i class="fas fa-check"></i>
                                        Approve
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($feedback['status'] !== 'rejected'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="feedback_id" value="<?= $feedback['feedback_id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-reject">
                                        <i class="fas fa-times"></i>
                                        Reject
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                <input type="hidden" name="feedback_id" value="<?= $feedback['feedback_id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-delete">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h2>No Feedback Yet</h2>
                <p>Patient feedback will appear here when submitted.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
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
