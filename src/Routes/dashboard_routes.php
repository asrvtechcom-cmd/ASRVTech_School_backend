<?php

declare(strict_types=1);

use App\Controllers\DashboardController;

return function (callable $addRoute, PDO $db) {
    $controller = new DashboardController($db);

    $addRoute('GET', '/stats', [$controller, 'getStats']);
    $addRoute('GET', '/dashboard/stats', [$controller, 'getStats']);
    $addRoute('GET', '/dashboard/full', [$controller, 'getFullDashboard']);
    $addRoute('GET', '/revenue-chart', [$controller, 'getRevenueChart']);
    $addRoute('GET', '/dashboard/revenue-chart', [$controller, 'getRevenueChart']);
    $addRoute('GET', '/attendance-report', [$controller, 'getAttendanceReport']);
    $addRoute('GET', '/dashboard/attendance-report', [$controller, 'getAttendanceReport']);
    $addRoute('GET', '/fees-summary', [$controller, 'getFeesSummary']);
    $addRoute('GET', '/dashboard/fees-summary', [$controller, 'getFeesSummary']);
    $addRoute('GET', '/academic-report', [$controller, 'getAcademicReport']);
    $addRoute('GET', '/dashboard/academic-report', [$controller, 'getAcademicReport']);
};
