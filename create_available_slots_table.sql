-- Create table for managing available appointment time slots
CREATE TABLE IF NOT EXISTS `available_time_slots` (
  `slot_id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `max_appointments` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`slot_id`),
  UNIQUE KEY `unique_date_time` (`date`, `time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default time slots for the next 30 days
-- This will populate the table with standard clinic hours (9 AM - 5 PM, excluding lunch 12-2 PM)
-- You can run this once or modify as needed

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS populate_time_slots()
BEGIN
    DECLARE date_counter INT DEFAULT 0;
    DECLARE slot_date DATE;

    WHILE date_counter < 30 DO
        SET slot_date = DATE_ADD(CURDATE(), INTERVAL date_counter DAY);

        -- Skip Sundays (day 1) - adjust as needed for your clinic schedule
        IF DAYOFWEEK(slot_date) != 1 THEN
            -- Morning slots
            INSERT IGNORE INTO available_time_slots (date, time, is_available) VALUES
                (slot_date, '09:00:00', 1),
                (slot_date, '09:30:00', 1),
                (slot_date, '10:00:00', 1),
                (slot_date, '10:30:00', 1),
                (slot_date, '11:00:00', 1),
                (slot_date, '11:30:00', 1),
                -- Afternoon slots (after lunch)
                (slot_date, '14:00:00', 1),
                (slot_date, '14:30:00', 1),
                (slot_date, '15:00:00', 1),
                (slot_date, '15:30:00', 1),
                (slot_date, '16:00:00', 1),
                (slot_date, '16:30:00', 1);
        END IF;

        SET date_counter = date_counter + 1;
    END WHILE;
END$$

DELIMITER ;

-- Run the procedure to populate initial slots
CALL populate_time_slots();

-- Drop the procedure after use (optional)
DROP PROCEDURE IF EXISTS populate_time_slots;