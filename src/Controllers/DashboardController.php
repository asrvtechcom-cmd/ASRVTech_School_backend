<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\StatsModel;

class DashboardController
{
    public function __construct(private PDO $db)
    {
    }

    public function getStats(): void
    {
        AuthMiddleware::requireRole(['admin']);
        
        $stats = (new StatsModel($this->db))->getAdminStats();
        Response::json(true, 'Stats fetched successfully', $stats);
    }

    public function getRevenueChart(): void
    {
        AuthMiddleware::requireRole(['admin']);
        
        $chartData = (new StatsModel($this->db))->getRevenueByMonth();
        Response::json(true, 'Revenue chart fetched successfully', $chartData);
    }

    public function getAttendanceReport(): void
    {
        AuthMiddleware::requireRole(['admin']);
        
        $data = (new StatsModel($this->db))->getAttendanceTrends();
        Response::json(true, 'Attendance report fetched successfully', $data);
    }

    public function getFeesSummary(): void
    {
        AuthMiddleware::requireRole(['admin']);
        
        $data = (new StatsModel($this->db))->getFeeStatusSummary();
        Response::json(true, 'Fees summary fetched successfully', $data);
    }

    public function getAcademicReport(): void
    {
        AuthMiddleware::requireRole(['admin']);
        
        $data = (new StatsModel($this->db))->getGradeDistribution();
        Response::json(true, 'Academic report fetched successfully', $data);
    }

    public function getFullDashboard(): void
    {
        AuthMiddleware::requireRole(['admin']);
        
        // Prevent any intermediate caching
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $statsModel = new StatsModel($this->db);
        
        $data = [
            'overview' => $statsModel->getAdminStats(),
            'revenue_chart' => $statsModel->getRevenueByMonth(),
            'academic_report' => $statsModel->getGradeDistribution(),
        ];
        
        Response::json(true, 'Full dashboard data fetched successfully', $data);
    }
}


