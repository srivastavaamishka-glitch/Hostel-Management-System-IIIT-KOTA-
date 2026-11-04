<?php
/**
 * Student Registration Approval / Rejection API Endpoint
 * IIIT Kota Hostel Management System
 * Approves or rejects pending student registrations strictly within staff hostel.
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

$input     = request_data();
$studentId = isset($input['student_id']) && is_numeric($input['student_id']) ? (int)$input['student_id'] : 0;
if ($studentId <= 0 && isset($input['id']) && is_numeric($input['id'])) {
    $studentId = (int)$input['id'];
}

$rawDecision = strtolower(trim((string)($input['decision'] ?? $input['action'] ?? '')));
$decision = match ($rawDecision) {
    'approve', 'approved' => 'approved',
    'reject', 'rejected'  => 'rejected',
    default               => null,
};

if ($studentId <= 0) {
    json_out(false, null, 'Invalid student ID specified.', 400);
}

if ($decision === null) {
    json_out(false, null, 'Invalid decision. Must be "approved" or "rejected".', 400);
}

$pdo = db();

try {
    $stmt = $pdo->prepare('
        SELECT id, college_id, hostel_type, status
        FROM students
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => $studentId]);
    $student = $stmt->fetch();

    if (!$student) {
        json_out(false, null, 'Student record not found.', 404);
    }

    // MANDATORY Security check: target record hostel_type MUST equal session hostel_type
    if ($student['hostel_type'] !== $sessionHostel) {
        json_out(false, null, 'Forbidden: Cross-hostel student approval denied.', 403);
    }

    $updateStmt = $pdo->prepare('
        UPDATE students
        SET status = :status
        WHERE id = :id
    ');
    $updateStmt->execute([
        ':status' => $decision,
        ':id'     => $studentId,
    ]);

    json_out(true, [
        'id'       => $studentId,
        'status'   => $decision,
        'message'  => 'Student registration ' . ($decision === 'approved' ? 'approved' : 'rejected') . ' successfully.',
    ]);
} catch (PDOException $e) {
    error_log('Student Decide DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to update student registration status', 500);
}

