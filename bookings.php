<?php
// =====================================================
// BookEase Lab - Booking History
// VULN-03/05: All bookings queryable by ID — no ownership filter
//             (IDOR — just change the id in the URL)
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();

$user = current_user();
$uid  = (int)$user['id'];

// Fetch only this user's bookings
$sql  = "SELECT b.*, r.name AS room_name, r.type AS room_type, r.price_per_night
         FROM bookings b
         JOIN rooms r ON b.room_id = r.id
         WHERE b.user_id = $uid
         ORDER BY b.created_at DESC";
$bookings = mysqli_query($conn, $sql);

$page_title = 'My Bookings';
require_once 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-calendar-check"></i> My Bookings</h1>
        <a href="/vuln1/rooms.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Booking</a>
    </div>

    <div class="vuln-note mb-md">
        <strong><i class="fa-solid fa-flask"></i> VULN-03 / VULN-05 (IDOR):</strong>
        Click any booking's invoice link, then change <code>?id=</code> to another number
        (e.g., <code>/vuln1/invoice.php?id=2</code>) to view <em>any</em> user's invoice.
        Similarly, <code>/vuln1/cancel.php?id=2</code> cancels another user's booking.
    </div>

    <?php if (mysqli_num_rows($bookings) === 0): ?>
    <div class="empty-state">
        <i class="fa-solid fa-calendar-xmark"></i>
        <p>No bookings yet. <a href="/vuln1/rooms.php">Browse rooms</a> to make your first booking.</p>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Guests</th>
                    <th>Total</th>
                    <th>Coupon</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($b = mysqli_fetch_assoc($bookings)): ?>
                <tr>
                    <td><strong>#<?= $b['id'] ?></strong></td>
                    <td><?= htmlspecialchars($b['room_name']) ?></td>
                    <td><?= $b['check_in'] ?></td>
                    <td><?= $b['check_out'] ?></td>
                    <td><?= $b['guests'] ?></td>
                    <td>$<?= number_format($b['total_price'], 2) ?></td>
                    <td><?= $b['coupon_code'] ? htmlspecialchars($b['coupon_code']) : '—' ?></td>
                    <td><span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                    <td class="notes-cell">
                        <!-- VULN-02: stored XSS — notes echoed raw -->
                        <?= $b['notes'] ?: '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="actions-cell">
                        <a href="/vuln1/invoice.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline">
                            <i class="fa-solid fa-file-invoice"></i> Invoice
                        </a>
                        <?php if (in_array($b['status'], ['confirmed','pending'])): ?>
                        <!-- VULN-07: No CSRF token on cancel link/form -->
                        <a href="/vuln1/cancel.php?id=<?= $b['id'] ?>"
                           onclick="return confirm('Cancel booking #<?= $b['id'] ?>?')"
                           class="btn btn-sm btn-danger">
                            <i class="fa-solid fa-ban"></i> Cancel
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
