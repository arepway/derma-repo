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

$conn = new mysqli($host, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$staff_id = $_SESSION['user_id'];

// Get staff info for header
$stmt = $pdo->prepare("SELECT full_name FROM medical_staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create' || $_POST['action'] == 'update') {
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $price = floatval($_POST['price']);
            $duration = intval($_POST['duration']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Handle image upload
            $image_path = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_dir = 'uploads/services/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_path = $upload_path;
                    }
                }
            }

            if ($_POST['action'] == 'create') {
                $sql = "INSERT INTO service (name, description, price, duration, is_active, image, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdiss", $name, $description, $price, $duration, $is_active, $image_path);

                if ($stmt->execute()) {
                    $message = "Service created successfully!";
                } else {
                    $error = "Error creating service: " . $conn->error;
                }
            } else {
                // Update service
                $service_id = intval($_POST['service_id']);

                // If no new image uploaded, keep the existing one
                if (empty($image_path)) {
                    $sql = "UPDATE service SET name = ?, description = ?, price = ?, duration = ?,
                            is_active = ?, updated_at = NOW() WHERE service_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssdiii", $name, $description, $price, $duration, $is_active, $service_id);
                } else {
                    // Delete old image if exists
                    $old_image_query = "SELECT image FROM service WHERE service_id = ?";
                    $old_stmt = $conn->prepare($old_image_query);
                    $old_stmt->bind_param("i", $service_id);
                    $old_stmt->execute();
                    $old_result = $old_stmt->get_result();
                    if ($old_row = $old_result->fetch_assoc()) {
                        if (!empty($old_row['image']) && file_exists($old_row['image'])) {
                            unlink($old_row['image']);
                        }
                    }

                    $sql = "UPDATE service SET name = ?, description = ?, price = ?, duration = ?,
                            is_active = ?, image = ?, updated_at = NOW() WHERE service_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssdiisi", $name, $description, $price, $duration, $is_active, $image_path, $service_id);
                }

                if ($stmt->execute()) {
                    $message = "Service updated successfully!";
                } else {
                    $error = "Error updating service: " . $conn->error;
                }
            }
        } elseif ($_POST['action'] == 'delete') {
            $service_id = intval($_POST['service_id']);

            // Delete image file if exists
            $image_query = "SELECT image FROM service WHERE service_id = ?";
            $img_stmt = $conn->prepare($image_query);
            $img_stmt->bind_param("i", $service_id);
            $img_stmt->execute();
            $img_result = $img_stmt->get_result();
            if ($img_row = $img_result->fetch_assoc()) {
                if (!empty($img_row['image']) && file_exists($img_row['image'])) {
                    unlink($img_row['image']);
                }
            }

            $sql = "DELETE FROM service WHERE service_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $service_id);

            if ($stmt->execute()) {
                $message = "Service deleted successfully!";
            } else {
                $error = "Error deleting service: " . $conn->error;
            }
        }
    }
}

