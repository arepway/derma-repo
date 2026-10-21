# Swiss Modernist Sidebar Integration Guide

This guide explains how to integrate the new Swiss modernist sidebar navigation into existing staff portal pages.

## Files Created

1. **`css/staff_layout.css`** - Complete CSS styling for the Swiss modernist design
2. **`includes/staff_sidebar.php`** - Reusable sidebar navigation component
3. **`includes/staff_header.php`** - Reusable HTML head and layout opening
4. **`includes/staff_footer.php`** - Reusable layout closing and scripts

## Integration Pattern

### Before (Old Pattern)
```php
<?php
require_once 'auth_check.php';
requireStaffLogin();
// ... database connection ...
// ... business logic ...
?>

<!DOCTYPE html>
<html>
<head>
    <title>Page Title</title>
    <!-- Custom CSS -->
    <style>
        /* Lots of custom styles */
    </style>
</head>
<body>
    <!-- Custom header/navigation -->
    <div class="header">...</div>
    <nav>...</nav>

    <!-- Page content -->
    <div class="container">
        <!-- Content here -->
    </div>
</body>
</html>
```

### After (New Pattern)
```php
<?php
require_once 'auth_check.php';
requireStaffLogin();
require_once 'db_connect.php'; // Use shared connection

// ... business logic ...

// Set page title
$page_title = "Your Page Title";

// Include header (includes sidebar automatically)
require_once 'includes/staff_header.php';
?>

<!-- Your page content goes here -->
<div class="module">
    <div class="module-header">
        <h2 class="module-title">Section Title</h2>
    </div>
    <div class="module-body">
        <!-- Content -->
    </div>
</div>

<?php require_once 'includes/staff_footer.php'; ?>
```

## Step-by-Step Integration

### 1. Update Database Connection

**Replace this:**
```php
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
```

**With this:**
```php
require_once 'db_connect.php'; // Uses PDO connection
```

### 2. Set Page Title

**Before the header include, add:**
```php
$page_title = "Appointments"; // Or whatever your page is about
```

### 3. Replace HTML Head Section

**Remove everything from `<!DOCTYPE html>` to `<body>` and replace with:**
```php
require_once 'includes/staff_header.php';
?>
```

### 4. Update Content Structure

**Replace old containers/wrappers with module structure:**
```php
<div class="module">
    <div class="module-header">
        <h2 class="module-title">Your Section Title</h2>
        <a href="link.php" class="module-action">Action Link →</a>
    </div>
    <div class="module-body">
        <!-- Or use table-wrapper for tables -->
        <div class="table-wrapper">
            <table class="data-table">
                <!-- table content -->
            </table>
        </div>
    </div>
</div>
```

### 5. Replace Closing Tags

**Remove `</body></html>` and replace with:**
```php
<?php require_once 'includes/staff_footer.php'; ?>
```

## Available CSS Classes

### Layout
- `.module` - Standard content block
- `.module-header` - Module header section
- `.module-title` - Module title
- `.module-action` - Action link in header
- `.module-body` - Module content area

### Tables
- `.table-wrapper` - Scrollable table container
- `.data-table` - Clean table styling
- Use `<th>` for headers, `<td>` for cells

### Forms
- `.form-group` - Form field wrapper
- `.form-label` - Form field label
- `.form-input` - Text inputs
- `.form-select` - Select dropdowns
- `.form-textarea` - Text areas

### Buttons
- `.btn` - Base button class
- `.btn-primary` - Primary action (black)
- `.btn-secondary` - Secondary action (outlined)
- `.btn-accent` - Danger/important action (red)

### Alerts
- `.alert-banner` - Alert container
- `.alert-success` - Success message (green)
- `.alert-warning` - Warning message (yellow)
- `.alert-icon` - Alert icon
- `.alert-content` - Alert text content
- `.alert-title` - Alert title
- `.alert-message` - Alert message

### Lists
- `.list-stack` - Stacked list container
- `.list-item` - Individual list item
- `.list-initial` - Avatar/initial
- `.list-content` - Item content
- `.list-primary` - Primary text
- `.list-secondary` - Secondary text
- `.list-badge` - Badge/tag

### Stats
- `.stats-overview` - Stats container
- `.stats-grid` - Grid layout for stats
- `.stat-cell` - Individual stat cell
- `.stat-label` - Stat label
- `.stat-value` - Large stat number
- `.stat-meta` - Stat description

## Example: Full Page Template

```php
<?php
require_once 'auth_check.php';
requireStaffLogin();
require_once 'db_connect.php';

// Your business logic
$items = [];
try {
    $stmt = $pdo->query("SELECT * FROM your_table");
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Set page title
$page_title = "Your Page Title";
require_once 'includes/staff_header.php';
?>

<!-- Error/Success Messages -->
<?php if (isset($error)): ?>
    <div class="alert-banner">
        <i class="fas fa-exclamation-circle alert-icon"></i>
        <div class="alert-content">
            <div class="alert-message"><?= htmlspecialchars($error) ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Main Content Module -->
<div class="module">
    <div class="module-header">
        <h2 class="module-title">Your Section</h2>
        <a href="add_item.php" class="module-action">Add New →</a>
    </div>

    <?php if ($items): ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Column 1</th>
                        <th>Column 2</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['field1']) ?></td>
                            <td><?= htmlspecialchars($item['field2']) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $item['id'] ?>" class="module-action">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="empty-message">No items found</div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/staff_footer.php'; ?>
```

## Pages to Update

Apply this pattern to the following staff portal pages:

- [x] `staff_dashboard.php` - ✅ Already updated
- [x] `staff_register.php` - ✅ Already updated
- [x] `manage_appointments.php` - ✅ Already updated
- [ ] `appointment_details.php`
- [x] `manage_patients.php` - ✅ Already updated
- [ ] `staff_messages.php`
- [ ] `manage_services.php`
- [ ] `manage_feedback.php`
- [ ] `manage_stock.php`
- [x] `edit_profileStaff.php` - ✅ Already updated

## Pages to SKIP

- Any time slot management pages (as requested)
- Patient portal pages
- Login pages

## Design Principles

The Swiss modernist design follows these principles:

1. **No gradients** - Use solid colors only
2. **Sharp borders** - 1px solid lines, no rounded corners on containers
3. **Neutral palette** - Warm grays with red accent
4. **Clear typography** - Inter font, proper hierarchy
5. **Data-first** - Content over decoration
6. **Grid system** - Structured spacing and alignment
7. **Minimal interactions** - Simple hover states, no animations
8. **Editorial feel** - Like a premium publication

## Color Palette

- Background: `#fafaf9` (off-white)
- Surface: `#ffffff` (white)
- Border: `#e7e5e4` (light gray)
- Text Primary: `#1c1917` (charcoal)
- Text Secondary: `#57534e` (warm gray)
- Text Muted: `#a8a29e` (light gray)
- Accent: `#dc2626` (editorial red)

## Support

For questions or issues with the integration:
1. Check this guide
2. Reference example pages:
   - `staff_register.php` - Simple form
   - `manage_patients.php` - Grid/list view with search
   - `manage_appointments.php` - AJAX filtering and grouping
   - `staff_dashboard.php` - Complex dashboard layout
3. Review `css/staff_layout.css` for available classes

## Notes

- The sidebar automatically highlights the active page
- Delete account modal is included in the footer
- Responsive design handles mobile automatically
- All forms use consistent styling
- Tables are scrollable on mobile
