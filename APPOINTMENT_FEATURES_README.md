# Appointment Management Features

## New Features Added

### 1. Date and Time Filtering in manage_appointments.php

**Location:** [manage_appointments.php](manage_appointments.php)

The admin can now filter appointments by:
- **Date**: Select a specific date to view all appointments on that day
- **Time**: Select a specific time slot to view appointments at that time
- Both filters can be used together or independently

**Features:**
- AJAX-based filtering (no page reload required)
- Real-time results display
- Clear filter button to reset and show all appointments
- Responsive design

### 2. Available Time Slots Management

**Location:** [manage_time_slots.php](manage_time_slots.php)

Admins can now:
- **Add available time slots** for specific dates
- **Enable/Disable** time slots without deleting them
- **Delete** time slots completely
- **View booking status** - see how many appointments are booked for each slot
- Manage slots for the next 30 days

**Features:**
- Visual time slot selector with checkboxes
- Color-coded status badges (Available/Unavailable/Booked)
- Protection against deleting slots with existing bookings (confirmation required)
- Batch add multiple time slots for a single date

### 3. Dynamic Time Slot Display for Patients

**Location:** [book_appointment.php](book_appointment.php)

Patients now see only available time slots when booking:
- When a patient selects a date, the system automatically fetches available times
- Only time slots that are:
  - Marked as available by admin
  - Not fully booked
  - Not in the past
- Real-time availability checking

**Features:**
- Dynamic time slot loading
- Loading indicators
- Error messages if no slots available
- Backward compatibility (falls back to default times if no slots configured)
- **Visual indicators for disabled dates:**
  - Disabled dates appear in RED in both card shuffle and scroll modes
  - "Not Available" badge on disabled date cards
  - Red dot indicator in scroll view
  - Click protection prevents selecting unavailable dates
  - Form validation prevents booking disabled dates with clear error message
  - Automatic updates when admin changes availability
- **Enhanced card shuffle interface:**
  - Three-card layout: past date (left, faded), current date (center, vibrant), future date (right, faded)
  - Keyboard navigation: Arrow Left/Right to navigate, Enter/Space to select
  - Touch swipe support for mobile devices
  - Visual focus on current date with stronger colors

### 4. Database Table

**Location:** [create_available_slots_table.sql](create_available_slots_table.sql)

A new database table `available_time_slots` stores:
- Date and time combinations
- Availability status
- Maximum appointments per slot
- Timestamps for tracking changes

**To set up:**
1. Open phpMyAdmin or MySQL client
2. Select the `dermatology_clinic` database
3. Run the SQL file: `create_available_slots_table.sql`

## How It Works

### Admin Workflow:
1. Navigate to **manage_appointments.php**
2. Use filters to view specific appointments by date/time
3. Click "Manage Available Time Slots" button
4. Add time slots for upcoming dates
5. Enable/disable or delete slots as needed

### Patient Workflow:
1. Navigate to **book_appointment.php**
2. Select a service
3. Choose a date from the date picker
4. Time dropdown automatically updates with only available times
5. Select an available time and complete booking

### System Behavior:
- When admin marks a slot as unavailable, it won't appear for patients
- When a slot is fully booked, it won't appear for new bookings
- Past dates and times are automatically filtered out
- If no time slots are configured in the database, the system uses default times (backward compatible)

## Files Modified/Created

### Created:
- `manage_time_slots.php` - Admin interface for managing time slots
- `get_available_times.php` - API endpoint for fetching available times
- `get_date_availability.php` - API endpoint for checking date availability status
- `create_available_slots_table.sql` - Database schema

### Modified:
- `manage_appointments.php` - Added filtering functionality
- `book_appointment.php` - Added dynamic time slot fetching and disabled date visualization

## API Endpoints

### GET /get_available_times.php

**Parameters:**
- `date` (required): Date in Y-m-d format (e.g., 2025-11-15)

**Response:**
```json
{
  "success": true,
  "times": [
    {
      "value": "09:00",
      "label": "09:00 AM"
    },
    {
      "value": "09:30",
      "label": "09:30 AM"
    }
  ]
}
```

**Error Response:**
```json
{
  "error": "Error message here"
}
```

### GET /get_date_availability.php

**Parameters:**
- `start_date` (optional): Starting date in Y-m-d format (default: today)
- `days` (optional): Number of days to check (default: 30)

**Response:**
```json
{
  "success": true,
  "availability": {
    "2025-11-01": {
      "available": true,
      "total_slots": 12,
      "available_slots": 10
    },
    "2025-11-02": {
      "available": false,
      "total_slots": 12,
      "available_slots": 0
    }
  },
  "start_date": "2025-11-01",
  "end_date": "2025-11-30"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Error message here"
}
```

## Security Features

- SQL injection protection using prepared statements
- XSS prevention with HTML escaping
- Date validation
- Past date prevention
- CSRF protection (form-based submissions)

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive design
- Touch-friendly interface
- Fallback for browsers without Fetch API support

## Future Enhancements (Optional)

- Email notifications when time slots are added/removed
- Recurring time slot templates (e.g., set Mon-Fri 9-5 for entire month)
- Time slot capacity management (multiple appointments per slot)
- Holiday management (bulk disable dates)
- Patient waitlist for fully booked slots
