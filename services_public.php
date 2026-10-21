<?php
session_start();
require_once 'db_connect.php';

// Fetch active services only
$service_query = "SELECT * FROM service WHERE is_active = 1 ORDER BY name ASC";
$service_stmt = $pdo->prepare($service_query);
$service_stmt->execute();
$service_result = $service_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - True Beauty Concept</title>
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
        }

        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
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

        .back-btn {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            text-decoration: none;
            border-radius: 50px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(10px);
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        /* Hero Section */
        .hero-section {
            background: transparent;
            padding: 120px 2rem 60px;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 20px rgba(44, 95, 111, 0.3);
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.4);
            margin-bottom: 1rem;
            letter-spacing: -1px;
            line-height: 1.2;
        }

        .hero-description {
            font-size: 1.15rem;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            margin: 0 auto 2.5rem;
            line-height: 1.8;
        }

        /* Stats */
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            padding: 1.5rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            min-width: 150px;
        }

        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.3);
            display: block;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 4rem;
        }

        /* Search and Filter */
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            margin-bottom: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid rgba(44, 95, 111, 0.1);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(44, 95, 111, 0.1);
        }

        .service-count {
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
            white-space: nowrap;
        }

        .service-count strong {
            color: var(--primary-color);
        }

        /* Services Showcase - Magazine Style */
        .services-showcase {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        /* Service Panel - Horizontal Layout */
        .service-panel {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 500px;
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(60px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .service-panel.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .service-panel:nth-child(even) {
            direction: rtl;
        }

        .service-panel:nth-child(even) > * {
            direction: ltr;
        }

        /* Image Section */
        .panel-image {
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .panel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .service-panel:hover .panel-image img {
            transform: scale(1.08);
        }

        .panel-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6rem;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .service-panel:hover .panel-image-placeholder {
            transform: scale(1.08);
        }

        .image-number {
            position: absolute;
            top: 2rem;
            left: 2rem;
            font-family: 'Playfair Display', serif;
            font-size: 5rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.15);
            line-height: 1;
            pointer-events: none;
        }

        .service-panel:nth-child(even) .image-number {
            left: auto;
            right: 2rem;
        }

        /* Content Section */
        .panel-content {
            background: rgba(255, 255, 255, 0.98);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .service-category {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .service-category::after {
            content: '';
            width: 30px;
            height: 2px;
            background: var(--accent-color);
        }

        .service-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .service-excerpt {
            color: var(--text-light);
            font-size: 1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
            max-height: 4.5em;
            overflow: hidden;
        }

        /* Service Meta Info */
        .service-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(44, 95, 111, 0.1);
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .meta-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-light);
            font-weight: 600;
        }

        .meta-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .meta-value.price {
            color: var(--accent-color);
        }

        /* Expand Button */
        .expand-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem 2rem;
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            position: relative;
            overflow: hidden;
        }

        .expand-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            transition: left 0.3s ease;
            z-index: -1;
        }

        .expand-btn:hover {
            color: white;
            border-color: var(--primary-color);
        }

        .expand-btn:hover::before {
            left: 0;
        }

        .expand-btn i {
            transition: transform 0.3s ease;
        }

        .expand-btn:hover i {
            transform: rotate(90deg);
        }

        /* Expanded Details Overlay */
        .details-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
        }

        .details-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .details-modal {
            background: white;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 20px;
            transform: scale(0.9) translateY(30px);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .details-overlay.active .details-modal {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            position: relative;
            height: 300px;
            overflow: hidden;
        }

        .modal-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-header-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
        }

        .modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: white;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 3rem;
        }

        .modal-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .modal-description {
            color: var(--text-light);
            font-size: 1.05rem;
            line-height: 1.9;
            margin-bottom: 2rem;
        }

        .modal-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .modal-detail-box {
            background: linear-gradient(135deg, rgba(138, 180, 167, 0.1), rgba(212, 165, 116, 0.1));
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
        }

        .modal-detail-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .modal-detail-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .modal-detail-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .modal-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-bottom: 2rem;
        }

        .modal-feature {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: var(--bg-light);
            border-radius: 25px;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .modal-feature i {
            color: var(--success);
        }

        .modal-book-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            width: 100%;
            padding: 1.2rem 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(44, 95, 111, 0.3);
        }

        .modal-book-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(44, 95, 111, 0.4);
        }

        .modal-book-btn.login-btn {
            background: linear-gradient(135deg, var(--accent-color), #c49660);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
        }

        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .empty-text {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 60px 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            display: none;
        }

        .no-results.show {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .service-panel {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .service-panel:nth-child(even) {
                direction: ltr;
            }

            .panel-image {
                height: 300px;
            }

            .service-title {
                font-size: 2rem;
            }

            .modal-details {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .hero-section {
                padding: 100px 1.5rem 40px;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-description {
                font-size: 1rem;
            }

            .hero-stats {
                gap: 1rem;
            }

            .stat-item {
                min-width: 120px;
                padding: 1rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .container {
                padding: 0 1rem 3rem;
            }

            .search-section {
                flex-direction: column;
                padding: 1rem;
            }

            .panel-content {
                padding: 2rem;
            }

            .service-meta {
                flex-direction: column;
                gap: 1rem;
            }

            .modal-details {
                grid-template-columns: 1fr;
            }

            .modal-body {
                padding: 2rem;
            }

            .modal-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .panel-image {
                height: 250px;
            }

            .service-title {
                font-size: 1.6rem;
            }

            .image-number {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-background"></div>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-spa"></i>
                <span>True Beauty Concept</span>
            </a>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Portal</span>
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fas fa-star"></i>
                Premium Services
            </span>
            <h1 class="hero-title">Expert Dermatology Care</h1>
            <p class="hero-description">
                Discover our comprehensive range of professional skin care services.
                Each treatment is tailored to your unique needs using cutting-edge technology
                and delivered by experienced dermatology specialists.
            </p>

            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($service_result); ?></span>
                    <span class="stat-label">Services</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">100%</span>
                    <span class="stat-label">Satisfaction</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Booking</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Container -->
    <div class="container">
        <!-- Search Section -->
        <div class="search-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" class="search-input" id="serviceSearch" placeholder="Search services by name or description...">
            </div>
            <div class="service-count">
                <strong id="visibleCount"><?php echo count($service_result); ?></strong> services available
            </div>
        </div>

        <div class="services-showcase" id="servicesShowcase">
            <?php if (count($service_result) > 0): ?>
                <?php
                // Service icons mapping
                $service_icons = [
                    'acne' => '🔬',
                    'laser' => '✨',
                    'facial' => '💆',
                    'botox' => '💉',
                    'skin' => '🧴',
                    'consultation' => '👨‍⚕️',
                    'treatment' => '💊',
                    'therapy' => '🌟',
                    'peel' => '🌸',
                    'rejuvenation' => '✨',
                    'hair' => '💇',
                    'anti' => '⏳',
                    'gatal' => '🩹',
                    'cream' => '🧴',
                    'injection' => '💉',
                ];

                // Feature tags based on keywords
                $feature_tags = [
                    'consultation' => ['Professional Advice', 'Personalized', 'Expert Guidance'],
                    'laser' => ['Advanced Tech', 'Non-invasive', 'Quick Recovery'],
                    'facial' => ['Relaxing', 'Rejuvenating', 'Deep Cleansing'],
                    'treatment' => ['Effective', 'Safe', 'Proven Results'],
                    'therapy' => ['Therapeutic', 'Healing', 'Restorative'],
                    'default' => ['Quality Care', 'Expert Staff', 'Premium Service']
                ];

                foreach ($service_result as $index => $service):
                    // Determine icon
                    $icon = '🏥';
                    foreach ($service_icons as $keyword => $emoji) {
                        if (stripos($service['name'], $keyword) !== false || stripos($service['description'], $keyword) !== false) {
                            $icon = $emoji;
                            break;
                        }
                    }

                    // Determine features
                    $features = $feature_tags['default'];
                    foreach ($feature_tags as $keyword => $tags) {
                        if ($keyword !== 'default' && (stripos($service['name'], $keyword) !== false || stripos($service['description'], $keyword) !== false)) {
                            $features = $tags;
                            break;
                        }
                    }

                    // Determine category
                    $category = 'Treatment';
                    if (stripos($service['name'], 'consultation') !== false) $category = 'Consultation';
                    elseif (stripos($service['name'], 'facial') !== false) $category = 'Facial Care';
                    elseif (stripos($service['name'], 'laser') !== false) $category = 'Laser Treatment';
                    elseif (stripos($service['name'], 'injection') !== false) $category = 'Injectable';
                ?>
                    <div class="service-panel"
                         data-name="<?php echo strtolower(htmlspecialchars($service['name'])); ?>"
                         data-desc="<?php echo strtolower(htmlspecialchars($service['description'])); ?>"
                         data-service-id="<?php echo $service['service_id']; ?>"
                         data-icon="<?php echo $icon; ?>"
                         data-features='<?php echo json_encode($features); ?>'>

                        <!-- Image Section -->
                        <div class="panel-image" onclick="openServiceModal(<?php echo $index; ?>)">
                            <span class="image-number"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                            <?php if (!empty($service['image']) && file_exists($service['image'])): ?>
                                <img src="<?php echo htmlspecialchars($service['image']); ?>"
                                     alt="<?php echo htmlspecialchars($service['name']); ?>">
                            <?php else: ?>
                                <div class="panel-image-placeholder"><?php echo $icon; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Content Section -->
                        <div class="panel-content">
                            <span class="service-category"><?php echo $category; ?></span>
                            <h2 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h2>
                            <p class="service-excerpt"><?php echo htmlspecialchars($service['description']); ?></p>

                            <div class="service-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Price</span>
                                    <span class="meta-value price">RM <?php echo number_format($service['price'], 2); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Duration</span>
                                    <span class="meta-value"><?php echo $service['duration']; ?> min</span>
                                </div>
                            </div>

                            <button class="expand-btn" onclick="openServiceModal(<?php echo $index; ?>)">
                                <span>View Details & Book</span>
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🏥</div>
                    <h3 class="empty-title">No Services Available</h3>
                    <p class="empty-text">We're currently updating our services. Please check back soon for available treatments.</p>
                </div>
            <?php endif; ?>

            <div class="no-results" id="noResults">
                <div class="empty-icon">🔍</div>
                <h3 class="empty-title">No Services Found</h3>
                <p class="empty-text">No services match your search. Try different keywords.</p>
            </div>
        </div>

        <!-- Details Modal Overlay -->
        <div class="details-overlay" id="detailsOverlay" onclick="closeServiceModal(event)">
            <div class="details-modal" onclick="event.stopPropagation()">
                <div class="modal-header" id="modalHeader">
                    <button class="modal-close" onclick="closeServiceModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h2 class="modal-title" id="modalTitle"></h2>
                    <p class="modal-description" id="modalDescription"></p>

                    <div class="modal-details">
                        <div class="modal-detail-box">
                            <div class="modal-detail-icon">💰</div>
                            <div class="modal-detail-label">Price</div>
                            <div class="modal-detail-value" id="modalPrice"></div>
                        </div>
                        <div class="modal-detail-box">
                            <div class="modal-detail-icon">⏱️</div>
                            <div class="modal-detail-label">Duration</div>
                            <div class="modal-detail-value" id="modalDuration"></div>
                        </div>
                        <div class="modal-detail-box">
                            <div class="modal-detail-icon">✅</div>
                            <div class="modal-detail-label">Status</div>
                            <div class="modal-detail-value">Available</div>
                        </div>
                    </div>

                    <div class="modal-features" id="modalFeatures"></div>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'patient'): ?>
                        <a href="#" class="modal-book-btn" id="modalBookBtn">
                            <i class="fas fa-calendar-check"></i>
                            <span>Book This Service</span>
                        </a>
                    <?php else: ?>
                        <a href="index_patient.php" class="modal-book-btn login-btn">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login to Book</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store service data for modal
        const servicesData = <?php echo json_encode($service_result); ?>;

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Scroll-triggered animations for panels
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.15
        };

        const panelObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.service-panel').forEach(panel => {
            panelObserver.observe(panel);
        });

        // Modal functionality
        function openServiceModal(index) {
            const service = servicesData[index];
            const panel = document.querySelectorAll('.service-panel')[index];
            const overlay = document.getElementById('detailsOverlay');
            const modalHeader = document.getElementById('modalHeader');

            // Set modal content
            document.getElementById('modalTitle').textContent = service.name;
            document.getElementById('modalDescription').textContent = service.description;
            document.getElementById('modalPrice').textContent = 'RM ' + parseFloat(service.price).toFixed(2);
            document.getElementById('modalDuration').textContent = service.duration + ' min';

            // Set header image
            if (service.image && service.image !== '') {
                modalHeader.innerHTML = `
                    <img src="${service.image}" alt="${service.name}">
                    <button class="modal-close" onclick="closeServiceModal()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            } else {
                const icon = panel.dataset.icon || '🏥';
                modalHeader.innerHTML = `
                    <div class="modal-header-placeholder">${icon}</div>
                    <button class="modal-close" onclick="closeServiceModal()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            }

            // Set features
            const features = JSON.parse(panel.dataset.features || '[]');
            const featuresHtml = features.map(f =>
                `<span class="modal-feature"><i class="fas fa-check"></i>${f}</span>`
            ).join('');
            document.getElementById('modalFeatures').innerHTML = featuresHtml;

            // Set book button href
            const bookBtn = document.getElementById('modalBookBtn');
            if (bookBtn) {
                bookBtn.href = `book_appointment.php?service_id=${service.service_id}`;
            }

            // Show modal
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeServiceModal(event) {
            if (event && event.target !== event.currentTarget) return;
            const overlay = document.getElementById('detailsOverlay');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeServiceModal();
            }
        });

        // Search functionality
        const searchInput = document.getElementById('serviceSearch');
        const servicePanels = document.querySelectorAll('.service-panel');
        const noResults = document.getElementById('noResults');
        const visibleCount = document.getElementById('visibleCount');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visiblePanels = 0;

            servicePanels.forEach(panel => {
                const name = panel.dataset.name || '';
                const desc = panel.dataset.desc || '';

                if (name.includes(searchTerm) || desc.includes(searchTerm)) {
                    panel.style.display = 'grid';
                    visiblePanels++;
                } else {
                    panel.style.display = 'none';
                }
            });

            // Show/hide no results message
            if (visiblePanels === 0 && searchTerm !== '') {
                noResults.classList.add('show');
            } else {
                noResults.classList.remove('show');
            }

            // Update count
            visibleCount.textContent = visiblePanels;
        });

        // Trigger initial visibility check for panels in viewport
        setTimeout(() => {
            servicePanels.forEach(panel => {
                const rect = panel.getBoundingClientRect();
                if (rect.top < window.innerHeight) {
                    panel.classList.add('visible');
                }
            });
        }, 100);
    </script>
</body>
</html>
