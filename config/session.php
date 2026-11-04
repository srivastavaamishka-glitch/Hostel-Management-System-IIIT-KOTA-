<?php
/**
 * Hardened Session Management & Core Security Middleware
 * IIIT Kota Hostel Management System
 * Target: XAMPP, PHP 8+
 */

declare(strict_types=1);

// Guard: fail fast with a readable message if db.php has not been copied yet.
if (!file_exists(__DIR__ . '/db.php')) {
    $isApi = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
          || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/'));
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'config/db.php not found. Copy config/db.example.php to config/db.php']);
    } else {
        echo '<b>Setup required:</b> <code>config/db.php</code> not found.<br>'
           . 'Copy <code>config/db.example.php</code> to <code>config/db.php</code> and update your credentials.';
    }
    exit;
}

// ------------------------------------------------------------------------------
// 1. Session Configuration & Hardened Initialization
// ------------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    // Apply strict cookie attributes (HttpOnly, SameSite=Strict)
    if (php_sapi_name() !== 'cli') {
        session_set_cookie_params([
            'lifetime' => 0,            // Expire on browser close
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,    // Active if HTTPS
            'httponly' => true,         // Prevent JavaScript access
            'samesite' => 'Strict',     // Mitigate CSRF
        ]);
    }

    session_start();
}

// ------------------------------------------------------------------------------
// 2. 30-Minute Idle Timeout Enforcement
// ------------------------------------------------------------------------------
$idleTimeoutSeconds = 1800; // 30 minutes

if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $idleTimeoutSeconds) {
        // Session expired: purge and destroy
        $_SESSION = [];
        if (ini_get('session.use_cookies') && php_sapi_name() !== 'cli') {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();

        // If this is an API call, return 401 immediately
        $isApi = (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/'))
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isApi) {
            json_out(false, null, 'Session timed out due to inactivity. Please log in again.', 401);
        }

        // Start fresh empty session for subsequent requests
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
$_SESSION['last_activity'] = time();

// ------------------------------------------------------------------------------
// 3. Contract: json_out($ok, $data, $error, $httpCode)
// Format: {ok: bool, data?: ..., error?: string}
// ------------------------------------------------------------------------------
/**
 * Emits a JSON API response adhering strictly to the system contract.
 *
 * @param bool $ok Success status
 * @param mixed $data Optional payload data
 * @param string|null $error Optional error description
 * @param int $httpCode HTTP response status code
 * @return never
 */
function json_out(bool $ok, mixed $data = null, ?string $error = null, int $httpCode = 200): void {
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }

    $response = ['ok' => $ok];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if ($error !== null) {
        $response['error'] = $error;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------------------------------------
// 4. Contract: csrf_token() & csrf_verify()
// ------------------------------------------------------------------------------
/**
 * Retrieves or generates the cryptographically secure CSRF token for the session.
 *
 * @return string
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the CSRF token received in the X-CSRF-Token request header.
 * Halts with 403 Forbidden on mismatch or missing token.
 *
 * @return bool
 */
function csrf_verify(): bool {
    // Check standard header, getallheaders(), and fallback POST
    $clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if ($clientToken === null && function_exists('getallheaders')) {
        $headers = getallheaders();
        $clientToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
    }

    if ($clientToken === null && isset($_POST['csrf_token'])) {
        $clientToken = (string)$_POST['csrf_token'];
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (empty($sessionToken) || empty($clientToken) || !hash_equals($sessionToken, (string)$clientToken)) {
        json_out(false, null, 'Invalid or missing CSRF token', 403);
    }

    return true;
}

// ------------------------------------------------------------------------------
// 5. Contract: requireAuth($role, $hostel=null)
// ------------------------------------------------------------------------------
/**
 * Guards an endpoint by enforcing active session authentication, role checks,
 * and strict hostel boundaries.
 *
 * @param string|array|null $role Required role ('student', 'staff', 'warden', 'caretaker', or array)
 * @param string|null $hostel Required hostel ('boys', 'girls') for cross-hostel protection
 * @return array Session user context ['user_id', 'role', 'hostel_type', 'identifier']
 */
function requireAuth(string|array|null $role = null, ?string $hostel = null): array {
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        json_out(false, null, 'Authentication required', 401);
    }

    $sessionRole = (string)$_SESSION['role'];
    $sessionHostel = (string)($_SESSION['hostel_type'] ?? '');

    // Role authorization check
    if ($role !== null) {
        $authorized = false;

        if ($role === 'staff') {
            // General staff access allows either warden or caretaker
            $authorized = in_array($sessionRole, ['warden', 'caretaker'], true);
        } elseif (is_array($role)) {
            $authorized = in_array($sessionRole, $role, true);
        } else {
            $authorized = ($sessionRole === $role);
        }

        if (!$authorized) {
            json_out(false, null, 'Forbidden: insufficient permissions', 403);
        }
    }

    // Cross-hostel authorization check
    if ($hostel !== null) {
        if ($sessionHostel === '' || $sessionHostel !== $hostel) {
            json_out(false, null, 'Forbidden: cross-hostel operation denied', 403);
        }
    }

    return [
        'user_id'     => $_SESSION['user_id'],
        'role'        => $sessionRole,
        'hostel_type' => $sessionHostel,
        'identifier'  => $_SESSION['identifier'] ?? '',
    ];
}

/**
 * Destroys the current session cleanly upon logout.
 *
 * @return void
 */
function destroy_session(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies') && php_sapi_name() !== 'cli') {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Retrieves request parameters whether submitted as form-data, urlencoded, or JSON payload.
 *
 * @return array
 */
function request_data(): array {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }
    }
    return $input;
}


