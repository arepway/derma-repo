-- Create medication_records table to track treatments given to patients
-- This version creates the table without foreign keys first, then adds them if possible

-- Drop the table if it exists
DROP TABLE IF EXISTS medication_records;

-- Create the table without foreign keys first
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
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Try to add foreign key for appointment_id
-- Note: This will only work if the 'appointment' table exists and has 'appointment_id' as primary key
-- If it fails, the table will still work, just without the foreign key constraint

-- Uncomment the lines below ONLY if your appointment table exists and has appointment_id column:
-- ALTER TABLE medication_records
-- ADD CONSTRAINT fk_medication_appointment
-- FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE;

-- Uncomment the lines below ONLY if your staff table exists and has staff_id column:
-- ALTER TABLE medication_records
-- ADD CONSTRAINT fk_medication_staff
-- FOREIGN KEY (recorded_by) REFERENCES staff(staff_id) ON DELETE SET NULL;

-- The table is now created and ready to use!
-- You can manually add foreign keys later if needed.
