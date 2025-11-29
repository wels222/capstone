<?php
// Common API bootstrap for hosting readiness: CORS, timezone, errors, sessions

// Prevent accidental output buffering issues
if (!headers_sent()) {
    // Timezone
    $tz = getenv('TIMEZONE') ?: 'Asia/Manila';
    date_default_timezone_set($tz);

    // Error visibility (default: hide in production)
    $appEnv = strtolower(getenv('APP_ENV') ?: 'production');
    $appDebug = getenv('APP_DEBUG');
    $isDebug = ($appDebug === '1' || $appEnv === 'development');
    ini_set('display_errors', $isDebug ? '1' : '0');
    error_reporting($isDebug ? E_ALL : E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

    // CORS handling
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $allowed = array_filter(array_map('trim', explode(',', getenv('ALLOWED_ORIGINS') ?: '')));

    $allowThisOrigin = false;
    if ($origin) {
        if (!empty($allowed)) {
            // Allow if origin exactly matches one of the allowed list
            $allowThisOrigin = in_array($origin, $allowed, true);
        } else {
            // If not explicitly configured, allow same-host origin to support SPA on same domain
            // Extract host from origin
            $parsed = parse_url($origin);
            $originHost = $parsed['host'] ?? '';
            $allowThisOrigin = ($originHost && $host && strtolower($originHost) === strtolower($host));
        }
    }

    if ($allowThisOrigin) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');

    // Handle preflight early
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Ensure session is available for session-based auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// No content-type is forced here; endpoints decide based on their response type
