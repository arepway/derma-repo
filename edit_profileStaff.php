<?php
require_once 'auth_check.php';
requireStaffLogin();
require_once 'db_connect.php';

$staff_id = $_SESSION['user_id'];

// Get staff info
$stmt = $pdo->prepare("SELECT * FROM medical_staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') == 'update_profile') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // Verify current password if trying to change password
    if ($new_password && !password_verify($current_password, $staff['password'])) {
        $profile_error = "Current password is incorrect.";
    } else {
        try {
            if ($new_password) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE medical_staff SET full_name = ?, email = ?, phone = ?, password = ? WHERE staff_id = ?");
                $stmt->execute([$full_name, $email, $phone, $hashed_password, $staff_id]);
            } else {
                // Update without password change
                $stmt = $pdo->prepare("UPDATE medical_staff SET full_name = ?, email = ?, phone = ? WHERE staff_id = ?");
                $stmt->execute([$full_name, $email, $phone, $staff_id]);
            }
            $profile_success = "Profile updated successfully!";
            // Refresh staff data
            $stmt = $pdo->prepare("SELECT * FROM medical_staff WHERE staff_id = ?");
            $stmt->execute([$staff_id]);
            $staff = $stmt->fetch();
            $_SESSION['user_name'] = $staff['full_name'];
        } catch(PDOException $e) {
            $profile_error = "Update failed: " . $e->getMessage();
        }
    }
}

$page_title = "Edit Profile";
require_once 'includes/staff_header.php';
?>

<!-- Success/Error Messages -->
<?php if (isset($profile_success)): ?>
    <div class="alert-banner alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">
            <div class="alert-title">Success!</div>
            <div class="alert-message"><?= htmlspecialchars($profile_success) ?></div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($profile_error)): ?>
    <div class="alert-banner">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content">
            <div class="alert-message"><?= htmlspecialchars($profile_error) ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Profile Information -->
<div class="module">
    <div class="module-header">
        <h2 class="module-title">Profile Information</h2>
    </div>
    <div class="module-body">
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-input" value="<?= htmlspecialchars($staff['full_name']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-input" value="<?= htmlspecialchars($staff['username']) ?>" disabled>
                <small style="color: var(--text-muted); font-size: var(--font-size-sm); display: block; margin-top: var(--space-2);">Username cannot be changed</small>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($staff['email']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($staff['phone']) ?>">
            </div>

            <div style="padding: var(--space-6) 0; margin: var(--space-6) 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
                <h3 style="font-size: var(--font-size-base); font-weight: 600; margin-bottom: var(--space-4); color: var(--text-primary);">Change Password (Optional)</h3>
                <p style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: var(--space-5);">Leave blank if you don't want to change your password</p>

                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" autocomplete="current-password">
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" autocomplete="new-password">
                </div>
            </div>

            <div style="display: flex; gap: var(--space-3);">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="staff_dashboard.php" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Account Information -->
<div class="module">
    <div class="module-header">
        <h2 class="module-title">Account Details</h2>
    </div>
    <div class="module-body">
        <div style="display: grid; gap: var(--space-4);">
            <div style="display: flex; justify-content: space-between; padding: var(--space-3) 0; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-tertiary); font-size: var(--font-size-sm); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Staff ID</span>
                <span style="color: var(--text-secondary); font-weight: 500;"><?= htmlspecialchars($staff['staff_id']) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: var(--space-3) 0; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-tertiary); font-size: var(--font-size-sm); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Username</span>
                <span style="color: var(--text-secondary); font-weight: 500;"><?= htmlspecialchars($staff['username']) ?></span>
            </div>
            <?php if (isset($staff['created_at'])): ?>
            <div style="display: flex; justify-content: space-between; padding: var(--space-3) 0;">
                <span style="color: var(--text-tertiary); font-size: var(--font-size-sm); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Member Since</span>
                <span style="color: var(--text-secondary); font-weight: 500;"><?= date('F j, Y', strtotime($staff['created_at'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/staff_footer.php'; ?>
