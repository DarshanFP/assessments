<?php
/**
 * Dashboard Manager Class
 * Handles dashboard statistics, metrics, and data summaries
 */
require_once 'DatabaseManager.php';

class DashboardManager {
    
    /**
     * Get dashboard statistics for Councillor
     */
    public static function getCouncillorStats() {
        try {
            // Try to get database connection
            $pdo = getDatabaseConnection();
            if (!$pdo) {
                error_log("Failed to get database connection in getCouncillorStats");
                return [];
            }
            
            $stats = [];
            
            // Total assessments count
            $sql = "SELECT COUNT(*) as total_assessments FROM Assessment";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['total_assessments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_assessments'];
            
            // Recent assessments (last 30 days)
            $sql = "SELECT COUNT(*) as recent_assessments FROM Assessment WHERE DateOfAssessment >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['recent_assessments'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_assessments'];
            
            // Total projects count
            $sql = "SELECT COUNT(*) as total_projects FROM Projects";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['total_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_projects'];
            
            // Active projects count
            $sql = "SELECT COUNT(*) as active_projects FROM Projects WHERE end_date >= CURDATE()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['active_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_projects'];
            
            // Total users count
            $sql = "SELECT COUNT(*) as total_users FROM ssmntUsers";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
            
            // Project In-Charge count
            $sql = "SELECT COUNT(*) as project_incharges FROM ssmntUsers WHERE role = 'Project In-Charge'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['project_incharges'] = $stmt->fetch(PDO::FETCH_ASSOC)['project_incharges'];
            
            // Total budget amount
            $sql = "SELECT SUM(total_budget) as total_budget FROM Projects";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['total_budget'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_budget'] ?? 0;
            
            // Total expenses amount
            $sql = "SELECT SUM(amount_expensed) as total_expenses FROM ExpenseEntries";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['total_expenses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_expenses'] ?? 0;
            
            // Available funds (total budget - total expenses)
            $stats['available_funds'] = $stats['total_budget'] - $stats['total_expenses'];
            
            // Recent transactions count
            $sql = "SELECT COUNT(*) as recent_transactions FROM ExpenseEntries WHERE expensed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['recent_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_transactions'];
            
            // Debug: Log the stats being returned
            error_log("Dashboard stats: " . json_encode($stats));
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Dashboard stats general error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                try {
                    $dbManager = DatabaseManager::getInstance();
                    $dbManager->releaseConnection($pdo);
                } catch (Exception $e) {
                    error_log("Error releasing database connection: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Get dashboard statistics for Project In-Charge
     */
    public static function getProjectInChargeStats($userId, $community) {
        try {
            // Use the same database connection as other pages
            require_once __DIR__ . '/dbh.inc.php';
            $stats = [];
            
            // User's projects count
            $sql = "SELECT COUNT(*) as my_projects FROM Projects WHERE project_incharge = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $stats['my_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['my_projects'];
            
            // Active projects count
            $sql = "SELECT COUNT(*) as active_projects FROM Projects WHERE project_incharge = :user_id AND end_date >= CURDATE()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $stats['active_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_projects'];
            
            // Community assessments count
            $sql = "SELECT COUNT(*) as community_assessments FROM Assessment WHERE Community = :community";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['community' => $community]);
            $stats['community_assessments'] = $stmt->fetch(PDO::FETCH_ASSOC)['community_assessments'];
            
            // Recent community assessments
            $sql = "SELECT COUNT(*) as recent_assessments FROM Assessment WHERE Community = :community AND DateOfAssessment >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['community' => $community]);
            $stats['recent_assessments'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_assessments'];
            
            // User's project budget total
            $sql = "SELECT SUM(total_budget) as my_budget FROM Projects WHERE project_incharge = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $stats['my_budget'] = $stmt->fetch(PDO::FETCH_ASSOC)['my_budget'] ?? 0;
            
            // Recent transactions for user's projects
            $sql = "SELECT COUNT(*) as recent_transactions FROM ExpenseEntries ee 
                    JOIN Projects p ON ee.project_id = p.project_id 
                    WHERE p.project_incharge = :user_id AND ee.expensed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $stats['recent_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_transactions'];
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent activities
     */
    public static function getRecentActivities($limit = 10) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT * FROM ActivityLog ORDER BY created_at DESC LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Recent activities error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get recent assessments
     */
    public static function getRecentAssessments($limit = 5) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT keyID, Community, AssessorsName, DateOfAssessment FROM Assessment ORDER BY DateOfAssessment DESC LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Recent assessments error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get recent projects
     */
    public static function getRecentProjects($limit = 5) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT project_id, project_name, total_budget, start_date FROM Projects ORDER BY created_at DESC LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Recent projects error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get community-wise assessment summary
     */
    public static function getCommunityAssessmentSummary() {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT Community, COUNT(*) as assessment_count FROM Assessment GROUP BY Community ORDER BY assessment_count DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Community summary error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get project budget summary
     */
    public static function getProjectBudgetSummary() {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT 
                        SUM(total_budget) as total_budget,
                        COUNT(*) as total_projects,
                        SUM(CASE WHEN end_date >= CURDATE() THEN total_budget ELSE 0 END) as active_budget,
                        COUNT(CASE WHEN end_date >= CURDATE() THEN 1 END) as active_projects
                    FROM Projects";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Budget summary error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Format currency for display
     */
    public static function formatCurrency($amount) {
        return '₹' . number_format($amount, 2);
    }
    
    /**
     * Get dashboard quick actions based on role
     */
    public static function getQuickActions($role) {
        $actions = [];
        
        switch ($role) {
            case 'Councillor':
                $actions = [
                    [
                        'title' => 'New Assessment',
                        'url' => '../View/AssessmentCentre.php',
                        'icon' => '📝',
                        'color' => 'bg-blue-500 hover:bg-blue-600'
                    ],
                    [
                        'title' => 'View Assessments',
                        'url' => '../View/AssessmentCentre.php',
                        'icon' => '📋',
                        'color' => 'bg-green-500 hover:bg-green-600'
                    ],
                    [
                        'title' => 'Create Project',
                        'url' => '../ProjectBudgets/Blade/project_entry_form.php',
                        'icon' => '🏗️',
                        'color' => 'bg-purple-500 hover:bg-purple-600'
                    ],
                    [
                        'title' => 'View All Projects',
                        'url' => '../ProjectBudgets/Blade/all_projects.php',
                        'icon' => '📊',
                        'color' => 'bg-red-500 hover:bg-red-600'
                    ],
                    [
                        'title' => 'Add New Member',
                        'url' => '../View/register.php',
                        'icon' => '👤',
                        'color' => 'bg-indigo-500 hover:bg-indigo-600'
                    ],
                    [
                        'title' => 'View Project In-Charges',
                        'url' => '../View/user_list.php',
                        'icon' => '👥',
                        'color' => 'bg-yellow-500 hover:bg-yellow-600'
                    ]
                ];
                break;
                
            case 'Project In-Charge':
                $actions = [
                    [
                        'title' => 'Create Project',
                        'url' => '../ProjectBudgets/Blade/project_entry_form.php',
                        'icon' => '📋',
                        'color' => 'bg-blue-500 hover:bg-blue-600'
                    ],
                    [
                        'title' => 'View My Projects',
                        'url' => '../ProjectBudgets/Blade/my_projects.php',
                        'icon' => '📊',
                        'color' => 'bg-green-500 hover:bg-green-600'
                    ],
                    [
                        'title' => 'Manage Transactions',
                        'url' => '../ProjectBudgets/Blade/transactions.php',
                        'icon' => '💰',
                        'color' => 'bg-purple-500 hover:bg-purple-600'
                    ],

                    [
                        'title' => 'Assessment Centre',
                        'url' => '../View/AssessmentCentre.php',
                        'icon' => '🏢',
                        'color' => 'bg-indigo-500 hover:bg-indigo-600'
                    ],
                    [
                        'title' => 'Assessment Staff',
                        'url' => '../View/AssessmentStaff.php',
                        'icon' => '👨‍💼',
                        'color' => 'bg-teal-500 hover:bg-teal-600'
                    ]
                ];
                break;
        }
        
        return $actions;
    }
}
?>
