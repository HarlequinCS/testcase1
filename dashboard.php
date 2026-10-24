<?php
// =====================================================
// BookEase Lab - User Dashboard
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();

$user = current_user();
$uid  = (int)$user['id'];

// Stats for current user
$bk_res  = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM bookings WHERE user_id=$uid AND status='confirmed'");
$bk_cnt  = mysqli_fetch_assoc($bk_res)['cnt'];

$pen_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM bookings WHERE user_id=$uid AND status='pending'");
$pen_cnt = mysqli_fetch_assoc($pen_res)['cnt'];

$spent_res = mysqli_query($conn, "SELECT COALESCE(SUM(total_price),0) AS total FROM bookings WHERE user_id=$uid AND status IN ('confirmed','completed')");
$spent     = mysqli_fetch_assoc($spent_res)['total'];

// Recent bookings
$recent = mysqli_query($conn, "SELECT b.*, r.name AS room_name FROM bookings b JOIN rooms r ON b.room_id=r.id WHERE b.user_id=$uid ORDER BY b.created_at DESC LIMIT 5");

$page_title = 'Dashboard';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-gauge-high"></i> Dashboard</h1>
        <p>Welcome back, <strong><?= htmlspecialchars($user['full_name']) ?></strong> &mdash; <span class="role-badge role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></p>
    </div>

    <div class="stats-row">
        <div class="stat-widget">
            <i class="fa-solid fa-calendar-check"></i>
            <div class="stat-widget-body">
                <div class="stat-widget-num"><?= $bk_cnt ?></div>
                <div class="stat-widget-label">Confirmed Bookings</div>
            </div>
        </div>
        <div class="stat-widget">
            <i class="fa-solid fa-hourglass-half"></i>
            <div class="stat-widget-body">
                <div class="stat-widget-num"><?= $pen_cnt ?></div>
                <div class="stat-widget-label">Pending Bookings</div>
            </div>
        </div>
        <div class="stat-widget">
            <i class="fa-solid fa-dollar-sign"></i>
            <div class="stat-widget-body">
                <div class="stat-widget-num">$<?= number_format($spent, 2) ?></div>
                <div class="stat-widget-label">Total Spent</div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-main">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Bookings</h3>
                    <a href="/vuln1/bookings.php" class="btn btn-sm btn-outline">View All</a>
                </div>
                <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($b = mysqli_fetch_assoc($recent)): ?>
                        <tr>
                            <td><a href="/vuln1/invoice.php?id=<?= $b['id'] ?>">#<?= $b['id'] ?></a></td>
                            <td><?= htmlspecialchars($b['room_name']) ?></td>
                            <td><?= $b['check_in'] ?></td>
                            <td><?= $b['check_out'] ?></td>
                            <td>$<?= number_format($b['total_price'], 2) ?></td>
                            <td><span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                            <td>
                                <?php if ($b['status'] === 'confirmed' || $b['status'] === 'pending'): ?>
                                <a href="/vuln1/cancel.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Cancel booking #<?= $b['id'] ?>?')">Cancel</a>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <div class="dashboard-side">
            <div class="card">
                <div class="card-header"><h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3></div>
                <div class="quick-actions">
                    <a href="/vuln1/rooms.php" class="quick-action">
                        <i class="fa-solid fa-bed"></i> Book a Room
                    </a>
                    <a href="/vuln1/bookings.php" class="quick-action">
                        <i class="fa-solid fa-list"></i> All Bookings
                    </a>
                    <a href="/vuln1/profile.php" class="quick-action">
                        <i class="fa-solid fa-user-pen"></i> Edit Profile
                    </a>
                    <a href="/vuln1/search.php" class="quick-action">
                        <i class="fa-solid fa-magnifying-glass"></i> Search Rooms
                    </a>
                </div>
            </div>

            <div class="card vuln-card-panel">
                <div class="card-header"><h3><i class="fa-solid fa-flask"></i> Lab Notes</h3></div>
                <ul class="lab-notes">
                    <li>Your user ID is: <strong><?= $uid ?></strong></li>
                    <li>Role stored in session: <strong><?= $user['role'] ?></strong></li>
                    <li>Try <code>/vuln1/invoice.php?id=1</code> (IDOR)</li>
                    <li>Try <code>/vuln1/cancel.php?id=6</code> (BOLA)</li>
                    <li>Try <code>/vuln1/admin/</code> (BAC)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
