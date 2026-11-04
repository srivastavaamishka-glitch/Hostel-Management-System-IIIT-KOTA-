<?php
/**
 * Update Complaint Status API Endpoint
 * IIIT Kota Hostel Management System
 * Allows staff to update status of complaints strictly within their own assigned hostel.
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

$input       = request_data();
$complaintId = isset($input['id']) && is_numeric($input['id']) ? (int)$input['id'] : 0;
if ($complaintId <= 0 && isset($input['complaint_id']) && is_numeric($input['complaint_id'])) {
    $complaintId = (int)$input['complaint_id'];
}

$newStatus = strtolower(trim((string)($input['status'] ?? '')));
$validStatuses = ['pending', 'in-progress', 'resolved'];

if ($complaintId <= 0) {
    json_out(false, null, 'Invalid complaint ID specified.', 400);
}

if (!in_array($newStatus, $validStatuses, true)) {
    json_out(false, null, 'Invalid status specified. Must be pending, in-progress, or resolved.', 400);
}

$pdo = db();

try {
    $stmt = $pdo->prepare('
        SELECT id, hostel_type, status
        FROM complaints
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => $complaintId]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        json_out(false, null, 'Complaint record not found.', 404);
    }

    // MANDATORY Security check: complaint hostel_type MUST equal session hostel_type
    if ($complaint['hostel_type'] !== $sessionHostel) {
        json_out(false, null, 'Forbidden: Cross-hostel complaint modification denied.', 403);
    }

    $updateStmt = $pdo->prepare('
        UPDATE complaints
        SET status = :status, updated_at = NOW()
        WHERE id = :id
    ');
    $updateStmt->execute([
        ':status' => $newStatus,
        ':id'     => $complaintId,
    ]);

    json_out(true, [
        'id'      => $complaintId,
        'status'  => $newStatus,
        'message' => 'Complaint status updated to ' . ucfirst($newStatus) . ' successfully.',
    ]);
} catch (PDOException $e) {
    error_log('Complaint Status Update DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to update complaint status', 500);
}

