<?php
require_once 'auth_check.php';
requirePatientLogin();

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

$patient_id = $_SESSION['user_id'];

// Mark all unread messages as read when the user accesses this page
$stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE patient_id = ? AND is_read = 0 AND sender_type != 'patient'");
$stmt->execute([$patient_id]);

// Get patient info
$stmt = $pdo->prepare("SELECT full_name FROM patient WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

// AI Chatbot function to process and respond to queries
function processChatbotQuery($pdo, $patient_id, $message) {
    $message_lower = strtolower($message);
    $response = null;

    // Check for appointment-related queries
    if (preg_match('/(appointment|booking|schedule|when|upcoming|next visit|my visit)/i', $message_lower)) {
        $stmt = $pdo->prepare("
            SELECT a.*, s.name as service_name
            FROM appointment a
            JOIN service s ON a.service_id = s.service_id
            WHERE a.patient_id = ? AND a.date >= CURDATE() AND a.status = 'Scheduled'
            ORDER BY a.date ASC, a.time ASC
            LIMIT 5
        ");
        $stmt->execute([$patient_id]);
        $appointments = $stmt->fetchAll();

        if (empty($appointments)) {
            $response = "📅 You don't have any upcoming appointments scheduled.\n\nWould you like to book a new appointment? Visit your dashboard to schedule one!";
        } else {
            $response = "📅 **Your Upcoming Appointments:**\n\n";
            foreach ($appointments as $index => $apt) {
                $date = date('l, F j, Y', strtotime($apt['date']));
                $time = date('g:i A', strtotime($apt['time']));
                $response .= ($index + 1) . ". **" . $apt['service_name'] . "**\n";
                $response .= "   📆 " . $date . "\n";
                $response .= "   ⏰ " . $time . "\n";
                $response .= "   💰 RM" . number_format($apt['price'], 2) . "\n\n";
            }
            $response .= "Need to reschedule? Contact our staff or visit your dashboard.";
        }
        return $response;
    }

    // Check for services-related queries
    if (preg_match('/(service|treatment|procedure|offer|available|what do you|can you do|option)/i', $message_lower)) {
        $stmt = $pdo->query("SELECT name, description, price, duration FROM service ORDER BY name ASC LIMIT 10");
        $services = $stmt->fetchAll();

        if (empty($services)) {
            $response = "🏥 Our services list is currently being updated. Please contact our staff for more information.";
        } else {
            $response = "🏥 **Our Available Services:**\n\n";
            foreach ($services as $index => $service) {
                $response .= ($index + 1) . ". **" . $service['name'] . "**\n";
                $response .= "   💰 RM" . number_format($service['price'], 2);
                if ($service['duration']) {
                    $response .= " • ⏱️ " . $service['duration'] . " mins";
                }
                $response .= "\n";
                if ($service['description']) {
                    $desc = strlen($service['description']) > 80 ? substr($service['description'], 0, 80) . '...' : $service['description'];
                    $response .= "   " . $desc . "\n";
                }
                $response .= "\n";
            }
            $response .= "Would you like to book any of these services?";
        }
        return $response;
    }

    // Check for price/cost queries
    if (preg_match('/(price|cost|how much|fee|charge|rate)/i', $message_lower)) {
        $stmt = $pdo->query("SELECT name, price FROM service ORDER BY price ASC");
        $services = $stmt->fetchAll();

        $response = "💰 **Service Pricing:**\n\n";
        foreach ($services as $service) {
            $response .= "• " . $service['name'] . " - **RM" . number_format($service['price'], 2) . "**\n";
        }
        $response .= "\n_Prices may vary based on individual treatment needs._";
        return $response;
    }

    // Check for operating hours queries
    if (preg_match('/(hour|open|close|time|when are you|operating|business hour|working hour)/i', $message_lower)) {
        $response = "🕐 **Operating Hours:**\n\n";
        $response .= "📅 Monday - Friday: 9:00 AM - 6:00 PM\n";
        $response .= "📅 Saturday: 9:00 AM - 1:00 PM\n";
        $response .= "📅 Sunday: Closed\n\n";
        $response .= "📞 For emergencies, please call our hotline.";
        return $response;
    }

    // Check for contact/location queries
    if (preg_match('/(contact|phone|call|address|location|where|find you|reach)/i', $message_lower)) {
        $response = "📍 **Contact Information:**\n\n";
        $response .= "🏢 True Beauty Concept Dermatology Clinic\n";
        $response .= "📍 123 Medical Center Drive\n";
        $response .= "📞 +60 12-345 6789\n";
        $response .= "📧 info@truebeautyconcept.com\n\n";
        $response .= "We're here to help! Feel free to visit us during operating hours.";
        return $response;
    }

    // Check for help/assistance queries
    if (preg_match('/(help|assist|support|what can you|how do i|guide)/i', $message_lower)) {
        $response = "🤖 **Hi! I'm your virtual assistant. Here's how I can help:**\n\n";
        $response .= "📅 **Appointments** - Ask about your upcoming appointments\n";
        $response .= "🏥 **Services** - View our available treatments\n";
        $response .= "💰 **Pricing** - Check service costs\n";
        $response .= "🕐 **Hours** - Our operating schedule\n";
        $response .= "📍 **Contact** - Location and phone number\n\n";
        $response .= "Just type your question naturally, and I'll do my best to help!\n\n";
        $response .= "_For complex inquiries, our medical staff will respond personally._";
        return $response;
    }

    // Check for greeting
    if (preg_match('/^(hi|hello|hey|good morning|good afternoon|good evening|greetings)/i', $message_lower)) {
        $patient_name = explode(' ', $GLOBALS['patient']['full_name'])[0];
        $hour = date('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        $response = "👋 **" . $greeting . ", " . $patient_name . "!**\n\n";
        $response .= "Welcome to True Beauty Concept. I'm your virtual assistant.\n\n";
        $response .= "How can I help you today? You can ask me about:\n";
        $response .= "• Your appointments\n";
        $response .= "• Our services & pricing\n";
        $response .= "• Operating hours\n";
        $response .= "• Contact information\n\n";
        $response .= "Or simply type your question!";
        return $response;
    }

    // Check for thank you
    if (preg_match('/(thank|thanks|appreciate)/i', $message_lower)) {
        $response = "😊 You're welcome! Is there anything else I can help you with?\n\n";
        $response .= "Feel free to ask about appointments, services, or anything else!";
        return $response;
    }

    // Check for cancel/reschedule
    if (preg_match('/(cancel|reschedule|change|modify|move)/i', $message_lower)) {
        $response = "🔄 **Appointment Changes:**\n\n";
        $response .= "To cancel or reschedule your appointment:\n\n";
        $response .= "1️⃣ Go to your **Dashboard**\n";
        $response .= "2️⃣ Find your appointment\n";
        $response .= "3️⃣ Click on the appointment to manage it\n\n";
        $response .= "⚠️ Please note: Cancellations should be made at least 24 hours in advance.\n\n";
        $response .= "Need help? Our staff will respond to assist you personally.";
        return $response;
    }

    // Default - no specific intent detected, will be handled by staff
    return null;
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        // Save patient message
        $stmt = $pdo->prepare("INSERT INTO messages (patient_id, sender_type, message, timestamp) VALUES (?, 'patient', ?, NOW())");
        $stmt->execute([$patient_id, $message]);

        // Process with chatbot
        $bot_response = processChatbotQuery($pdo, $patient_id, $message);

        // If chatbot has a response, save it
        if ($bot_response) {
            // Small delay to make it feel more natural
            usleep(500000); // 0.5 second delay

            $stmt = $pdo->prepare("INSERT INTO messages (patient_id, sender_type, message, timestamp) VALUES (?, 'bot', ?, NOW())");
            $stmt->execute([$patient_id, $bot_response]);
        }
    }

    // Redirect to prevent form resubmission
    header("Location: patient_messages.php");
    exit;
}

// Get all messages for this patient
$stmt = $pdo->prepare("
    SELECT m.*,
           p.full_name as patient_name
    FROM messages m
    LEFT JOIN patient p ON m.patient_id = p.patient_id
    WHERE m.patient_id = ?
    ORDER BY m.timestamp ASC
");
$stmt->execute([$patient_id]);
$messages = $stmt->fetchAll();

// Group messages by date
$grouped_messages = [];
foreach ($messages as $msg) {
    $date = date('Y-m-d', strtotime($msg['timestamp']));
    if (!isset($grouped_messages[$date])) {
        $grouped_messages[$date] = [];
    }
    $grouped_messages[$date][] = $msg;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - True Beauty Concept</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c5f6f;
            --secondary-color: #8ab4a7;
            --accent-color: #d4a574;
            --text-dark: #1a1a1a;
            --text-light: #f5f5f5;
            --success: #10b981;
            --info: #3b82f6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            height: 100vh;
            position: relative;
            overflow: hidden;
            color: var(--text-dark);
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
                radial-gradient(circle at 95% 45%, rgba(255, 255, 255, 0.6) 1px, transparent 1px);
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
                radial-gradient(circle at 92% 12%, rgba(255, 255, 255, 0.8) 2px, transparent 2px);
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

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(212, 165, 116, 0.6);
            border-radius: 50%;
            animation: float-particle 20s infinite;
        }

        .particle:nth-child(1) { left: 10%; animation-delay: 0s; animation-duration: 25s; }
        .particle:nth-child(2) { left: 20%; animation-delay: 3s; animation-duration: 20s; }
        .particle:nth-child(3) { left: 30%; animation-delay: 6s; animation-duration: 28s; }
        .particle:nth-child(4) { left: 40%; animation-delay: 9s; animation-duration: 22s; }
        .particle:nth-child(5) { left: 50%; animation-delay: 12s; animation-duration: 30s; }
        .particle:nth-child(6) { left: 60%; animation-delay: 15s; animation-duration: 24s; }
        .particle:nth-child(7) { left: 70%; animation-delay: 18s; animation-duration: 26s; }
        .particle:nth-child(8) { left: 80%; animation-delay: 21s; animation-duration: 21s; }
        .particle:nth-child(9) { left: 90%; animation-delay: 24s; animation-duration: 27s; }

        @keyframes float-particle {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) scale(1);
                opacity: 0;
            }
        }

        .chat-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        /* Chat Header */
        .chat-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 30px;
            border-radius: 25px 25px 0 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            position: relative;
            overflow: hidden;
        }

        .chat-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color));
        }

        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .doctor-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 8px 25px rgba(44, 95, 111, 0.4);
            position: relative;
        }

        .doctor-avatar::after {
            content: '';
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 14px;
            height: 14px;
            background: var(--success);
            border-radius: 50%;
            border: 3px solid white;
            animation: pulse-status 2s ease-in-out infinite;
        }

        @keyframes pulse-status {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        .doctor-info {
            flex: 1;
            min-width: 0;
        }

        .doctor-name {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-color);
            margin-bottom: 4px;
            font-family: 'Playfair Display', serif;
        }

        .doctor-status {
            font-size: 0.85rem;
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .back-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            box-shadow: 0 8px 25px rgba(44, 95, 111, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(44, 95, 111, 0.4);
        }

        .back-btn i {
            transition: transform 0.3s ease;
        }

        .back-btn:hover i {
            transform: translateX(-3px);
        }

        /* Messages Area */
        .messages-area {
            flex: 1;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            padding: 25px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .messages-area::-webkit-scrollbar {
            width: 6px;
        }

        .messages-area::-webkit-scrollbar-track {
            background: transparent;
        }

        .messages-area::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            border-radius: 10px;
        }

        /* Date Separator */
        .date-separator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
        }

        .date-separator span {
            background: rgba(44, 95, 111, 0.1);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--primary-color);
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .message {
            display: flex;
            gap: 12px;
            max-width: 75%;
            animation: messageSlide 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .message.sent {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message.received {
            align-self: flex-start;
        }

        .message-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .message.received .message-avatar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .message.bot .message-avatar {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
        }

        .message.sent .message-avatar {
            background: linear-gradient(135deg, var(--accent-color), #c49660);
        }

        .message-content {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .message-bubble {
            padding: 14px 18px;
            border-radius: 20px;
            word-wrap: break-word;
            line-height: 1.6;
            font-size: 0.95rem;
            position: relative;
        }

        .message.sent .message-bubble {
            background: linear-gradient(135deg, var(--accent-color), #c49660);
            color: white;
            border-bottom-right-radius: 6px;
            box-shadow: 0 8px 25px rgba(212, 165, 116, 0.3);
        }

        .message.received .message-bubble {
            background: white;
            color: var(--text-dark);
            border-bottom-left-radius: 6px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .message.bot .message-bubble {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(99, 102, 241, 0.1));
            color: var(--text-dark);
            border-bottom-left-radius: 6px;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .bot-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            color: #8b5cf6;
            font-weight: 600;
            margin-bottom: 4px;
            padding: 2px 8px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 10px;
            width: fit-content;
        }

        .bot-indicator i {
            font-size: 0.65rem;
        }

        .message-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 8px;
        }

        .message-time {
            font-size: 0.75rem;
            color: #888;
        }

        .message.sent .message-meta {
            justify-content: flex-end;
        }

        .message-status {
            font-size: 0.7rem;
            color: var(--success);
        }

        .message.sent .message-status {
            display: inline;
        }

        /* Empty State */
        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #888;
            gap: 20px;
            text-align: center;
            padding: 40px;
        }

        .empty-icon-wrapper {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, rgba(44, 95, 111, 0.1), rgba(212, 165, 116, 0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .empty-icon-wrapper i {
            font-size: 48px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .empty-state p {
            font-size: 1rem;
            max-width: 300px;
            line-height: 1.6;
        }

        /* Quick Replies */
        .quick-replies {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 10px;
        }

        .quick-reply-btn {
            background: rgba(44, 95, 111, 0.1);
            border: 2px solid rgba(44, 95, 111, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .quick-reply-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        /* Input Area */
        .input-area {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 15px 25px 20px;
            border-radius: 0 0 25px 25px;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.1);
        }

        .quick-actions-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            overflow-x: auto;
            padding-bottom: 5px;
            scrollbar-width: none;
        }

        .quick-actions-bar::-webkit-scrollbar {
            display: none;
        }

        .quick-action-chip {
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.2);
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            color: #8b5cf6;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }

        .quick-action-chip:hover {
            background: #8b5cf6;
            color: white;
            border-color: #8b5cf6;
            transform: translateY(-2px);
        }

        .quick-action-chip i {
            font-size: 0.7rem;
        }

        .input-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }

        .input-wrapper {
            flex: 1;
            position: relative;
        }

        .message-input {
            width: 100%;
            padding: 15px 55px 15px 20px;
            border: 2px solid rgba(44, 95, 111, 0.15);
            border-radius: 25px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            background: rgba(248, 249, 250, 0.8);
            transition: all 0.3s ease;
            resize: none;
            max-height: 120px;
            min-height: 52px;
        }

        .message-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(44, 95, 111, 0.1);
        }

        .message-input::placeholder {
            color: #999;
        }

        .input-actions {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 8px;
        }

        .emoji-btn {
            background: none;
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            padding: 5px;
        }

        .emoji-btn:hover {
            transform: scale(1.2) rotate(10deg);
        }

        .send-btn {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s ease;
            flex-shrink: 0;
            box-shadow: 0 8px 25px rgba(44, 95, 111, 0.3);
        }

        .send-btn:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 12px 35px rgba(44, 95, 111, 0.4);
        }

        .send-btn:active {
            transform: scale(0.95);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: scale(1);
        }

        .send-btn i {
            transition: transform 0.3s ease;
        }

        .send-btn:hover i {
            transform: translateX(2px) translateY(-2px);
        }

        /* Typing Indicator */
        .typing-indicator {
            display: none;
            padding: 12px 18px;
            background: white;
            border-radius: 20px;
            border-bottom-left-radius: 6px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            align-self: flex-start;
            margin-left: 50px;
        }

        .typing-indicator.show {
            display: block;
            animation: messageSlide 0.4s ease;
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dots span {
            width: 8px;
            height: 8px;
            background: var(--secondary-color);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-8px); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .chat-container {
                padding: 0;
            }

            .chat-header {
                border-radius: 0;
                padding: 15px 20px;
            }

            .input-area {
                border-radius: 0;
                padding: 15px 20px;
            }

            .message {
                max-width: 85%;
            }

            .back-btn {
                padding: 10px 16px;
                font-size: 0.85rem;
            }

            .back-btn span {
                display: none;
            }

            .doctor-name {
                font-size: 1rem;
            }

            .messages-area {
                padding: 15px;
            }

            .quick-replies {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .doctor-avatar {
                width: 45px;
                height: 45px;
                font-size: 16px;
            }

            .message-avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .message-bubble {
                padding: 12px 14px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="animated-background"></div>

    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="chat-container">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-header-info">
                <div class="doctor-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="doctor-info">
                    <div class="doctor-name">AI Assistant & Medical Staff</div>
                    <div class="doctor-status">
                        <i class="fas fa-circle" style="font-size: 8px;"></i>
                        AI ready • Staff online
                    </div>
                </div>
            </div>
            <a href="patient_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Messages Area -->
        <div class="messages-area" id="messagesArea">
            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    <div class="empty-icon-wrapper">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Start a Conversation</h3>
                    <p>Send a message to our medical staff and we'll respond as soon as possible.</p>
                    <div class="quick-replies">
                        <button class="quick-reply-btn" onclick="setQuickReply('Hello, I have a question about my appointment.')">
                            <i class="fas fa-calendar-check"></i> Appointment Query
                        </button>
                        <button class="quick-reply-btn" onclick="setQuickReply('I would like to inquire about your services.')">
                            <i class="fas fa-concierge-bell"></i> Service Info
                        </button>
                        <button class="quick-reply-btn" onclick="setQuickReply('I need help with my treatment.')">
                            <i class="fas fa-medkit"></i> Treatment Help
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php
                $current_date = '';
                foreach ($messages as $msg):
                    $msg_date = date('Y-m-d', strtotime($msg['timestamp']));
                    if ($msg_date !== $current_date):
                        $current_date = $msg_date;
                        $display_date = date('Y-m-d') === $msg_date ? 'Today' : (date('Y-m-d', strtotime('-1 day')) === $msg_date ? 'Yesterday' : date('M d, Y', strtotime($msg_date)));
                ?>
                    <div class="date-separator">
                        <span><?= $display_date ?></span>
                    </div>
                <?php endif; ?>
                    <?php
                    $msg_class = 'received';
                    if ($msg['sender_type'] === 'patient') {
                        $msg_class = 'sent';
                    } elseif ($msg['sender_type'] === 'bot') {
                        $msg_class = 'received bot';
                    }
                    ?>
                    <div class="message <?= $msg_class ?>">
                        <div class="message-avatar">
                            <?php
                            if ($msg['sender_type'] === 'patient') {
                                echo strtoupper(substr($patient['full_name'], 0, 1));
                            } elseif ($msg['sender_type'] === 'bot') {
                                echo '<i class="fas fa-robot" style="font-size: 14px;"></i>';
                            } else {
                                echo '<i class="fas fa-user-md" style="font-size: 14px;"></i>';
                            }
                            ?>
                        </div>
                        <div class="message-content">
                            <?php if ($msg['sender_type'] === 'bot'): ?>
                                <div class="bot-indicator">
                                    <i class="fas fa-bolt"></i> AI Assistant
                                </div>
                            <?php endif; ?>
                            <div class="message-bubble">
                                <?php
                                // Convert markdown-style bold to HTML
                                $formatted_msg = htmlspecialchars($msg['message']);
                                $formatted_msg = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $formatted_msg);
                                $formatted_msg = preg_replace('/_(.+?)_/s', '<em>$1</em>', $formatted_msg);
                                echo nl2br($formatted_msg);
                                ?>
                            </div>
                            <div class="message-meta">
                                <span class="message-time">
                                    <?php
                                    $time = new DateTime($msg['timestamp']);
                                    echo $time->format('g:i A');
                                    ?>
                                </span>
                                <?php if ($msg['sender_type'] === 'patient'): ?>
                                    <span class="message-status">
                                        <i class="fas fa-check-double"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Typing Indicator -->
                <div class="typing-indicator" id="typingIndicator">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Input Area -->
        <div class="input-area">
            <!-- Quick Action Chips -->
            <div class="quick-actions-bar">
                <button type="button" class="quick-action-chip" onclick="setQuickReply('When is my next appointment?')">
                    <i class="fas fa-calendar"></i> My Appointments
                </button>
                <button type="button" class="quick-action-chip" onclick="setQuickReply('What services do you offer?')">
                    <i class="fas fa-stethoscope"></i> Services
                </button>
                <button type="button" class="quick-action-chip" onclick="setQuickReply('What are your prices?')">
                    <i class="fas fa-tag"></i> Pricing
                </button>
                <button type="button" class="quick-action-chip" onclick="setQuickReply('What are your operating hours?')">
                    <i class="fas fa-clock"></i> Hours
                </button>
                <button type="button" class="quick-action-chip" onclick="setQuickReply('How can I contact you?')">
                    <i class="fas fa-phone"></i> Contact
                </button>
                <button type="button" class="quick-action-chip" onclick="setQuickReply('Help')">
                    <i class="fas fa-question-circle"></i> Help
                </button>
            </div>

            <form method="POST" class="input-form" id="messageForm">
                <div class="input-wrapper">
                    <textarea
                        name="message"
                        id="messageInput"
                        class="message-input"
                        placeholder="Ask me anything... Try 'When is my appointment?'"
                        rows="1"
                        required
                    ></textarea>
                    <div class="input-actions">
                        <button type="button" class="emoji-btn" onclick="insertEmoji()" title="Add emoji">
                            <i class="far fa-smile"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="send-btn" id="sendBtn" title="Send message">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        const messageInput = document.getElementById('messageInput');
        const messagesArea = document.getElementById('messagesArea');

        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Submit on Enter (but allow Shift+Enter for new line)
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim()) {
                    document.getElementById('messageForm').submit();
                }
            }
        });

        // Scroll to bottom of messages
        function scrollToBottom() {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Scroll to bottom on load
        window.addEventListener('load', scrollToBottom);

        // Simple emoji insertion with picker
        function insertEmoji() {
            const emojis = ['😊', '👍', '❤️', '😄', '🎉', '👏', '🙏', '💪', '✨', '🌟', '😷', '💊', '🏥', '👨‍⚕️'];
            const emoji = emojis[Math.floor(Math.random() * emojis.length)];
            messageInput.value += emoji;
            messageInput.focus();
        }

        // Quick reply function
        function setQuickReply(text) {
            messageInput.value = text;
            messageInput.focus();
            messageInput.style.height = 'auto';
            messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
        }

        // Show typing indicator occasionally (for visual effect)
        function showTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator && Math.random() > 0.7) {
                indicator.classList.add('show');
                setTimeout(() => {
                    indicator.classList.remove('show');
                }, 3000);
            }
        }

        // Auto-refresh messages every 5 seconds only when input is not focused
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
                showTypingIndicator();
                setTimeout(() => {
                    location.reload();
                }, 3500);
            }
        }, 8000);

        // Add smooth entrance animation to messages on load
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach((msg, index) => {
                msg.style.animationDelay = `${index * 0.05}s`;
            });
        });
    </script>
</body>
</html>
