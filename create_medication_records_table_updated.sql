-- Create medication_records table to track treatments given to patients
-- Updated version using medical_staff table name

-- Drop the table if it exists
DROP TABLE IF EXISTS medication_records;

-- Create the table with proper foreign keys
CREATE TABLE medication_records (
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
    INDEX idx_recorded_at (recorded_at),
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES medical_staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Success message
SELECT 'Table medication_records created successfully!' AS message;
