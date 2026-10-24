<?php
// =====================================================
// BookEase Lab - Admin Dashboard
// VULN-03 (BAC): Only checks session role, easily bypassed.
//                Direct URL access works if you set session role='admin'.
// =====================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();   // VULN-03: client-side session role check only

// Aggregate stats
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'];
$total_rooms    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM rooms"))['c'];
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings"))['c'];
$total_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_price),0) AS t FROM bookings WHERE status IN ('confirmed','completed')"))['t'];

// Recent bookings
$recent = mysqli_query($conn, "SELECT b.*, r.name AS room_name, u.username
                                FROM bookings b
                                JOIN rooms r ON b.room_id=r.id
                                JOIN users u ON b.user_id=u.id
                                ORDER BY b.created_at DESC LIMIT 8");

// Recent activity
$activity = mysqli_query($conn, "SELECT l.*, u.username FROM activity_log l
                                  LEFT JOIN users u ON l.user_id=u.id
                                  ORDER BY l.created_at DESC LIMIT 10");

$page_title = 'Admin Dashboard';
require_once '../includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-shield-halved"></i> Admin Dashboard</h1>
        <span class="role-badge role-admin">Administrator</span>
    </div>

    <div class="vuln-note mb-md">
        <strong><i class="fa-solid fa-flask"></i> VULN-03 (Broken Access Control):</strong>
        This page checks only <code>$_SESSION['user']['role']</code>. Log in as any user,
        then in browser DevTools &rarr; Application &rarr; Storage &rarr; Session, change the role cookie,
        or use a PHP session manipulation tool to escalate privilege.
    </div>

    <div class="stats-row">
        <div class="stat-widget"><i class="fa-solid fa-users"></i>
            <div class="stat-widget-body">
                <div class="stat-widget-num"><?= $total_users ?></div>
                <div class="stat-widget-label">Total Users</div>
            </div>
        </div>
        <div class="stat-widget"><i class="fa-solid fa-bed"></i>
            <div class="stat-widget-body">
                <div class="stat-widget-num"><?= $total_rooms ?></div>
                <div class="stat-widget-label">Rooms</div>
            </div>
        </div>
        <div class="stat-widget"><i class="fa-solid fa-calendar-check"></i>
            <div class="stat-widget-body">
                <div class="stat-widget-num"><?= $total_bookings ?></div>
                <div class="stat-widget-label">Bookings</div>
            </div>
        </div>
        <div class="stat-widget"><i class="fa-solid fa-dollar-sign"></i>
            <div class="stat-widget-body">
                <div class="stat-widget-num">$<?= number_format($total_revenue, 0) ?></div>
                <div class="stat-widget-label">Revenue</div>
            </div>
        </div>
    </div>

    <div class="admin-nav-grid">
        <a href="/vuln1/admin/users.php"    class="admin-nav-card"><i class="fa-solid fa-users"></i><span>Manage Users</span></a>
        <a href="/vuln1/admin/bookings.php" class="admin-nav-card"><i class="fa-solid fa-calendar-check"></i><span>All Bookings</span></a>
        <a href="/vuln1/admin/rooms.php"    class="admin-nav-card"><i class="fa-solid fa-bed"></i><span>Manage Rooms</span></a>
        <a href="/vuln1/admin/logs.php"     class="admin-nav-card"><i class="fa-solid fa-clipboard-list"></i><span>Activity Logs</span></a>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-main">
            <div class="card">
                <div class="card-header"><h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Bookings</h3></div>
                <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>ID</th><th>User</th><th>Room</th><th>Check-in</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php while ($b = mysqli_fetch_assoc($recent)): ?>
                    <tr>
                        <td>#<?= $b['id'] ?></td>
                        <td><?= htmlspecialchars($b['username']) ?></td>
                        <td><?= htmlspecialchars($b['room_name']) ?></td>
                        <td><?= $b['check_in'] ?></td>
                        <td>$<?= number_format($b['total_price'],2) ?></td>
                        <td><span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                        <td>
                            <a href="/vuln1/invoice.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline">View</a>
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
                <div class="card-header"><h3><i class="fa-solid fa-clipboard-list"></i> Activity Log</h3></div>
                <ul class="activity-list">
                <?php while ($l = mysqli_fetch_assoc($activity)): ?>
                    <li class="activity-item">
                        <i class="fa-solid fa-circle-dot activity-dot"></i>
                        <div>
                            <strong><?= htmlspecialchars($l['username'] ?? 'System') ?></strong>
                            <?= htmlspecialchars($l['action']) ?>
                            <div class="activity-time"><?= $l['created_at'] ?></div>
                        </div>
                    </li>
                <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
