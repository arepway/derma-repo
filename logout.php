<?php
// Configure session settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 28800);
    ini_set('session.cookie_lifetime', 28800);
    session_set_cookie_params([
        'lifetime' => 28800,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Detect which session to log out from based on referrer or query parameter
$logout_type = $_GET['type'] ?? null;
$redirect_url = 'index.php';
$user_type = null;
$session_name_to_destroy = null;

// If type is explicitly specified, use that
if ($logout_type === 'staff') {
    $session_name_to_destroy = 'STAFF_SESSION';
    $redirect_url = 'index_staff.php';
} elseif ($logout_type === 'patient') {
    $session_name_to_destroy = 'PATIENT_SESSION';
    $redirect_url = 'index_patient.php';
} else {
    // Otherwise, detect from HTTP_REFERER
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'staff') !== false || strpos($referer, 'manage_') !== false) {
        $session_name_to_destroy = 'STAFF_SESSION';
        $redirect_url = 'index_staff.php';
    } elseif (strpos($referer, 'patient') !== false) {
        $session_name_to_destroy = 'PATIENT_SESSION';
        $redirect_url = 'index_patient.php';
    } else {
        // Fallback: Check each session to find which one is active
        // Check STAFF_SESSION first
        session_name('STAFF_SESSION');
        session_start();
        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff') {
            $session_name_to_destroy = 'STAFF_SESSION';
            $redirect_url = 'index_staff.php';
            session_write_close();
        } else {
            session_write_close();

            // Check PATIENT_SESSION
            session_name('PATIENT_SESSION');
            session_start();
            if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient') {
                $session_name_to_destroy = 'PATIENT_SESSION';
                $redirect_url = 'index_patient.php';
                session_write_close();
            } else {
                session_write_close();

                // Check DERMA_SESSION
                session_name('DERMA_SESSION');
                session_start();
                if (isset($_SESSION['user_type'])) {
                    $session_name_to_destroy = 'DERMA_SESSION';
                    if ($_SESSION['user_type'] === 'staff') {
                        $redirect_url = 'index_staff.php';
                    } elseif ($_SESSION['user_type'] === 'patient') {
                        $redirect_url = 'index_patient.php';
                    }
                    session_write_close();
                }
            }
        }
    }
}

// Destroy ONLY the session that was identified
if ($session_name_to_destroy) {
    session_name($session_name_to_destroy);
    session_start();
    session_unset();
    session_destroy();

    // Delete the specific session cookie
    if (isset($_COOKIE[$session_name_to_destroy])) {
        setcookie($session_name_to_destroy, '', time() - 3600, '/');
    }
}

// Set success message in a temporary session
session_name('TEMP_LOGOUT_MSG');
session_start();
$_SESSION['logout_success'] = true;

// Redirect to appropriate page
header('Location: ' . $redirect_url);
exit;
?>
