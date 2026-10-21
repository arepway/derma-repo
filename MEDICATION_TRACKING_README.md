# Medication Tracking System

## Overview

A comprehensive medication and treatment tracking system that allows administrators to record detailed medication information for each patient appointment, including medication steps, injection tracking, and automatic inventory updates.

## Features Added

### 1. Appointment Details Page
**Location:** [appointment_details.php](appointment_details.php)

A detailed view for each appointment that displays:
- **Patient Information**: Full name, email, phone, date of birth, age, gender
- **Appointment Information**: Date & time, status, notes
- **Service/Treatment Information**: Service name, description, price, duration
- **Medication Records**: Complete history of medications given for this appointment

### 2. Medication Record Management

Administrators can record:
- **Medication/Treatment Name** (required)
- **Medication Steps/Instructions**: Step-by-step instructions for the patient
- **Additional Notes**: Any extra observations or information
- **Injection Usage**: Checkbox to indicate if an injection was used
- **Injection Quantity**: Number of injections used (updates inventory)

### 3. Automatic Inventory Integration

When an injection is marked as used:
- The system automatically decreases the inventory stock
- Quantity is deducted from the inventory table
- Only updates if sufficient stock is available
- Prevents negative inventory

### 4. Medication Records History

For each appointment, view:
- All medication records added
- Who recorded each medication (staff member name)
- When it was recorded (timestamp)
- Full medication details including steps and notes
- Injection badge if injections were used
- Delete functionality for each record

## Database Schema

### medication_records Table

Created by running: [create_medication_records_table.sql](create_medication_records_table.sql)

```sql
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
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES medical_staff(staff_id) ON DELETE SET NULL
)
```

**Fields:**
- `record_id`: Unique identifier for each medication record
- `appointment_id`: Links to the appointment
- `medication_name`: Name of medication or treatment
- `medication_steps`: Step-by-step instructions (optional)
- `used_injection`: Boolean flag (0 or 1)
- `injection_quantity`: Number of injections used
- `notes`: Additional notes or observations
- `recorded_by`: Staff member who added the record
- `recorded_at`: When the record was created
- `updated_at`: Last update timestamp

## Setup Instructions

### 1. Create the Database Table

Run the SQL file in phpMyAdmin or MySQL client:

```bash
# Navigate to phpMyAdmin
# Select the dermatology_clinic database
# Import or run: create_medication_records_table.sql
```

Or execute directly:
```sql
SOURCE c:/xampp/htdocs/dermatology_clinic/create_medication_records_table.sql;
```

### 2. Access the Feature

1. **From Appointments List**:
   - Go to [manage_appointments.php](manage_appointments.php)
   - Click "View Details & Add Medication" button on any appointment
   - This opens the appointment details page

2. **Direct Access**:
   - Navigate to: `appointment_details.php?id={appointment_id}`

## User Workflow

### Admin Workflow:

1. **View Appointment**:
   - Navigate to Manage Appointments
   - Find the patient's appointment
   - Click "View Details & Add Medication"

2. **Add Medication Record**:
   - Fill in medication/treatment name (required)
   - Add step-by-step instructions for the patient
   - Add any additional notes
   - Check "This treatment used an injection" if applicable
   - Enter injection quantity if applicable
   - Click "Save Medication Record"

3. **Review History**:
   - View all medication records for this appointment
   - See who added each record and when
   - Delete records if needed

4. **Inventory Update**:
   - If injection is marked, inventory automatically updates
   - Stock is decreased by the specified quantity
   - View changes in inventory management page

## Features Breakdown

### Visual Design

**Modern UI Elements:**
- Inter font family for clean typography
- Card-based layout with gradients
- Color-coded status badges
- Smooth animations and transitions
- Responsive design for mobile/tablet
- Icon-based visual hierarchy

**Color Coding:**
- Scheduled: Blue gradient
- Completed: Green gradient
- Cancelled: Red gradient
- Injections: Orange/amber highlight

### Form Features

