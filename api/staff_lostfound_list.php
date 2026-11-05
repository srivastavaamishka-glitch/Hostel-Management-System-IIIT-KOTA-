<?php
/**
 * Staff Lost & Found Directory List API Endpoint
 * IIIT Kota Hostel Management System
 * Displays items strictly scoped to the authenticated staff member's hostel.
 * Requires warden or caretaker role.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// Only staff (warden or caretaker) may access this endpoint
$auth = requireAuth('staff');

$sessionHostel = (string)($_SESSION['hostel_type'] ?? '');
if ($sessionHostel === '') {
    json_out(false, null, 'Hostel assignment missing from session', 403);
}

$pdo = db();

try {
    $stmt = $pdo->prepare('
        SELECT lf.id, lf.type, lf.item_description, lf.image_path, lf.date_logged, lf.status,
               lf.resolved_at, lf.resolved_by_role,
               s.college_id, s.room_no
        FROM lost_found lf
        JOIN students s ON lf.student_id = s.id
        WHERE lf.hostel_type = :session_hostel
        ORDER BY lf.status ASC, lf.id DESC
    ');
    $stmt->execute([':session_hostel' => $sessionHostel]);
    $items = $stmt->fetchAll();

    $data = array_map(function ($item) {
        return [
            'id'               => (int)$item['id'],
            'type'             => (string)$item['type'],
            'item_description' => (string)$item['item_description'],
            'has_image'        => !empty($item['image_path']),
            'date_logged'      => (string)$item['date_logged'],
            'status'           => (string)$item['status'],
            'resolved_at'      => $item['resolved_at'] !== null ? (string)$item['resolved_at'] : null,
            'resolved_by_role' => $item['resolved_by_role'] !== null ? (string)$item['resolved_by_role'] : null,
            'college_id'       => (string)$item['college_id'],
            'room_no'          => (string)($item['room_no'] ?? ''),
        ];
    }, $items);

    json_out(true, $data);
} catch (PDOException $e) {
    error_log('Staff Lost and Found List DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to retrieve lost and found directory.', 500);
}

