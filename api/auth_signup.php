<?php
/**
 * Student Registration API Endpoint
 * IIIT Kota Hostel Management System
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, null, 'Method not allowed', 405);
}

// Enforce CSRF verification
csrf_verify();

// Parse input from POST or JSON
$input     = request_data();
$collegeId = trim((string)($input['college_id'] ?? ''));
$password  = (string)($input['password'] ?? '');
$gender    = trim(strtolower((string)($input['gender'] ?? '')));
$branch    = trim((string)($input['branch'] ?? ''));
$roomNo    = trim((string)($input['room_no'] ?? ''));

$fieldErrors = [];

// 1. Validate College ID with required format: ^20\d{2}ku[a-zA-Z]{2,5}\d{2,5}@(iiitkota|iiit\.kota)\.ac\.in$
// e.g. 2025kucp1010@iiit.kota.ac.in or 2024kuec1001@iiitkota.ac.in
if (empty($collegeId)) {
    $fieldErrors['college_id'] = 'College ID is required.';
} elseif (!preg_match('/^20\d{2}ku[a-z]{2,5}\d{2,5}@(iiitkota|iiit\.kota)\.ac\.in$/i', $collegeId)) {
    $fieldErrors['college_id'] = 'Invalid format. Must match 20XXku<branch><roll>@iiit.kota.ac.in (e.g. 2025kucp1010@iiit.kota.ac.in).';
}

// 2. Validate Password (min 8 characters)
if (strlen($password) < 8) {
    $fieldErrors['password'] = 'Password must be at least 8 characters long.';
}

// 3. Validate Gender
if (!in_array($gender, ['male', 'female'], true)) {
    $fieldErrors['gender'] = 'Please select a valid gender (male or female).';
}

// 4. Validate Branch
if (empty($branch)) {
    $fieldErrors['branch'] = 'Branch is required.';
}

// 5. Validate Room Number
if (empty($roomNo)) {
    $fieldErrors['room_no'] = 'Room number is required.';
}

if (!empty($fieldErrors)) {
    json_out(false, $fieldErrors, 'Please correct the highlighted form errors.', 422);
}

// Derive hostel_type strictly server-side from gender (never from client input)
$hostelType = ($gender === 'female') ? 'girls' : 'boys';

$pdo = db();

// Check if College ID is already registered
$checkStmt = $pdo->prepare('SELECT id FROM students WHERE college_id = :college_id LIMIT 1');
$checkStmt->execute([':college_id' => $collegeId]);
if ($checkStmt->fetch()) {
    json_out(false, ['college_id' => 'This College ID is already registered.'], 'A student account with this College ID already exists.', 409);
}

// Hash password using standard bcrypt
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

try {
    $insertStmt = $pdo->prepare('
        INSERT INTO students (college_id, password_hash, gender, hostel_type, branch, room_no, status, created_at)
        VALUES (:college_id, :password_hash, :gender, :hostel_type, :branch, :room_no, "pending", NOW())
    ');
    $insertStmt->execute([
        ':college_id'    => $collegeId,
        ':password_hash' => $passwordHash,
        ':gender'        => $gender,
        ':hostel_type'   => $hostelType,
        ':branch'        => $branch,
        ':room_no'       => $roomNo,
    ]);

    json_out(true, [
        'message' => 'Registration submitted successfully. Your account is pending approval by the hostel warden.'
    ]);
} catch (PDOException $e) {
    error_log('Signup DB Error: ' . $e->getMessage());
    json_out(false, null, 'Registration failed due to a database error. Please try again.', 500);
}

