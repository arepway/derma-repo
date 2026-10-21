<?php
require_once 'auth_check.php';
requireStaffLogin();
require_once 'db_connect.php';

// Set timezone to prevent date shifting issues
date_default_timezone_set('Asia/Manila');

// --- Detect which datetime columns exist and build a safe datetime expression ---
$cols = [];
$colRes = $pdo->query("SHOW COLUMNS FROM `appointment`");
while ($c = $colRes->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = $c['Field'];
}

if (in_array('appointment_datetime', $cols)) {
    $datetime_expr = 'a.appointment_datetime';
} elseif (in_array('appointment_date', $cols) && in_array('appointment_time', $cols)) {
    $datetime_expr = "CONCAT(a.appointment_date, ' ', a.appointment_time)";
} elseif (in_array('date', $cols) && in_array('time', $cols)) {
    $datetime_expr = "CONCAT(a.date, ' ', a.time)";
} elseif (in_array('appointment_date', $cols)) {
    $datetime_expr = 'a.appointment_date';
} elseif (in_array('date', $cols)) {
    $datetime_expr = 'a.date';
} else {
    $datetime_expr = 'NULL';
}

// --- Handle AJAX requests for filtering ---
if (isset($_GET['ajax']) && $_GET['ajax'] === 'filter') {
    $filter_date = $_GET['filter_date'] ?? '';
    $filter_time = $_GET['filter_time'] ?? '';

    $where_conditions = [];
    $params = [];

    if (!empty($filter_date)) {
        $where_conditions[] = "DATE({$datetime_expr}) = ?";
        $params[] = $filter_date;
    }

    if (!empty($filter_time)) {
        // Detect which time column exists
        if (in_array('appointment_time', $cols)) {
            $time_expr = 'a.appointment_time';
        } elseif (in_array('time', $cols)) {
            $time_expr = 'a.time';
        } else {
            $time_expr = "TIME({$datetime_expr})";
        }

        $where_conditions[] = "{$time_expr} = ?";
        $params[] = $filter_time;
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    $sql = "
        SELECT
          p.patient_id,
          p.full_name,
          p.date_of_birth,
          p.phone,
          p.email,
          a.appointment_id,
          {$datetime_expr} AS appointment_datetime,
          a.status,
          a.notes
        FROM patient p
        INNER JOIN appointment a ON a.patient_id = p.patient_id
        {$where_clause}
        ORDER BY p.full_name ASC, appointment_datetime ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $patients = [];
    foreach ($res as $row) {
        $pid = $row['patient_id'];
        if (!isset($patients[$pid])) {
            $patients[$pid] = [
                'id' => $pid,
                'full_name' => $row['full_name'],
                'date_of_birth' => $row['date_of_birth'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'appointments' => []
            ];
        }
        if (!empty($row['appointment_id'])) {
            $patients[$pid]['appointments'][] = [
                'appointment_id' => $row['appointment_id'],
                'datetime' => $row['appointment_datetime'],
                'status' => $row['status'],
                'notes' => $row['notes']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(array_values($patients));
    exit;
}

// --- Build and run query using the detected datetime expression ---
$sql = "
    SELECT
      p.patient_id,
      p.full_name,
      p.date_of_birth,
      p.phone,
      p.email,
      a.appointment_id,
      {$datetime_expr} AS appointment_datetime,
      a.status,
      a.notes
    FROM patient p
    LEFT JOIN appointment a ON a.patient_id = p.patient_id
    ORDER BY p.full_name ASC, appointment_datetime ASC
";
$res = $pdo->query($sql);

// --- Group by patient ---
$patients = [];
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $pid = $row['patient_id'];
    if (!isset($patients[$pid])) {
        $patients[$pid] = [
            'id' => $pid,
            'full_name' => $row['full_name'],
            'date_of_birth' => $row['date_of_birth'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'appointments' => []
        ];
    }
    if (!empty($row['appointment_id'])) {
        $patients[$pid]['appointments'][] = [
            'appointment_id' => $row['appointment_id'],
            'datetime' => $row['appointment_datetime'],
            'status' => $row['status'],
            'notes' => $row['notes']
        ];
    }
}

// --- Helper functions ---
function categorize($dtStr) {
    if (empty($dtStr)) return 'past';
    $now = new DateTime();
    try {
        $dt = new DateTime($dtStr);
    } catch (Exception $e) {
        return 'past';
    }
    if ($dt < $now) return 'past';
    $today = $now->format('Y-m-d');
    if ($dt->format('Y-m-d') === $today) return 'current';
    return 'future';
}

function format_dt($dtStr) {
    if (empty($dtStr)) return '';
    try {
        $dt = new DateTime($dtStr);
        return $dt->format('M j, Y • h:i A');
    } catch (Exception $e) {
        return htmlspecialchars($dtStr);
    }
}

// Calculate statistics
$current_count = 0;
$future_count = 0;
$past_count = 0;
foreach ($patients as $patient) {
    foreach ($patient['appointments'] as $app) {
        $cat = categorize($app['datetime']);
        if ($cat === 'current') $current_count++;
        elseif ($cat === 'future') $future_count++;
        else $past_count++;
    }
}

$page_title = "Manage Appointments";
require_once 'includes/staff_header.php';
?>

<!-- Statistics Overview -->
<div class="stats-overview">
    <div class="stats-grid">
        <div class="stat-cell">
            <div class="stat-label">Current</div>
            <div class="stat-value" style="color: var(--accent);"><?= $current_count ?></div>
            <div class="stat-meta">Today's Appointments</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Future</div>
            <div class="stat-value"><?= $future_count ?></div>
            <div class="stat-meta">Upcoming Appointments</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Past</div>
            <div class="stat-value" style="color: var(--text-muted);"><?= $past_count ?></div>
            <div class="stat-meta">Completed Appointments</div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="module">
    <div class="module-header">
        <h2 class="module-title">Filter Appointments</h2>
    </div>
    <div class="module-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-5);">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Filter by Date</label>
                <input type="date" id="filter-date" class="form-input">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Filter by Time</label>
                <select id="filter-time" class="form-select">
                    <option value="">All Times</option>
                    <option value="09:00:00">09:00 AM</option>
                    <option value="09:30:00">09:30 AM</option>
                    <option value="10:00:00">10:00 AM</option>
                    <option value="10:30:00">10:30 AM</option>
                    <option value="11:00:00">11:00 AM</option>
                    <option value="11:30:00">11:30 AM</option>
                    <option value="14:00:00">02:00 PM</option>
                    <option value="14:30:00">02:30 PM</option>
                    <option value="15:00:00">03:00 PM</option>
                    <option value="15:30:00">03:30 PM</option>
                    <option value="16:00:00">04:00 PM</option>
                    <option value="16:30:00">04:30 PM</option>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: var(--space-3);">
            <button id="apply-filter" class="btn btn-primary">
                <i class="fas fa-check"></i> Apply Filter
            </button>
            <button id="clear-filter" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>
    </div>
</div>

<!-- Appointments Container -->
<div id="appointments-container">
    <?php if (empty($patients)): ?>
        <div class="module">
            <div class="module-body">
                <div style="text-align: center; padding: var(--space-8);">
                    <div style="font-size: 4rem; color: var(--text-muted); margin-bottom: var(--space-4);">
                        <i class="fas fa-clipboard"></i>
                    </div>
                    <h3 style="font-size: var(--font-size-lg); font-weight: 600; margin-bottom: var(--space-2);">No Patients Found</h3>
                    <p style="color: var(--text-secondary);">There are currently no patients in the system.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($patients as $patient): ?>
            <?php
                $buckets = ['current'=>[], 'future'=>[], 'past'=>[]];
                foreach ($patient['appointments'] as $app) {
                    $cat = categorize($app['datetime']);
                    $buckets[$cat][] = $app;
                }
            ?>
            <div class="module" style="margin-bottom: var(--space-6);">
                <div class="module-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-4);">
                    <div>
                        <h2 class="module-title"><?= htmlspecialchars($patient['full_name']) ?></h2>
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-4); margin-top: var(--space-2); font-size: var(--font-size-sm); color: var(--text-secondary);">
                            <?php if ($patient['date_of_birth']): ?>
                                <span>
                                    <i class="fas fa-birthday-cake" style="color: var(--text-muted);"></i>
                                    <?= htmlspecialchars($patient['date_of_birth']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($patient['phone']): ?>
                                <span>
                                    <i class="fas fa-phone" style="color: var(--text-muted);"></i>
                                    <?= htmlspecialchars($patient['phone']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($patient['email']): ?>
                                <span>
                                    <i class="fas fa-envelope" style="color: var(--text-muted);"></i>
                                    <?= htmlspecialchars($patient['email']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: flex; gap: var(--space-3);">
                        <div style="text-align: center; padding: var(--space-2) var(--space-3); background: var(--background); border: 1px solid var(--border);">
                            <div style="font-size: var(--font-size-xl); font-weight: 700; color: var(--accent);"><?= count($buckets['current']) ?></div>
                            <div style="font-size: var(--font-size-xs); color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em;">Current</div>
                        </div>
                        <div style="text-align: center; padding: var(--space-2) var(--space-3); background: var(--background); border: 1px solid var(--border);">
                            <div style="font-size: var(--font-size-xl); font-weight: 700; color: var(--text-primary);"><?= count($buckets['future']) ?></div>
                            <div style="font-size: var(--font-size-xs); color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em;">Future</div>
                        </div>
                        <div style="text-align: center; padding: var(--space-2) var(--space-3); background: var(--background); border: 1px solid var(--border);">
                            <div style="font-size: var(--font-size-xl); font-weight: 700; color: var(--text-muted);"><?= count($buckets['past']) ?></div>
                            <div style="font-size: var(--font-size-xs); color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em;">Past</div>
                        </div>
                    </div>
                </div>

                <div class="module-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-5);">
                        <!-- Current Appointments -->
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3); padding-bottom: var(--space-3); border-bottom: 2px solid var(--accent);">
                                <h3 style="font-size: var(--font-size-base); font-weight: 600; color: var(--text-primary);">Current</h3>
                                <span style="background: var(--accent); color: var(--surface); padding: var(--space-1) var(--space-3); font-size: var(--font-size-sm); font-weight: 700;"><?= count($buckets['current']) ?></span>
                            </div>
                            <?php if (empty($buckets['current'])): ?>
                                <div style="text-align: center; padding: var(--space-5); color: var(--text-muted); font-size: var(--font-size-sm);">No current appointments</div>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                                    <?php foreach ($buckets['current'] as $a): ?>
                                        <div style="background: var(--background); border-left: 3px solid var(--accent); padding: var(--space-4);">
                                            <div style="font-size: var(--font-size-base); font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-2);">
                                                <?= format_dt($a['datetime']) ?>
                                            </div>
                                            <?php if ($a['status']): ?>
                                                <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: var(--space-2);">
                                                    Status: <?= htmlspecialchars($a['status']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($a['notes']): ?>
                                                <div style="font-size: var(--font-size-sm); color: var(--text-secondary); padding: var(--space-2); background: var(--surface); border: 1px solid var(--border); margin-bottom: var(--space-3);">
                                                    <i class="fas fa-sticky-note"></i> <?= htmlspecialchars($a['notes']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <a href="appointment_details.php?id=<?= $a['appointment_id'] ?>" class="module-action">
                                                View Details →
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Future Appointments -->
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3); padding-bottom: var(--space-3); border-bottom: 2px solid var(--text-primary);">
                                <h3 style="font-size: var(--font-size-base); font-weight: 600; color: var(--text-primary);">Future</h3>
                                <span style="background: var(--text-primary); color: var(--surface); padding: var(--space-1) var(--space-3); font-size: var(--font-size-sm); font-weight: 700;"><?= count($buckets['future']) ?></span>
                            </div>
                            <?php if (empty($buckets['future'])): ?>
                                <div style="text-align: center; padding: var(--space-5); color: var(--text-muted); font-size: var(--font-size-sm);">No future appointments</div>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                                    <?php foreach ($buckets['future'] as $a): ?>
                                        <div style="background: var(--background); border-left: 3px solid var(--text-primary); padding: var(--space-4);">
                                            <div style="font-size: var(--font-size-base); font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-2);">
                                                <?= format_dt($a['datetime']) ?>
                                            </div>
                                            <?php if ($a['status']): ?>
                                                <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: var(--space-2);">
                                                    Status: <?= htmlspecialchars($a['status']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($a['notes']): ?>
                                                <div style="font-size: var(--font-size-sm); color: var(--text-secondary); padding: var(--space-2); background: var(--surface); border: 1px solid var(--border); margin-bottom: var(--space-3);">
                                                    <i class="fas fa-sticky-note"></i> <?= htmlspecialchars($a['notes']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <a href="appointment_details.php?id=<?= $a['appointment_id'] ?>" class="module-action">
                                                View Details →
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Past Appointments -->
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3); padding-bottom: var(--space-3); border-bottom: 2px solid var(--border-strong);">
                                <h3 style="font-size: var(--font-size-base); font-weight: 600; color: var(--text-primary);">Past</h3>
                                <span style="background: var(--text-muted); color: var(--surface); padding: var(--space-1) var(--space-3); font-size: var(--font-size-sm); font-weight: 700;"><?= count($buckets['past']) ?></span>
                            </div>
                            <?php if (empty($buckets['past'])): ?>
                                <div style="text-align: center; padding: var(--space-5); color: var(--text-muted); font-size: var(--font-size-sm);">No past appointments</div>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                                    <?php foreach ($buckets['past'] as $a): ?>
                                        <div style="background: var(--background); border-left: 3px solid var(--border-strong); padding: var(--space-4);">
                                            <div style="font-size: var(--font-size-base); font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-2);">
                                                <?= format_dt($a['datetime']) ?>
                                            </div>
                                            <?php if ($a['status']): ?>
                                                <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: var(--space-2);">
                                                    Status: <?= htmlspecialchars($a['status']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($a['notes']): ?>
                                                <div style="font-size: var(--font-size-sm); color: var(--text-secondary); padding: var(--space-2); background: var(--surface); border: 1px solid var(--border); margin-bottom: var(--space-3);">
                                                    <i class="fas fa-sticky-note"></i> <?= htmlspecialchars($a['notes']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <a href="appointment_details.php?id=<?= $a['appointment_id'] ?>" class="module-action">
                                                View Details →
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// AJAX Filtering functionality
const applyFilterBtn = document.getElementById('apply-filter');
const clearFilterBtn = document.getElementById('clear-filter');
const filterDate = document.getElementById('filter-date');
const filterTime = document.getElementById('filter-time');

applyFilterBtn.addEventListener('click', function() {
    const filterDateValue = filterDate.value;
    const filterTimeValue = filterTime.value;

    // Show loading state
    const originalText = applyFilterBtn.innerHTML;
    applyFilterBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';
    applyFilterBtn.disabled = true;

    // Build query string
    let queryParams = 'ajax=filter';
    if (filterDateValue) queryParams += '&filter_date=' + encodeURIComponent(filterDateValue);
    if (filterTimeValue) queryParams += '&filter_time=' + encodeURIComponent(filterTimeValue);

    // Make AJAX request
    fetch('?' + queryParams)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
                return;
            }
            renderAppointments(data);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to filter appointments. Please try again.');
        })
        .finally(() => {
            applyFilterBtn.innerHTML = originalText;
            applyFilterBtn.disabled = false;
        });
});

clearFilterBtn.addEventListener('click', function() {
    filterDate.value = '';
    filterTime.value = '';
    location.reload();
});

function categorize(dtStr) {
    if (!dtStr) return 'past';
    const now = new Date();
    const dt = new Date(dtStr);
    if (isNaN(dt.getTime())) return 'past';
    if (dt < now) return 'past';
    const today = now.toISOString().split('T')[0];
    const dtDate = dt.toISOString().split('T')[0];
    if (dtDate === today) return 'current';
    return 'future';
}

function formatDt(dtStr) {
    if (!dtStr) return '';
    const dt = new Date(dtStr);
    if (isNaN(dt.getTime())) return dtStr;

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[dt.getMonth()];
    const day = dt.getDate();
    const year = dt.getFullYear();
    let hours = dt.getHours();
    const minutes = String(dt.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;

    return `${month} ${day}, ${year} • ${hours}:${minutes} ${ampm}`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function renderAppointments(patients) {
    const container = document.getElementById('appointments-container');

    if (patients.length === 0) {
        container.innerHTML = `
            <div class="module">
                <div class="module-body">
                    <div style="text-align: center; padding: var(--space-8);">
                        <div style="font-size: 4rem; color: var(--text-muted); margin-bottom: var(--space-4);">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 style="font-size: var(--font-size-lg); font-weight: 600; margin-bottom: var(--space-2);">No Results Found</h3>
                        <p style="color: var(--text-secondary);">No appointments match your filter criteria. Try adjusting your filters.</p>
                    </div>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    patients.forEach(patient => {
        const buckets = { current: [], future: [], past: [] };
        patient.appointments.forEach(app => {
            const cat = categorize(app.datetime);
            buckets[cat].push(app);
        });

        html += `
            <div class="module" style="margin-bottom: var(--space-6);">
                <div class="module-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-4);">
                    <div>
                        <h2 class="module-title">${escapeHtml(patient.full_name)}</h2>
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-4); margin-top: var(--space-2); font-size: var(--font-size-sm); color: var(--text-secondary);">
                            ${patient.date_of_birth ? '<span><i class="fas fa-birthday-cake" style="color: var(--text-muted);"></i> ' + escapeHtml(patient.date_of_birth) + '</span>' : ''}
                            ${patient.phone ? '<span><i class="fas fa-phone" style="color: var(--text-muted);"></i> ' + escapeHtml(patient.phone) + '</span>' : ''}
                            ${patient.email ? '<span><i class="fas fa-envelope" style="color: var(--text-muted);"></i> ' + escapeHtml(patient.email) + '</span>' : ''}
                        </div>
                    </div>
                    <div style="display: flex; gap: var(--space-3);">
                        <div style="text-align: center; padding: var(--space-2) var(--space-3); background: var(--background); border: 1px solid var(--border);">
                            <div style="font-size: var(--font-size-xl); font-weight: 700; color: var(--accent);">${buckets.current.length}</div>
                            <div style="font-size: var(--font-size-xs); color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em;">Current</div>
                        </div>
                        <div style="text-align: center; padding: var(--space-2) var(--space-3); background: var(--background); border: 1px solid var(--border);">
                            <div style="font-size: var(--font-size-xl); font-weight: 700; color: var(--text-primary);">${buckets.future.length}</div>
                            <div style="font-size: var(--font-size-xs); color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em;">Future</div>
                        </div>
                        <div style="text-align: center; padding: var(--space-2) var(--space-3); background: var(--background); border: 1px solid var(--border);">
                            <div style="font-size: var(--font-size-xl); font-weight: 700; color: var(--text-muted);">${buckets.past.length}</div>
                            <div style="font-size: var(--font-size-xs); color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em;">Past</div>
                        </div>
                    </div>
                </div>

                <div class="module-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-5);">
        `;

        ['current', 'future', 'past'].forEach(type => {
            const borderColor = type === 'current' ? 'var(--accent)' : type === 'future' ? 'var(--text-primary)' : 'var(--border-strong)';
            const bgColor = type === 'current' ? 'var(--accent)' : type === 'future' ? 'var(--text-primary)' : 'var(--text-muted)';

            html += `
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3); padding-bottom: var(--space-3); border-bottom: 2px solid ${borderColor};">
                        <h3 style="font-size: var(--font-size-base); font-weight: 600; color: var(--text-primary);">${type.charAt(0).toUpperCase() + type.slice(1)}</h3>
                        <span style="background: ${bgColor}; color: var(--surface); padding: var(--space-1) var(--space-3); font-size: var(--font-size-sm); font-weight: 700;">${buckets[type].length}</span>
                    </div>
            `;

            if (buckets[type].length === 0) {
                html += `<div style="text-align: center; padding: var(--space-5); color: var(--text-muted); font-size: var(--font-size-sm);">No ${type} appointments</div>`;
            } else {
                html += '<div style="display: flex; flex-direction: column; gap: var(--space-3);">';
                buckets[type].forEach(a => {
                    const textColor = type === 'past' ? 'var(--text-secondary)' : 'var(--text-primary)';
                    html += `
                        <div style="background: var(--background); border-left: 3px solid ${borderColor}; padding: var(--space-4);">
                            <div style="font-size: var(--font-size-base); font-weight: 600; color: ${textColor}; margin-bottom: var(--space-2);">
                                ${formatDt(a.datetime)}
                            </div>
                            ${a.status ? '<div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: var(--space-2);">Status: ' + escapeHtml(a.status) + '</div>' : ''}
                            ${a.notes ? '<div style="font-size: var(--font-size-sm); color: var(--text-secondary); padding: var(--space-2); background: var(--surface); border: 1px solid var(--border); margin-bottom: var(--space-3);"><i class="fas fa-sticky-note"></i> ' + escapeHtml(a.notes) + '</div>' : ''}
                            <a href="appointment_details.php?id=${a.appointment_id}" class="module-action">
                                View Details →
                            </a>
                        </div>
                    `;
                });
                html += '</div>';
            }

            html += '</div>';
        });

        html += `
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}
</script>

<?php require_once 'includes/staff_footer.php'; ?>
