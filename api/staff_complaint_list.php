<?php
/**
 * Staff Complaints Directory API Endpoint
 * IIIT Kota Hostel Management System
 * Lists complaints strictly for the authenticated staff member's hostel.
 * Supports category and status filtering.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_out(false, null, 'Method not allowed', 405);
}

// Staff authentication (warden or caretaker)
$auth = requireAuth('staff');

$sessionHostel = (string)($_SESSION['hostel_type'] ?? '');
if ($sessionHostel === '') {
    json_out(false, null, 'Hostel assignment missing from session', 403);
}

$category = isset($_GET['category']) ? trim((string)$_GET['category']) : '';
$status   = isset($_GET['status']) ? trim((string)$_GET['status']) : '';

$validCategories = ['Electrical', 'Plumbing', 'Carpentry', 'Discipline'];
$validStatuses   = ['pending', 'in-progress', 'resolved'];

$pdo = db();

try {
    $query = '
        SELECT c.id, c.student_id, c.hostel_type, c.category, c.description, c.image_path, c.status, c.created_at, c.updated_at,
               s.college_id, s.room_no
        FROM complaints c
        JOIN students s ON c.student_id = s.id
        WHERE c.hostel_type = :session_hostel
    ';

    $params = [':session_hostel' => $sessionHostel];

    if ($category !== '' && in_array($category, $validCategories, true)) {
        $query .= ' AND c.category = :category';
        $params[':category'] = $category;
    }

    if ($status !== '' && in_array($status, $validStatuses, true)) {
        $query .= ' AND c.status = :status';
        $params[':status'] = $status;
    }

    $query .= " ORDER BY FIELD(c.status, 'pending', 'in-progress', 'resolved'), c.id DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll();

    $data = array_map(function ($c) {
        return [
            'id'          => (int)$c['id'],
            'student_id'  => (int)$c['student_id'],
            'college_id'  => (string)$c['college_id'],
            'room_no'     => (string)$c['room_no'],
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
    error_log('Staff Complaint List DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to retrieve complaints directory', 500);
}