**Interactive Elements:**
- Real-time toggle for injection section
- Auto-hiding alert messages (5 seconds)
- Loading states and transitions
- Confirmation dialogs for deletions
- Form validation

**Accessibility:**
- Clear labels with icons
- Required field indicators
- Descriptive placeholders
- Error messages
- Keyboard-friendly

### Security Features

- Session-based authentication (staff only)
- PDO prepared statements (SQL injection prevention)
- HTML escaping (XSS prevention)
- Input validation
- Foreign key constraints
- Deletion confirmations

## Integration Points

### With Existing Systems:

1. **Appointment System**:
   - Links from manage_appointments.php
   - Fetches appointment details via appointment_id
   - Displays service and patient information

2. **Inventory System** (if exists):
   - Automatically updates when injections are used
   - Checks table existence before updating
   - Uses partial match for medication names
   - Prevents negative stock

3. **Medical Staff System**:
   - Tracks who recorded each medication
   - Displays medical staff member's full name
   - Links via staff_id foreign key to medical_staff table

## API / Data Flow

### Viewing Appointment Details:
```
GET appointment_details.php?id=123
↓
Fetch appointment details (JOIN patient, service)
↓
Fetch medication records for appointment
↓
Display in beautiful UI
```

### Adding Medication Record:
```
POST to appointment_details.php
↓
Validate medication_name (required)
↓
INSERT into medication_records table
↓
IF used_injection = true
  ↓
  UPDATE inventory (decrease stock)
↓
Redirect with success message
```

### Deleting Record:
```
POST delete_record with record_id
↓
DELETE from medication_records
↓
Redirect with success message
```

## File Structure

### Created Files:
- `appointment_details.php` - Main appointment details and medication tracking page
- `create_medication_records_table.sql` - Database schema for medication records
- `MEDICATION_TRACKING_README.md` - This documentation file

### Modified Files:
- `manage_appointments.php` - Added "View Details & Add Medication" buttons

## Example Usage

### Example 1: Recording Hydrocortisone Treatment

```
Medication Name: Hydrocortisone Cream 1%
Steps:
1. Wash and dry the affected area
2. Apply a thin layer of cream to the rash
3. Gently rub until absorbed
4. Use twice daily (morning and evening)
5. Avoid sun exposure on treated areas

Notes: Patient has mild eczema on forearms. Follow up in 2 weeks.
Injection Used: No
```

### Example 2: Recording Botox Injection

```
Medication Name: Botox (Botulinum Toxin Type A)
Steps:
1. Avoid rubbing treated area for 24 hours
2. Stay upright for 4 hours post-treatment
3. Avoid strenuous exercise for 24 hours
4. Results appear in 3-7 days
5. Return for follow-up in 2 weeks

Notes: Administered 20 units for forehead lines. Patient tolerated well.
Injection Used: Yes
Injection Quantity: 1
```

## Troubleshooting

### Issue: Inventory not updating
**Solution**: Check if the `inventory` table exists in your database. The system only updates inventory if the table is present.

### Issue: Can't access appointment details
**Solution**: Ensure you're logged in as staff. Only staff members can access this feature.

### Issue: Medication record not saving
**Solution**: Ensure medication name is filled in (required field). Check database connection.

### Issue: Can't delete medication record
**Solution**: Confirm deletion in the popup dialog. Check that you have proper permissions.

## Future Enhancements (Optional)

- Print medication instructions for patients
- Email medication steps to patients
- Track medication allergies and contraindications
- Medication templates for common treatments
- Batch medication entry for multiple appointments
- Export medication history to PDF
- Integration with prescription system
- Medication cost tracking
- Insurance claim integration

## Browser Compatibility

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Dependencies

- PHP 7.0+
- MySQL 5.7+ / MariaDB 10.2+
- PDO extension
- Session support
- Modern browser with JavaScript enabled

## Support

For issues or questions:
1. Check this documentation
2. Review the code comments in appointment_details.php
3. Check database table structure
4. Verify staff login session
5. Check browser console for JavaScript errors
