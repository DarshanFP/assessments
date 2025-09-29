<?php
/**
 * Report Manager Class
 * Handles comprehensive reporting functionality
 */
require_once 'DatabaseManager.php';

class ReportManager {
    
    /**
     * Generate assessment summary report
     */
    public static function generateAssessmentReport($filters = []) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT 
                        a.keyID,
                        a.Community,
                        a.AssessorsName,
                        a.DateOfAssessment,
                        a.AssessmentCentre,
                        a.TotalScore,
                        a.Grade,
                        a.Remarks
                    FROM Assessment a
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['community'])) {
                $sql .= " AND a.Community = :community";
                $params['community'] = $filters['community'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND a.DateOfAssessment >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND a.DateOfAssessment <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            
            if (!empty($filters['assessor'])) {
                $sql .= " AND a.AssessorsName LIKE :assessor";
                $params['assessor'] = '%' . $filters['assessor'] . '%';
            }
            
            $sql .= " ORDER BY a.DateOfAssessment DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary statistics
            $summary = self::calculateAssessmentSummary($assessments);
            
            return [
                'assessments' => $assessments,
                'summary' => $summary,
                'filters' => $filters
            ];
            
        } catch (PDOException $e) {
            error_log("Assessment report error: " . $e->getMessage());
            return ['assessments' => [], 'summary' => [], 'filters' => $filters];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Calculate assessment summary statistics
     */
    private static function calculateAssessmentSummary($assessments) {
        $summary = [
            'total_assessments' => count($assessments),
            'communities' => [],
            'grades' => [],
            'assessors' => [],
            'date_range' => [],
            'average_score' => 0
        ];
        
        if (empty($assessments)) {
            return $summary;
        }
        
        $totalScore = 0;
        $validScores = 0;
        
        foreach ($assessments as $assessment) {
            // Community count
            $community = $assessment['Community'];
            if (!isset($summary['communities'][$community])) {
                $summary['communities'][$community] = 0;
            }
            $summary['communities'][$community]++;
            
            // Grade count
            $grade = $assessment['Grade'];
            if (!isset($summary['grades'][$grade])) {
                $summary['grades'][$grade] = 0;
            }
            $summary['grades'][$grade]++;
            
            // Assessor count
            $assessor = $assessment['AssessorsName'];
            if (!isset($summary['assessors'][$assessor])) {
                $summary['assessors'][$assessor] = 0;
            }
            $summary['assessors'][$assessor]++;
            
            // Score calculation
            if (!empty($assessment['TotalScore']) && is_numeric($assessment['TotalScore'])) {
                $totalScore += $assessment['TotalScore'];
                $validScores++;
            }
            
            // Date range
            $date = $assessment['DateOfAssessment'];
            if (empty($summary['date_range']['min']) || $date < $summary['date_range']['min']) {
                $summary['date_range']['min'] = $date;
            }
            if (empty($summary['date_range']['max']) || $date > $summary['date_range']['max']) {
                $summary['date_range']['max'] = $date;
            }
        }
        
        // Calculate average score
        if ($validScores > 0) {
            $summary['average_score'] = round($totalScore / $validScores, 2);
        }
        
        return $summary;
    }
    
    /**
     * Generate project summary report
     */
    public static function generateProjectReport($filters = []) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT 
                        p.project_id,
                        p.project_name,
                        p.description,
                        p.total_budget,
                        p.start_date,
                        p.end_date,
                        p.status,
                        p.project_incharge,
                        u.full_name as incharge_name,
                        u.community as incharge_community
                    FROM Projects p
                    LEFT JOIN ssmntUsers u ON p.project_incharge = u.id
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $sql .= " AND p.status = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['community'])) {
                $sql .= " AND u.community = :community";
                $params['community'] = $filters['community'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND p.start_date >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND p.end_date <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            
            if (!empty($filters['incharge'])) {
                $sql .= " AND p.project_incharge = :incharge";
                $params['incharge'] = $filters['incharge'];
            }
            
            $sql .= " ORDER BY p.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary statistics
            $summary = self::calculateProjectSummary($projects);
            
            return [
                'projects' => $projects,
                'summary' => $summary,
                'filters' => $filters
            ];
            
        } catch (PDOException $e) {
            error_log("Project report error: " . $e->getMessage());
            return ['projects' => [], 'summary' => [], 'filters' => $filters];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Calculate project summary statistics
     */
    private static function calculateProjectSummary($projects) {
        $summary = [
            'total_projects' => count($projects),
            'total_budget' => 0,
            'active_projects' => 0,
            'completed_projects' => 0,
            'status_breakdown' => [],
            'community_breakdown' => [],
            'incharge_breakdown' => []
        ];
        
        if (empty($projects)) {
            return $summary;
        }
        
        foreach ($projects as $project) {
            // Budget calculation
            if (!empty($project['total_budget']) && is_numeric($project['total_budget'])) {
                $summary['total_budget'] += $project['total_budget'];
            }
            
            // Status breakdown
            $status = $project['status'] ?? 'Unknown';
            if (!isset($summary['status_breakdown'][$status])) {
                $summary['status_breakdown'][$status] = 0;
            }
            $summary['status_breakdown'][$status]++;
            
            // Active/Completed count
            if ($status === 'Active' || $status === 'In Progress') {
                $summary['active_projects']++;
            } elseif ($status === 'Completed' || $status === 'Finished') {
                $summary['completed_projects']++;
            }
            
            // Community breakdown
            $community = $project['incharge_community'] ?? 'Unknown';
            if (!isset($summary['community_breakdown'][$community])) {
                $summary['community_breakdown'][$community] = 0;
            }
            $summary['community_breakdown'][$community]++;
            
            // In-charge breakdown
            $incharge = $project['incharge_name'] ?? 'Unknown';
            if (!isset($summary['incharge_breakdown'][$incharge])) {
                $summary['incharge_breakdown'][$incharge] = 0;
            }
            $summary['incharge_breakdown'][$incharge]++;
        }
        
        return $summary;
    }
    
    /**
     * Generate user activity report
     */
    public static function generateActivityReport($filters = []) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT 
                        al.id,
                        al.user_id,
                        al.action,
                        al.module,
                        al.status,
                        al.details,
                        al.created_at,
                        u.username,
                        u.full_name,
                        u.role
                    FROM ActivityLog al
                    LEFT JOIN ssmntUsers u ON al.user_id = u.id
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['user_id'])) {
                $sql .= " AND al.user_id = :user_id";
                $params['user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['module'])) {
                $sql .= " AND al.module = :module";
                $params['module'] = $filters['module'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND al.status = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND al.created_at >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND al.created_at <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            
            $sql .= " ORDER BY al.created_at DESC";
            
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT :limit";
                $params['limit'] = $filters['limit'];
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary statistics
            $summary = self::calculateActivitySummary($activities);
            
            return [
                'activities' => $activities,
                'summary' => $summary,
                'filters' => $filters
            ];
            
        } catch (PDOException $e) {
            error_log("Activity report error: " . $e->getMessage());
            return ['activities' => [], 'summary' => [], 'filters' => $filters];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Calculate activity summary statistics
     */
    private static function calculateActivitySummary($activities) {
        $summary = [
            'total_activities' => count($activities),
            'users' => [],
            'modules' => [],
            'status_breakdown' => [],
            'date_breakdown' => []
        ];
        
        if (empty($activities)) {
            return $summary;
        }
        
        foreach ($activities as $activity) {
            // User breakdown
            $user = $activity['full_name'] ?? $activity['username'] ?? 'Unknown';
            if (!isset($summary['users'][$user])) {
                $summary['users'][$user] = 0;
            }
            $summary['users'][$user]++;
            
            // Module breakdown
            $module = $activity['module'] ?? 'Unknown';
            if (!isset($summary['modules'][$module])) {
                $summary['modules'][$module] = 0;
            }
            $summary['modules'][$module]++;
            
            // Status breakdown
            $status = $activity['status'] ?? 'Unknown';
            if (!isset($summary['status_breakdown'][$status])) {
                $summary['status_breakdown'][$status] = 0;
            }
            $summary['status_breakdown'][$status]++;
            
            // Date breakdown (by day)
            $date = date('Y-m-d', strtotime($activity['created_at']));
            if (!isset($summary['date_breakdown'][$date])) {
                $summary['date_breakdown'][$date] = 0;
            }
            $summary['date_breakdown'][$date]++;
        }
        
        return $summary;
    }
    
    /**
     * Generate financial report
     */
    public static function generateFinancialReport($filters = []) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT 
                        t.transaction_id,
                        t.project_id,
                        t.amount,
                        t.transaction_type,
                        t.balance_update_direction,
                        t.description,
                        t.created_at,
                        p.project_name,
                        p.total_budget,
                        u.full_name as incharge_name
                    FROM Transactions t
                    LEFT JOIN Projects p ON t.project_id = p.project_id
                    LEFT JOIN ssmntUsers u ON p.project_incharge = u.id
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['project_id'])) {
                $sql .= " AND t.project_id = :project_id";
                $params['project_id'] = $filters['project_id'];
            }
            
            if (!empty($filters['transaction_type'])) {
                $sql .= " AND t.transaction_type = :transaction_type";
                $params['transaction_type'] = $filters['transaction_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND t.created_at >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND t.created_at <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            
            $sql .= " ORDER BY t.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary statistics
            $summary = self::calculateFinancialSummary($transactions);
            
            return [
                'transactions' => $transactions,
                'summary' => $summary,
                'filters' => $filters
            ];
            
        } catch (PDOException $e) {
            error_log("Financial report error: " . $e->getMessage());
            return ['transactions' => [], 'summary' => [], 'filters' => $filters];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Calculate financial summary statistics
     */
    private static function calculateFinancialSummary($transactions) {
        $summary = [
            'total_transactions' => count($transactions),
            'total_amount' => 0,
            'receipts' => 0,
            'vouchers' => 0,
            'transaction_types' => [],
            'project_breakdown' => [],
            'date_breakdown' => []
        ];
        
        if (empty($transactions)) {
            return $summary;
        }
        
        foreach ($transactions as $transaction) {
            $amount = $transaction['amount'] ?? 0;
            $summary['total_amount'] += $amount;
            
            // Receipt/Voucher breakdown
            $direction = $transaction['balance_update_direction'] ?? '';
            if ($direction === 'receipt') {
                $summary['receipts'] += $amount;
            } elseif ($direction === 'voucher') {
                $summary['vouchers'] += $amount;
            }
            
            // Transaction type breakdown
            $type = $transaction['transaction_type'] ?? 'Unknown';
            if (!isset($summary['transaction_types'][$type])) {
                $summary['transaction_types'][$type] = 0;
            }
            $summary['transaction_types'][$type] += $amount;
            
            // Project breakdown
            $project = $transaction['project_name'] ?? 'Unknown';
            if (!isset($summary['project_breakdown'][$project])) {
                $summary['project_breakdown'][$project] = 0;
            }
            $summary['project_breakdown'][$project] += $amount;
            
            // Date breakdown (by month)
            $date = date('Y-m', strtotime($transaction['created_at']));
            if (!isset($summary['date_breakdown'][$date])) {
                $summary['date_breakdown'][$date] = 0;
            }
            $summary['date_breakdown'][$date] += $amount;
        }
        
        return $summary;
    }
    
    /**
     * Export report to CSV
     */
    public static function exportToCSV($data, $filename) {
        if (empty($data)) {
            return false;
        }
        
        $headers = array_keys($data[0]);
        $csv = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($csv, $headers);
        
        // Add data
        foreach ($data as $row) {
            fputcsv($csv, $row);
        }
        
        rewind($csv);
        $csvData = stream_get_contents($csv);
        fclose($csv);
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csvData));
        
        echo $csvData;
        return true;
    }
    
    /**
     * Generate PDF report (placeholder for future implementation)
     */
    public static function generatePDF($reportData, $reportType) {
        // This would integrate with a PDF library like TCPDF or mPDF
        // For now, return a placeholder
        return [
            'status' => 'not_implemented',
            'message' => 'PDF generation will be implemented in future versions'
        ];
    }
}
?>
