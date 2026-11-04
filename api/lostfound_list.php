<?php
/**
 * Lost & Found Directory List API Endpoint
 * IIIT Kota Hostel Management System
 * Displays items strictly scoped to the authenticated user's hostel.
 * Displays poster's college_id (joined from students) as contact info.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// Authenticate session (student or staff)
$auth = requireAuth();

$currentUserId = (int)$_SESSION['user_id'];
$sessionHostel = (string)($_SESSION['hostel_type'] ?? '');

if ($sessionHostel === '') {
    json_out(false, null, 'Hostel assignment missing from session', 403);
}

$pdo = db();

try {
    $stmt = $pdo->prepare('
        SELECT lf.id, lf.student_id, lf.type, lf.item_description, lf.image_path, lf.date_logged, lf.status,
               s.college_id AS contact_email, s.room_no AS contact_room
        FROM lost_found lf
        JOIN students s ON lf.student_id = s.id
        WHERE lf.hostel_type = :session_hostel
        ORDER BY lf.status ASC, lf.id DESC
    ');
    $stmt->execute([':session_hostel' => $sessionHostel]);
    $items = $stmt->fetchAll();

    $data = array_map(function ($item) use ($currentUserId) {
        return [
            'id'               => (int)$item['id'],
            'type'             => (string)$item['type'],
            'item_description' => (string)$item['item_description'],
            'has_image'        => !empty($item['image_path']),
            'date_logged'      => (string)$item['date_logged'],
            'status'           => (string)$item['status'],
            'contact_email'    => (string)$item['contact_email'],
            'contact_room'     => (string)$item['contact_room'],
            'is_owner'         => ((int)$item['student_id'] === $currentUserId),
        ];
    }, $items);

    json_out(true, $data);
} catch (PDOException $e) {
    error_log('Lost and Found List DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to retrieve lost and found directory.', 500);
}

