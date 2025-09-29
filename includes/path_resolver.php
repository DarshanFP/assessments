<?php
/**
 * Path Resolver for Dynamic Navigation Links
 * This file handles path resolution for sidebar links based on current page location
 */

class PathResolver {
    
    /**
     * Get the base path to the assessments root directory
     */
    public static function getBasePath() {
        // Get the current script path
        $currentScript = $_SERVER['SCRIPT_NAME'];
        
        // Remove the filename to get the directory
        $currentDir = dirname($currentScript);
        
        // Check if we're in a subdomain or root domain
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $isSubdomain = !empty($httpHost) && strpos($httpHost, '.') !== false && 
                      strpos($httpHost, 'localhost') === false;
        
        if ($isSubdomain) {
            // Production subdomain environment - use absolute paths from domain root
            return '/';
        } else {
            // Localhost environment - use relative paths
            // Find the assessments directory in the path
            $pathParts = explode('/', trim($currentDir, '/'));
            $assessmentsIndex = array_search('assessments', $pathParts);
            
            if ($assessmentsIndex !== false) {
                // We found the assessments directory, calculate path from there
                $levels = count($pathParts) - $assessmentsIndex - 1;
                $basePath = '';
                for ($i = 0; $i < $levels; $i++) {
                    $basePath .= '../';
                }
                return $basePath;
            } else {
                // If we can't find 'assessments' in the path, we might be in a subdirectory
                // Try to find the root by looking for common patterns
                if (in_array('ProjectBudgets', $pathParts)) {
                    // We're in ProjectBudgets directory, go up to assessments root
                    $projectBudgetsIndex = array_search('ProjectBudgets', $pathParts);
                    
                    // Check if we're running from CLI test (root directory)
                    // In this case, ProjectBudgets paths should be relative to root
                    if (defined('PHP_SAPI') && PHP_SAPI === 'cli' && count($pathParts) === 2 && $pathParts[0] === 'ProjectBudgets') {
                        return '';
                    }
                    
                    $levels = count($pathParts) - $projectBudgetsIndex;
                    $basePath = '';
                    for ($i = 0; $i < $levels; $i++) {
                        $basePath .= '../';
                    }
                    return $basePath;
                } else {
                    // If we're at the root level (no assessments in path), return empty string
                    if (count($pathParts) <= 1) {
                        return '';
                    }
                    
                    // Fallback to old method if assessments directory not found
                    $levels = substr_count($currentDir, '/') - 1;
                    $basePath = '';
                    for ($i = 0; $i < $levels; $i++) {
                        $basePath .= '../';
                    }
                    return $basePath;
                }
            }
        }
    }
    
    /**
     * Resolve a path relative to the assessments root
     */
    public static function resolve($path) {
        $basePath = self::getBasePath();
        return $basePath . $path;
    }
    
    /**
     * Get common paths used throughout the application
     */
    public static function getCommonPaths() {
        return [
            'root' => self::resolve(''),
            'index' => self::resolve('index.php'),
            'logout' => self::resolve('logout.php'),
            'view' => self::resolve('View/'),
            'projectbudgets' => self::resolve('ProjectBudgets/Blade/'),
            'edit' => self::resolve('Edit/'),
            'controller' => self::resolve('Controller/'),
            'includes' => self::resolve('includes/'),
        ];
    }
    
