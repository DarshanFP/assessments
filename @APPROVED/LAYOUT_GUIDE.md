# Assessment System Layout Guide

## Overview

This guide explains the new layout system implemented for the Assessment System. The layout provides a consistent structure across all pages with proper spacing for topbar, sidebar, content, and footer.

## Layout Components

### 1. Topbar (`topbar.php`)

- **Position**: Fixed at the top (60px height)
- **Content**:
  - Left: System title "Assessment System"
  - Center: Empty (for balance)
  - Right: User greeting and name
- **Styling**: Dark background (#2d3748) with white text

### 2. Sidebar (`includes/role_based_sidebar.php`)

- **Position**: Fixed on the left (220px width)
- **Content**: Role-based navigation links
- **Styling**: Dark background (#1c2b3a) with white text
- **Features**: Collapsible sections for Project Budget and Assessments

### 3. Main Content Area

- **Position**: Centered with left margin for sidebar
- **Padding**: 20px internal padding
- **Background**: White with subtle shadow
- **Responsive**: Adapts to mobile screens

### 4. Footer (`footer.php`)

- **Position**: Fixed at the bottom (50px height)
- **Content**:
  - Left: Empty (for balance)
  - Center: Copyright information
  - Right: Current date and time in IST
- **Styling**: Dark background (#2d3748) with light text

## CSS Files

### `unified.css`

Unified CSS file that consolidates all styles and removes conflicts:

- Global reset and base styles
- Layout container structure
- Topbar, sidebar, and footer positioning
- Form and table styles
- Button and alert components
- Responsive design
- Print styles
- CSS variables for consistent theming
- No conflicting rules

## Page Structure Template

```php
<?php
// Start session before any output (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/SessionManager.php';
require_once 'includes/DatabaseManager.php';
require_once 'includes/RoleMiddleware.php';
require_once 'includes/SidebarManager.php';

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Your page-specific logic here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Title - Assessment System</title>

    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="unified.css">
</head>
<body>
        <!-- Layout Container -->
    <div class="layout-container">

        <!-- Topbar -->
        <?php include 'topbar.php'; ?>

        <!-- Sidebar -->
        <?php include 'includes/role_based_sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="main-content">

            <!-- Content Container -->
            <div class="content-container">

                <!-- Page Header -->
                <div class="page-header mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">Page Title</h1>
                    <p class="text-gray-600">Page description</p>
                </div>

                <!-- Display Session Messages -->
                <?php
                $successMessage = SessionManager::getMessage('success');
                $errorMessage = SessionManager::getMessage('error');

                if ($successMessage): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <!-- Main Content -->
                <div class="form-container">
                    <!-- Your page content goes here -->
                </div>

            </div>
        </div>

        <!-- Footer -->
        <?php include 'footer.php'; ?>

    </div>

    <!-- JavaScript -->
    <script>
        // Your page-specific JavaScript here
    </script>
</body>
</html>
```

## CSS Classes

### Layout Classes

- `.layout-container`: Main wrapper with proper spacing
- `.main-content`: Content area with sidebar margin
- `.content-container`: Centered content with max-width
- `.form-container`: Styled container for forms and content

### Component Classes

- `.btn`: Base button styles
- `.btn-primary`: Primary button (blue)
- `.btn-success`: Success button (green)
- `.btn-danger`: Danger button (red)
- `.alert`: Base alert styles
- `.alert-success`: Success alert (green)
- `.alert-error`: Error alert (red)
- `.alert-warning`: Warning alert (yellow)

### Utility Classes

- `.text-center`, `.text-right`, `.text-left`: Text alignment
- `.mb-4`, `.mt-4`: Margin utilities
- `.p-4`: Padding utilities
- `.hidden`, `.visible`: Display utilities

## Responsive Design

The layout is fully responsive:

- **Desktop**: Full sidebar visible
- **Mobile**: Sidebar hidden by default, can be toggled
- **Print**: Topbar, sidebar, and footer hidden

## Implementation Steps

1. **Include CSS files** in the head section:

   ```html
   <link rel="stylesheet" href="unified.css" />
   ```

2. **Use the layout structure**:

   ```html
   <div class="layout-container">
     <?php include 'topbar.php'; ?>
     <?php include 'includes/role_based_sidebar.php'; ?>
     <div class="main-content">
       <div class="content-container">
         <!-- Your content -->
       </div>
     </div>
     <?php include 'footer.php'; ?>
   </div>
   ```

3. **Use provided CSS classes** for consistent styling

4. **Test on different screen sizes** to ensure responsiveness

## Files Updated

### New Files Created

- `unified.css`: Unified CSS file (replaces layout.css and style.css)
- `footer.php`: Footer component
- `page_template.php`: Template for new pages
- `test_unified_layout.php`: Test page for layout verification
- `test_final_layout.php`: Final verification page

### Files Modified

- `topbar.php`: Removed duplicate HTML structure and date/time
- `sidebar_councillor.php`: Removed duplicate HTML structure
- `index.php`: Updated to use unified layout
- `View/dashboard.php`: Updated to use new layout
- `View/AssessmentDocumentation.php`: Complete layout restructure
- `ProjectBudgets/Blade/project_entry_form.php`: Updated to use unified CSS and proper form structure
- **31 total pages** updated to use unified.css
- **28 pages** with full layout structure (topbar + sidebar + footer)
- **3 pages** with simple layout (login, register, logout)

## Benefits

1. **Consistent Layout**: All pages now have the same structure
2. **Proper Spacing**: Content is properly spaced from topbar, sidebar, and footer
3. **Responsive Design**: Works on all screen sizes
4. **Maintainable**: Centralized CSS makes updates easier
5. **Professional Look**: Clean, modern appearance
6. **Accessibility**: Proper semantic structure and contrast

## Testing

Use `test_final_layout.php` to verify that all components are working correctly:

- Topbar positioning and content (no date/time)
- Sidebar navigation (properly positioned on left)
- Content area spacing (no excessive top space)
- Form width (should use full width, not squeezed)
- Footer with IST time
- Responsive behavior
- Button and alert styles
- All form components (inputs, selects, textareas)
- Alert messages (success, error, warning)
- Card layouts and grid systems

## Notes

- All times are displayed in IST (Indian Standard Time)
- The footer shows current date and time that updates on page refresh
- Session management is handled automatically
- Role-based sidebar shows different navigation based on user role
