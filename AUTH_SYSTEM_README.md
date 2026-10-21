# Centralized Authentication System Documentation

## Overview
All pages in the dermatology clinic system now use a centralized authentication system via `auth_check.php`. This ensures consistent session management and prevents login/logout errors.

## Core File: auth_check.php

### Available Functions

#### 1. `requireStaffLogin($redirect = true)`
Ensures the user is logged in as staff. Use this for all staff-only pages.
```php
require_once 'auth_check.php';
requireStaffLogin();
```

#### 2. `requirePatientLogin($redirect = true)`
Ensures the user is logged in as a patient. Use this for all patient-only pages.
```php
require_once 'auth_check.php';
requirePatientLogin();
```

#### 3. `preventLoggedInAccess()`
Prevents logged-in users from accessing login pages. Use this on login/registration pages.
```php
require_once 'auth_check.php';
preventLoggedInAccess(); // Redirects to appropriate dashboard if already logged in
```

#### 4. `isLoggedIn()`
Check if any user is logged in (returns boolean).
```php
if (isLoggedIn()) {
    // User is logged in
}
```

#### 5. `getUserType()`
Get the current user type ('staff' or 'patient').
```php
$userType = getUserType(); // Returns 'staff', 'patient', or null
```

#### 6. `getUserId()`
Get the current user ID.
```php
$userId = getUserId(); // Returns user_id or null
```

#### 7. `logout($redirect_url = 'index.php')`
Logout the current user and redirect.
```php
logout('index_staff.php'); // Logout and redirect to staff login
```

#### 8. `setLoginSession($user_id, $user_type, $additional_data = [])`
Set login session after successful authentication.
```php
setLoginSession($user_id, 'staff', ['full_name' => $name]);
```

## Session Features

### Automatic Session Timeout
- **Inactivity Timeout**: 2 hours (7200 seconds)
- **Session Lifetime**: 8 hours (28800 seconds)
- Session automatically expires after inactivity period

### Session Security
- Automatic session regeneration on login
- Last activity tracking
- Secure session cookie parameters

## Implementation by Page Type

### Staff-Only Pages
All these pages use `requireStaffLogin()`:
- staff_dashboard.php
- manage_appointments.php
- manage_patients.php
- manage_services.php
- manage_time_slots.php
- appointment_details.php
- complete_appointment.php
- patient_appointment_history.php
- edit_profileStaff.php

**Example:**
```php
<?php
require_once 'auth_check.php';
requireStaffLogin();
require_once 'db_connect.php';

// Your page code here
$staff_id = $_SESSION['user_id'];
?>
```

### Patient-Only Pages
All these pages use `requirePatientLogin()`:
- patient_dashboard.php
- book_appointment.php
- cancel_appointment.php
- get_available_times.php (API)
- get_date_availability.php (API)

**Example:**
```php
<?php
require_once 'auth_check.php';
requirePatientLogin();
require_once 'db_connect.php';

// Your page code here
$patient_id = $_SESSION['user_id'];
?>
```

### Login Pages
All these pages use `preventLoggedInAccess()`:
- index_staff.php
- index_patient.php

**Example:**
```php
<?php
require_once 'auth_check.php';
preventLoggedInAccess(); // Redirects to dashboard if already logged in
require_once 'db_connect.php';

// Login form and authentication logic here
?>
```

### API Endpoints
For API endpoints that return JSON, use the non-redirect version:
```php
<?php
require_once 'auth_check.php';
requirePatientLogin(false); // Don't redirect, just check

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// API logic here
?>
```

## Logout Implementation

Use the centralized logout page:
```html
<a href="logout.php">Logout</a>
```

The logout.php automatically:
1. Detects user type (staff/patient)
2. Destroys session
3. Redirects to appropriate login page

## Session Variables

After successful authentication, these session variables are available:
- `$_SESSION['user_id']` - User ID (staff_id or patient_id)
- `$_SESSION['user_type']` - User type ('staff' or 'patient')
- `$_SESSION['last_activity']` - Timestamp of last activity
- `$_SESSION['login_time']` - Timestamp of login

## Error Handling

### Redirect URL Preservation
When not authenticated, the system saves the requested URL:
```php
$_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
```

After login, you can redirect back:
```php
$redirect = $_SESSION['redirect_url'] ?? 'dashboard.php';
unset($_SESSION['redirect_url']);
header("Location: $redirect");
```

### Error Messages
Error messages are stored in session:
```php
$_SESSION['error_msg'] = "Your session has expired. Please log in again.";
```

Display them in your pages:
```php
<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['error_msg']) ?>
        <?php unset($_SESSION['error_msg']); ?>
    </div>
<?php endif; ?>
```

## Migration Guide

### Old Pattern (Before)
```php
<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Location: index_staff.php');
    exit;
}

$host = 'localhost';
$dbname = 'dermatology_clinic';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch(PDOException $e) {
    die("Connection failed");
}
?>
```

### New Pattern (After)
```php
<?php
require_once 'auth_check.php';
requireStaffLogin();
require_once 'db_connect.php';
?>
```

Much cleaner and more maintainable!

## Benefits

1. **Consistency**: All pages use the same authentication logic
2. **Security**: Centralized security is easier to audit and update
3. **Maintainability**: Changes only need to be made in one place
4. **No Duplicate Code**: Eliminates repeated session_start() and auth checks
5. **Type Safety**: Clear separation between staff and patient areas
6. **Session Timeout**: Automatic timeout prevents unauthorized access
7. **Better UX**: Login pages redirect logged-in users to dashboards

## Troubleshooting

### "Headers already sent" Error
Make sure `require_once 'auth_check.php';` is the FIRST line (after `<?php`) with no output before it.

### Session Not Persisting
Check that session cookies are enabled in browser and `session_start()` isn't called multiple times.

### Redirect Loop
Make sure login pages use `preventLoggedInAccess()` and not authentication checks.

### API Authentication
For AJAX/API calls, use `requireLogin(false)` to prevent redirects and return JSON errors instead.

## Testing Checklist

- [ ] Staff can login and access staff-only pages
- [ ] Patients can login and access patient-only pages
- [ ] Unauthenticated users are redirected to login
- [ ] Session expires after 2 hours of inactivity
- [ ] Logged-in users can't access login pages (redirected to dashboard)
- [ ] Logout works correctly for both user types
- [ ] Cross-type access is prevented (staff can't access patient pages)
- [ ] Session persists across page navigation
- [ ] Error messages display correctly

## Future Enhancements

Consider adding:
- Remember me functionality
- Two-factor authentication
- Password reset via email
- Account lockout after failed attempts
- Session activity logging
- Role-based permissions (admin, doctor, nurse, etc.)
