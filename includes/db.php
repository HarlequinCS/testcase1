<?php
// =====================================================
// BookEase Lab - Database Connection
// VULN-09: Verbose error messages expose DB credentials
//          and internal structure on failure.
// =====================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // VULN: default empty root password
define('DB_NAME', 'bookease_lab');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    // VULN-09: exposes host, user, DB name, and exact MySQL error
    die("Database connection failed: " . mysqli_connect_error() .
        " [Host=" . DB_HOST . " User=" . DB_USER . " DB=" . DB_NAME . "]");
}

mysqli_set_charset($conn, 'utf8mb4');