// Fetch all services
$service_query = "SELECT * FROM service ORDER BY created_at DESC";
$services_result = $conn->query($service_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Dermatology Clinic</title>
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
            position: relative;
            z-index: 1;
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

        .back-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44, 95, 111, 0.3);
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

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

        /* Bento Layout */
        .bento-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 1200px) {
            .bento-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Messages */
        .message {
            padding: 18px 24px;
            border-radius: 15px;
            margin-bottom: 24px;
            font-size: 15px;
            backdrop-filter: blur(10px);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .success {
            background: rgba(52, 199, 89, 0.15);
            color: #34c759;
            border: 2px solid rgba(52, 199, 89, 0.3);
        }

        .error {
            background: rgba(255, 59, 48, 0.15);
            color: #ff3b30;
            border: 2px solid rgba(255, 59, 48, 0.3);
        }

        /* Form Section */
        .form-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .bento-grid .form-section {
            margin-bottom: 0;
        }

        @media (max-width: 1200px) {
            .bento-grid .form-section {
                margin-bottom: 24px;
            }
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 600;
            color: white;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg, var(--primary-color), var(--accent-color)) 1;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: white;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 4px rgba(138, 180, 167, 0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.9);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppons', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 4px 15px rgba(44, 95, 111, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 95, 111, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            max-height: 70vh;
            overflow-y: auto;
            padding-right: 8px;
        }

        .services-grid::-webkit-scrollbar {
            width: 6px;
        }

        .services-grid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }

        .services-grid::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .services-grid::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .service-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(138, 180, 167, 0.3);
            border-color: rgba(138, 180, 167, 0.5);
        }

        .service-image-wrapper {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .service-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .service-image-placeholder {
            font-size: 48px;
            color: white;
            opacity: 0.7;
        }

        .service-content {
            padding: 20px;
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .service-name {
            font-weight: 600;
            font-size: 18px;
            color: white;
            flex: 1;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: rgba(52, 199, 89, 0.15);
            color: #34c759;
            border: 1px solid rgba(52, 199, 89, 0.3);
        }

        .status-inactive {
            background: rgba(255, 59, 48, 0.15);
            color: #ff3b30;
            border: 1px solid rgba(255, 59, 48, 0.3);
        }

        .service-description {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .service-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 16px;
            padding: 12px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .meta-label {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-value {
            font-size: 16px;
            font-weight: 600;
            color: white;
        }

        .service-actions {
            display: flex;
            gap: 8px;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-direction: column;
            }

            .header-left, .header-right {
                width: 100%;
                justify-content: center;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 640px) {
            .container {
                padding: 0 1rem 1rem;
            }

            .page-title {
                font-size: 22px;
            }

            .form-section {
                padding: 20px;
            }

            .service-card {
                padding: 0;
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
        
    <div class="container">
        <!-- Navigation -->
        <div class="nav-container">
            <div class="nav-links">
                <a href="staff_dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_services.php" class="nav-link active">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Services</span>
                </a>
                <a href="manage_feedback.php" class="nav-link">
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

        <?php if ($message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Bento Grid Layout -->
        <div class="bento-grid">
        <!-- Service Form -->
        <div class="form-section">
            <h2 class="section-title" id="form-title">
                <i class="fas fa-plus-circle"></i> Add New Service
            </h2>
            <form method="POST" enctype="multipart/form-data" id="serviceForm">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="service_id" id="service_id">

                <div class="form-group">
                    <label for="name"><i class="fas fa-tag"></i> Service Name *</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g., Acne Treatment" required>
                </div>

                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description *</label>
                    <textarea class="form-control" id="description" name="description" placeholder="Describe the service details..." required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price"><i class="fas fa-dollar-sign"></i> Price (RM) *</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label for="duration"><i class="fas fa-clock"></i> Duration (minutes) *</label>
                        <input type="number" class="form-control" id="duration" name="duration" min="1" placeholder="30" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="image"><i class="fas fa-image"></i> Service Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    <small style="color: rgba(255, 255, 255, 0.5); font-size: 12px; margin-top: 5px; display: block;">Accepted formats: JPG, JPEG, PNG, GIF</small>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" checked>
                        <label for="is_active">Active (Service is available for booking)</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Service
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Services Grid -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-list"></i> Existing Services
            </h2>
            <div class="services-grid">
                <?php while ($service = $services_result->fetch_assoc()): ?>
                    <div class="service-card">
                        <div class="service-image-wrapper">
                            <?php if (!empty($service['image']) && file_exists($service['image'])): ?>
                                <img src="<?php echo htmlspecialchars($service['image']); ?>" alt="Service" class="service-image">
                            <?php else: ?>
                                <div class="service-image-placeholder">
                                    <i class="fas fa-stethoscope"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="service-content">
                            <div class="service-header">
                                <div class="service-name"><?php echo htmlspecialchars($service['name']); ?></div>
                                <span class="status-badge <?php echo $service['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            <div class="service-description">
                                <?php echo htmlspecialchars($service['description']); ?>
                            </div>
                            <div class="service-meta">
                                <div class="meta-item">
                                    <div class="meta-label"><i class="fas fa-dollar-sign"></i> Price</div>
                                    <div class="meta-value">RM<?php echo number_format($service['price'], 2); ?></div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-label"><i class="far fa-clock"></i> Duration</div>
                                    <div class="meta-value"><?php echo $service['duration']; ?> min</div>
                                </div>
                            </div>
                            <div class="service-actions">
                                <button onclick='editService(<?php echo json_encode($service, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="btn btn-warning" style="flex: 1;">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="flex: 1; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this service? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="width: 100%;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        </div><!-- End Bento Grid -->
    </div>

    <script>
        function editService(service) {
            document.getElementById('form-title').innerHTML = '<i class="fas fa-edit"></i> Edit Service';
            document.getElementById('form-action').value = 'update';
            document.getElementById('service_id').value = service.service_id;
            document.getElementById('name').value = service.name;
            document.getElementById('description').value = service.description;
            document.getElementById('price').value = service.price;
            document.getElementById('duration').value = service.duration;
            document.getElementById('is_active').checked = service.is_active == 1;

            // Scroll to form
            document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('serviceForm').reset();
            document.getElementById('form-title').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Service';
            document.getElementById('form-action').value = 'create';
            document.getElementById('service_id').value = '';
        }
    </script>
</body>
</html>
