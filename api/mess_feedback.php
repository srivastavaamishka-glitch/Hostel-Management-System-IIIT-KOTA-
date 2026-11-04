<?php
/**
 * Anonymous Mess Feedback API Endpoint
 * IIIT Kota Hostel Management System
 * Enforces atomic transaction inserting into mess_feedback (anonymized)
 * and feedback_votes (per-student voting guard).
 * NO query may ever join feedback_votes to mess_feedback.
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
$mealId = isset($input['meal_id']) && is_numeric($input['meal_id']) ? (int)$input['meal_id'] : 0;
$rating = isset($input['rating']) && is_numeric($input['rating']) ? (int)$input['rating'] : 0;

if ($mealId <= 0) {
    json_out(false, null, 'Invalid meal selected.', 400);
}

if ($rating < 1 || $rating > 5) {
    json_out(false, null, 'Rating must be an integer between 1 and 5.', 400);
}

$pdo = db();
$todayDate = date('Y-m-d');
$todayDay  = date('D');

// Verify that the meal exists and corresponds to today's menu
$checkMeal = $pdo->prepare('SELECT id, day, meal_type FROM mess_menu WHERE id = :id LIMIT 1');
$checkMeal->execute([':id' => $mealId]);
$meal = $checkMeal->fetch();

if (!$meal) {
    json_out(false, null, 'Meal not found in mess menu.', 404);
}

if ($meal['day'] !== $todayDay) {
    json_out(false, null, 'Feedback can only be submitted for today\'s scheduled meals.', 400);
}

// ------------------------------------------------------------------------------
// Single Atomic Transaction:
// 1. Insert into feedback_votes (prevents multiple votes per student per meal per day)
// 2. Insert into mess_feedback (completely anonymized, no student reference)
// ------------------------------------------------------------------------------
$pdo->beginTransaction();

try {
    // Step 1: Record student vote to prevent duplicates
    $voteStmt = $pdo->prepare('
        INSERT INTO feedback_votes (student_id, meal_id, feedback_date)
        VALUES (:student_id, :meal_id, :feedback_date)
    ');
    $voteStmt->execute([
        ':student_id'    => $studentId,
        ':meal_id'       => $mealId,
        ':feedback_date' => $todayDate,
    ]);

    // Step 2: Insert anonymous feedback (NO student ID referenced)
    $feedbackStmt = $pdo->prepare('
        INSERT INTO mess_feedback (meal_id, hostel_type, feedback_date, rating, created_at)
        VALUES (:meal_id, :hostel_type, :feedback_date, :rating, NOW())
    ');
    $feedbackStmt->execute([
        ':meal_id'       => $mealId,
        ':hostel_type'   => $sessionHostel,
        ':feedback_date' => $todayDate,
        ':rating'        => $rating,
    ]);

    $pdo->commit();

    json_out(true, [
        'message' => 'Thank you! Your feedback has been recorded anonymously.',
        'meal_id' => $mealId,
        'rating'  => $rating,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Catch duplicate primary key error on feedback_votes (student_id, meal_id, feedback_date)
    if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry') || $e->errorInfo[1] === 1062) {
        json_out(false, null, 'Already rated. You have already submitted feedback for this meal today.', 409);
    }

    error_log('Mess Feedback Transaction Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to record mess feedback.', 500);
}

