<?php
require_once 'auth_check.php';
requireStaffLogin();
require_once 'db_connect.php';

// Fetch patients
$stmt = $pdo->query("SELECT patient_id, full_name, username, date_of_birth, sex, phone, email FROM patient ORDER BY full_name ASC");
$patients = $stmt->fetchAll();

// Handle success/error messages
$success_message = '';
$error_message = '';

if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $patient_name = $_GET['name'] ?? 'Patient';
    $success_message = "Patient '" . htmlspecialchars($patient_name) . "' has been successfully deleted.";
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'missing_id':
            $error_message = "Error: Patient ID is missing.";
            break;
        case 'not_found':
            $error_message = "Error: Patient not found.";
            break;
        case 'delete_failed':
            $error_message = "Error: Failed to delete patient. " . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '');
            break;
        default:
            $error_message = "An unknown error occurred.";
    }
}

$page_title = "Patient Management";
$additional_css = ['css/manage_patients_custom.css'];
require_once 'includes/staff_header.php';
?>

<!-- Alert Messages -->
<?php if ($success_message): ?>
    <div class="alert-banner alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">
            <div class="alert-message"><?= $success_message ?></div>
        </div>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert-banner">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content">
            <div class="alert-message"><?= $error_message ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Stats Overview -->
<div class="stats-overview">
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-cell">
            <div class="stat-label">Total</div>
            <div class="stat-value"><?= count($patients) ?></div>
            <div class="stat-meta">Registered Patients</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Shown</div>
            <div class="stat-value" id="visibleCount"><?= count($patients) ?></div>
            <div class="stat-meta">Patients Displayed</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Search</div>
            <div style="margin-top: var(--space-4);">
                <input type="text" id="searchInput" class="form-input" placeholder="Search patients..." style="margin-bottom: 0;">
            </div>
        </div>
    </div>
</div>

<!-- Patients List -->
<div class="module">
    <div class="module-header">
        <h2 class="module-title">Patient List</h2>
        <div class="view-toggle">
            <button class="btn-view active" onclick="setView('grid')" title="Grid View">
                <i class="fas fa-th-large"></i>
            </button>
            <button class="btn-view" onclick="setView('list')" title="List View">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </div>

    <?php if ($patients): ?>
        <div class="patients-grid" id="patientsGrid">
            <?php foreach ($patients as $patient): ?>
                <div class="patient-card" data-search="<?= strtolower(htmlspecialchars($patient['full_name'] . ' ' . $patient['email'] . ' ' . $patient['phone'])) ?>">
                    <div class="patient-header">
                        <div class="patient-avatar">
                            <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                        </div>
                        <div class="patient-name-section">
                            <div class="patient-name"><?= htmlspecialchars($patient['full_name']) ?></div>
                            <div class="patient-username">@<?= htmlspecialchars($patient['username']) ?></div>
                        </div>
                    </div>

                    <div class="patient-info">
                        <div class="info-row">
                            <span class="info-label">DOB</span>
                            <span class="info-value"><?= htmlspecialchars($patient['date_of_birth']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Sex</span>
                            <span class="info-value"><?= htmlspecialchars($patient['sex']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?= htmlspecialchars($patient['phone']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($patient['email']) ?></span>
                        </div>
                    </div>

                    <div class="patient-actions">
                        <a href="patient_appointment_history.php?patient_id=<?= $patient['patient_id'] ?>" class="btn btn-secondary">
                            <i class="fas fa-history"></i> History
                        </a>
                        <form action="delete_patient.php" method="POST" style="margin: 0;">
                            <input type="hidden" name="patient_id" value="<?= $patient['patient_id'] ?>">
                            <button type="submit" class="btn btn-accent" onclick="return confirm('Are you sure you want to delete this patient? This action cannot be undone.')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="empty-message">No patients found in the system</div>
        </div>
    <?php endif; ?>
</div>

<style>
/* View Toggle Buttons */
.view-toggle {
    display: flex;
    gap: var(--space-2);
}

.btn-view {
    background: transparent;
    border: 1px solid var(--border-strong);
    color: var(--text-secondary);
    padding: var(--space-2) var(--space-3);
    cursor: pointer;
    font-size: var(--font-size-sm);
    transition: all 0.15s ease;
}

.btn-view:hover {
    border-color: var(--text-primary);
    color: var(--text-primary);
}

.btn-view.active {
    background: var(--text-primary);
    color: var(--surface);
    border-color: var(--text-primary);
}

/* Patients Grid */
.patients-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--space-6);
    padding: var(--space-6);
}

.patients-grid.list-view {
    grid-template-columns: 1fr;
}

.patient-card {
    background: var(--background);
    border: 1px solid var(--border);
    padding: var(--space-5);
    transition: all 0.15s ease;
}

.patient-card:hover {
    border-color: var(--border-strong);
}

.patient-header {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
    padding-bottom: var(--space-4);
    border-bottom: 1px solid var(--border);
}

.patient-avatar {
    width: 48px;
    height: 48px;
    background: var(--text-primary);
    color: var(--surface);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: var(--font-size-lg);
}

.patient-name-section {
    flex: 1;
}

.patient-name {
    font-size: var(--font-size-base);
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--space-1);
}

.patient-username {
    font-size: var(--font-size-sm);
    color: var(--text-muted);
}

.patient-info {
    margin-bottom: var(--space-5);
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: var(--space-2) 0;
    border-bottom: 1px solid var(--border);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-size: var(--font-size-sm);
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

.info-value {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    font-weight: 500;
}

.patient-actions {
    display: flex;
    gap: var(--space-3);
    padding-top: var(--space-4);
    border-top: 1px solid var(--border);
}

.patient-actions form {
    flex: 1;
}

.patient-actions .btn {
    width: 100%;
    justify-content: center;
}

/* List View Adjustments */
.patients-grid.list-view .patient-card {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-4) var(--space-5);
}

.patients-grid.list-view .patient-header {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.patients-grid.list-view .patient-info {
    display: flex;
    gap: var(--space-6);
    margin-bottom: 0;
}

.patients-grid.list-view .info-row {
    border-bottom: none;
    padding: 0;
}

.patients-grid.list-view .patient-actions {
    border-top: none;
    padding-top: 0;
    flex-direction: row;
    width: auto;
}

.patients-grid.list-view .patient-actions form {
    flex: 0;
}

.patients-grid.list-view .patient-actions .btn {
    width: auto;
}

/* Responsive */
@media (max-width: 768px) {
    .patients-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Search functionality
const searchInput = document.getElementById('searchInput');
const patientsGrid = document.getElementById('patientsGrid');
const visibleCount = document.getElementById('visibleCount');

searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const cards = patientsGrid.querySelectorAll('.patient-card');
    let visible = 0;

    cards.forEach(card => {
        const searchData = card.dataset.search;
        if (searchData.includes(searchTerm)) {
            card.style.display = '';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    visibleCount.textContent = visible;
});

// View toggle
function setView(view) {
    const grid = document.getElementById('patientsGrid');
    const buttons = document.querySelectorAll('.btn-view');

    buttons.forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');

    if (view === 'list') {
        grid.classList.add('list-view');
    } else {
        grid.classList.remove('list-view');
    }
}
</script>

<?php require_once 'includes/staff_footer.php'; ?>
