<?php
/**
 * Role Middleware Class
 * Handles role-based access control and authorization
 */
class RoleMiddleware {
    
    /**
     * Check if user has specific role
     */
    public static function checkRole($requiredRole) {
        if (!SessionManager::isLoggedIn()) {
            header("Location: ../View/access_denied.php");
            exit();
        }
        
        if (SessionManager::getUserRole() !== $requiredRole) {
            header("Location: ../View/access_denied.php");
            exit();
        }
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public static function checkMultipleRoles($allowedRoles) {
        if (!SessionManager::isLoggedIn()) {
            header("Location: ../View/access_denied.php");
            exit();
        }
        
        if (!in_array(SessionManager::getUserRole(), $allowedRoles)) {
            header("Location: ../View/access_denied.php");
            exit();
        }
    }
    
    /**
     * Check if user is Councillor
     */
    public static function requireCouncillor() {
        self::checkRole('Councillor');
    }
    
    /**
     * Check if user is Project In-Charge
     */
    public static function requireProjectInCharge() {
        self::checkRole('Project In-Charge');
    }
    
    /**
     * Check if user is either Councillor or Project In-Charge
     */
    public static function requireAnyRole() {
        self::checkMultipleRoles(['Councillor', 'Project In-Charge']);
    }
    
    /**
     * Get accessible assessment modules based on role
     */
    public static function getAccessibleModules() {
        if (!SessionManager::isLoggedIn()) {
            return [];
        }
        
        $role = SessionManager::getUserRole();
        
        switch ($role) {
            case 'Councillor':
                return [
                    'AssessmentCentre' => true,
                    'AssessmentStaff' => true,
                    'AssessmentSisters' => true,
                    'AssessmentProgramme' => true,
                    'AssessmentDocumentation' => true,
                    'AssessmentFinance' => true,
                    'Centre' => true,
                    'Log' => true,
                    'UserManagement' => true,
                    'ProjectBudget' => true,
                    'Transactions' => true,
                    'Reports' => true
                ];
                
            case 'Project In-Charge':
                return [
                    'AssessmentCentre' => true,
                    'AssessmentStaff' => true,
                    'AssessmentSisters' => true,
                    'AssessmentProgramme' => true,
                    'AssessmentDocumentation' => true,
                    'AssessmentFinance' => true,
                    'Centre' => true,
                    'Log' => false,
                    'UserManagement' => false,
                    'ProjectBudget' => true,
                    'Transactions' => true,
                    'Reports' => true
                ];
                
            default:
                return [];
        }
    }
    
    /**
     * Check if user can access specific module
     */
    public static function canAccessModule($moduleName) {
        $accessibleModules = self::getAccessibleModules();
        return isset($accessibleModules[$moduleName]) && $accessibleModules[$moduleName];
    }
    
    /**
     * Get user's dashboard path based on role
     */
    public static function getDashboardPath() {
        if (!SessionManager::isLoggedIn()) {
            return '../index.php';
        }
        
        $role = SessionManager::getUserRole();
        
        switch ($role) {
            case 'Councillor':
                return 'CouncillorDashboard.php';
            case 'Project In-Charge':
                return 'ProjectInChargeDashboard.php';
            default:
                return 'dashboard.php';
        }
    }
    
    /**
     * Get user's sidebar type based on role
     */
    public static function getSidebarType() {
        if (!SessionManager::isLoggedIn()) {
            return 'default';
        }
        
        $role = SessionManager::getUserRole();
        
        switch ($role) {
            case 'Councillor':
                return 'councillor';
            case 'Project In-Charge':
                return 'project_incharge';
            default:
                return 'default';
        }
    }
    
    /**
     * Check if user can perform specific action
     */
    public static function canPerformAction($action) {
        if (!SessionManager::isLoggedIn()) {
            return false;
        }
        
        $role = SessionManager::getUserRole();
        
        $permissions = [
            'Councillor' => [
                'view_all_projects' => true,
                'create_project' => true,
                'edit_project' => true,
                'delete_project' => true,
                'view_all_transactions' => true,
                'create_transaction' => true,
                'edit_transaction' => true,
                'delete_transaction' => true,
                'view_reports' => true,
                'manage_users' => true,
                'view_logs' => true,
                'edit_assessments' => true,
                'delete_assessments' => true
            ],
            'Project In-Charge' => [
                'view_all_projects' => false,
                'create_project' => true,
                'edit_project' => true,
                'delete_project' => false,
                'view_all_transactions' => false,
                'create_transaction' => true,
                'edit_transaction' => true,
                'delete_transaction' => false,
                'view_reports' => true,
                'manage_users' => false,
                'view_logs' => false,
                'edit_assessments' => true,
                'delete_assessments' => false
            ]
        ];
        
        return isset($permissions[$role][$action]) && $permissions[$role][$action];
    }
    
    /**
     * Get user's community restrictions
     */
    public static function getCommunityRestrictions() {
        if (!SessionManager::isLoggedIn()) {
            return null;
        }
        
        $role = SessionManager::getUserRole();
        
        if ($role === 'Project In-Charge') {
            return SessionManager::getUserCommunity();
        }
        
        return null; // Councillors can access all communities
    }
    
    /**
     * Log unauthorized access attempt
     */
    public static function logUnauthorizedAccess($attemptedAction) {
        if (SessionManager::isLoggedIn()) {
            $userId = SessionManager::getUserId();
            $userName = SessionManager::getUserFullName();
            $userRole = SessionManager::getUserRole();
            
            $logMessage = "Unauthorized access attempt: User ID {$userId} ({$userName}) with role {$userRole} attempted to access {$attemptedAction}";
            logActivityToDatabase($userId, 'Unauthorized Access', 'Error', $logMessage);
            logActivityToFile($logMessage, "warning");
        }
    }
}
?>
