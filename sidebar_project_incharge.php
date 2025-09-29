<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use absolute path for includes
require_once __DIR__ . '/includes/dbh.inc.php';
require_once __DIR__ . '/includes/path_resolver.php';

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
    <!-- Project In-Charge Dashboard Link -->
    <a href="<?php echo $paths['project_incharge_dashboard']; ?>" class="<?php echo PathResolver::isCurrentPage('ProjectInChargeDashboard.php') ? 'active' : ''; ?>">Dashboard</a>

    <!-- Assessments Section -->
    <div class="assessments-section">
        <h3 id="assessments-toggle">Assessments</h3>
        <div id="assessments-links" 
             style="<?php echo in_array($currentPage, ['ProjectInChargeAssessmentIndex.php']) ? 'display: block;' : 'display: none;'; ?>">
            <a href="<?php echo $paths['project_incharge_assessment_index']; ?>" class="<?php echo PathResolver::isCurrentPage('ProjectInChargeAssessmentIndex.php') ? 'active' : ''; ?>">View Assessments</a>
        </div>
    </div>

    <!-- Recommendations Section -->
    <div class="recommendations-section">
        <h3 id="recommendations-toggle">Recommendations</h3>
        <div id="recommendations-links" 
             style="<?php echo in_array($currentPage, ['RecommendationsList.php']) ? 'display: block;' : 'display: none;'; ?>">
            <a href="<?php echo PathResolver::resolve('View/RecommendationsList.php'); ?>" class="<?php echo PathResolver::isCurrentPage('RecommendationsList.php') ? 'active' : ''; ?>">View Recommendations</a>
        </div>
    </div>

    <!-- Project Budget Section -->
    <div class="project-budget-section">
        <h3 id="project-budget-toggle">Project Budget</h3>
        <div id="project-budget-links" 
             style="<?php echo in_array($currentPage, ['project_entry_form.php', 'my_projects.php', 'all_projects.php', 'transactions.php', 'all_transactions.php']) ? 'display: block;' : 'display: none;'; ?>">
            <a href="<?php echo $paths['project_entry_form']; ?>" class="<?php echo PathResolver::isCurrentPage('project_entry_form.php') ? 'active' : ''; ?>">Add New Project</a>
            <a href="<?php echo $paths['my_projects']; ?>" class="<?php echo PathResolver::isCurrentPage('my_projects.php') ? 'active' : ''; ?>">View My Projects</a>
            <a href="<?php echo $paths['all_projects']; ?>" class="<?php echo PathResolver::isCurrentPage('all_projects.php') ? 'active' : ''; ?>">View All Projects</a>
            <a href="<?php echo $paths['transactions']; ?>" class="<?php echo PathResolver::isCurrentPage('transactions.php') ? 'active' : ''; ?>">My Transactions</a>
            <a href="<?php echo $paths['all_transactions']; ?>" class="<?php echo PathResolver::isCurrentPage('all_transactions.php') ? 'active' : ''; ?>">All Transactions</a>
        </div>
    </div>

    <!-- Activity Management Section -->
    <div class="activity-management-section">
        <h3 id="activity-management-toggle">Activity Management</h3>
        <div id="activity-management-links" 
             style="<?php echo in_array($currentPage, ['activity_entry_form.php', 'activities_list.php']) ? 'display: block;' : 'display: none;'; ?>">
            <a href="<?php echo $paths['activity_entry_form']; ?>" class="<?php echo PathResolver::isCurrentPage('activity_entry_form.php') ? 'active' : ''; ?>">Add New Activity</a>
            <a href="<?php echo $paths['activities_list']; ?>" class="<?php echo PathResolver::isCurrentPage('activities_list.php') ? 'active' : ''; ?>">View My Activities</a>
        </div>
    </div>

    <!-- Logout Link -->
    <a href="<?php echo $paths['logout']; ?>" class="text-red-500">Logout</a>
</div>

<script>
    // Simple and direct approach
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Project In-Charge sidebar JavaScript loaded');
        
        // Assessments Section Toggle
        const assessmentsToggleButton = document.getElementById('assessments-toggle');
        const assessmentsLinksContainer = document.getElementById('assessments-links');
        console.log('Assessments elements found:', !!assessmentsToggleButton, !!assessmentsLinksContainer);
        
        if (assessmentsToggleButton && assessmentsLinksContainer) {
            assessmentsToggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Assessments toggle clicked');
                const isCurrentlyVisible = assessmentsLinksContainer.style.display === 'block';
                assessmentsLinksContainer.style.display = isCurrentlyVisible ? 'none' : 'block';
                console.log('Assessments section toggled to:', assessmentsLinksContainer.style.display);
            });
        }

        // Recommendations Section Toggle
        const recommendationsToggleButton = document.getElementById('recommendations-toggle');
        const recommendationsLinksContainer = document.getElementById('recommendations-links');
        console.log('Recommendations elements found:', !!recommendationsToggleButton, !!recommendationsLinksContainer);
        
        if (recommendationsToggleButton && recommendationsLinksContainer) {
            recommendationsToggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Recommendations toggle clicked');
                const isCurrentlyVisible = recommendationsLinksContainer.style.display === 'block';
                recommendationsLinksContainer.style.display = isCurrentlyVisible ? 'none' : 'block';
                console.log('Recommendations section toggled to:', recommendationsLinksContainer.style.display);
            });
        }

        // Project Budget Section Toggle
        const projectToggleButton = document.getElementById('project-budget-toggle');
        const projectLinksContainer = document.getElementById('project-budget-links');
        console.log('Project Budget elements found:', !!projectToggleButton, !!projectLinksContainer);
        
        if (projectToggleButton && projectLinksContainer) {
            projectToggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Project Budget toggle clicked');
                const isCurrentlyVisible = projectLinksContainer.style.display === 'block';
                projectLinksContainer.style.display = isCurrentlyVisible ? 'none' : 'block';
                console.log('Project Budget section toggled to:', projectLinksContainer.style.display);
            });
        }

        // Activity Management Section Toggle
        const activityToggleButton = document.getElementById('activity-management-toggle');
        const activityLinksContainer = document.getElementById('activity-management-links');
        console.log('Activity Management elements found:', !!activityToggleButton, !!activityLinksContainer);
        
        if (activityToggleButton && activityLinksContainer) {
            activityToggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Activity Management toggle clicked');
                const isCurrentlyVisible = activityLinksContainer.style.display === 'block';
                activityLinksContainer.style.display = isCurrentlyVisible ? 'none' : 'block';
                console.log('Activity Management section toggled to:', activityLinksContainer.style.display);
            });
        }
    });
</script>
