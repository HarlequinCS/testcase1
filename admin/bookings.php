<?php
// =====================================================
// BookEase Lab - Admin: All Bookings
// =====================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

$success = $error = '';

// Status update — VULN-07: No CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $bid    = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    mysqli_query($conn, "UPDATE bookings SET status='$status' WHERE id=$bid");
    $success = "Booking #$bid status updated to $status.";
}

$filter = $_GET['status'] ?? '';
$sql = "SELECT b.*, r.name AS room_name, u.username, u.email
        FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN users u ON b.user_id=u.id";
if ($filter) {
    $sql .= " WHERE b.status='$filter'"; // VULN-01: filter not sanitized
}
$sql .= " ORDER BY b.created_at DESC";
$bookings = mysqli_query($conn, $sql);

$page_title = 'All Bookings';
require_once '../includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-calendar-check"></i> All Bookings</h1>
        <a href="/vuln1/admin/" class="btn btn-outline">← Admin Home</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="filter-bar mb-md">
        <span>Filter:</span>
        <?php foreach (['','pending','confirmed','cancelled','completed'] as $s): ?>
        <a href="?status=<?= $s ?>" class="filter-btn <?= $filter===$s?'active':'' ?>"><?= $s ? ucfirst($s) : 'All' ?></a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>User</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Notes</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
            <tr>
                <td>#<?= $b['id'] ?></td>
                <td><?= htmlspecialchars($b['username']) ?><br><small><?= htmlspecialchars($b['email']) ?></small></td>
                <td><?= htmlspecialchars($b['room_name']) ?></td>
                <td><?= $b['check_in'] ?></td>
                <td><?= $b['check_out'] ?></td>
                <td>$<?= number_format($b['total_price'],2) ?></td>
                <td class="notes-cell">
                    <!-- VULN-02: Stored XSS rendered in admin panel too -->
                    <?= $b['notes'] ?: '—' ?>
                </td>
                <td>
                    <!-- VULN-07: No CSRF -->
                    <form method="POST" style="display:flex;gap:.3rem">
                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                        <select name="status" class="form-control form-control-sm">
                            <?php foreach (['pending','confirmed','cancelled','completed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button name="update_status" value="1" class="btn btn-sm btn-primary">Update</button>
                    </form>
                </td>
                <td><a href="/vuln1/invoice.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline">Invoice</a></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
