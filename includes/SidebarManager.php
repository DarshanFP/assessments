<?php
/**
 * Sidebar Manager Class
 * Handles role-based sidebar rendering and navigation
 */
require_once 'path_helper.php';

class SidebarManager {
    
    /**
     * Render the appropriate sidebar based on user role
     */
    public static function renderSidebar() {
        if (!SessionManager::isLoggedIn()) {
            return;
        }
        
        $sidebarType = RoleMiddleware::getSidebarType();
        
        switch ($sidebarType) {
            case 'councillor':
                self::renderCouncillorSidebar();
                break;
            case 'project_incharge':
                self::renderProjectInChargeSidebar();
                break;
            default:
                self::renderDefaultSidebar();
                break;
        }
    }
    
    /**
     * Render Councillor sidebar
     */
    private static function renderCouncillorSidebar() {
        $currentPage = getCurrentPage();
        ?>
        <div class="sidebar-container">
            <!-- Dashboard Link -->
            <a href="../View/CouncillorDashboard.php" class="<?= ($currentPage == 'CouncillorDashboard.php') ? 'active' : '' ?>">Dashboard</a>

            <!-- Project Budget Section -->
            <div class="project-budget-section">
                <h3 id="project-budget-toggle">Project Budget</h3>
                <div id="project-budget-links" 
                     style="<?= in_array($currentPage, ['project_entry_form.php', 'my_projects.php', 'all_projects.php']) ? 'display: block;' : 'display: none;' ?>">

                    <a href="../ProjectBudgets/Blade/project_entry_form.php" class="<?= ($currentPage == 'project_entry_form.php') ? 'active' : '' ?>">Add New Project</a>
                    <a href="../ProjectBudgets/Blade/my_projects.php" class="<?= ($currentPage == 'my_projects.php') ? 'active' : '' ?>">View My Projects</a>
                    <a href="../ProjectBudgets/Blade/all_projects.php" class="<?= ($currentPage == 'all_projects.php') ? 'active' : '' ?>">View All Projects</a>
                    <a href="../ProjectBudgets/Blade/transactions.php" class="<?= ($currentPage == 'transactions.php') ? 'active' : '' ?>">Transactions</a>
                    <a href="../ProjectBudgets/Blade/all_transactions.php" class="<?= ($currentPage == 'all_transactions.php') ? 'active' : '' ?>">All Transactions</a>
                </div>
            </div>

            <!-- Assessments Section -->
            <div class="assessments-section">
                <h3 id="assessments-toggle">Assessments</h3>
                <div id="assessments-links" 
                     style="<?= in_array($currentPage, ['AssessmentCentre.php', 'AssessmentStaff.php', 'AssessmentSisters.php', 'AssessmentProgramme.php', 'AssessmentDocumentation.php', 'AssessmentFinance.php', 'centre.php', 'log.php']) ? 'display: block;' : 'display: none;' ?>">

                    <a href="../View/AssessmentCentre.php" class="<?= ($currentPage == 'AssessmentCentre.php') ? 'active' : '' ?>">Assessment Centre</a>
                    <a href="../View/AssessmentStaff.php" class="<?= ($currentPage == 'AssessmentStaff.php') ? 'active' : '' ?>">Assessment Staff</a>
                    <a href="../View/AssessmentSisters.php" class="<?= ($currentPage == 'AssessmentSisters.php') ? 'active' : '' ?>">Assessment Sisters</a>
                    <a href="../View/AssessmentProgramme.php" class="<?= ($currentPage == 'AssessmentProgramme.php') ? 'active' : '' ?>">Assessment Programme</a>
                    <a href="../View/AssessmentDocumentation.php" class="<?= ($currentPage == 'AssessmentDocumentation.php') ? 'active' : '' ?>">Assessment Documentation</a>
                    <a href="../View/AssessmentFinance.php" class="<?= ($currentPage == 'AssessmentFinance.php') ? 'active' : '' ?>">Assessment Finance</a>
                    <a href="../View/centre.php" class="<?= ($currentPage == 'centre.php') ? 'active' : '' ?>">Centre</a>
                    <a href="../View/log.php" class="<?= ($currentPage == 'log.php') ? 'active' : '' ?>">Activity Log</a>
                </div>
            </div>

            <!-- Councillor-Specific Links -->
            <a href="../View/user_list.php" class="<?= ($currentPage == 'user_list.php') ? 'active' : '' ?>">Project In-Charge List</a>
            <a href="../View/register.php" class="<?= ($currentPage == 'register.php') ? 'active' : '' ?>">Add New Member</a>

            <!-- Logout Link -->
            <a href="../logout.php" class="text-red-500">Logout</a>
        </div>
        <?php
    }
    
