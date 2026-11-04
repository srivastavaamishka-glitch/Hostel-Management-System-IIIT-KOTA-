<?php
/**
 * Mess Feedback Analytics API Endpoint (Last 7 Days)
 * IIIT Kota Hostel Management System
 * Aggregates average rating and vote count per meal strictly scoped to staff hostel.
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

$pdo = db();

try {
    // 1. Per-meal analytics for last 7 days scoped strictly to session hostel
    $stmt = $pdo->prepare("
        SELECT mm.id AS meal_id, mm.day, mm.meal_type, mm.items,
               ROUND(AVG(mf.rating), 1) AS avg_rating,
               COUNT(mf.id) AS vote_count
        FROM mess_menu mm
        LEFT JOIN mess_feedback mf ON mm.id = mf.meal_id
             AND mf.hostel_type = :session_hostel
             AND mf.feedback_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY mm.id, mm.day, mm.meal_type, mm.items
        ORDER BY FIELD(mm.day, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'),
                 FIELD(mm.meal_type, 'breakfast', 'lunch', 'dinner')
    ");
    $stmt->execute([':session_hostel' => $sessionHostel]);
    $rows = $stmt->fetchAll();

    $analytics = array_map(function ($r) {
        return [
            'meal_id'    => (int)$r['meal_id'],
            'day'        => (string)$r['day'],
            'meal_type'  => (string)$r['meal_type'],
            'items'      => (string)$r['items'],
            'avg_rating' => $r['avg_rating'] !== null ? (float)$r['avg_rating'] : 0.0,
            'vote_count' => (int)$r['vote_count'],
        ];
    }, $rows);

    // 2. Summary stats for last 7 days
    $summaryStmt = $pdo->prepare("
        SELECT ROUND(AVG(rating), 1) AS overall_avg,
               COUNT(id) AS total_votes
        FROM mess_feedback
        WHERE hostel_type = :session_hostel
          AND feedback_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $summaryStmt->execute([':session_hostel' => $sessionHostel]);
    $summaryRow = $summaryStmt->fetch();

    $overallAvg = ($summaryRow && $summaryRow['overall_avg'] !== null) ? (float)$summaryRow['overall_avg'] : 0.0;
    $totalVotes = ($summaryRow && $summaryRow['total_votes'] !== null) ? (int)$summaryRow['total_votes'] : 0;

    json_out(true, [
        'hostel_type'    => $sessionHostel,
        'period_days'    => 7,
        'overall_rating' => $overallAvg,
        'total_votes'    => $totalVotes,
        'analytics'      => $analytics,
    ]);
} catch (PDOException $e) {
    error_log('Mess Analytics DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to generate mess feedback analytics', 500);
}

