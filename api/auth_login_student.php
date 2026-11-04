<?php
/**
 * Student Authentication API Endpoint
 * IIIT Kota Hostel Management System
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, null, 'Method not allowed', 405);
}

// Verify CSRF
csrf_verify();

$input     = request_data();
$collegeId = trim((string)($input['college_id'] ?? ''));
$password  = (string)($input['password'] ?? '');
$ip        = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

if (empty($collegeId) || empty($password)) {
    json_out(false, null, 'Please provide both College ID and password.', 400);
}

$pdo = db();

// ------------------------------------------------------------------------------
// Rate Limiting: 5 failed attempts per identifier + IP within 15 minutes
// ------------------------------------------------------------------------------
$rateStmt = $pdo->prepare('
    SELECT COUNT(*) AS failure_count
    FROM login_attempts
    WHERE identifier = :identifier
      AND ip = :ip
      AND attempted_at > (NOW() - INTERVAL 15 MINUTE)
');
$rateStmt->execute([
    ':identifier' => $collegeId,
    ':ip'         => $ip,
]);
$failureCount = (int)$rateStmt->fetchColumn();

if ($failureCount >= 5) {
    json_out(false, null, 'Account locked out due to multiple failed login attempts. Please try again after 15 minutes.', 429);
}

// ------------------------------------------------------------------------------
// Student Credential Verification
// ------------------------------------------------------------------------------
$userStmt = $pdo->prepare('
    SELECT id, college_id, password_hash, gender, hostel_type, branch, room_no, status
    FROM students
    WHERE college_id = :college_id
    LIMIT 1
');
$userStmt->execute([':college_id' => $collegeId]);
$student = $userStmt->fetch();

if (!$student || !password_verify($password, (string)$student['password_hash'])) {
    // Record failed attempt for rate limiting
    $logStmt = $pdo->prepare('
        INSERT INTO login_attempts (identifier, ip, attempted_at)
        VALUES (:identifier, :ip, NOW())
    ');
    $logStmt->execute([
        ':identifier' => $collegeId,
        ':ip'         => $ip,
    ]);

    json_out(false, null, 'Invalid College ID or password.', 401);
}

// ------------------------------------------------------------------------------
// Approval Status Checks
// ------------------------------------------------------------------------------
if ($student['status'] === 'pending') {
    json_out(false, null, 'Your registration is awaiting approval by the hostel warden. Please check back later.', 403);
}

if ($student['status'] === 'rejected') {
    json_out(false, null, 'Your registration request has been rejected. Please contact the hostel administration.', 403);
}

if ($student['status'] !== 'approved') {
    json_out(false, null, 'Your account is inactive.', 403);
}

// ------------------------------------------------------------------------------
// Login Success: Session Initialization & Rate Limit Reset
// ------------------------------------------------------------------------------
// Clear failed attempts for this identifier and IP
$clearStmt = $pdo->prepare('
    DELETE FROM login_attempts
    WHERE identifier = :identifier AND ip = :ip
');
$clearStmt->execute([
    ':identifier' => $collegeId,
    ':ip'         => $ip,
]);

// Regenerate session ID to mitigate session fixation attacks
session_regenerate_id(true);

$_SESSION['user_id']     = (int)$student['id'];
$_SESSION['college_id']  = $student['college_id'];
$_SESSION['role']        = 'student';
$_SESSION['hostel_type'] = $student['hostel_type'];
$_SESSION['branch']      = $student['branch'];
$_SESSION['room_no']     = $student['room_no'];
$_SESSION['gender']      = $student['gender'];
$_SESSION['last_activity'] = time();

json_out(true, [
    'message'  => 'Login successful',
    'redirect' => 'student.php',
]);

