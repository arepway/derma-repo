-- Create medication_records table to track treatments given to patients
CREATE TABLE IF NOT EXISTS medication_records (
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
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES staff(staff_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for faster queries
CREATE INDEX idx_appointment_id ON medication_records(appointment_id);
CREATE INDEX idx_recorded_at ON medication_records(recorded_at);