    /**
     * Render Project In-Charge sidebar
     */
    private static function renderProjectInChargeSidebar() {
        $currentPage = getCurrentPage();
        ?>
        <div class="sidebar-container">
            <!-- Dashboard Link -->
            <a href="../View/ProjectInChargeDashboard.php" class="<?= ($currentPage == 'ProjectInChargeDashboard.php') ? 'active' : '' ?>">Dashboard</a>

            <!-- Project Budget Section -->
            <div class="project-budget-section">
                <h3 id="project-budget-toggle">Project Budget</h3>
                <div id="project-budget-links" 
                     style="<?= in_array($currentPage, ['project_entry_form.php', 'my_projects.php', 'transactions.php']) ? 'display: block;' : 'display: none;' ?>">

                    <a href="../ProjectBudgets/Blade/project_entry_form.php" class="<?= ($currentPage == 'project_entry_form.php') ? 'active' : '' ?>">Add New Project</a>
                    <a href="../ProjectBudgets/Blade/my_projects.php" class="<?= ($currentPage == 'my_projects.php') ? 'active' : '' ?>">View My Projects</a>
                    <a href="../ProjectBudgets/Blade/transactions.php" class="<?= ($currentPage == 'transactions.php') ? 'active' : '' ?>">Transactions</a>
                </div>
            </div>

            <!-- Assessments Section -->
            <div class="assessments-section">
                <h3 id="assessments-toggle">Assessments</h3>
                <div id="assessments-links" 
                     style="<?= in_array($currentPage, ['AssessmentCentre.php', 'AssessmentStaff.php', 'AssessmentSisters.php', 'AssessmentProgramme.php', 'AssessmentDocumentation.php', 'AssessmentFinance.php', 'centre.php']) ? 'display: block;' : 'display: none;' ?>">

                    <a href="../View/AssessmentCentre.php" class="<?= ($currentPage == 'AssessmentCentre.php') ? 'active' : '' ?>">Assessment Centre</a>
                    <a href="../View/AssessmentStaff.php" class="<?= ($currentPage == 'AssessmentStaff.php') ? 'active' : '' ?>">Assessment Staff</a>
                    <a href="../View/AssessmentSisters.php" class="<?= ($currentPage == 'AssessmentSisters.php') ? 'active' : '' ?>">Assessment Sisters</a>
                    <a href="../View/AssessmentProgramme.php" class="<?= ($currentPage == 'AssessmentProgramme.php') ? 'active' : '' ?>">Assessment Programme</a>
                    <a href="../View/AssessmentDocumentation.php" class="<?= ($currentPage == 'AssessmentDocumentation.php') ? 'active' : '' ?>">Assessment Documentation</a>
                    <a href="../View/AssessmentFinance.php" class="<?= ($currentPage == 'AssessmentFinance.php') ? 'active' : '' ?>">Assessment Finance</a>
                    <a href="../View/centre.php" class="<?= ($currentPage == 'centre.php') ? 'active' : '' ?>">Centre</a>
                </div>
            </div>

            <!-- Logout Link -->
            <a href="../logout.php" class="text-red-500">Logout</a>
        </div>
        <?php
    }
    
    /**
     * Render default sidebar (fallback)
     */
    private static function renderDefaultSidebar() {
        $currentPage = getCurrentPage();
        ?>
        <div class="sidebar-container">
            <a href="../View/dashboard.php" class="<?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">Dashboard</a>
            <a href="../logout.php" class="text-red-500">Logout</a>
        </div>
        <?php
    }
    
    /**
     * Get navigation items based on role
     */
    public static function getNavigationItems() {
        if (!SessionManager::isLoggedIn()) {
            return [];
        }
        
        $role = SessionManager::getUserRole();
        $currentPage = getCurrentPage();
        
        $items = [
            'dashboard' => [
                'url' => RoleMiddleware::getDashboardPath(),
                'text' => 'Dashboard',
                'active' => in_array($currentPage, ['dashboard.php', 'CouncillorDashboard.php', 'ProjectInChargeDashboard.php'])
            ]
        ];
        
        // Add role-specific items
        if ($role === 'Councillor') {
            $items['user_management'] = [
                'url' => '../View/user_list.php',
                'text' => 'Project In-Charge List',
                'active' => $currentPage === 'user_list.php'
            ];
            $items['add_member'] = [
                'url' => '../View/register.php',
                'text' => 'Add New Member',
                'active' => $currentPage === 'register.php'
            ];
        }
        
        return $items;
    }
    
    /**
     * Get sidebar CSS
     */
    public static function getSidebarCSS() {
        return '
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
                font-family: Arial, sans-serif;
            }

            /* Links */
            .sidebar-container a {
                display: block;
                padding: 10px 20px;
                text-decoration: none;
                color: #ffffff;
                font-size: 14px;
                font-weight: normal;
                border-radius: 8px;
                transition: background-color 0.2s, font-size 0.2s;
            }

            /* Active Tab */
            .sidebar-container a.active {
                background-color: #4a6fd1;
                color: #ffffff;
                font-weight: bold;
                font-size: 15px;
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
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
            }

            /* Hidden Links Containers */
            #project-budget-links,
            #assessments-links {
                display: none;
                padding-left: 20px;
            }

            body {
                margin: 0;
                padding-left: 220px;
                background-color: #f0f4f8;
            }
        </style>';
    }
    
    /**
     * Get sidebar JavaScript
     */
    public static function getSidebarJS() {
        return '
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Project Budget Section Toggle
                const projectToggleButton = document.getElementById("project-budget-toggle");
                const projectLinksContainer = document.getElementById("project-budget-links");
                if (projectToggleButton && projectLinksContainer) {
                    projectToggleButton.addEventListener("click", function () {
                        const isCurrentlyVisible = projectLinksContainer.style.display === "block";
                        projectLinksContainer.style.display = isCurrentlyVisible ? "none" : "block";
                    });
                }
            
                // Assessments Section Toggle
                const assessmentsToggleButton = document.getElementById("assessments-toggle");
                const assessmentsLinksContainer = document.getElementById("assessments-links");
                if (assessmentsToggleButton && assessmentsLinksContainer) {
                    assessmentsToggleButton.addEventListener("click", function () {
                        const isCurrentlyVisible = assessmentsLinksContainer.style.display === "block";
                        assessmentsLinksContainer.style.display = isCurrentlyVisible ? "none" : "block";
                    });
                }
            });
        </script>';
    }
}
?>
