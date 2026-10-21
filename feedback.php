<?php
session_start();
require_once 'db_connect.php';

$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient';

// Fetch approved feedback from patients
try {
    $stmt = $pdo->query("
        SELECT pf.*, p.full_name,
               COALESCE(pf.is_anonymous, 0) as is_anonymous
        FROM patient_feedback pf
        JOIN patient p ON pf.patient_id = p.patient_id
        WHERE pf.status = 'approved'
        ORDER BY pf.created_at DESC
        LIMIT 20
    ");
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
    <title>Patient Testimonials - True Beauty Concept</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
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
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Animated Background - Starry Night */
        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1a2a3a 0%, #2c3e50 50%, #1a2a3a 100%);
            z-index: -1;
        }

        /* Stars Layer 1 - Small stars */
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
                radial-gradient(circle at 12% 92%, rgba(255, 255, 255, 0.5) 1px, transparent 1px);
            animation: twinkle1 3s ease-in-out infinite;
        }

        /* Stars Layer 2 - Medium stars with different timing */
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
                radial-gradient(circle at 18% 48%, rgba(255, 255, 255, 0.6) 2px, transparent 2px),
                radial-gradient(circle at 48% 8%, rgba(255, 255, 255, 0.9) 2px, transparent 2px),
                radial-gradient(circle at 78% 58%, rgba(255, 255, 255, 0.7) 2px, transparent 2px);
            animation: twinkle2 4s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes twinkle1 {
            0%, 100% {
                opacity: 0.4;
            }
            50% {
                opacity: 1;
            }
        }

        @keyframes twinkle2 {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.3;
            }
        }

        /* Navigation Bar */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: transparent;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.3);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo i {
            color: var(--accent-color);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2.5rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #ffffff;
            font-weight: 500;
            font-size: 1rem;
            position: relative;
            transition: color 0.3s ease;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent-color);
            transition: width 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--accent-color);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a.active {
            color: var(--accent-color);
        }

        .nav-links a.active::after {
            width: 100%;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .profile-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 20px rgba(44, 95, 111, 0.3);
        }

        .dropdown-menu {
            position: absolute;
            top: 60px;
            right: 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .profile-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: block;
            padding: 1rem 1.5rem;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .dropdown-menu a:first-child {
            border-radius: 15px 15px 0 0;
        }

        .dropdown-menu a:last-child {
            border-radius: 0 0 15px 15px;
        }

        .dropdown-menu a:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding-left: 2rem;
        }

        .dropdown-menu a i {
            margin-right: 10px;
            width: 20px;
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
        }

        .menu-toggle span {
            width: 25px;
            height: 3px;
            background: #ffffff;
            border-radius: 3px;
            transition: all 0.3s ease;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            min-height: calc(100vh - 80px);
            background: transparent;
            padding: 4rem 2rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 3rem 2rem;
            background: transparent;
            border-radius: 20px;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: #ffffff;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.4);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .page-title i {
            color: var(--accent-color);
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.3);
            max-width: 700px;
            margin: 0 auto;
        }

        /* CTA Section */
        .cta-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-bottom: 3rem;
            text-align: center;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
        }

        .cta-title i {
            color: var(--accent-color);
        }

        .cta-text {
            color: var(--text-light);
            margin-bottom: 1.5rem;
            font-size: 1.05rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
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

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Feedback Grid */
        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
        }

        .feedback-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
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
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
        }

        .feedback-card:hover::before {
            transform: scaleX(1);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--bg-light);
        }

        .feedback-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(138, 180, 167, 0.3);
        }

        .author-info h3 {
            font-size: 1.1rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .author-info p {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .feedback-rating {
            display: flex;
            gap: 0.3rem;
            font-size: 1.2rem;
        }

        .feedback-rating i {
            color: var(--accent-color);
        }

        .feedback-rating i.empty {
            color: #ddd;
        }

        .feedback-message {
            color: var(--text-light);
            line-height: 1.7;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .feedback-date {
            font-size: 0.85rem;
            color: #999;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feedback-date i {
            color: var(--secondary-color);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
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
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-light);
            font-size: 1.05rem;
        }

        /* Footer Text */
        .feedback-footer-text {
            text-align: center;
            padding: 2rem 0 1rem;
            margin-top: 2rem;
        }

        .feedback-footer-text p {
            font-size: 1.00rem;
            color: var(--text-light);
        }

        .feedback-footer-text a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .feedback-footer-text a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .nav-links {
                position: absolute;
                top: 80px;
                left: 0;
                width: 100%;
                background: rgba(0, 0, 0, 0.8);
                backdrop-filter: blur(10px);
                flex-direction: column;
                padding: 2rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .nav-links.active {
                transform: translateX(0);
            }

            .menu-toggle {
                display: flex;
            }

            .page-title {
                font-size: 2rem;
                flex-direction: column;
            }

            .feedback-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 2rem 1rem;
            }

            .cta-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 640px) {
            .logo {
                font-size: 1.4rem;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .btn {
                padding: 0.7rem 1.4rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-background"></div>

    <!-- Navigation Bar -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-spa"></i>
                <span>True Beauty Concept</span>
            </a>
            <div class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">Home</a></li>
                <li><a href="services_public.php">Services</a></li>
                <li><a href="feedback.php" class="active">Feedback</a></li>
                <li><a href="index.php#contact">Contact</a></li>

                <li class="profile-dropdown">
                    <div class="profile-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="dropdown-menu">
                        <?php if ($is_logged_in): ?>
                            <a href="patient_dashboard.php">
                                <i class="fas fa-home"></i>
                                My Dashboard
                            </a>
                            <a href="patient_feedback_list.php">
                                <i class="fas fa-comment-dots"></i>
                                My Feedback
                            </a>
                            <a href="?logout=1">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        <?php else: ?>
                            <a href="index_patient.php">
                                <i class="fas fa-user-circle"></i>
                                Patient Login
                            </a>
                            <a href="registration.php">
                                <i class="fas fa-user-plus"></i>
                                Patient Registration
                            </a>
                            <a href="index_staff.php">
                                <i class="fas fa-user-md"></i>
                                Staff Login
                            </a>
                        <?php endif; ?>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-star"></i>
                    <span>Patient Testimonials</span>
                </h1>
                <p class="page-subtitle">
                    See what our valued patients have to say about their experiences at True Beauty Concept
                </p>
            </div>

            <!-- Feedback Grid -->
            <?php if (!empty($feedbacks)): ?>
                <div class="feedback-grid">
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="feedback-card">
                            <div class="feedback-header">
                                <div class="feedback-author">
                                    <div class="author-avatar">
                                        <?php if ($feedback['is_anonymous']): ?>
                                            <i class="fas fa-user-secret"></i>
                                        <?php else: ?>
                                            <?= strtoupper(substr($feedback['full_name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="author-info">
                                        <h3><?= $feedback['is_anonymous'] ? 'Anonymous' : htmlspecialchars($feedback['full_name']) ?></h3>
                                        <p>Verified Patient</p>
                                    </div>
                                </div>
                                <div class="feedback-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $feedback['rating'] ? '' : 'empty' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="feedback-message">
                                <?= nl2br(htmlspecialchars($feedback['message'])) ?>
                            </div>
                            <div class="feedback-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('F j, Y', strtotime($feedback['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h2>No Feedback Yet</h2>
                    <p>Be the first to share your experience with our clinic!</p>
                </div>
            <?php endif; ?>

            <!-- Footer Text -->
            <?php if (!$is_logged_in): ?>
            <div class="feedback-footer-text">
                <p>Want to share your experience? <a href="index_patient.php">Login</a> or <a href="registration.php">Register</a> to submit your feedback.</p>
            </div>
            <?php else: ?>
            <div class="feedback-footer-text">
                <p>Want to share your experience? Visit your <a href="patient_feedback_list.php">feedback page</a> to submit feedback.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                navLinks.classList.toggle('active');
            });
        }

        // Handle logout
        <?php if (isset($_GET['logout'])): ?>
            <?php
            session_destroy();
            header('Location: feedback.php');
            exit;
            ?>
        <?php endif; ?>
    </script>
</body>
</html>
