<?php
/**
 * Reopen Lost & Found Listing API Endpoint
 * IIIT Kota Hostel Management System
 *
 * Authorisation rules:
 *   - Warden only: may reopen a resolved listing in their hostel.
 *   - Caretakers and students receive 403 Forbidden.
 * Cross-hostel operations are always rejected.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, null, 'Method not allowed', 405);
}

// Strictly require warden role and valid CSRF token
$auth = requireAuth('warden');
csrf_verify();

$sessionHostel = (string)($_SESSION['hostel_type'] ?? '');
if ($sessionHostel === '') {
    json_out(false, null, 'Hostel assignment missing from session.', 403);
}

$input  = request_data();
$postId = isset($input['id']) && is_numeric($input['id']) ? (int)$input['id'] : 0;
if ($postId <= 0) {
    json_out(false, null, 'Invalid listing ID provided.', 400);
}

$pdo = db();

try {
    $stmt = $pdo->prepare('
        UPDATE lost_found
        SET status = "active", resolved_at = NULL, resolved_by_role = NULL
        WHERE id = :id AND hostel_type = :hostel_type AND status = "resolved"
    ');
    $stmt->execute([
        ':id'          => $postId,
        ':hostel_type' => $sessionHostel,
    ]);

    if ($stmt->rowCount() === 0) {
        json_out(false, null, 'Not found, already active, or not in your hostel.', 400);
    }

    json_out(true, [
        'message' => 'Item reopened successfully.',
        'id'      => $postId,
        'status'  => 'active',
    ]);
} catch (PDOException $e) {
    error_log('Lost and Found Reopen DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to reopen listing.', 500);
}
