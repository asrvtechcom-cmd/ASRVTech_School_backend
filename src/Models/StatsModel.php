<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

class StatsModel
{
    public function __construct(private PDO $db)
    {
    }

    private function countTable(string $table): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM `{$table}`");
            $value = $stmt?->fetchColumn();
            return $value !== false ? (int) $value : 0;
        } catch (PDOException) {
            return 0;
        }
    }

    private function countUsersByRole(string $role): int
    {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
            $stmt->execute(['role' => $role]);
            $value = $stmt->fetchColumn();
            return $value !== false ? (int) $value : 0;
        } catch (PDOException) {
            return 0;
        }
    }

    public function getAdminStats(): array
    {
        $studentsFromStudentsTable = $this->countTable('students');
        $teachersFromTeachersTable = $this->countTable('teachers');

        // Fallback for installations where user role rows exist but profile tables are incomplete.
        $totalStudents = max($studentsFromStudentsTable, $this->countUsersByRole('student'));
        $totalTeachers = max($teachersFromTeachersTable, $this->countUsersByRole('teacher'));

        $activeClasses = $this->countTable('classes');

        $totalRevenue = 0.0;
        $pendingFees = 0.0;
        try {
            $paid = $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM fees WHERE status = 'paid'")->fetchColumn();
            $pending = $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM fees WHERE status IN ('pending', 'unpaid', 'overdue', 'partial')")->fetchColumn();
            $totalRevenue = (float) ($paid ?? 0);
            $pendingFees = (float) ($pending ?? 0);
        } catch (PDOException) {
            $totalRevenue = 0.0;
            $pendingFees = 0.0;
        }

        $recentStudents = [];
        try {
            $stmt = $this->db->query('SELECT name, created_at FROM students ORDER BY id DESC LIMIT 5');
            $recentStudents = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (PDOException) {
            $recentStudents = [];
        }

        return [
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'active_classes' => $activeClasses,
            'total_revenue' => $totalRevenue,
            'pending_fees' => $pendingFees,
            'recent_students' => $recentStudents,
        ];
    }

    public function getRevenueByMonth(): array
    {
        try {
            $sql = "SELECT 
                        MONTHNAME(created_at) as month, 
                        SUM(amount) as total 
                    FROM fees 
                    WHERE status = 'paid' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    GROUP BY MONTH(created_at)
                    ORDER BY MONTH(created_at) ASC";

            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }

    public function getAttendanceTrends(): array
    {
        try {
            $sql = "SELECT 
                        c.name as class_name,
                        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                        COUNT(a.id) as total_attendance
                    FROM classes c
                    LEFT JOIN students s ON s.class_id = c.id
                    LEFT JOIN attendance a ON a.student_id = s.id
                    WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) OR a.date IS NULL
                    GROUP BY c.id
                    ORDER BY c.name ASC";

            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }

    public function getFeeStatusSummary(): array
    {
        try {
            $sql = "SELECT 
                        status, 
                        SUM(amount) as total_amount,
                        COUNT(*) as record_count
                    FROM fees 
                    GROUP BY status";

            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }

    public function getGradeDistribution(): array
    {
        try {
            $sql = "SELECT 
                        grade, 
                        COUNT(*) as count 
                    FROM grades 
                    GROUP BY grade 
                    ORDER BY grade ASC";

            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }
}
