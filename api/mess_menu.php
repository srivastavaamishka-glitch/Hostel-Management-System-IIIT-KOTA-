<?php
/**
 * Shared Mess Menu & Student Rated State API Endpoint
 * IIIT Kota Hostel Management System
 * Shared menu across hostels with today's rated state for the authenticated student.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// Authenticate session
$auth = requireAuth();

$studentId = (int)($_SESSION['user_id'] ?? 0);
$pdo = db();

$todayDay  = date('D');       // e.g. 'Tue'
$todayDate = date('Y-m-d');   // e.g. '2026-11-03'

try {
    // 1. Fetch full weekly menu (SHARED across all hostels, no hostel filter)
    $menuStmt = $pdo->query("
        SELECT id, day, meal_type, items
        FROM mess_menu
        ORDER BY FIELD(day, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'),
                 FIELD(meal_type, 'breakfast', 'lunch', 'dinner')
    ");
    $menu = $menuStmt->fetchAll();

    // 2. Fetch today's rated meals for this student (NEVER JOINED to mess_feedback)
    $ratedMeals = [];
    if ($studentId > 0 && ($_SESSION['role'] ?? '') === 'student') {
        $voteStmt = $pdo->prepare('
            SELECT meal_id
            FROM feedback_votes
            WHERE student_id = :student_id
              AND feedback_date = :today_date
        ');
        $voteStmt->execute([
            ':student_id'  => $studentId,
            ':today_date' => $todayDate,
        ]);
        $ratedMeals = $voteStmt->fetchAll(PDO::FETCH_COLUMN);
        $ratedMeals = array_map('intval', $ratedMeals);
    }

    json_out(true, [
        'today_day'   => $todayDay,
        'today_date'  => $todayDate,
        'rated_meals' => $ratedMeals,
        'menu'        => $menu,
    ]);
} catch (PDOException $e) {
    error_log('Mess Menu DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to load mess menu.', 500);
}

