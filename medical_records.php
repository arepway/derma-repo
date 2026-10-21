<?php
require_once 'auth_check.php';
requirePatientLogin();

// Database connection - Update these settings to match your setup
$host = 'localhost';           // or '127.0.0.1'
$port = '3307';               // MySQL port (usually 3306)
$dbname = 'dermatology_clinic';
$db_username = 'root';        // your MySQL username
$db_password = '';            // your MySQL password (empty for XAMPP/WAMP default)

$mysqli = new mysqli($host, $db_username, $db_password, $dbname, $port);
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

$patient_id = $_SESSION['user_id'];

// Get filter parameters
$filter_from = isset($_GET['from']) ? $_GET['from'] : '';
$filter_to = isset($_GET['to']) ? $_GET['to'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters
$query = "SELECT
    a.appointment_id,
    a.date,
    a.time,
    a.status,
    s.name as service_name,
    s.description as service_description,
    CONCAT(ms.full_name) as doctor_name,
    ms.email as doctor_email,
    mr.medication_name,
    mr.medication_steps,
    mr.notes,
    mr.used_injection,
    mr.injection_quantity,
    mr.recorded_at as record_created_at
FROM appointment a
INNER JOIN service s ON a.service_id = s.service_id
LEFT JOIN medical_staff ms ON a.staff_id = ms.staff_id
LEFT JOIN medication_records mr ON a.appointment_id = mr.appointment_id
WHERE a.patient_id = ?";

$params = [$patient_id];
$types = "i";

// Add date filters
if (!empty($filter_from)) {
    $query .= " AND a.date >= ?";
    $params[] = $filter_from;
    $types .= "s";
}

if (!empty($filter_to)) {
    $query .= " AND a.date <= ?";
    $params[] = $filter_to;
    $types .= "s";
}

// Add status filter - only show completed appointments by default
if (!empty($filter_status)) {
    $query .= " AND a.status = ?";
    $params[] = $filter_status;
    $types .= "s";
} else {
    // Show only completed appointments (ones with medical records)
    $query .= " AND a.status = 'Completed'";
}

$query .= " ORDER BY a.date DESC, a.time DESC";

$stmt = $mysqli->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stats_query = "SELECT
    COUNT(*) as total_appointments,
    SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN mr.medication_name IS NOT NULL THEN 1 ELSE 0 END) as records_with_statement
FROM appointment a
LEFT JOIN medication_records mr ON a.appointment_id = mr.appointment_id
WHERE a.patient_id = ?";

