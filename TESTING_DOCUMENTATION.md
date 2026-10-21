# Testing Documentation
## Dermatology Clinic Management System

**Version:** 1.0
**Last Updated:** 2025-01-12
**Project:** True Beauty Concept - Dermatology Clinic System

---

## Table of Contents
1. [Testing Overview](#testing-overview)
2. [Testing Stages](#testing-stages)
3. [Test Environment](#test-environment)
4. [Test Cases by Module](#test-cases-by-module)
5. [Testing Checklist](#testing-checklist)
6. [Bug Tracking](#bug-tracking)
7. [Test Results Template](#test-results-template)

---

## Testing Overview

### Purpose
This document outlines the comprehensive testing strategy for the Dermatology Clinic Management System to ensure functionality, reliability, security, and user experience meet project requirements.

### Scope
- **In Scope:** All system modules, user roles, database operations, security features
- **Out of Scope:** Third-party services (Google Maps, Font Awesome CDN)

### Testing Objectives
- Verify all functional requirements are met
- Ensure data integrity and security
- Validate user interface and user experience
- Confirm system performance and reliability
- Test cross-browser compatibility

---

## Testing Stages

### Stage 1: Unit Testing
**Timeline:** During Development
**Responsibility:** Developer

#### What to Test:
- Individual PHP functions
- Database query execution
- Form validation logic
- Session management functions
- Password hashing and verification

#### Tools:
- Manual PHP testing
- Browser developer console
- MySQL Workbench/phpMyAdmin

---

### Stage 2: Integration Testing
**Timeline:** After Unit Testing
**Responsibility:** Developer/QA Team

#### What to Test:
- Database connections
- Session persistence across pages
- User authentication flow
- Data flow between modules
- Email notifications (if implemented)

#### Key Integration Points:
| Module A | Module B | Integration Point |
|----------|----------|-------------------|
| Login | Dashboard | Session data transfer |
| Appointment Booking | Time Slots | Available slot validation |
| Appointment Completion | Stock Management | Injection stock deduction |
| Message System | User Profiles | Sender/receiver identification |
| Account Deletion | Related Records | Cascade deletion |

---

### Stage 3: Functional Testing
**Timeline:** After Integration Testing
**Responsibility:** QA Team

#### What to Test:
All system features according to requirements specification.

---

### Stage 4: User Acceptance Testing (UAT)
**Timeline:** Before Deployment
**Responsibility:** End Users (Staff/Patients)

#### What to Test:
- Real-world scenarios
- User workflows
- Interface usability
- Business process validation

---

### Stage 5: Security Testing
**Timeline:** Before Deployment
**Responsibility:** Security Specialist/Developer

#### What to Test:
- SQL Injection vulnerability
- XSS (Cross-Site Scripting)
- CSRF (Cross-Site Request Forgery)
- Session hijacking
- Password security
- Authentication bypass attempts
- Authorization checks

---

### Stage 6: Performance Testing
**Timeline:** Before Deployment
**Responsibility:** QA Team/Developer

#### What to Test:
- Page load times
- Database query performance
- Concurrent user handling
- Large data set handling
- Memory usage

---

### Stage 7: Compatibility Testing
**Timeline:** Before Deployment
**Responsibility:** QA Team

#### What to Test:
**Browsers:**
- Google Chrome (Latest)
- Mozilla Firefox (Latest)
- Microsoft Edge (Latest)
- Safari (Latest)

**Devices:**
- Desktop (1920x1080, 1366x768)
- Tablet (iPad, Android tablets)
- Mobile (Various screen sizes)

---

## Test Environment

### Development Environment
- **OS:** Windows 10/11
- **Server:** XAMPP (Apache 2.4+)
- **PHP:** 7.4+
- **MySQL:** 5.7+ (Port 3307)
- **Browser:** Chrome (Latest)

### Production Environment
- **OS:** Linux/Windows Server
- **Web Server:** Apache/Nginx
- **PHP:** 7.4+
- **MySQL:** 5.7+
- **SSL:** Required for HTTPS

---

## Test Cases by Module

### 1. Authentication & Authorization

#### Test Case 1.1: Patient Login
| Test ID | TC-AUTH-001 |
|---------|-------------|
| **Module** | Patient Login |
| **Test Case** | Valid patient login |
| **Precondition** | Patient account exists with username "testpatient" |
| **Test Steps** | 1. Navigate to index_patient.php<br>2. Enter username: "testpatient"<br>3. Enter valid password<br>4. Click Login |
| **Expected Result** | User redirected to patient_dashboard.php<br>Session created successfully |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 1.2: Staff Login
| Test ID | TC-AUTH-002 |
|---------|-------------|
| **Module** | Staff Login |
| **Test Case** | Valid staff login |
| **Precondition** | Staff account exists OR admin credentials (admin/admin123) |
| **Test Steps** | 1. Navigate to index_staff.php<br>2. Enter username: "admin"<br>3. Enter password: "admin123"<br>4. Click Login |
| **Expected Result** | User redirected to staff_dashboard.php<br>Session created successfully |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 1.3: Invalid Login
| Test ID | TC-AUTH-003 |
|---------|-------------|
| **Module** | Authentication |
| **Test Case** | Invalid credentials |
| **Test Steps** | 1. Navigate to login page<br>2. Enter invalid username/password<br>3. Click Login |
| **Expected Result** | Error message displayed<br>User remains on login page<br>No session created |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 1.4: Session Persistence
| Test ID | TC-AUTH-004 |
|---------|-------------|
| **Module** | Session Management |
| **Test Case** | Session persists across pages |
| **Test Steps** | 1. Login as patient<br>2. Navigate to Messages<br>3. Navigate back to Dashboard<br>4. Check session validity |
| **Expected Result** | User remains logged in<br>Session data intact |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 1.5: Concurrent Sessions
| Test ID | TC-AUTH-005 |
|---------|-------------|
| **Module** | Session Management |
| **Test Case** | Patient and staff concurrent login |
| **Test Steps** | 1. Open browser tab 1, login as patient<br>2. Open browser tab 2, login as staff<br>3. Verify both sessions work independently |
| **Expected Result** | Both users logged in simultaneously<br>No session conflicts |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 1.6: Logout Redirect
| Test ID | TC-AUTH-006 |
|---------|-------------|
| **Module** | Logout |
| **Test Case** | Patient logout redirects to patient index |
| **Test Steps** | 1. Login as patient<br>2. Click Logout<br>3. Verify redirect URL |
| **Expected Result** | Redirected to index_patient.php<br>Session destroyed |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 1.7: Authorization Check
| Test ID | TC-AUTH-007 |
|---------|-------------|
| **Module** | Authorization |
| **Test Case** | Patient cannot access staff pages |
| **Test Steps** | 1. Login as patient<br>2. Manually navigate to staff_dashboard.php<br>3. Verify redirect |
| **Expected Result** | Access denied<br>Redirected to patient login |
| **Status** | ☐ Pass ☐ Fail |

---

### 2. Patient Registration

#### Test Case 2.1: New Patient Registration
| Test ID | TC-REG-001 |
|---------|------------|
| **Module** | Patient Registration |
| **Test Case** | Register new patient account |
| **Test Steps** | 1. Navigate to index_patient.php<br>2. Fill all required fields<br>3. Submit form |
| **Expected Result** | Account created<br>Success message displayed<br>Password hashed in database |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 2.2: Duplicate Username
| Test ID | TC-REG-002 |
|---------|------------|
| **Module** | Patient Registration |
| **Test Case** | Attempt duplicate username |
| **Test Steps** | 1. Try to register with existing username<br>2. Submit form |
| **Expected Result** | Error message: "Username already exists"<br>Registration fails |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 2.3: Form Validation
| Test ID | TC-REG-003 |
|---------|------------|
| **Module** | Patient Registration |
| **Test Case** | Empty required fields |
| **Test Steps** | 1. Leave required fields empty<br>2. Submit form |
| **Expected Result** | HTML5 validation prevents submission<br>Error indicators shown |
| **Status** | ☐ Pass ☐ Fail |

---

### 3. Appointment Booking

#### Test Case 3.1: Book Available Slot
| Test ID | TC-APPT-001 |
|---------|-------------|
| **Module** | Appointment Booking |
| **Test Case** | Book an available time slot |
| **Precondition** | Patient logged in, available time slots exist |
| **Test Steps** | 1. Navigate to book_appointment.php<br>2. Select service<br>3. Select date<br>4. Select available time slot<br>5. Submit booking |
| **Expected Result** | Appointment created<br>Time slot marked as unavailable<br>Success message displayed |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 3.2: Double Booking Prevention
| Test ID | TC-APPT-002 |
|---------|-------------|
| **Module** | Appointment Booking |
| **Test Case** | Attempt to book occupied slot |
| **Test Steps** | 1. Try to book already occupied time slot<br>2. Submit booking |
| **Expected Result** | Error message displayed<br>Booking prevented |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 3.3: Cancel Appointment
| Test ID | TC-APPT-003 |
|---------|-------------|
| **Module** | Appointment Management |
| **Test Case** | Cancel existing appointment |
| **Test Steps** | 1. Navigate to patient dashboard<br>2. Select appointment<br>3. Click cancel<br>4. Confirm cancellation |
| **Expected Result** | Appointment status updated to "Cancelled"<br>Time slot becomes available again |
| **Status** | ☐ Pass ☐ Fail |

---

### 4. Time Slot Management

#### Test Case 4.1: Add Default Time Slots
| Test ID | TC-TIME-001 |
|---------|-------------|
| **Module** | Time Slot Management |
| **Test Case** | Add default time slots for a day |
| **Precondition** | Staff logged in |
| **Test Steps** | 1. Navigate to manage_time_slots.php<br>2. Enable a day (e.g., Monday)<br>3. Click "Add Default Slots"<br>4. Verify slots created |
| **Expected Result** | Default time slots (9AM-5PM) created<br>Success message displayed |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 4.2: Toggle Slot Availability
| Test ID | TC-TIME-002 |
|---------|-------------|
| **Module** | Time Slot Management |
| **Test Case** | Enable/disable individual slot |
| **Test Steps** | 1. Navigate to manage_time_slots.php<br>2. Click toggle on a slot<br>3. Verify status change |
| **Expected Result** | Slot availability toggled<br>Status updated in database |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 4.3: Delete Time Slot
| Test ID | TC-TIME-003 |
|---------|-------------|
| **Module** | Time Slot Management |
| **Test Case** | Delete a time slot |
| **Test Steps** | 1. Click delete on a slot<br>2. Confirm deletion |
| **Expected Result** | Slot removed from database<br>No appointments exist for that slot |
| **Status** | ☐ Pass ☐ Fail |

---

### 5. Appointment Completion & Stock Management

#### Test Case 5.1: Complete Appointment with Injection
| Test ID | TC-COMP-001 |
|---------|-------------|
| **Module** | Appointment Completion |
| **Test Case** | Complete appointment using injection |
| **Precondition** | Appointment exists, injection stock > 0 |
| **Test Steps** | 1. Navigate to appointment_details.php<br>2. Fill diagnosis<br>3. Check "Injection Used"<br>4. Fill medication details<br>5. Click Complete |
| **Expected Result** | Appointment status = "Completed"<br>Injection stock decremented by 1<br>Medical record created |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 5.2: Stock Depletion Handling
| Test ID | TC-COMP-002 |
|---------|-------------|
| **Module** | Stock Management |
| **Test Case** | Complete appointment when stock = 0 |
| **Test Steps** | 1. Set injection stock to 0<br>2. Try to complete appointment with injection<br>3. Submit |
| **Expected Result** | Error message: "No injection stock available"<br>Completion prevented |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 5.3: Stock Alert Display
| Test ID | TC-STOCK-001 |
|---------|-------------|
| **Module** | Stock Management |
| **Test Case** | Low stock warning display |
| **Precondition** | Injection stock ≤ threshold |
| **Test Steps** | 1. Login as staff<br>2. View staff_dashboard.php |
| **Expected Result** | Warning alert displayed<br>Current stock count shown<br>Link to manage stock |
| **Status** | ☐ Pass ☐ Fail |

---

### 6. Messaging System

#### Test Case 6.1: Send Patient Message
| Test ID | TC-MSG-001 |
|---------|------------|
| **Module** | Messaging |
| **Test Case** | Patient sends message to staff |
| **Test Steps** | 1. Login as patient<br>2. Navigate to patient_messages.php<br>3. Type message<br>4. Click send |
| **Expected Result** | Message saved to database<br>Message displayed in chat<br>sender_type = "patient" |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 6.2: Staff Reply
| Test ID | TC-MSG-002 |
|---------|------------|
| **Module** | Messaging |
| **Test Case** | Staff replies to patient |
| **Test Steps** | 1. Login as staff<br>2. Navigate to staff_messages.php<br>3. Select patient conversation<br>4. Send reply |
| **Expected Result** | Reply saved with sender_type = "staff"<br>Staff name anonymous (shows "Medical Staff") |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 6.3: Message Auto-Refresh
| Test ID | TC-MSG-003 |
|---------|------------|
| **Module** | Messaging |
| **Test Case** | Messages auto-refresh |
| **Test Steps** | 1. Open patient messages<br>2. Wait 5 seconds without typing<br>3. Verify page refresh |
| **Expected Result** | Page reloads every 5 seconds<br>No reload when typing |
| **Status** | ☐ Pass ☐ Fail |

---

### 7. Account Deletion

#### Test Case 7.1: Patient Account Deletion
| Test ID | TC-DEL-001 |
|---------|------------|
| **Module** | Account Deletion |
| **Test Case** | Patient deletes own account |
| **Test Steps** | 1. Login as patient<br>2. Navigate to dashboard<br>3. Click "Delete Account" in quick actions<br>4. Enter password<br>5. Confirm deletion |
| **Expected Result** | Account deleted<br>Appointments deleted<br>Messages deleted<br>Medical records deleted<br>Redirected to index_patient.php |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 7.2: Wrong Password Deletion
| Test ID | TC-DEL-002 |
|---------|------------|
| **Module** | Account Deletion |
| **Test Case** | Attempt deletion with wrong password |
| **Test Steps** | 1. Click Delete Account<br>2. Enter incorrect password<br>3. Submit |
| **Expected Result** | Error: "Incorrect password"<br>Account NOT deleted<br>Modal remains open |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 7.3: Staff Account Deletion
| Test ID | TC-DEL-003 |
|---------|------------|
| **Module** | Account Deletion |
| **Test Case** | Staff deletes own account |
| **Test Steps** | 1. Login as staff<br>2. Click Delete Account<br>3. Enter password<br>4. Confirm |
| **Expected Result** | Staff account deleted<br>Related records updated/deleted<br>Appointments keep history but staff_id = NULL |
| **Status** | ☐ Pass ☐ Fail |

---

### 8. Staff Registration

#### Test Case 8.1: Add New Staff
| Test ID | TC-STAFF-001 |
|---------|-------------|
| **Module** | Staff Management |
| **Test Case** | Create new staff account |
| **Precondition** | Logged in as admin/staff |
| **Test Steps** | 1. Navigate to staff_register.php<br>2. Fill all fields<br>3. Select role (if available)<br>4. Submit |
| **Expected Result** | Staff account created<br>Password hashed<br>Success message displayed |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 8.2: Role Column Handling
| Test ID | TC-STAFF-002 |
|---------|-------------|
| **Module** | Staff Management |
| **Test Case** | System handles missing role column |
| **Test Steps** | 1. Check if role column exists in medical_staff table<br>2. Verify form adjusts accordingly |
| **Expected Result** | If role column missing: role field hidden<br>Registration works without role |
| **Status** | ☐ Pass ☐ Fail |

---

### 9. User Interface & Experience

#### Test Case 9.1: Responsive Design - Mobile
| Test ID | TC-UI-001 |
|---------|-----------|
| **Module** | UI/UX |
| **Test Case** | Mobile responsiveness |
| **Test Steps** | 1. Open site on mobile device (or dev tools)<br>2. Test all pages<br>3. Verify layouts adapt |
| **Expected Result** | All elements visible<br>No horizontal scroll<br>Touch-friendly buttons |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 9.2: Browser Compatibility
| Test ID | TC-UI-002 |
|---------|-----------|
| **Module** | UI/UX |
| **Test Case** | Cross-browser testing |
| **Test Steps** | 1. Test on Chrome<br>2. Test on Firefox<br>3. Test on Edge<br>4. Test on Safari |
| **Expected Result** | Consistent appearance and functionality across all browsers |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 9.3: Modal Functionality
| Test ID | TC-UI-003 |
|---------|-----------|
| **Module** | UI Components |
| **Test Case** | Delete account modal works |
| **Test Steps** | 1. Click delete account<br>2. Modal opens<br>3. Click outside modal<br>4. Press Escape key |
| **Expected Result** | Modal opens smoothly<br>Closes on outside click<br>Closes on Escape key |
| **Status** | ☐ Pass ☐ Fail |

---

### 10. Security Testing

#### Test Case 10.1: SQL Injection
| Test ID | TC-SEC-001 |
|---------|------------|
| **Module** | Security |
| **Test Case** | SQL injection prevention |
| **Test Steps** | 1. Login form: Enter `admin' OR '1'='1` as username<br>2. Submit<br>3. Verify behavior |
| **Expected Result** | Login fails<br>No database error exposed<br>Prepared statements prevent injection |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 10.2: XSS Attack
| Test ID | TC-SEC-002 |
|---------|------------|
| **Module** | Security |
| **Test Case** | XSS prevention |
| **Test Steps** | 1. In message box, enter: `<script>alert('XSS')</script>`<br>2. Send message<br>3. View message |
| **Expected Result** | Script tags displayed as text<br>No script execution<br>htmlspecialchars() working |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 10.3: Session Hijacking
| Test ID | TC-SEC-003 |
|---------|------------|
| **Module** | Security |
| **Test Case** | Session security |
| **Test Steps** | 1. Login<br>2. Copy session cookie<br>3. Open incognito window<br>4. Try to use copied session |
| **Expected Result** | Session regenerates on login<br>HttpOnly cookies prevent JavaScript access |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 10.4: Password Security
| Test ID | TC-SEC-004 |
|---------|------------|
| **Module** | Security |
| **Test Case** | Password hashing |
| **Test Steps** | 1. Register new account<br>2. Check database password field<br>3. Verify it's hashed |
| **Expected Result** | Password stored as bcrypt hash<br>Plain text not visible<br>Password verification works |
| **Status** | ☐ Pass ☐ Fail |

#### Test Case 10.5: Authorization Bypass
| Test ID | TC-SEC-005 |
|---------|------------|
| **Module** | Security |
| **Test Case** | Prevent unauthorized access |
| **Test Steps** | 1. Logout<br>2. Manually navigate to staff_dashboard.php<br>3. Verify redirect |
| **Expected Result** | Redirected to login page<br>Access denied message<br>No page content visible |
| **Status** | ☐ Pass ☐ Fail |

---

## Testing Checklist

### Pre-Testing Setup
- [ ] XAMPP installed and running
- [ ] Database created with all tables
- [ ] Test data populated (patients, staff, services)
- [ ] Browser developer tools ready
- [ ] Test user accounts created
- [ ] Backup database before testing

### Functional Testing Checklist
- [ ] Patient registration works
- [ ] Patient login/logout works
- [ ] Staff login/logout works
- [ ] Appointment booking works
- [ ] Appointment cancellation works
- [ ] Time slot management works
- [ ] Appointment completion works
- [ ] Stock management works
- [ ] Messaging system works
- [ ] Account deletion works
- [ ] Staff registration works
- [ ] Profile editing works
- [ ] Password change works

### Security Testing Checklist
- [ ] SQL injection prevented
- [ ] XSS attacks prevented
- [ ] CSRF protection implemented
- [ ] Password properly hashed
- [ ] Session management secure
- [ ] Authorization checks in place
- [ ] Input validation working

### UI/UX Testing Checklist
- [ ] Responsive on mobile devices
- [ ] Works on different screen sizes
- [ ] Consistent across browsers
- [ ] Buttons and links functional
- [ ] Forms validate properly
- [ ] Error messages clear
- [ ] Success messages display
- [ ] Navigation intuitive
- [ ] Loading states visible

### Performance Testing Checklist
- [ ] Page load < 3 seconds
- [ ] Database queries optimized
- [ ] No memory leaks
- [ ] Handles concurrent users
- [ ] Large data sets perform well

---

## Bug Tracking

### Bug Report Template

| Bug ID | [BUG-XXX] |
|--------|-----------|
| **Date Reported** | YYYY-MM-DD |
| **Reported By** | [Name] |
| **Module** | [Module Name] |
| **Priority** | ☐ Critical ☐ High ☐ Medium ☐ Low |
| **Status** | ☐ Open ☐ In Progress ☐ Fixed ☐ Closed |
| **Environment** | Browser: [Chrome/Firefox/etc.]<br>OS: [Windows/Mac/Linux] |
| **Steps to Reproduce** | 1. Step one<br>2. Step two<br>3. Step three |
| **Expected Result** | [What should happen] |
| **Actual Result** | [What actually happened] |
| **Screenshots** | [Attach if available] |
| **Error Messages** | [Copy exact error message] |
| **Fixed By** | [Developer Name] |
| **Fix Date** | YYYY-MM-DD |
| **Fix Description** | [What was changed] |

### Priority Definitions
- **Critical:** System crash, data loss, security breach
- **High:** Major feature broken, no workaround available
- **Medium:** Feature partially broken, workaround available
- **Low:** Minor UI issue, typo, cosmetic problem

---

## Test Results Template

### Test Execution Summary

**Test Date:** [Date]
**Tested By:** [Tester Name]
**Build Version:** [Version Number]
**Environment:** [Browser, OS, Server]

#### Overall Results
| Category | Total Tests | Passed | Failed | Blocked | Pass Rate |
|----------|-------------|--------|--------|---------|-----------|
| Authentication | 7 | 0 | 0 | 0 | 0% |
| Registration | 3 | 0 | 0 | 0 | 0% |
| Appointments | 3 | 0 | 0 | 0 | 0% |
| Time Slots | 3 | 0 | 0 | 0 | 0% |
| Stock Management | 3 | 0 | 0 | 0 | 0% |
| Messaging | 3 | 0 | 0 | 0 | 0% |
| Account Deletion | 3 | 0 | 0 | 0 | 0% |
| Staff Management | 2 | 0 | 0 | 0 | 0% |
| UI/UX | 3 | 0 | 0 | 0 | 0% |
| Security | 5 | 0 | 0 | 0 | 0% |
| **TOTAL** | **35** | **0** | **0** | **0** | **0%** |

#### Critical Issues Found
| Bug ID | Description | Status |
|--------|-------------|--------|
| BUG-001 | [Description] | Open |

#### Notes
[Add any observations, recommendations, or concerns here]

---

## Sign-Off

### Test Sign-Off
| Role | Name | Signature | Date |
|------|------|-----------|------|
| QA Lead | _____________ | _____________ | _______ |
| Developer | _____________ | _____________ | _______ |
| Project Manager | _____________ | _____________ | _______ |
| Client Representative | _____________ | _____________ | _______ |

---

## Appendices

### Appendix A: Test Data

#### Test Accounts
| Username | Password | Role | Purpose |
|----------|----------|------|---------|
| admin | admin123 | Staff | Default admin account |
| testpatient1 | Test@123 | Patient | Testing patient features |
| teststaff1 | Staff@123 | Staff | Testing staff features |

#### Test Services
| Service Name | Duration | Price |
|--------------|----------|-------|
| Consultation | 30 min | RM 50 |
| Facial Treatment | 60 min | RM 150 |
| Laser Treatment | 45 min | RM 200 |

### Appendix B: Database Schema Verification
Check that all required tables exist:
- [ ] `patient`
- [ ] `medical_staff`
- [ ] `appointment`
- [ ] `service`
- [ ] `time_slots`
- [ ] `stock`
- [ ] `messages`
- [ ] `medication_records`

### Appendix C: Known Limitations
- Auto-refresh in messaging may cause data loss if user is typing
- Map requires internet connection
- Email notifications not implemented
- PDF report generation not available

---

**End of Testing Documentation**
