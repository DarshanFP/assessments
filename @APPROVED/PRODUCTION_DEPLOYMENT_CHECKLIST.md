# Production Deployment Checklist

## Overview

This document lists all files and folders that need to be uploaded to the production server based on the work accomplished in this chat session.

## Database Changes (CRITICAL - Run First)

### 1. Database Migration Scripts

**Priority: HIGH - Run these first before uploading any code**

```
📁 /Applications/MAMP/htdocs/assessments/
├── create_organizations_system.sql          [NEW] - Dynamic organization system migration
└── create_notifications_system.sql          [NEW] - Notifications system (if implemented)
```

**Instructions:**

1. Run `create_organizations_system.sql` first
2. This will create the Organizations table and update Projects table
3. Existing projects will be automatically assigned to SSCT organization

## Core System Files

### 2. Database Connection & Configuration

```
📁 /Applications/MAMP/htdocs/assessments/
├── config/database.php                      [EXISTING] - Database configuration
└── includes/
    ├── dbh.inc.php                          [EXISTING] - Database connection
    ├── auth_check.php                       [EXISTING] - Authentication
    ├── path_resolver.php                    [UPDATED] - Path resolution with new routes
    └── role_based_sidebar.php               [EXISTING] - Role-based navigation
```

### 3. Navigation & Layout

```
📁 /Applications/MAMP/htdocs/assessments/
├── sidebar_councillor.php                   [UPDATED] - Added new menu items
├── topbar.php                               [EXISTING] - Top navigation
├── footer.php                               [EXISTING] - Footer
└── unified.css                              [EXISTING] - Main stylesheet
```

## Project Budget System Files

### 4. Project Entry & Management

```
📁 /Applications/MAMP/htdocs/assessments/ProjectBudgets/
├── Blade/
│   ├── project_entry_form.php               [UPDATED] - Added funding fields, multiple in-charges
│   ├── my_projects.php                      [UPDATED] - Enhanced with new fields and actions
│   ├── all_projects.php                     [UPDATED] - Enhanced with new fields and actions
│   ├── organization_reports.php             [UPDATED] - Dynamic organization reports
│   ├── organization_management.php          [NEW] - Organization management interface
│   ├── transactions.php                     [EXISTING] - Transaction management
│   └── all_transactions.php                 [EXISTING] - All transactions view
└── Controller/
    ├── project_entry_process.php            [UPDATED] - Enhanced with new fields and multiple in-charges
    ├── organization_management_process.php  [NEW] - Organization management controller
    └── transactions_process.php             [EXISTING] - Transaction processing
```

### 5. New Features Added

```
📁 /Applications/MAMP/htdocs/assessments/ProjectBudgets/
├── Blade/
│   ├── project_edit_form.php                [NEW] - Project editing interface
│   ├── project_expense_breakdown.php        [NEW] - Expense breakdown view
│   ├── project_edit_approvals.php           [NEW] - Project edit approvals
│   ├── expense_edit_approvals.php           [NEW] - Expense edit approvals
│   ├── activity_entry_form.php              [NEW] - Activity management
│   ├── activities_list.php                  [NEW] - Activities listing
│   ├── activity_edit_form.php               [NEW] - Activity editing
│   ├── activity_detail.php                  [NEW] - Activity details
│   └── activity_approvals.php               [NEW] - Activity approvals
└── Controller/
    ├── project_deactivate_process.php       [NEW] - Project deactivation
    ├── project_edit_process.php             [NEW] - Project editing
    ├── activity_management_process.php      [NEW] - Activity management
    └── approval_process.php                 [NEW] - Approval workflows
```

## Documentation Files

### 6. Implementation Documentation

```
📁 /Applications/MAMP/htdocs/assessments/@APPROVED/
├── ORGANIZATION_IMPLEMENTATION_SUMMARY.md   [EXISTING] - Original implementation
├── DYNAMIC_ORGANIZATION_SYSTEM_SUMMARY.md   [NEW] - Dynamic system documentation
└── PRODUCTION_DEPLOYMENT_CHECKLIST.md       [NEW] - This file
```

## Additional System Files

### 7. Notifications System (if implemented)

```
📁 /Applications/MAMP/htdocs/assessments/
├── ajax/
│   └── delete_notification.php              [EXISTING] - Notification management
└── includes/
    ├── NotificationManager.php              [NEW] - Notification handling
    └── notification_functions.php           [NEW] - Notification utilities
```

### 8. Enhanced Features

