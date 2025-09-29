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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Sidebar Container */
        .sidebar-container {
            position: fixed;
            left: 0;
            top: 60px;
            height: calc(100% - 60px);
            width: 220px;
            background-color: #1c2b3a;
            padding-top: 20px;
            overflow-y: auto;
            font-family: Arial, sans-serif; /* Global Font */
        }

        /* Links */
        .sidebar-container a {
            display: block;
            padding: 10px 20px;
            text-decoration: none;
            color: #ffffff;
            font-size: 14px; /* Inner links font size */
            font-weight: normal;
            border-radius: 8px;
            transition: background-color 0.2s, font-size 0.2s;
        }

        /* Active Tab */
        .sidebar-container a.active {
            background-color: #4a6fd1;
            color: #ffffff;
            font-weight: bold; /* Emphasize active tab */
            font-size: 15px; /* Slightly larger font for active tab */
        }

        .sidebar-container a:hover {
            background-color: #3b5998;
            color: #ffffff;
        }

        /* Section Headings */
        .project-budget-section h3,
        .assessments-section h3 {
            color: #ffffff;
            padding-left: 20px;
            margin-bottom: 10px;
            font-size: 16px; /* Larger font for main headings */
            font-weight: bold;
            cursor: pointer;
        }

        /* Hidden Links Containers */
        #project-budget-links,
        #assessments-links {
            display: none; /* Initially hidden */
            padding-left: 20px;
        }

        body {
            margin: 0;
            padding-left: 220px;
            background-color: #f0f4f8;
        }
    </style>
</head>
<body>
    <div class="sidebar-container">
        <!-- Councillor Dashboard Link -->
        <a href="<?php echo $paths['councillor_dashboard']; ?>" class="<?php echo PathResolver::isCurrentPage('CouncillorDashboard.php') ? 'active' : ''; ?>">Dashboard</a>

        <!-- Project Budget Section -->
        <div class="project-budget-section">
            <h3 id="project-budget-toggle">Project Budget</h3>
            <div id="project-budget-links" 
                 style="<?php echo in_array($currentPage, ['project_entry_form.php', 'my_projects.php', 'all_projects.php']) ? 'display: block;' : 'display: none;'; ?>">
                <a href="<?php echo $paths['project_entry_form']; ?>" class="<?php echo PathResolver::isCurrentPage('project_entry_form.php') ? 'active' : ''; ?>">Add New Project</a>
                <a href="<?php echo $paths['my_projects']; ?>" class="<?php echo PathResolver::isCurrentPage('my_projects.php') ? 'active' : ''; ?>">View My Projects</a>
                <a href="<?php echo $paths['all_projects']; ?>" class="<?php echo PathResolver::isCurrentPage('all_projects.php') ? 'active' : ''; ?>">View All Projects</a>
            </div>
        </div>

        <!-- Assessments Section -->
        <div class="assessments-section">
            <h3 id="assessments-toggle">Assessments</h3>
            <div id="assessments-links" 
                 style="<?php echo in_array($currentPage, ['AssessmentCentre.php', 'AssessmentStaff.php', 'AssessmentSisters.php', 'AssessmentProgramme.php', 'AssessmentDocumentation.php', 'AssessmentFinance.php', 'centre.php', 'log.php']) ? 'display: block;' : 'display: none;'; ?>">
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
</body>
</html>
