<?php
// =====================================================
// BookEase Lab - Cancel Booking
// VULN-03/05: IDOR — no ownership check; any logged-in user
//             can cancel any other user's booking
// VULN-07: No CSRF token
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();

$user       = current_user();
$booking_id = (int)($_GET['id'] ?? 0);

// VULN-03/05: Fetch booking WITHOUT checking user_id = current user
$res = mysqli_query($conn, "SELECT b.*, r.name AS room_name, u.username AS owner
                             FROM bookings b
                             JOIN rooms r ON b.room_id = r.id
                             JOIN users u ON b.user_id = u.id
                             WHERE b.id = $booking_id");

if (!$res || mysqli_num_rows($res) === 0) {
    die("Booking #$booking_id not found. " . mysqli_error($conn)); // VULN-09
}
$booking = mysqli_fetch_assoc($res);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULN-03: No check that $user['id'] === $booking['user_id']
    if (in_array($booking['status'], ['confirmed','pending'])) {
        mysqli_query($conn, "UPDATE bookings SET status='cancelled' WHERE id=$booking_id");
        log_activity($conn, 'booking_cancelled', "Booking #$booking_id cancelled by user {$user['username']}");
        $success = "Booking #$booking_id has been cancelled.";
        $booking['status'] = 'cancelled';
    } else {
        $error = "Cannot cancel a booking with status: {$booking['status']}.";
    }
}

$page_title = "Cancel Booking #$booking_id";
require_once 'includes/header.php';
?>
<div class="container container-sm">
    <div class="page-header">
        <h1><i class="fa-solid fa-ban"></i> Cancel Booking #<?= $booking_id ?></h1>
    </div>

    <div class="vuln-note mb-md">
        <strong><i class="fa-solid fa-flask"></i> VULN-03 / VULN-05 (IDOR) + VULN-07 (CSRF):</strong><br>
        This page cancels booking <strong>#<?= $booking_id ?></strong> owned by
        <strong><?= htmlspecialchars($booking['owner']) ?></strong> —
        no ownership check is performed.
        Logged in as: <strong><?= htmlspecialchars($user['username']) ?></strong>
        <?php if ($user['username'] !== $booking['owner']): ?>
        <span style="color:#e53e3e"> ← You are cancelling someone else's booking!</span>
        <?php endif; ?>
    </div>

    <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if ($booking['status'] !== 'cancelled'): ?>
    <div class="card p-lg text-center">
        <i class="fa-solid fa-triangle-exclamation fa-3x" style="color:#e53e3e;margin-bottom:1rem"></i>
        <h3>Confirm Cancellation</h3>
        <p>You are about to cancel:</p>
        <div class="cancel-summary">
            <div><strong>Room:</strong> <?= htmlspecialchars($booking['room_name']) ?></div>
            <div><strong>Check-in:</strong> <?= $booking['check_in'] ?></div>
            <div><strong>Check-out:</strong> <?= $booking['check_out'] ?></div>
            <div><strong>Status:</strong> <?= ucfirst($booking['status']) ?></div>
            <div><strong>Owner:</strong> <?= htmlspecialchars($booking['owner']) ?></div>
        </div>
        <!-- VULN-07: No CSRF token in this form -->
        <form method="POST" style="margin-top:1.5rem">
            <button type="submit" class="btn btn-danger btn-lg">
                <i class="fa-solid fa-ban"></i> Yes, Cancel This Booking
            </button>
            <a href="/vuln1/bookings.php" class="btn btn-outline btn-lg" style="margin-left:.5rem">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </a>
        </form>
    </div>
    <?php else: ?>
    <div class="card p-lg text-center">
        <i class="fa-solid fa-check-circle fa-3x" style="color:#38a169;margin-bottom:1rem"></i>
        <h3>Booking Cancelled</h3>
        <p>Booking #<?= $booking_id ?> is now cancelled.</p>
        <a href="/vuln1/bookings.php" class="btn btn-primary">Back to My Bookings</a>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