    /**
     * Get specific page paths
     */
    public static function getPagePaths() {
        $base = self::getBasePath();
        
        return [
            // Dashboard pages
            'councillor_dashboard' => $base . 'View/CouncillorDashboard.php',
            'project_incharge_dashboard' => $base . 'View/ProjectInChargeDashboard.php',
            'dashboard' => $base . 'View/dashboard.php',
            
            // Project Budget pages
            'project_entry_form' => $base . 'ProjectBudgets/Blade/project_entry_form.php',
            'my_projects' => $base . 'ProjectBudgets/Blade/my_projects.php',
            'all_projects' => $base . 'ProjectBudgets/Blade/all_projects.php',
            'project_edit_approvals' => $base . 'ProjectBudgets/Blade/project_edit_approvals.php',
            'expense_edit_approvals' => $base . 'ProjectBudgets/Blade/expense_edit_approvals.php',
            'organization_reports' => $base . 'ProjectBudgets/Blade/organization_reports.php',
            'organization_management' => $base . 'ProjectBudgets/Blade/organization_management.php',
            'transactions' => $base . 'ProjectBudgets/Blade/transactions.php',
            'all_transactions' => $base . 'ProjectBudgets/Blade/all_transactions.php',
            
            // Activity Management pages
            'activity_entry_form' => $base . 'ProjectBudgets/Blade/activity_entry_form.php',
            'activities_list' => $base . 'ProjectBudgets/Blade/activities_list.php',
            'activity_edit_form' => $base . 'ProjectBudgets/Blade/activity_edit_form.php',
            'activity_detail' => $base . 'ProjectBudgets/Blade/activity_detail.php',
            'activity_approvals' => $base . 'ProjectBudgets/Blade/activity_approvals.php',
            
            // Assessment pages
            'assessment_index' => $base . 'View/AssessmentIndex.php',
            'assessment_centre' => $base . 'View/AssessmentCentre.php',
            'assessment_centre_detail' => $base . 'View/AssessmentCentreDetail.php',
            'project_incharge_assessment_index' => $base . 'View/ProjectInChargeAssessmentIndex.php',
            'assessment_staff' => $base . 'View/AssessmentStaff.php',
            'assessment_sisters' => $base . 'View/AssessmentSisters.php',
            'assessment_programme' => $base . 'View/AssessmentProgramme.php',
            'assessment_documentation' => $base . 'View/AssessmentDocumentation.php',
            'assessment_finance' => $base . 'View/AssessmentFinance.php',
            'centre' => $base . 'View/centre.php',
            'log' => $base . 'View/log.php',
            
            // User management pages
            'user_list' => $base . 'View/user_list.php',
            'register' => $base . 'View/register.php',
            'edit_user' => $base . 'View/edit_user.php',
            'reset_password' => $base . 'View/reset_password.php',
            'access_denied' => $base . 'View/access_denied.php',
            'activity_log' => $base . 'View/ActivityLog.php',
            
            // Edit pages
            'edit_assessment_centre' => $base . 'Edit/AssessmentCentreEdit.php',
            'edit_assessment_documentation' => $base . 'Edit/AssessmentDocumentationEdit.php',
            'edit_assessment_programme' => $base . 'Edit/AssessmentProgrammeEdit.php',
            'edit_assessment_sisters' => $base . 'Edit/AssessmentSistersEdit.php',
            'edit_assessment_staff' => $base . 'Edit/AssessmentStaffEdit.php',
            'edit_finance' => $base . 'Edit/FinanceEdit.php',
            
            // System pages
            'logout' => $base . 'logout.php',
            'index' => $base . 'index.php',
        ];
    }
    
    /**
     * Get the current page name for active state detection
     */
    public static function getCurrentPage() {
        return basename($_SERVER['PHP_SELF']);
    }
    
    /**
     * Check if current page matches a specific page
     */
    public static function isCurrentPage($pageName) {
        return self::getCurrentPage() === $pageName;
    }
    
    /**
     * Get debug information for troubleshooting
     */
    public static function getDebugInfo() {
        $currentDir = dirname($_SERVER['SCRIPT_NAME']);
        $pathParts = explode('/', trim($currentDir, '/'));
        $assessmentsIndex = array_search('assessments', $pathParts);
        
        return [
            'script_name' => $_SERVER['SCRIPT_NAME'],
            'current_dir' => $currentDir,
            'path_parts' => $pathParts,
            'assessments_index' => $assessmentsIndex,
            'base_path' => self::getBasePath(),
            'current_page' => self::getCurrentPage(),
            'levels_deep' => substr_count($currentDir, '/') - 1,
            'http_host' => $_SERVER['HTTP_HOST'] ?? 'CLI',
            'is_subdomain' => !empty($_SERVER['HTTP_HOST'] ?? '') && strpos($_SERVER['HTTP_HOST'], '.') !== false && 
                             strpos($_SERVER['HTTP_HOST'], 'localhost') === false,
        ];
    }
}
?>
