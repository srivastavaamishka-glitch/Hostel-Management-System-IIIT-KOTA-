<?php
/**
 * Student Complaints List API Endpoint
 * IIIT Kota Hostel Management System
 * Fetches complaints strictly belonging to the authenticated student and their assigned hostel.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// Authenticate student role
$auth = requireAuth('student');

$studentId     = (int)$_SESSION['user_id'];
$sessionHostel = (string)$_SESSION['hostel_type'];

$pdo = db();

try {
    $stmt = $pdo->prepare('
        SELECT id, category, description, image_path, status, created_at, updated_at
        FROM complaints
        WHERE student_id = :student_id
          AND hostel_type = :session_hostel
        ORDER BY id DESC
    ');
    $stmt->execute([
        ':student_id'     => $studentId,
        ':session_hostel' => $sessionHostel,
    ]);

    $complaints = $stmt->fetchAll();

    // Map whether each complaint has an attachment available for streaming
    $data = array_map(function ($c) {
        return [
            'id'          => (int)$c['id'],
            'category'    => (string)$c['category'],
            'description' => (string)$c['description'],
            'has_image'   => !empty($c['image_path']),
            'status'      => (string)$c['status'],
            'created_at'  => (string)$c['created_at'],
            'updated_at'  => (string)$c['updated_at'],
        ];
    }, $complaints);

    json_out(true, $data);
} catch (PDOException $e) {
    error_log('Complaint List DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to retrieve complaints.', 500);
}

