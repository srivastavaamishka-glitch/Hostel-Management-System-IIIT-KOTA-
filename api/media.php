<?php
/**
 * Secure Media Streaming API Endpoint
 * IIIT Kota Hostel Management System
 * Streams attachments only after verifying user session and ownership/hostel boundaries.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// Active authentication check
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Authentication required']);
    exit;
}

$userId        = (int)$_SESSION['user_id'];
$role          = (string)$_SESSION['role'];
$sessionHostel = (string)($_SESSION['hostel_type'] ?? '');

$type     = isset($_GET['type']) ? strtolower(trim((string)$_GET['type'])) : 'complaint';
$targetId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($targetId <= 0) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Invalid ID specified']);
    exit;
}

$pdo = db();
$imagePath = '';

try {
    if ($type === 'lostfound') {
        $stmt = $pdo->prepare('
            SELECT id, student_id, hostel_type, image_path
            FROM lost_found
            WHERE id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $targetId]);
        $item = $stmt->fetch();

        if (!$item || empty($item['image_path'])) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Attachment not found']);
            exit;
        }

        // Cross-hostel guard: Lost & Found items can be viewed by anyone inside that same hostel
        if ($item['hostel_type'] !== $sessionHostel) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Forbidden: Cross-hostel attachment access denied']);
            exit;
        }

        $imagePath = (string)$item['image_path'];
    } else {
        // Complaint attachment
        $stmt = $pdo->prepare('
            SELECT id, student_id, hostel_type, image_path
            FROM complaints
            WHERE id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $targetId]);
        $complaint = $stmt->fetch();

        if (!$complaint || empty($complaint['image_path'])) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Attachment not found']);
            exit;
        }

        // Authorization Check: Student's own complaint OR Staff within same hostel
        if ($role === 'student') {
            if ((int)$complaint['student_id'] !== $userId || $complaint['hostel_type'] !== $sessionHostel) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Forbidden: You can only view attachments on your own complaints']);
                exit;
            }
        } elseif (in_array($role, ['warden', 'caretaker'], true)) {
            if ($complaint['hostel_type'] !== $sessionHostel) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Forbidden: Cross-hostel attachment access denied']);
                exit;
            }
        } else {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Forbidden: Insufficient privileges']);
            exit;
        }

        $imagePath = (string)$complaint['image_path'];
    }

    // --------------------------------------------------------------------------
    // Secure File Path Resolution (Stored outside web root in /uploads)
    // --------------------------------------------------------------------------
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    $safeName   = basename($imagePath);
    $filePath   = $uploadsDir ? realpath($uploadsDir . '/' . $safeName) : false;

    if (!$filePath || !file_exists($filePath) || !str_starts_with($filePath, $uploadsDir)) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Attachment file missing from disk']);
        exit;
    }

    // Determine MIME type strictly from file content
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($filePath);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes, true)) {
        http_response_code(415);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Unsupported media type']);
        exit;
    }

    // Stream image binary with secure headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . (string)filesize($filePath));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=86400');
    header('Content-Disposition: inline; filename="' . $safeName . '"');

    readfile($filePath);
    exit;

} catch (PDOException $e) {
    error_log('Media Stream DB Error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Server error processing media request']);
    exit;
}

