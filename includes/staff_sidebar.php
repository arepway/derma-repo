<?php
// Determine the active page
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread messages count if not already set
if (!isset($unread_messages)) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM messages WHERE is_read = 0 AND sender_type = 'patient'");
        $stmt->execute();
        $unread_messages = $stmt->fetch()['total'];
    } catch (PDOException $e) {
        $unread_messages = 0;
    }
}

// Get staff info if not already set
if (!isset($staff)) {
    try {
        $staff_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT * FROM medical_staff WHERE staff_id = ?");
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch();
    } catch (PDOException $e) {
        $staff = ['full_name' => 'User'];
    }
}
?>

<!-- Left Sidebar Navigation -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h1 class="brand-name">True Beauty Concept</h1>
        <div class="brand-role">Medical Staff</div>
    </div>

    <!-- Main Navigation -->
    <nav class="sidebar-section">
        <div class="sidebar-section-title">Navigation</div>
        <ul class="sidebar-nav">
            <a href="staff_dashboard.php" class="sidebar-nav-item <?= $current_page === 'staff_dashboard.php' ? 'active' : '' ?>">
                <span>Dashboard</span>
            </a>
            <a href="manage_appointments.php" class="sidebar-nav-item <?= $current_page === 'manage_appointments.php' || $current_page === 'appointment_details.php' ? 'active' : '' ?>">
                <span>Appointments</span>
            </a>
            <a href="manage_patients.php" class="sidebar-nav-item <?= $current_page === 'manage_patients.php' ? 'active' : '' ?>">
                <span>Patients</span>
            </a>
            <a href="staff_messages.php" class="sidebar-nav-item <?= $current_page === 'staff_messages.php' ? 'active' : '' ?>">
                <span>Messages</span>
                <?php if ($unread_messages > 0): ?>
                    <span class="nav-badge"><?= $unread_messages ?></span>
                <?php endif; ?>
            </a>
        </ul>
    </nav>

    <!-- Management Section -->
    <nav class="sidebar-section">
        <div class="sidebar-section-title">Management</div>
        <ul class="sidebar-nav">
            <a href="manage_services.php" class="sidebar-nav-item <?= $current_page === 'manage_services.php' ? 'active' : '' ?>">
                <span>Services</span>
            </a>
            <a href="manage_feedback.php" class="sidebar-nav-item <?= $current_page === 'manage_feedback.php' ? 'active' : '' ?>">
                <span>Feedback</span>
            </a>
            <a href="manage_stock.php" class="sidebar-nav-item <?= $current_page === 'manage_stock.php' ? 'active' : '' ?>">
                <span>Stock</span>
            </a>
        </ul>
    </nav>

    <!-- Account Section -->
    <nav class="sidebar-section">
        <div class="sidebar-section-title">Account</div>
        <ul class="sidebar-nav">
            <a href="staff_register.php" class="sidebar-nav-item <?= $current_page === 'staff_register.php' ? 'active' : '' ?>">
                <span>Add Staff Member</span>
            </a>
            <a href="edit_profileStaff.php" class="sidebar-nav-item <?= $current_page === 'edit_profileStaff.php' ? 'active' : '' ?>">
                <span>Edit Profile</span>
            </a>
            <a href="#" class="sidebar-nav-item" onclick="openDeleteModal(); return false;">
                <span>Delete Account</span>
            </a>
            <a href="?logout=1" class="sidebar-nav-item">
                <span>Logout</span>
            </a>
        </ul>
    </nav>

    <!-- User Info Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($staff['full_name'], 0, 1)) ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($staff['full_name']) ?></div>
                <div class="user-title">Medical Staff</div>
            </div>
        </div>
    </div>
</aside>
