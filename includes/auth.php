<?php
// =====================================================
// BookEase Lab - Auth Helpers
// VULN-04: Weak session management; no regeneration,
//          no HttpOnly/Secure flags, no timeout.
// =====================================================

session_start();   // VULN-04/VULN-05: no session options set

/**
 * Returns the currently logged-in user array, or null.
 */
function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

/**
 * Require login — redirect to login page if not authenticated.
 * VULN-04: Session fixation possible — ID never regenerated on login.
 */
function require_login() {
    if (!isset($_SESSION['user'])) {
        header('Location: /vuln1/login.php');
        exit;
    }
}

/**
 * Require admin role.
 * VULN-03B / VULN-10: Only checks client-side session role; no DB re-verification.
 */
function require_admin() {
    require_login();
    if ($_SESSION['user']['role'] !== 'admin') {
        // VULN-09: reveals that an admin area exists and what role is needed
        http_response_code(403);
        die("Access denied. Requires role: admin. Your role: " . htmlspecialchars($_SESSION['user']['role']));
    }
}

/**
 * Require staff or admin role.
 */
function require_staff() {
    require_login();
    if (!in_array($_SESSION['user']['role'], ['staff','admin'])) {
        http_response_code(403);
        die("Access denied. Requires role: staff or admin. Your role: " . $_SESSION['user']['role']);
    }
}

/**
 * Hash a password — VULN-04: MD5, no salt.
 */
function hash_password($plain) {
    return md5($plain);   // VULN-04: weak hashing
}

/**
 * Log an activity to the DB.
 */
function log_activity($conn, $action, $detail = '') {
    $uid = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 'NULL';
    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $sql = "INSERT INTO activity_log (user_id, action, detail, ip_address)
            VALUES ($uid, '$action', '$detail', '$ip')";
    mysqli_query($conn, $sql);
}
