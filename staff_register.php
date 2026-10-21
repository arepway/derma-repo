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

// Check if role column exists in medical_staff table
$has_role_column = false;
try {
    $columns_result = $pdo->query("SHOW COLUMNS FROM medical_staff");
    $available_columns = [];
    while ($col = $columns_result->fetch(PDO::FETCH_ASSOC)) {
        $available_columns[] = $col['Field'];
    }
    $has_role_column = in_array('role', $available_columns);
} catch (PDOException $e) {
    // Continue without role column
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['new_full_name'] ?? '';
    $username_new = $_POST['new_username'] ?? '';
    $password_new = password_hash($_POST['new_password'] ?? '', PASSWORD_DEFAULT);
    $email = $_POST['new_email'] ?? '';
    $phone = $_POST['new_phone'] ?? '';
    $role = $_POST['role'] ?? 'Medical Staff';

    try {
        // Build INSERT query based on available columns
        if ($has_role_column) {
            $stmt = $pdo->prepare("INSERT INTO medical_staff (full_name, username, password, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $username_new, $password_new, $email, $phone, $role]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO medical_staff (full_name, username, password, email, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $username_new, $password_new, $email, $phone]);
        }
        $success = "Medical staff account created successfully!";
    } catch(PDOException $e) {
        $error = "Failed to create staff: " . $e->getMessage();
    }
}

// Set page title for header
$page_title = "Add Medical Staff";
require_once 'includes/staff_header.php';
?>

<!-- Success/Error Alerts -->
<?php if (isset($error)): ?>
    <div class="alert-banner">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content">
            <div class="alert-message"><?= htmlspecialchars($error) ?></div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert-banner alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">
            <div class="alert-title">Success!</div>
            <div class="alert-message"><?= htmlspecialchars($success) ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Add Staff Form -->
<div class="module">
    <div class="module-header">
        <h2 class="module-title">New Staff Member</h2>
    </div>
    <div class="module-body">
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="new_full_name" class="form-input" required placeholder="Dr. John Smith">
            </div>

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="new_username" class="form-input" required placeholder="johnsmith">
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="new_password" class="form-input" required placeholder="••••••••">
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="new_email" class="form-input" required placeholder="doctor@clinic.com">
            </div>

            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="new_phone" class="form-input" placeholder="+1234567890">
            </div>

            <?php if ($has_role_column): ?>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="Medical Staff">Medical Staff</option>
                    <option value="Doctor">Doctor</option>
                    <option value="Nurse">Nurse</option>
                    <option value="Administrator">Administrator</option>
                </select>
            </div>
            <?php endif; ?>

            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                <button type="submit" class="btn btn-primary">
                    Create Account
                </button>
                <a href="staff_dashboard.php" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/staff_footer.php'; ?>
