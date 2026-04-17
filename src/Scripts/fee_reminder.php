<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Utils\Helper;
use App\Models\Fee;
use App\Services\NotificationService;
use App\Config\Database;

// 1. Load Env
Helper::loadEnvFile(__DIR__ . '/../../.env');

// 2. Bootstrap DB
$db = (new Database())->connect();

echo "[" . date('Y-m-d H:i:s') . "] Starting Fee Reminder Cron Script...\n";

try {
    $feeModel = new Fee($db);
    $notificationService = new NotificationService($db);

    // 3. Mark Overdue
    $overdueCount = $feeModel->markOverdue();
    echo "Marked $overdueCount fees as overdue.\n";

    // 4. Find all pending/overdue fees to notify
    $stmt = $db->query('
        SELECT f.*, s.name AS student_name, u.email AS parent_email
        FROM fees f
        INNER JOIN students s ON s.id = f.student_id
        INNER JOIN users u ON u.id = s.parent_id
        WHERE f.status IN ("pending", "overdue")
    ');
    $feesToNotify = $stmt->fetchAll();

    foreach ($feesToNotify as $fee) {
        echo "Sending reminder for Student: {$fee['student_name']} (Amount: {$fee['amount']})\n";
        
        $success = $notificationService->sendFeeAlert(
            (int) $fee['student_id'],
            (string) $fee['amount'],
            (string) ($fee['due_date'] ?? 'N/A')
        );

        if ($success) {
            echo "   -> Success\n";
        } else {
            echo "   -> Failed\n";
        }
    }

    echo "[" . date('Y-m-d H:i:s') . "] Fee Reminder Cron Script Finished.\n";

} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
