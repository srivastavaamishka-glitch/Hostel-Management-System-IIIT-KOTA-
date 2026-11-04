<?php
/**
 * Create Official Notice Broadcast API Endpoint
 * IIIT Kota Hostel Management System
 * Allows staff to broadcast notices strictly scoped to their assigned hostel.
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

$staffId       = (int)$_SESSION['user_id'];
$sessionHostel = (string)($_SESSION['hostel_type'] ?? '');
if ($sessionHostel === '') {
    json_out(false, null, 'Hostel assignment missing from session', 403);
}

$input   = request_data();
$message = trim((string)($input['message'] ?? ''));

$fieldErrors = [];
if (empty($message)) {
    $fieldErrors['message'] = 'Please enter a notice message.';
} elseif (strlen($message) < 5) {
    $fieldErrors['message'] = 'Notice message must be at least 5 characters long.';
} elseif (strlen($message) > 3000) {
    $fieldErrors['message'] = 'Notice message cannot exceed 3,000 characters.';
}

if (!empty($fieldErrors)) {
    json_out(false, $fieldErrors, 'Please correct the highlighted form errors.', 422);
}

$pdo = db();

try {
    $stmt = $pdo->prepare('
        INSERT INTO notices (hostel_type, message, posted_by, created_at)
        VALUES (:hostel_type, :message, :posted_by, NOW())
    ');
    $stmt->execute([
        ':hostel_type' => $sessionHostel,
        ':message'     => $message,
        ':posted_by'   => $staffId,
    ]);

    $newId = (int)$pdo->lastInsertId();

    json_out(true, [
        'id'             => $newId,
        'message_text'   => $message,
        'created_at'     => date('Y-m-d H:i:s'),
        'posted_by_name' => (string)($_SESSION['name'] ?? 'Staff'),
        'posted_by_role' => (string)($_SESSION['role'] ?? 'caretaker'),
        'message'        => 'Official notice published successfully.',
    ]);
} catch (PDOException $e) {
    error_log('Notice Create DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to publish official notice', 500);
}

