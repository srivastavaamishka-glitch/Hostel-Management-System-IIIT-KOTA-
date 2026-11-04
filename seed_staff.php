<?php
/**
 * One-time Staff Database Seeder (CLI / Web)
 * IIIT Kota Hostel Management System
 *
 * Inserts 4 default staff members (Warden + Caretaker for Boys and Girls Hostels)
 * using password_hash(..., PASSWORD_BCRYPT) at runtime.
 * Safe to re-run: skips usernames that already exist.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "========================================================\n";
echo " IIIT Kota Hostel Management System - Staff Seeder\n";
echo "========================================================\n\n";

try {
    $pdo = db();
} catch (Throwable $e) {
    echo "[FATAL ERROR] Could not connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

// 4 Default Staff Profiles
$staffMembers = [
    [
        'username'    => 'warden_boys',
        'name'        => 'Dr. A. K. Sharma',
        'role'        => 'warden',
        'hostel_type' => 'boys',
        'raw_code'    => 'WardenBoys@123',
        'contact_no'  => '+919876543210',
    ],
    [
        'username'    => 'caretaker_boys',
        'name'        => 'Mr. Rajesh Verma',
        'role'        => 'caretaker',
        'hostel_type' => 'boys',
        'raw_code'    => 'CaretakerBoys@123',
        'contact_no'  => '+919876543211',
    ],
    [
        'username'    => 'warden_girls',
        'name'        => 'Dr. Sunita Gupta',
        'role'        => 'warden',
        'hostel_type' => 'girls',
        'raw_code'    => 'WardenGirls@123',
        'contact_no'  => '+919876543220',
    ],
    [
        'username'    => 'caretaker_girls',
        'name'        => 'Mrs. Meena Kumari',
        'role'        => 'caretaker',
        'hostel_type' => 'girls',
        'raw_code'    => 'CaretakerGirls@123',
        'contact_no'  => '+919876543221',
    ],
];

$checkStmt = $pdo->prepare('SELECT id, staff_username, code_hash FROM staff WHERE staff_username = :username');
$insertStmt = $pdo->prepare('
    INSERT INTO staff (staff_username, name, role, hostel_type, code_hash, contact_no)
    VALUES (:username, :name, :role, :hostel_type, :code_hash, :contact_no)
');

$createdCount = 0;
$skippedCount = 0;

echo "Processing staff accounts:\n";
echo "--------------------------------------------------------\n";

foreach ($staffMembers as $member) {
    $checkStmt->execute([':username' => $member['username']]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        $hashPrefix = substr((string)$existing['code_hash'], 0, 4);
        echo sprintf(
            "[SKIP]    User '%s' already exists (ID: %d). Hash prefix: '%s'\n",
            $member['username'],
            $existing['id'],
            $hashPrefix
        );
        $skippedCount++;
    } else {
        // Generate runtime bcrypt hash starting with $2y$
        $codeHash = password_hash($member['raw_code'], PASSWORD_BCRYPT);
        $hashPrefix = substr($codeHash, 0, 4);

        $insertStmt->execute([
            ':username'    => $member['username'],
            ':name'        => $member['name'],
            ':role'        => $member['role'],
            ':hostel_type' => $member['hostel_type'],
            ':code_hash'   => $codeHash,
            ':contact_no'  => $member['contact_no'],
        ]);

        $newId = (int)$pdo->lastInsertId();
        echo sprintf(
            "[CREATED] User '%s' created (ID: %d, %s, %s). Hash prefix: '%s'\n",
            $member['username'],
            $newId,
            $member['role'],
            $member['hostel_type'],
            $hashPrefix
        );
        $createdCount++;
    }
}

echo "--------------------------------------------------------\n";
echo "Summary: {$createdCount} created, {$skippedCount} skipped.\n\n";

echo "Default Staff Credentials for Testing:\n";
echo "========================================================================================================\n";
printf("%-18s | %-12s | %-8s | %-18s | %-18s\n", "Username", "Role", "Hostel", "Access Code", "Hash Verification");
echo "--------------------------------------------------------------------------------------------------------\n";

$verifyStmt = $pdo->prepare('SELECT staff_username, role, hostel_type, code_hash FROM staff ORDER BY id ASC');
$verifyStmt->execute();
$allStaff = $verifyStmt->fetchAll();

foreach ($allStaff as $s) {
    // Find matching raw code from definition list
    $raw = 'unknown';
    foreach ($staffMembers as $m) {
        if ($m['username'] === $s['staff_username']) {
            $raw = $m['raw_code'];
            break;
        }
    }
    $isValidBcrypt = str_starts_with((string)$s['code_hash'], '$2y$');
    $statusText = $isValidBcrypt ? 'VALID ($2y$)' : 'INVALID HASH';

    printf(
        "%-18s | %-12s | %-8s | %-18s | %-18s\n",
        $s['staff_username'],
        $s['role'],
        $s['hostel_type'],
        $raw,
        $statusText
    );
}
echo "========================================================================================================\n";
echo "Staff seeding completed successfully.\n";

