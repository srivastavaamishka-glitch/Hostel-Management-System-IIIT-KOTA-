<?php
/**
 * Resolve Lost & Found Listing API Endpoint
 * IIIT Kota Hostel Management System
 * Enforces strict server-side poster ownership verification before allowing resolution.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, null, 'Method not allowed', 405);
}

// Student authentication & CSRF verification
$auth = requireAuth('student');
csrf_verify();

$studentId     = (int)$_SESSION['user_id'];
$sessionHostel = (string)$_SESSION['hostel_type'];

$input = request_data();
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

    // Strict Ownership Verification (Must be poster's own listing)
    if ((int)$item['student_id'] !== $studentId) {
        json_out(false, null, 'Forbidden: You can only resolve your own listings.', 403);
    }

    // Cross-hostel guard
    if ($item['hostel_type'] !== $sessionHostel) {
        json_out(false, null, 'Forbidden: Cross-hostel operation denied.', 403);
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

