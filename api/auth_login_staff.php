<?php
/**
 * Staff Authentication API Endpoint
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

$input    = request_data();
$username = trim((string)($input['staff_username'] ?? ''));
$code     = (string)($input['code'] ?? '');
$ip       = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

if (empty($username) || empty($code)) {
    json_out(false, null, 'Please provide both staff username and access code.', 400);
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
    ':identifier' => $username,
    ':ip'         => $ip,
]);
$failureCount = (int)$rateStmt->fetchColumn();

if ($failureCount >= 5) {
    json_out(false, null, 'Account locked out due to multiple failed login attempts. Please try again after 15 minutes.', 429);
}

// ------------------------------------------------------------------------------
// Staff Credential Verification
// ------------------------------------------------------------------------------
$staffStmt = $pdo->prepare('
    SELECT id, staff_username, name, role, hostel_type, code_hash, contact_no
    FROM staff
    WHERE staff_username = :username
    LIMIT 1
');
$staffStmt->execute([':username' => $username]);
$staff = $staffStmt->fetch();

if (!$staff || !password_verify($code, (string)$staff['code_hash'])) {
    // Record failed attempt
    $logStmt = $pdo->prepare('
        INSERT INTO login_attempts (identifier, ip, attempted_at)
        VALUES (:identifier, :ip, NOW())
    ');
    $logStmt->execute([
        ':identifier' => $username,
        ':ip'         => $ip,
    ]);

    json_out(false, null, 'Invalid staff username or access code.', 401);
}

// ------------------------------------------------------------------------------
// Login Success: Session Initialization & Rate Limit Reset
// ------------------------------------------------------------------------------
// Clear failed attempts for this staff member and IP
$clearStmt = $pdo->prepare('
    DELETE FROM login_attempts
    WHERE identifier = :identifier AND ip = :ip
');
$clearStmt->execute([
    ':identifier' => $username,
    ':ip'         => $ip,
]);

// Regenerate session ID to prevent fixation
session_regenerate_id(true);

$_SESSION['user_id']        = (int)$staff['id'];
$_SESSION['staff_username'] = $staff['staff_username'];
$_SESSION['name']           = $staff['name'];
$_SESSION['role']           = $staff['role']; // 'warden' or 'caretaker'
$_SESSION['hostel_type']    = $staff['hostel_type']; // 'boys' or 'girls'
$_SESSION['contact_no']     = $staff['contact_no'];
$_SESSION['last_activity']  = time();

json_out(true, [
    'message'  => 'Staff login successful',
    'redirect' => 'staff.php',
]);

