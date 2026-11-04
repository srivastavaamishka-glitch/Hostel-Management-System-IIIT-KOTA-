<?php
/**
 * Create Complaint API Endpoint
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

$category    = trim((string)($_POST['category'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));

$fieldErrors = [];

// Validate Category
$validCategories = ['Electrical', 'Plumbing', 'Carpentry', 'Discipline'];
if (empty($category) || !in_array($category, $validCategories, true)) {
    $fieldErrors['category'] = 'Please select a valid complaint category.';
}

// Validate Description
if (empty($description)) {
    $fieldErrors['description'] = 'Complaint description cannot be empty.';
} elseif (strlen($description) < 10) {
    $fieldErrors['description'] = 'Please provide at least 10 characters describing the issue.';
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
        INSERT INTO complaints (student_id, hostel_type, category, description, image_path, status, created_at, updated_at)
        VALUES (:student_id, :hostel_type, :category, :description, :image_path, "pending", NOW(), NOW())
    ');
    $stmt->execute([
        ':student_id'  => $studentId,
        ':hostel_type' => $sessionHostel,
        ':category'    => $category,
        ':description' => $description,
        ':image_path'  => $imageFilename,
    ]);

    $complaintId = (int)$pdo->lastInsertId();

    json_out(true, [
        'id'         => $complaintId,
        'message'    => 'Complaint logged successfully.',
        'status'     => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
} catch (PDOException $e) {
    error_log('Complaint Create DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to record complaint in database.', 500);
}

