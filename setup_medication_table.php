<?php
/**
 * Setup script for medication_records table
 * This script will check your database structure and create the table with appropriate constraints
 */

require_once 'db_connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Medication Records Table</title>
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #f2f2f7 0%, #e5e5ea 100%);
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #007aff;
            margin-bottom: 20px;
        }
        .step {
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            border-left: 4px solid #007aff;
        }
        .success {
            background: #d1fae5;
            border-left-color: #34c759;
            color: #065f46;
        }
        .error {
            background: #fee2e2;
            border-left-color: #ff3b30;
            color: #991b1b;
        }
        .info {
            background: #e0f2fe;
            border-left-color: #007aff;
            color: #075985;
        }
        .warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
            color: #92400e;
        }
        code {
            background: #f2f2f7;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #007aff 0%, #0051d5 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin-top: 20px;
            font-weight: 600;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🏥 Medication Records Table Setup</h1>";

try {
    // Step 1: Check if medication_records table already exists
    echo "<div class='step info'><strong>Step 1:</strong> Checking if table exists...</div>";

    $check = $pdo->query("SHOW TABLES LIKE 'medication_records'");
    if ($check->rowCount() > 0) {
        echo "<div class='step warning'>⚠ Table <code>medication_records</code> already exists! Skipping creation.</div>";
        echo "<a href='appointment_details.php' class='btn'>Go to Appointment Details</a>";
        echo "</div></body></html>";
        exit;
    }

    echo "<div class='step success'>✓ Table doesn't exist. Proceeding with creation...</div>";

    // Step 2: Check appointment table structure
    echo "<div class='step info'><strong>Step 2:</strong> Checking appointment table...</div>";

    $appointment_exists = false;
    $appointment_column = null;

    $tables = $pdo->query("SHOW TABLES LIKE 'appointment'")->fetchAll();
    if (count($tables) > 0) {
        $cols = $pdo->query("DESCRIBE appointment")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            if ($col['Field'] == 'appointment_id') {
                $appointment_column = 'appointment_id';
                $appointment_exists = true;
                break;
            }
        }
    }

    if ($appointment_exists) {
        echo "<div class='step success'>✓ Found appointment table with <code>$appointment_column</code> column</div>";
    } else {
        echo "<div class='step warning'>⚠ Appointment table not found or doesn't have appointment_id column</div>";
    }

    // Step 3: Check medical_staff table structure
    echo "<div class='step info'><strong>Step 3:</strong> Checking medical_staff table...</div>";

    $staff_exists = false;
    $staff_column = null;

    $tables = $pdo->query("SHOW TABLES LIKE 'medical_staff'")->fetchAll();
    if (count($tables) > 0) {
        $cols = $pdo->query("DESCRIBE medical_staff")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            if ($col['Field'] == 'staff_id') {
                $staff_column = 'staff_id';
                $staff_exists = true;
                break;
            }
        }
    }

    if ($staff_exists) {
        echo "<div class='step success'>✓ Found medical_staff table with <code>$staff_column</code> column</div>";
    } else {
        echo "<div class='step warning'>⚠ medical_staff table not found or doesn't have staff_id column</div>";
    }

    // Step 4: Create the table
    echo "<div class='step info'><strong>Step 4:</strong> Creating medication_records table...</div>";

    $sql = "CREATE TABLE medication_records (
        record_id INT AUTO_INCREMENT PRIMARY KEY,
        appointment_id INT NOT NULL,
        medication_name VARCHAR(255) NOT NULL,
        medication_steps TEXT,
        used_injection TINYINT(1) DEFAULT 0,
        injection_quantity INT DEFAULT 0,
        notes TEXT,
        recorded_by INT,
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_appointment_id (appointment_id),
        INDEX idx_recorded_by (recorded_by),
        INDEX idx_recorded_at (recorded_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "<div class='step success'>✓ Table created successfully!</div>";

    // Step 5: Add foreign keys if possible
    echo "<div class='step info'><strong>Step 5:</strong> Adding foreign key constraints...</div>";

    if ($appointment_exists) {
        try {
            $pdo->exec("ALTER TABLE medication_records
                        ADD CONSTRAINT fk_medication_appointment
                        FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE");
            echo "<div class='step success'>✓ Added foreign key constraint for appointment_id</div>";
        } catch (PDOException $e) {
            echo "<div class='step warning'>⚠ Could not add appointment foreign key: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='step warning'>⚠ Skipped appointment foreign key (table not found)</div>";
    }

    if ($staff_exists) {
        try {
            $pdo->exec("ALTER TABLE medication_records
                        ADD CONSTRAINT fk_medication_staff
                        FOREIGN KEY (recorded_by) REFERENCES medical_staff(staff_id) ON DELETE SET NULL");
            echo "<div class='step success'>✓ Added foreign key constraint for recorded_by (medical_staff)</div>";
        } catch (PDOException $e) {
            echo "<div class='step warning'>⚠ Could not add medical_staff foreign key: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='step warning'>⚠ Skipped medical_staff foreign key (table not found)</div>";
    }

    // Final message
    echo "<div class='step success' style='margin-top: 30px; padding: 20px;'>
            <h2 style='color: #34c759; margin-top: 0;'>🎉 Setup Complete!</h2>
            <p>The <code>medication_records</code> table has been created successfully.</p>
            <p>You can now use the medication tracking feature in the appointment details page.</p>
          </div>";

    echo "<a href='manage_appointments.php' class='btn'>Go to Manage Appointments</a>";
    echo " <a href='appointment_details.php' class='btn' style='background: linear-gradient(135deg, #34c759 0%, #30b350 100%);'>Test Appointment Details</a>";

} catch (PDOException $e) {
    echo "<div class='step error'>
            <strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "
          </div>";
    echo "<div class='step info'>
            <strong>Troubleshooting:</strong>
            <ul>
                <li>Make sure your database connection is correct in <code>db_connect.php</code></li>
                <li>Check that you have CREATE TABLE permissions</li>
                <li>Verify that the appointment and staff tables exist</li>
            </ul>
          </div>";
}

echo "</div></body></html>";
?>
