<?php

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/models/ReportHelper.php';
require_once ROOT_PATH . '/models/ActivityLog.php';

class ReportsApiController extends Controller
{
    private $reportHelper;

    public function __construct()
    {
        parent::__construct();
        $this->reportHelper = new ReportHelper();
    }

    /**
     * GET /api/reports/inventory - Dashboard summary
     */
    public function inventory()
    {
        try {
            $summary = $this->reportHelper->getInventorySummary();
            
            ActivityLog::logActivity('VIEW', 'report', null, 'Viewed inventory summary report');
            
            Response::success('Inventory summary retrieved successfully', $summary);
        } catch (Exception $e) {
            Response::error('Failed to get inventory summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/transactions - Transaction summary
     */
    public function transactions()
    {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
            $endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today
            
            // Validate dates
            if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
                Response::error('Invalid date format. Use YYYY-MM-DD', 400);
                return;
            }

            $summary = $this->reportHelper->getTransactionSummary($startDate, $endDate);
            
            ActivityLog::logActivity('VIEW', 'report', null, "Viewed transaction summary report ({$startDate} to {$endDate})");
            
            Response::success('Transaction summary retrieved successfully', [
                'summary' => $summary,
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);
        } catch (Exception $e) {
            Response::error('Failed to get transaction summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/low-stock - Low stock alert
     */
    public function lowStock()
    {
        try {
            $materials = $this->reportHelper->getLowStockMaterials();
            
            ActivityLog::logActivity('VIEW', 'report', null, 'Viewed low stock report');
            
            Response::success('Low stock materials retrieved successfully', [
                'materials' => $materials,
                'count' => count($materials)
            ]);
        } catch (Exception $e) {
            Response::error('Failed to get low stock materials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/material-trend/{id} - Material trend
     */
    public function materialTrend($materialId)
    {
        try {
            $days = $_GET['days'] ?? 30;
            
            if (!is_numeric($materialId) || !is_numeric($days)) {
                Response::error('Invalid parameters', 400);
                return;
            }

            $trendData = $this->reportHelper->getMaterialTrend($materialId, $days);
            
            ActivityLog::logActivity('VIEW', 'report', $materialId, "Viewed material trend report for material ID {$materialId}");
            
            Response::success('Material trend data retrieved successfully', [
                'material_id' => (int)$materialId,
                'days' => (int)$days,
                'trend_data' => $trendData
            ]);
        } catch (Exception $e) {
            Response::error('Failed to get material trend: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/category-distribution - Category pie chart
     */
    public function categoryDistribution()
    {
        try {
            $distribution = $this->reportHelper->getCategoryDistribution();
            
            ActivityLog::logActivity('VIEW', 'report', null, 'Viewed category distribution report');
            
            Response::success('Category distribution retrieved successfully', $distribution);
        } catch (Exception $e) {
            Response::error('Failed to get category distribution: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/supplier-performance - Supplier ranking
     */
    public function supplierPerformance()
    {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
                Response::error('Invalid date format. Use YYYY-MM-DD', 400);
                return;
            }

            $performance = $this->reportHelper->getSupplierPerformance($startDate, $endDate);
            
            ActivityLog::logActivity('VIEW', 'report', null, "Viewed supplier performance report ({$startDate} to {$endDate})");
            
            Response::success('Supplier performance retrieved successfully', [
                'performance' => $performance,
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);
        } catch (Exception $e) {
            Response::error('Failed to get supplier performance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/stock-movement/{id} - Stock movement detail
     */
    public function stockMovement($materialId)
    {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            if (!is_numeric($materialId) || !$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
                Response::error('Invalid parameters', 400);
                return;
            }

            $movements = $this->reportHelper->getStockMovement($materialId, $startDate, $endDate);
            
            ActivityLog::logActivity('VIEW', 'report', $materialId, "Viewed stock movement report for material ID {$materialId}");
            
            Response::success('Stock movement retrieved successfully', [
                'material_id' => (int)$materialId,
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'movements' => $movements
            ]);
        } catch (Exception $e) {
            Response::error('Failed to get stock movement: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/top-materials - Top materials
     */
    public function topMaterials()
    {
        try {
            $type = $_GET['type'] ?? 'value'; // value, quantity, usage
            $limit = $_GET['limit'] ?? 10;
            
            if (!in_array($type, ['value', 'quantity', 'usage']) || !is_numeric($limit)) {
                Response::error('Invalid parameters', 400);
                return;
            }

            $materials = $this->reportHelper->getTopMaterials($type, $limit);
            
            ActivityLog::logActivity('VIEW', 'report', null, "Viewed top materials report (type: {$type}, limit: {$limit})");
            
            Response::success('Top materials retrieved successfully', [
                'type' => $type,
                'limit' => (int)$limit,
                'materials' => $materials
            ]);
        } catch (Exception $e) {
            Response::error('Failed to get top materials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/reports/stock-value-by-category - Stock value by category
     */
    public function stockValueByCategory()
    {
        try {
            $data = $this->reportHelper->getStockValueByCategory();
            
            ActivityLog::logActivity('VIEW', 'report', null, 'Viewed stock value by category report');
            
            Response::success('Stock value by category retrieved successfully', $data);
        } catch (Exception $e) {
            Response::error('Failed to get stock value by category: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate date format
     */
    private function isValidDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}