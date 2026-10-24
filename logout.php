<?php
// =====================================================
// BookEase Lab - Logout
// VULN-04: session_destroy() but cookie persists (no explicit
//          cookie deletion), session data not cleared on server.
// =====================================================
require_once 'includes/auth.php';
// VULN-04: Does not call session_regenerate_id(), does not unset cookie
session_unset();
session_destroy();
// Cookie is still in the browser until it expires naturally.
// No redirect with cache-control headers — back-button still shows pages.
header('Location: /vuln1/login.php?msg=logged_out');
exit;