$stats_stmt = $mysqli->prepare($stats_query);
$stats_stmt->bind_param("i", $patient_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.75, maximum-scale=1.0, user-scalable=yes">
    <title>Medical Records - True Beauty Concept</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            background: #1a2a3a;
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

        /* Navigation Bar - Liquid Glass */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
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
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
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
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .back-btn {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
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
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-color: rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        /* Hero Section */
        .hero-section {
            background: transparent;
            color: white;
            padding: 140px 2rem 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
            line-height: 1.2;
        }

        .hero-description {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 3rem;
            opacity: 0.95;
            line-height: 1.8;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 4rem;
            margin-top: 3rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 80px 2rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 3rem;
        }

        .filter-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .filter-input,
        .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(138, 180, 167, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn-filter,
        .btn-reset {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-filter {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--accent-color) 100%);
            color: white;
            flex: 1;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(138, 180, 167, 0.3);
        }

        .btn-reset {
            background: white;
            color: var(--text-dark);
            border: 2px solid #e0e0e0;
        }

        .btn-reset:hover {
            background: var(--bg-light);
        }

        /* Records Grid */
        .records-grid {
            display: grid;
            gap: 2rem;
        }

        .record-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .record-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 50px rgba(44, 95, 111, 0.15);
        }

        .record-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--accent-color) 100%);
            padding: 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .record-header-left {
            flex: 1;
        }

        .record-date {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .record-service {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 0.25rem;
        }

        .record-doctor {
            font-size: 0.9rem;
            opacity: 0.85;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .record-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .record-body {
            padding: 2rem;
        }

        .record-section {
            margin-bottom: 2rem;
        }

        .record-section:last-child {
            margin-bottom: 0;
        }

        .record-section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .record-section-content {
            background: var(--bg-light);
            padding: 1.5rem;
            border-radius: 15px;
            line-height: 1.8;
            color: var(--text-dark);
        }

        .record-empty {
            color: var(--text-light);
            font-style: italic;
        }

        .record-footer {
            padding: 1.5rem 2rem;
            background: var(--bg-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #e0e0e0;
        }

        .completed-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: white;
            border-radius: 20px;
            border: 2px dashed var(--secondary-color);
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
            margin-bottom: 2rem;
        }

        .btn-book {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--accent-color) 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(138, 180, 167, 0.4);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .record-card {
            animation: fadeInUp 0.6s ease-out backwards;
        }

        .record-card:nth-child(1) { animation-delay: 0.1s; }
        .record-card:nth-child(2) { animation-delay: 0.2s; }
        .record-card:nth-child(3) { animation-delay: 0.3s; }
        .record-card:nth-child(4) { animation-delay: 0.4s; }
        .record-card:nth-child(5) { animation-delay: 0.5s; }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-description {
                font-size: 1rem;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .hero-section {
                padding: 120px 1.5rem 60px;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-stats {
                flex-direction: column;
                gap: 2rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .container {
                padding: 60px 1.5rem;
            }

            .record-header {
                flex-direction: column;
                gap: 1rem;
            }

            .record-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: start;
            }

            .filter-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Starry Night Background -->
    <div class="animated-background"></div>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-leaf"></i>
                <span>True Beauty Concept</span>
            </a>
            <a href="patient_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <span class="hero-badge">Medical Records</span>
            <h1 class="hero-title">Your Health Journey</h1>
            <p class="hero-description">Access your complete appointment history and medical records. Review treatment plans, medications, and follow your progress over time.</p>

            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['total_appointments']; ?></span>
                    <span class="stat-label">Total Appointments</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['completed_count']; ?></span>
                    <span class="stat-label">Completed</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['records_with_statement']; ?></span>
                    <span class="stat-label">Medical Records</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Filter Section -->
        <div class="filter-section">
            <h2 class="filter-title">
                <i class="fas fa-filter"></i>
                Filter Records
            </h2>
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label class="filter-label">From Date</label>
                    <input type="date" name="from" class="filter-input" value="<?php echo htmlspecialchars($filter_from); ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">To Date</label>
                    <input type="date" name="to" class="filter-input" value="<?php echo htmlspecialchars($filter_to); ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-select">
                        <option value="">All Completed</option>
                        <option value="Completed" <?php echo $filter_status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i>
                        Apply Filter
                    </button>
                    <a href="medical_records.php" class="btn-reset">
                        <i class="fas fa-redo"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Records Grid -->
        <div class="records-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($record = $result->fetch_assoc()): ?>
                    <div class="record-card">
                        <div class="record-header">
                            <div class="record-header-left">
                                <div class="record-date">
                                    <?php echo date('F d, Y', strtotime($record['date'])); ?>
                                </div>
                                <div class="record-service">
                                    <i class="fas fa-spa"></i>
                                    <?php echo htmlspecialchars($record['service_name']); ?>
                                </div>
                                <div class="record-doctor">
                                    <i class="fas fa-user-md"></i>
                                    <?php echo $record['doctor_name'] ? 'Dr. ' . htmlspecialchars($record['doctor_name']) : 'Not Assigned'; ?>
                                </div>
                            </div>
                            <div class="record-badge">
                                <?php echo date('g:i A', strtotime($record['time'])); ?>
                            </div>
                        </div>

                        <div class="record-body">
                            <!-- Medication/Treatment Name -->
                            <div class="record-section">
                                <h3 class="record-section-title">
                                    <i class="fas fa-pills"></i>
                                    Medication/Treatment
                                </h3>
                                <div class="record-section-content">
                                    <?php if (!empty($record['medication_name'])): ?>
                                        <strong><?php echo htmlspecialchars($record['medication_name']); ?></strong>
                                        <?php if ($record['used_injection']): ?>
                                            <br><small style="color: var(--text-light);">
                                                <i class="fas fa-syringe"></i> Injection used (Quantity: <?php echo $record['injection_quantity']; ?>)
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="record-empty">No medication recorded</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Treatment Steps/Instructions -->
                            <div class="record-section">
                                <h3 class="record-section-title">
                                    <i class="fas fa-clipboard-list"></i>
                                    Treatment Instructions
                                </h3>
                                <div class="record-section-content">
                                    <?php if (!empty($record['medication_steps'])): ?>
                                        <?php echo nl2br(htmlspecialchars($record['medication_steps'])); ?>
                                    <?php else: ?>
                                        <span class="record-empty">No instructions recorded</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Additional Notes -->
                            <?php if (!empty($record['notes'])): ?>
                            <div class="record-section">
                                <h3 class="record-section-title">
                                    <i class="fas fa-notes-medical"></i>
                                    Additional Notes
                                </h3>
                                <div class="record-section-content">
                                    <?php echo nl2br(htmlspecialchars($record['notes'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="record-footer">
                            <?php if (!empty($record['record_created_at'])): ?>
                            <div class="completed-date">
                                <i class="fas fa-check-circle"></i>
                                Record created on <?php echo date('M d, Y \a\t g:i A', strtotime($record['record_created_at'])); ?>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3 class="empty-title">No Medical Records Found</h3>
                    <p class="empty-text">You don't have any completed appointments yet. Book an appointment to start building your medical history.</p>
                    <a href="services_public.php" class="btn-book">
                        <i class="fas fa-calendar-plus"></i>
                        Book Appointment
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$mysqli->close();
?>
