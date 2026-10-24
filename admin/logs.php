<?php
// =====================================================
// BookEase Lab - Admin: Activity Logs
// =====================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

$logs = mysqli_query($conn, "SELECT l.*, u.username FROM activity_log l
                              LEFT JOIN users u ON l.user_id=u.id
                              ORDER BY l.created_at DESC LIMIT 100");

$page_title = 'Activity Logs';
require_once '../includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-clipboard-list"></i> Activity Logs</h1>
        <a href="/vuln1/admin/" class="btn btn-outline">← Admin Home</a>
    </div>

    <!-- VULN-09: Logs expose IP addresses, actions, and internal details -->
    <div class="vuln-note mb-md">
        <strong><i class="fa-solid fa-flask"></i> VULN-09:</strong>
        Logs expose usernames, IP addresses, and internal action details
        to any admin-role user (including a privilege-escalated attacker).
    </div>

    <div class="card">
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>ID</th><th>User</th><th>Action</th><th>Detail</th><th>IP</th><th>Time</th></tr></thead>
            <tbody>
            <?php while ($l = mysqli_fetch_assoc($logs)): ?>
            <tr>
                <td><?= $l['id'] ?></td>
                <td><?= htmlspecialchars($l['username'] ?? 'System') ?></td>
                <td><?= htmlspecialchars($l['action']) ?></td>
                <td><?= htmlspecialchars($l['detail']) ?></td>
                <td><?= htmlspecialchars($l['ip_address']) ?></td>
                <td><?= $l['created_at'] ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