```
📁 /Applications/MAMP/htdocs/assessments/
├── includes/
│   ├── ProjectManager.php                   [NEW] - Project management utilities
│   ├── ApprovalManager.php                  [NEW] - Approval workflow management
│   ├── ActivityManager.php                  [NEW] - Activity management utilities
│   └── EnhancedEmailManager.php             [EXISTING] - Email notifications
└── UserManagement/
    └── UserManager.php                      [EXISTING] - User management
```

## Deployment Steps

### Step 1: Database Migration (CRITICAL)

```bash
# Connect to production database and run:
mysql -u [username] -p [database_name] < create_organizations_system.sql
```

### Step 2: File Upload Order

1. **Core system files first** (config, includes, navigation)
2. **Project budget system files** (Blade and Controller folders)
3. **New feature files** (activity management, approvals)
4. **Documentation files** (for reference)

### Step 3: File Permissions

```bash
# Navigate to your assessments directory first
cd /path/to/your/assessments/

# Set directory permissions (directories need execute permission)
chmod 755 .
chmod 755 ProjectBudgets/
chmod 755 ProjectBudgets/Blade/
chmod 755 ProjectBudgets/Controller/
chmod 755 includes/
chmod 755 @APPROVED/

# Set file permissions (files need read/write for owner, read for others)
chmod 644 *.php
chmod 644 *.css
chmod 644 *.sql
chmod 644 ProjectBudgets/Blade/*.php
chmod 644 ProjectBudgets/Controller/*.php
chmod 644 includes/*.php
chmod 644 config/*.php

# Alternative: Set permissions recursively (be careful with this)
# chmod -R 755 .  # This sets 755 for all files and directories
# find . -type f -exec chmod 644 {} \;  # Then set files to 644
# find . -type d -exec chmod 755 {} \;  # And directories to 755
```

### Step 4: Configuration Updates

1. Update `config/database.php` with production database credentials
2. Verify `includes/dbh.inc.php` connection settings
3. Test database connectivity

### Step 5: Testing Checklist

- [ ] Database migration completed successfully
- [ ] Organization management interface accessible
- [ ] Project creation with new fields works
- [ ] Multiple project in-charges assignment works
- [ ] Organization reports display correctly
- [ ] All navigation links work
- [ ] User authentication functions properly
- [ ] File permissions are correct

## Files to Exclude from Production

### Development Files (Do NOT upload)

```
📁 /Applications/MAMP/htdocs/assessments/
├── test_*.php                               [EXCLUDE] - Test files
├── debug_*.php                              [EXCLUDE] - Debug files
├── check_*.php                              [EXCLUDE] - Check files
├── setup_*.php                              [EXCLUDE] - Setup files
├── install_*.php                            [EXCLUDE] - Installation files
├── logs/                                    [EXCLUDE] - Log files (create empty folder)
├── vendor/                                  [EXCLUDE] - Composer dependencies (if using)
└── .git/                                    [EXCLUDE] - Git repository
```

## Backup Recommendations

### Before Deployment

1. **Database Backup**

   ```bash
   mysqldump -u [username] -p [database_name] > backup_before_organization_system.sql
   ```

2. **File Backup**
   ```bash
   tar -czf backup_before_organization_system.tar.gz /path/to/current/assessments/
   ```

### After Deployment

1. Test all functionality
2. Verify data integrity
3. Check user access and permissions
4. Monitor error logs

## Rollback Plan

### If Issues Occur

1. **Database Rollback**

   ```bash
   mysql -u [username] -p [database_name] < backup_before_organization_system.sql
   ```

2. **File Rollback**
   ```bash
   tar -xzf backup_before_organization_system.tar.gz -C /path/to/assessments/
   ```

## Post-Deployment Tasks

1. **Update Documentation**

   - Update any system documentation
   - Notify users of new features
   - Update user guides if necessary

2. **User Training**

   - Train Councillors on organization management
   - Train Project In-Charges on new project features
   - Update user manuals

3. **Monitoring**
   - Monitor system performance
   - Check error logs regularly
   - Monitor user feedback

## Support Information

### Key Features Added

- Dynamic organization management (SSCT, SAES, and unlimited others)
- Multiple project in-charges per project
- Enhanced project fields (funding source, fund type)
- Project deactivation capability
- Activity management system
- Approval workflows
- Enhanced reporting and statistics

### Contact Information

- System Administrator: [Your contact details]
- Database Administrator: [DB admin contact]
- Technical Support: [Support contact]

---

**Important Notes:**

- Always test in a staging environment first
- Keep backups of both database and files
- Monitor the system after deployment
- Have a rollback plan ready
- Document any customizations made during deployment
