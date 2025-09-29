<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use absolute path for includes
require_once __DIR__ . '/includes/dbh.inc.php';
// Path resolver should already be included by the calling page

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . PathResolver::resolve('index.php'));
    exit();
}

// Get the current page and paths
$currentPage = PathResolver::getCurrentPage();
$paths = PathResolver::getPagePaths();
?>

<!-- Sidebar Container -->
<div class="sidebar-container">
    <!-- Councillor Dashboard Link -->
    <a href="<?php echo $paths['councillor_dashboard']; ?>" class="<?php echo PathResolver::isCurrentPage('CouncillorDashboard.php') ? 'active' : ''; ?>">Dashboard</a>

    <!-- Project Budget Section -->
    <div class="project-budget-section">
        <h3 id="project-budget-toggle">Project Budget</h3>
        <div id="project-budget-links" 
             style="display: block;">
            <a href="<?php echo $paths['project_entry_form']; ?>" class="<?php echo PathResolver::isCurrentPage('project_entry_form.php') ? 'active' : ''; ?>">Add New Project</a>
            <a href="<?php echo $paths['my_projects']; ?>" class="<?php echo PathResolver::isCurrentPage('my_projects.php') ? 'active' : ''; ?>">View My Projects</a>
            <a href="<?php echo $paths['all_projects']; ?>" class="<?php echo PathResolver::isCurrentPage('all_projects.php') ? 'active' : ''; ?>">View All Projects</a>
            <a href="<?php echo $paths['project_edit_approvals']; ?>" class="<?php echo PathResolver::isCurrentPage('project_edit_approvals.php') ? 'active' : ''; ?>">Project Edit Approvals</a>
            <a href="<?php echo $paths['expense_edit_approvals']; ?>" class="<?php echo PathResolver::isCurrentPage('expense_edit_approvals.php') ? 'active' : ''; ?>">Expense Edit Approvals</a>
            <a href="<?php echo $paths['organization_reports']; ?>" class="<?php echo PathResolver::isCurrentPage('organization_reports.php') ? 'active' : ''; ?>">Organization Reports</a>
            <a href="<?php echo $paths['organization_management']; ?>" class="<?php echo PathResolver::isCurrentPage('organization_management.php') ? 'active' : ''; ?>">Manage Organizations</a>
            <a href="<?php echo $paths['transactions']; ?>" class="<?php echo PathResolver::isCurrentPage('transactions.php') ? 'active' : ''; ?>">My Transactions</a>
            <a href="<?php echo $paths['all_transactions']; ?>" class="<?php echo PathResolver::isCurrentPage('all_transactions.php') ? 'active' : ''; ?>">All Transactions</a>
        </div>
    </div>

    <!-- Activity Management Section -->
    <div class="activity-management-section">
        <h3 id="activity-management-toggle">Activity Management</h3>
        <div id="activity-management-links" 
             style="display: block;">
            <a href="<?php echo $paths['activity_entry_form']; ?>" class="<?php echo PathResolver::isCurrentPage('activity_entry_form.php') ? 'active' : ''; ?>">Add New Activity</a>
            <a href="<?php echo $paths['activities_list']; ?>" class="<?php echo PathResolver::isCurrentPage('activities_list.php') ? 'active' : ''; ?>">View All Activities</a>
            <a href="<?php echo $paths['activity_approvals']; ?>" class="<?php echo PathResolver::isCurrentPage('activity_approvals.php') ? 'active' : ''; ?>">Activity Edit Approvals</a>
        </div>
    </div>

    <!-- Assessments Section -->
    <div class="assessments-section">
        <h3 id="assessments-toggle">Assessments</h3>
        <div id="assessments-links" 
             style="display: block;">
            <a href="<?php echo $paths['assessment_index']; ?>" class="<?php echo PathResolver::isCurrentPage('AssessmentIndex.php') ? 'active' : ''; ?>">View All Assessments</a>
            <a href="<?php echo $paths['assessment_centre']; ?>" class="<?php echo PathResolver::isCurrentPage('AssessmentCentre.php') ? 'active' : ''; ?>">Assessment Centre</a>
            <a href="<?php echo $paths['assessment_staff']; ?>" class="<?php echo PathResolver::isCurrentPage('AssessmentStaff.php') ? 'active' : ''; ?>">Assessment Staff</a>
            <a href="<?php echo $paths['assessment_sisters']; ?>" class="<?php echo PathResolver::isCurrentPage('AssessmentSisters.php') ? 'active' : ''; ?>">Assessment Sisters</a>
            <a href="<?php echo $paths['assessment_programme']; ?>" class="<?php echo PathResolver::isCurrentPage('AssessmentProgramme.php') ? 'active' : ''; ?>">Assessment Programme</a>
            <a href="<?php echo $paths['assessment_documentation']; ?>" class="<?php echo PathResolver::isCurrentPage('AssessmentDocumentation.php') ? 'active' : ''; ?>">Assessment Documentation</a>
            <a href="<?php echo $paths['assessment_finance']; ?>" class="<?php echo PathResolver::isCurrentPage('AssessmentFinance.php') ? 'active' : ''; ?>">Assessment Finance</a>
            <a href="<?php echo $paths['centre']; ?>" class="<?php echo PathResolver::isCurrentPage('centre.php') ? 'active' : ''; ?>">Centre</a>
            <a href="<?php echo $paths['log']; ?>" class="<?php echo PathResolver::isCurrentPage('log.php') ? 'active' : ''; ?>">Activity Log</a>
        </div>
    </div>

    <!-- Councillor-Specific Links -->
    <a href="<?php echo $paths['user_list']; ?>" class="<?php echo PathResolver::isCurrentPage('user_list.php') ? 'active' : ''; ?>">Project In-Charge List</a>
    <a href="<?php echo $paths['register']; ?>" class="<?php echo PathResolver::isCurrentPage('register.php') ? 'active' : ''; ?>">Add New Member</a>

    <!-- Logout Link -->
    <a href="<?php echo $paths['logout']; ?>" class="text-red-500">Logout</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Project Budget Section Toggle
        const projectToggleButton = document.getElementById('project-budget-toggle');
        const projectLinksContainer = document.getElementById('project-budget-links');
        if (projectToggleButton && projectLinksContainer) {
            projectToggleButton.addEventListener('click', function () {
                const isCurrentlyVisible = projectLinksContainer.style.display === 'block';
                projectLinksContainer.style.display = isCurrentlyVisible ? 'none' : 'block';
            });
        }
    
        // Activity Management Section Toggle
        const activityToggleButton = document.getElementById('activity-management-toggle');
        const activityLinksContainer = document.getElementById('activity-management-links');
        if (activityToggleButton && activityLinksContainer) {
            activityToggleButton.addEventListener('click', function () {
                const isCurrentlyVisible = activityLinksContainer.style.display === 'block';
                activityLinksContainer.style.display = isCurrentlyVisible ? 'none' : 'block';
            });
        }

        // Assessments Section Toggle
        const assessmentsToggleButton = document.getElementById('assessments-toggle');
        const assessmentsLinksContainer = document.getElementById('assessments-links');
        if (assessmentsToggleButton && assessmentsLinksContainer) {
            assessmentsToggleButton.addEventListener('click', function () {
                const isCurrentlyVisible = assessmentsLinksContainer.style.display === 'block';
                assessmentsLinksContainer.style.display = isCurrentlyVisible ? 'none' : 'block';
            });
        }
    });
</script>
