-- Create messages table for patient-staff communication
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `sender_type` enum('patient','staff') NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`message_id`),
  KEY `patient_id` (`patient_id`),
  KEY `staff_id` (`staff_id`),
  KEY `timestamp` (`timestamp`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `medical_staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
