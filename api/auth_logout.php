<?php
/**
 * User Logout API Endpoint
 * IIIT Kota Hostel Management System
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

// Verify CSRF token
csrf_verify();

// Destroy session and unset cookies
destroy_session();

json_out(true, [
    'message'  => 'Logged out successfully',
    'redirect' => 'index.php',
]);

