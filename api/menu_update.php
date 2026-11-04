<?php
/**
 * Shared Mess Menu Update API Endpoint
 * IIIT Kota Hostel Management System
 * Enforces role = warden server-side strictly. Caretakers and students are denied with 403.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, null, 'Method not allowed', 405);
}

// STRICT GUARD: Only wardens are authorized to modify the shared mess menu
$auth = requireAuth('warden');
csrf_verify();

$input  = request_data();
$mealId = isset($input['meal_id']) && is_numeric($input['meal_id']) ? (int)$input['meal_id'] : 0;
if ($mealId <= 0 && isset($input['id']) && is_numeric($input['id'])) {
    $mealId = (int)$input['id'];
}

$items = trim((string)($input['items'] ?? ''));

$fieldErrors = [];
if ($mealId <= 0) {
    json_out(false, null, 'Invalid menu meal ID specified.', 400);
}

if (empty($items)) {
    $fieldErrors['items'] = 'Menu items description cannot be blank.';
} elseif (strlen($items) < 3) {
    $fieldErrors['items'] = 'Menu items description must be at least 3 characters.';
} elseif (strlen($items) > 500) {
    $fieldErrors['items'] = 'Menu items description cannot exceed 500 characters.';
}

if (!empty($fieldErrors)) {
    json_out(false, $fieldErrors, 'Please correct the highlighted form errors.', 422);
}

$pdo = db();

try {
    // Verify menu item exists
    $checkStmt = $pdo->prepare('SELECT id, day, meal_type FROM mess_menu WHERE id = :id LIMIT 1');
    $checkStmt->execute([':id' => $mealId]);
    $meal = $checkStmt->fetch();

    if (!$meal) {
        json_out(false, null, 'Mess menu item not found.', 404);
    }

    $updateStmt = $pdo->prepare('UPDATE mess_menu SET items = :items WHERE id = :id');
    $updateStmt->execute([
        ':items' => $items,
        ':id'    => $mealId,
    ]);

    json_out(true, [
        'id'        => $mealId,
        'day'       => $meal['day'],
        'meal_type' => $meal['meal_type'],
        'items'     => $items,
        'message'   => 'Mess menu item updated successfully.',
    ]);
} catch (PDOException $e) {
    error_log('Mess Menu Update DB Error: ' . $e->getMessage());
    json_out(false, null, 'Failed to update mess menu item', 500);
}

