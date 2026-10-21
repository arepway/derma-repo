<?php
/**
 * Centralized Authentication Helper
 * This file ensures consistent session management across all pages
 */

// Configure session settings BEFORE starting session
if (session_status() === PHP_SESSION_NONE) {
    // Set session lifetime to 8 hours (28800 seconds)
    ini_set('session.gc_maxlifetime', 28800);
    ini_set('session.cookie_lifetime', 28800);

    // Use different session names for staff and patient to allow simultaneous logins
    // This allows accessing both portals in different browser tabs
    $current_file = basename($_SERVER['PHP_SELF']);
    if (strpos($current_file, 'staff') !== false || strpos($current_file, 'manage_') !== false ||
        strpos($current_file, 'complete_') !== false || strpos($current_file, 'edit_profileStaff') !== false ||
        $current_file === 'appointment_details.php' || strpos($current_file, 'delete_patient') !== false ||
        $current_file === 'patient_appointment_history.php') {
        session_name('STAFF_SESSION');
    } elseif (strpos($current_file, 'patient') !== false || strpos($current_file, 'book_') !== false ||
              strpos($current_file, 'cancel_') !== false || strpos($current_file, 'edit_profilePatient') !== false ||
              strpos($current_file, 'get_') !== false || strpos($current_file, 'medical_records') !== false ||
              strpos($current_file, 'appointment_details_patient') !== false) {
        session_name('PATIENT_SESSION');
    } else {
        session_name('DERMA_SESSION');
    }

    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 28800,  // 8 hours
        'path' => '/',
        'domain' => '',
        'secure' => false,    // Set to true if using HTTPS
        'httponly' => true,   // Prevents JavaScript access to session cookie
        'samesite' => 'Lax'   // CSRF protection
    ]);

    // Start the session
    session_start();
}

/**
 * Check if user is logged in as staff
 * @param bool $redirect Whether to redirect to login page if not authenticated
 * @return bool True if authenticated as staff
 */
function requireStaffLogin($redirect = true) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
        if ($redirect) {
            $_SESSION['error_msg'] = "Please log in to access this page.";
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: index_staff.php');
            exit;
        }
        return false;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check if user is logged in as patient
 * @param bool $redirect Whether to redirect to login page if not authenticated
 * @return bool True if authenticated as patient
 */
function requirePatientLogin($redirect = true) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
        if ($redirect) {
            $_SESSION['error_msg'] = "Please log in to access this page.";
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: index_patient.php');
            exit;
        }
        return false;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check if user is logged in (either staff or patient)
 * @param bool $redirect Whether to redirect to home page if not authenticated
 * @return bool True if authenticated
 */
function requireLogin($redirect = true) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        if ($redirect) {
            $_SESSION['error_msg'] = "Please log in to access this page.";
            header('Location: index.php');
            exit;
        }
        return false;
    }

    // Check session timeout (2 hours of inactivity)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
        session_unset();
        session_destroy();
        if ($redirect) {
            session_start();
            $_SESSION['error_msg'] = "Your session has expired. Please log in again.";
            header('Location: index.php');
            exit;
        }
        return false;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check if user is already logged in
 * @return bool True if logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Get current user type
 * @return string|null 'staff', 'patient', or null
 */
function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Get current user ID
 * @return int|null User ID or null
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Logout user and destroy session
 * @param string $redirect_url URL to redirect after logout
 */
function logout($redirect_url = 'index.php') {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['success_msg'] = "You have been logged out successfully.";
    header("Location: $redirect_url");
    exit;
}

/**
 * Prevent logged-in users from accessing login pages
 * Redirects to appropriate dashboard
 */
function preventLoggedInAccess() {
    if (isLoggedIn()) {
        if (getUserType() === 'staff') {
            header('Location: staff_dashboard.php');
            exit;
        } elseif (getUserType() === 'patient') {
            header('Location: patient_dashboard.php');
            exit;
        }
    }
}

/**
 * Set login session
 * @param int $user_id
 * @param string $user_type 'staff' or 'patient'
 * @param array $additional_data Additional session data
 */
function setLoginSession($user_id, $user_type, $additional_data = []) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = $user_type;
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time'] = time();

    // Set additional session data
    foreach ($additional_data as $key => $value) {
        $_SESSION[$key] = $value;
    }

    // Regenerate session ID for security
    session_regenerate_id(true);
}
?>
