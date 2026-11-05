<?php
/**
 * Resolve Lost & Found Listing API Endpoint
 * IIIT Kota Hostel Management System
 *
 * Authorisation rules:
 *   - Students: may resolve only their own active listing in their hostel.
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

if ($sessionHostel === '') {
    json_out(false, null, 'Hostel assignment missing from session.', 403);
}

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
    if ($isStudent) {
        $stmt = $pdo->prepare('
            UPDATE lost_found
            SET status = "resolved", resolved_at = NOW(), resolved_by_role = "student"
            WHERE id = :id AND student_id = :student_id AND hostel_type = :hostel_type AND status = "active"
        ');
        $stmt->execute([
            ':id'          => $postId,
            ':student_id'  => $sessionUserId,
            ':hostel_type' => $sessionHostel,
        ]);
    } else {
        // Staff: role comes strictly from session ('warden' or 'caretaker')
        $stmt = $pdo->prepare('
            UPDATE lost_found
            SET status = "resolved", resolved_at = NOW(), resolved_by_role = :role
            WHERE id = :id AND hostel_type = :hostel_type AND status = "active"
        ');
        $stmt->execute([
            ':id'          => $postId,
            ':hostel_type' => $sessionHostel,
            ':role'        => $sessionRole,
        ]);
    }

    if ($stmt->rowCount() === 0) {
        json_out(false, null, 'Not found, already resolved, or not in your hostel.', 400);
    }

    $fetchStmt = $pdo->prepare('SELECT resolved_at, resolved_by_role FROM lost_found WHERE id = :id');
    $fetchStmt->execute([':id' => $postId]);
    $updated = $fetchStmt->fetch();

    json_out(true, [
        'message'          => 'Item marked as resolved successfully.',
        'id'               => $postId,
        'status'           => 'resolved',
        'resolved_at'      => $updated ? (string)$updated['resolved_at'] : null,
        'resolved_by_role' => $updated ? (string)$updated['resolved_by_role'] : ($isStudent ? 'student' : $sessionRole),
    ]);
} catch (PDOException $e) {
    error_log('Lost and Found Resolve DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to update listing.', 500);
}
