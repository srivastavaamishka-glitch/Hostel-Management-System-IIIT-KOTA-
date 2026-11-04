<?php
/**
 * Notices List API Endpoint
 * IIIT Kota Hostel Management System
 * Fetches notices strictly filtered by session hostel_type.
 * Supports ?since=ID for lightweight delta polling.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// Authenticate session (student or staff)
$auth = requireAuth();

$sessionHostel = (string)($_SESSION['hostel_type'] ?? '');
if ($sessionHostel === '') {
    json_out(false, null, 'Hostel assignment missing from session', 403);
}

$pdo = db();

$sinceId = isset($_GET['since']) && is_numeric($_GET['since']) ? (int)$_GET['since'] : 0;

try {
    if ($sinceId > 0) {
        $stmt = $pdo->prepare('
            SELECT n.id, n.message, n.created_at, s.name AS posted_by_name, s.role AS posted_by_role
            FROM notices n
            JOIN staff s ON n.posted_by = s.id
            WHERE n.hostel_type = :session_hostel
              AND n.id > :since_id
            ORDER BY n.id ASC
        ');
        $stmt->execute([
            ':session_hostel' => $sessionHostel,
            ':since_id'       => $sinceId,
        ]);
    } else {
        $stmt = $pdo->prepare('
            SELECT n.id, n.message, n.created_at, s.name AS posted_by_name, s.role AS posted_by_role
            FROM notices n
            JOIN staff s ON n.posted_by = s.id
            WHERE n.hostel_type = :session_hostel
            ORDER BY n.id DESC
            LIMIT 50
        ');
        $stmt->execute([
            ':session_hostel' => $sessionHostel,
        ]);
    }

    $notices = $stmt->fetchAll();
    json_out(true, $notices);
} catch (PDOException $e) {
    error_log('Notice List DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to retrieve notices', 500);
}

