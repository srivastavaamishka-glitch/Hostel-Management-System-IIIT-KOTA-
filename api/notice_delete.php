<?php
/**
 * Delete Official Notice API Endpoint
 * IIIT Kota Hostel Management System
 * Allows staff to delete notices strictly within their own assigned hostel.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, null, 'Method not allowed', 405);
}

// Staff authentication & CSRF verification
$auth = requireAuth('staff');
csrf_verify();

$sessionHostel = (string)($_SESSION['hostel_type'] ?? '');
if ($sessionHostel === '') {
    json_out(false, null, 'Hostel assignment missing from session', 403);
}

$input    = request_data();
$noticeId = isset($input['id']) && is_numeric($input['id']) ? (int)$input['id'] : 0;
if ($noticeId <= 0 && isset($input['notice_id']) && is_numeric($input['notice_id'])) {
    $noticeId = (int)$input['notice_id'];
}

if ($noticeId <= 0) {
    json_out(false, null, 'Invalid notice ID specified.', 400);
}

$pdo = db();

try {
    $stmt = $pdo->prepare('
        SELECT id, hostel_type, posted_by
        FROM notices
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => $noticeId]);
    $notice = $stmt->fetch();

    if (!$notice) {
        json_out(false, null, 'Notice not found.', 404);
    }

    // MANDATORY Security check: notice hostel_type MUST equal session hostel_type
    if ($notice['hostel_type'] !== $sessionHostel) {
        json_out(false, null, 'Forbidden: Cross-hostel notice operation denied.', 403);
    }

    $deleteStmt = $pdo->prepare('DELETE FROM notices WHERE id = :id');
    $deleteStmt->execute([':id' => $noticeId]);

    json_out(true, [
        'id'      => $noticeId,
        'message' => 'Notice removed successfully.',
    ]);
} catch (PDOException $e) {
    error_log('Notice Delete DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to delete notice', 500);
}

