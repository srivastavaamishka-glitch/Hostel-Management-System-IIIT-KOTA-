<?php
/**
 * Resolve Lost & Found Listing API Endpoint
 * IIIT Kota Hostel Management System
 *
 * Authorisation rules:
 *   - Students: may resolve only their own listing.
 *   - Staff (warden / caretaker): may resolve any active listing in their hostel.
 * Cross-hostel operations are always rejected.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, null, 'Method not allowed', 405);
}

// Require authentication (student OR staff) and CSRF token
$auth = requireAuth();
csrf_verify();

$sessionRole   = (string)$_SESSION['role'];
$sessionUserId = (int)$_SESSION['user_id'];
$sessionHostel = (string)($_SESSION['hostel_type'] ?? '');

// Determine caller type
$isStaff   = in_array($sessionRole, ['warden', 'caretaker'], true);
$isStudent = ($sessionRole === 'student');

if (!$isStaff && !$isStudent) {
    json_out(false, null, 'Forbidden: insufficient permissions.', 403);
}

$input  = request_data();
$postId = isset($input['id']) && is_numeric($input['id']) ? (int)$input['id'] : 0;
if ($postId <= 0) {
    json_out(false, null, 'Invalid listing ID provided.', 400);
}

$pdo = db();

try {
    $stmt = $pdo->prepare('
        SELECT id, student_id, hostel_type, status
        FROM lost_found
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => $postId]);
    $item = $stmt->fetch();

    if (!$item) {
        json_out(false, null, 'Listing not found.', 404);
    }

    // Cross-hostel guard (applies to both students and staff)
    if ($item['hostel_type'] !== $sessionHostel) {
        json_out(false, null, 'Forbidden: Cross-hostel operation denied.', 403);
    }

    // Students may only resolve their own listing
    if ($isStudent && (int)$item['student_id'] !== $sessionUserId) {
        json_out(false, null, 'Forbidden: You can only resolve your own listings.', 403);
    }

    if ($item['status'] === 'resolved') {
        json_out(true, ['message' => 'Listing is already resolved.', 'id' => $postId]);
    }

    // Update status to resolved
    $updateStmt = $pdo->prepare('UPDATE lost_found SET status = "resolved" WHERE id = :id');
    $updateStmt->execute([':id' => $postId]);

    json_out(true, [
        'message' => 'Item marked as resolved successfully.',
        'id'      => $postId,
        'status'  => 'resolved',
    ]);
} catch (PDOException $e) {
    error_log('Lost and Found Resolve DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to update listing.', 500);
}
