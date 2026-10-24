<?php
// =====================================================
// BookEase Lab - Shared HTML Header
// VULN-10: No security headers set anywhere
// =====================================================
$user = current_user();
$page_title = $page_title ?? 'BookEase Lab';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> — BookEase Lab</title>
    <!-- VULN-10: Missing Content-Security-Policy, X-Frame-Options,
         X-Content-Type-Options, Strict-Transport-Security, Referrer-Policy -->
    <link rel="stylesheet" href="/vuln1/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <a href="/vuln1/" class="brand">
            <i class="fa-solid fa-hotel"></i> BookEase<span class="brand-lab">Lab</span>
        </a>
        <ul class="nav-links">
            <li><a href="/vuln1/rooms.php"><i class="fa-solid fa-bed"></i> Rooms</a></li>
            <li><a href="/vuln1/search.php"><i class="fa-solid fa-magnifying-glass"></i> Search</a></li>
            <?php if ($user): ?>
                <li><a href="/vuln1/bookings.php"><i class="fa-solid fa-calendar-check"></i> My Bookings</a></li>
                <li><a href="/vuln1/profile.php"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($user['username']) ?></a></li>
                <?php if (in_array($user['role'], ['staff','admin'])): ?>
                <li><a href="/vuln1/admin/" class="nav-badge">Admin</a></li>
                <?php endif; ?>
                <li><a href="/vuln1/logout.php" class="btn-nav-logout">Logout</a></li>
            <?php else: ?>
                <li><a href="/vuln1/login.php" class="btn-nav">Login</a></li>
                <li><a href="/vuln1/register.php" class="btn-nav btn-nav-alt">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<main class="main-content">
