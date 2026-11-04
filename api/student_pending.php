<?php
/**
 * Pending Student Registrations API Endpoint
 * IIIT Kota Hostel Management System
 * Fetches pending students strictly for the authenticated staff member's hostel.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_out(false, null, 'Method not allowed', 405);
}

// Staff authentication (warden or caretaker)
$auth = requireAuth('staff');

$sessionHostel = (string)($_SESSION['hostel_type'] ?? '');
if ($sessionHostel === '') {
    json_out(false, null, 'Hostel assignment missing from session', 403);
}

$pdo = db();

try {
    $stmt = $pdo->prepare('
        SELECT id, college_id, gender, hostel_type, branch, room_no, created_at
        FROM students
        WHERE hostel_type = :session_hostel
          AND status = "pending"
        ORDER BY id ASC
    ');
    $stmt->execute([':session_hostel' => $sessionHostel]);
    $pendingStudents = $stmt->fetchAll();

    $data = array_map(function ($s) {
        return [
            'id'          => (int)$s['id'],
            'college_id'  => (string)$s['college_id'],
            'gender'      => (string)$s['gender'],
            'hostel_type' => (string)$s['hostel_type'],
            'branch'      => (string)$s['branch'],
            'room_no'     => (string)$s['room_no'],
            'created_at'  => (string)$s['created_at'],
        ];
    }, $pendingStudents);

    json_out(true, $data);
} catch (PDOException $e) {
    error_log('Pending Students DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to retrieve pending student registrations', 500);
}

