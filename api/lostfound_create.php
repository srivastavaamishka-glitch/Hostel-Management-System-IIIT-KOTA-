<?php
/**
 * Create Lost & Found Post API Endpoint
 * IIIT Kota Hostel Management System
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
$type        = strtolower(trim((string)($input['type'] ?? '')));
$description = trim((string)($input['item_description'] ?? $input['description'] ?? ''));

$fieldErrors = [];

// Validate Type
if (!in_array($type, ['lost', 'found'], true)) {
    $fieldErrors['type'] = 'Please specify whether the item was lost or found.';
}

// Validate Description
if (empty($description)) {
    $fieldErrors['item_description'] = 'Please enter an item description.';
} elseif (strlen($description) < 5) {
    $fieldErrors['item_description'] = 'Description must be at least 5 characters long.';
}

// Validate & Process Optional Image Attachment
$imageFilename = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $fieldErrors['image'] = 'File upload failed with error code ' . (int)$file['error'];
    } else {
        // 1. Max size: 2MB (2 * 1024 * 1024 = 2097152 bytes)
        $maxBytes = 2 * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            $fieldErrors['image'] = 'Attachment exceeds the 2MB size limit.';
        } else {
            // 2. Extension allowlist check
            $origName = (string)$file['name'];
            $origExt  = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($origExt, $allowedExtensions, true)) {
                $fieldErrors['image'] = 'Invalid file extension. Only JPG, PNG, and WebP are allowed.';
            } else {
                // 3. finfo MIME check (NEVER trust $_FILES['type'])
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
                $allowedMimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];

                if (!array_key_exists($mimeType, $allowedMimeTypes)) {
                    $fieldErrors['image'] = 'Invalid image content. Only real JPEG, PNG, and WebP images are allowed.';
                } else {
                    // 4. Safe random filename via bin2hex(random_bytes(16))
                    $safeExt = $allowedMimeTypes[$mimeType];
                    $imageFilename = bin2hex(random_bytes(16)) . '.' . $safeExt;
                    $targetDir = __DIR__ . '/../uploads';

                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }

                    $targetPath = $targetDir . '/' . $imageFilename;
                    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $fieldErrors['image'] = 'Failed to store uploaded attachment securely.';
                        $imageFilename = null;
                    }
                }
            }
        }
    }
}

if (!empty($fieldErrors)) {
    json_out(false, $fieldErrors, 'Please correct the highlighted form errors.', 422);
}

$pdo = db();

try {
    $stmt = $pdo->prepare('
        INSERT INTO lost_found (student_id, hostel_type, type, item_description, image_path, date_logged, status)
        VALUES (:student_id, :hostel_type, :type, :description, :image_path, CURDATE(), "active")
    ');
    $stmt->execute([
        ':student_id'   => $studentId,
        ':hostel_type'  => $sessionHostel,
        ':type'         => $type,
        ':description'  => $description,
        ':image_path'   => $imageFilename,
    ]);

    $newId = (int)$pdo->lastInsertId();

    json_out(true, [
        'id'          => $newId,
        'message'     => 'Lost & Found listing published successfully.',
        'type'        => $type,
        'has_image'   => ($imageFilename !== null),
        'date_logged' => date('Y-m-d'),
        'status'      => 'active',
    ]);
} catch (PDOException $e) {
    error_log('Lost and Found Create DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to create listing.', 500);
}

