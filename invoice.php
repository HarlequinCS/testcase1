<?php
// =====================================================
// BookEase Lab - Invoice / Booking Detail
// VULN-03/05: IDOR — no ownership check; any booking ID works
// VULN-02: Notes rendered raw (stored XSS)
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();

$booking_id = (int)($_GET['id'] ?? 0);

// VULN-03/05: No WHERE user_id = current_user — any logged-in user can view any invoice
$sql = "SELECT b.*, r.name AS room_name, r.type AS room_type, r.price_per_night,
               u.full_name, u.email, u.phone, u.username
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = $booking_id";

$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) === 0) {
    // VULN-09: Reveals that booking ID does not exist (enumeration aid)
    die("Booking #$booking_id not found. " . mysqli_error($conn));
}
$b = mysqli_fetch_assoc($res);

$nights    = (strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400;
$subtotal  = $b['price_per_night'] * $nights;
$discount  = $subtotal * ($b['discount_pct'] / 100);

$page_title = "Invoice #$booking_id";
require_once 'includes/header.php';
?>
<div class="container container-sm">

    <div class="vuln-note mb-md">
        <strong><i class="fa-solid fa-flask"></i> VULN-03 / VULN-05 (IDOR):</strong>
        This invoice is visible to <em>any logged-in user</em> who knows the booking ID.
        Change <code>?id=</code> in the URL (1–8) to access other users' invoices and PII.
    </div>

    <div class="invoice card">
        <div class="invoice-header">
            <div>
                <h1 class="invoice-title"><i class="fa-solid fa-file-invoice"></i> Invoice</h1>
                <p class="invoice-sub">BookEase Lab &mdash; Booking Confirmation</p>
            </div>
            <div class="invoice-meta">
                <div><strong>Booking #</strong><?= $b['id'] ?></div>
                <div><strong>Date:</strong> <?= $b['created_at'] ?></div>
                <div><span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></div>
            </div>
        </div>

        <!-- VULN-03: Customer PII visible to any user who guesses the ID -->
        <div class="invoice-section">
            <h3>Customer Information</h3>
            <div class="invoice-grid">
                <div><label>Name:</label> <?= htmlspecialchars($b['full_name']) ?></div>
                <div><label>Username:</label> <?= htmlspecialchars($b['username']) ?></div>
                <div><label>Email:</label> <?= htmlspecialchars($b['email']) ?></div>
                <div><label>Phone:</label> <?= htmlspecialchars($b['phone']) ?></div>
            </div>
        </div>

        <div class="invoice-section">
            <h3>Booking Details</h3>
            <div class="invoice-grid">
                <div><label>Room:</label> <?= htmlspecialchars($b['room_name']) ?></div>
                <div><label>Type:</label> <?= ucfirst($b['room_type']) ?></div>
                <div><label>Check-in:</label> <?= $b['check_in'] ?></div>
                <div><label>Check-out:</label> <?= $b['check_out'] ?></div>
                <div><label>Nights:</label> <?= $nights ?></div>
                <div><label>Guests:</label> <?= $b['guests'] ?></div>
            </div>
        </div>

        <?php if ($b['notes']): ?>
        <div class="invoice-section">
            <h3>Special Requests</h3>
            <!-- VULN-02: Stored XSS — notes rendered raw, not escaped -->
            <div class="notes-display"><?= $b['notes'] ?></div>
        </div>
        <?php endif; ?>

        <div class="invoice-section">
            <h3>Payment Summary</h3>
            <div class="price-breakdown">
                <div class="price-line">
                    <span>Room rate (<?= $b['room_type'] ?>)</span>
                    <span>$<?= number_format($b['price_per_night'], 2) ?>/night</span>
                </div>
                <div class="price-line">
                    <span><?= $nights ?> night(s)</span>
                    <span>$<?= number_format($subtotal, 2) ?></span>
                </div>
                <?php if ($b['discount_pct'] > 0): ?>
                <div class="price-line discount-line">
                    <span>Discount (<?= $b['coupon_code'] ?>, <?= $b['discount_pct'] ?>%)</span>
                    <span>-$<?= number_format($discount, 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="price-total">
                    <strong>Total Charged</strong>
                    <strong>$<?= number_format($b['total_price'], 2) ?></strong>
                </div>
                <?php
                $expected = $subtotal - $discount;
                if (abs($expected - $b['total_price']) > 0.01): ?>
                <div class="price-line vuln-hint" style="color:#e53e3e">
                    ⚠ VULN-06: Expected $<?= number_format($expected, 2) ?> but charged $<?= number_format($b['total_price'], 2) ?> — price was manipulated!
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="invoice-actions">
            <a href="/vuln1/bookings.php" class="btn btn-outline">
                <i class="fa-solid fa-arrow-left"></i> Back to Bookings
            </a>
            <?php if (in_array($b['status'], ['confirmed','pending'])): ?>
            <a href="/vuln1/cancel.php?id=<?= $b['id'] ?>"
               onclick="return confirm('Cancel this booking?')"
               class="btn btn-danger">
                <i class="fa-solid fa-ban"></i> Cancel Booking
            </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa-solid fa-print"></i> Print Invoice
            </button>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
