<?php
// =====================================================
// BookEase Lab - Book a Room
// VULN-06: Total price taken from hidden POST field (price manipulation)
// VULN-02: Stored XSS in booking notes
// VULN-07: No CSRF token
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();

$user    = current_user();
$room_id = (int)($_GET['room_id'] ?? 0);
$error   = '';
$success = '';

// Fetch the room
$room_res = mysqli_query($conn, "SELECT * FROM rooms WHERE id=$room_id AND status='available'");
if (mysqli_num_rows($room_res) === 0) {
    die('Room not found or unavailable.');
}
$room = mysqli_fetch_assoc($room_res);

// Handle coupon
$coupon_discount = 0;
$coupon_code     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in  = $_POST['check_in']  ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $guests    = (int)($_POST['guests'] ?? 1);
    $notes     = $_POST['notes']     ?? '';
    $coupon_code = trim($_POST['coupon_code'] ?? '');

    // Client-supplied price — VULN-06: never recalculated server-side
    $total_price = floatval($_POST['total_price'] ?? 0);

    // Minimal date validation only
    if (!$check_in || !$check_out || $check_in >= $check_out) {
        $error = 'Invalid check-in or check-out date.';
    } elseif ($guests < 1) {
        $error = 'Guest count must be at least 1.';
    } else {
        // Coupon handling — VULN: single-use coupons can be reused (checked but not atomic)
        $discount_pct = 0;
        if ($coupon_code) {
            $coup_res = mysqli_query($conn, "SELECT * FROM coupons WHERE code='$coupon_code'");
            if ($coup_row = mysqli_fetch_assoc($coup_res)) {
                if ($coup_row['expires_at'] < date('Y-m-d')) {
                    $error = 'Coupon has expired.';
                } elseif ($coup_row['single_use'] && $coup_row['used']) {
                    $error = 'This coupon has already been used.';
                } else {
                    $discount_pct = (int)$coup_row['discount'];
                }
            } else {
                $error = 'Invalid coupon code.';
            }
        }

        if (!$error) {
            // VULN-06: $total_price is from the POST form field — not calculated from room rate!
            // Attacker can set total_price=1.00 regardless of actual cost.
            $uid = (int)$user['id'];

            // VULN-02: $notes stored directly — Stored XSS
            $notes_escaped = mysqli_real_escape_string($conn, $notes);  // escapes SQL but XSS survives
            $sql = "INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_price, status, notes, coupon_code, discount_pct)
                    VALUES ($uid, $room_id, '$check_in', '$check_out', $guests, $total_price, 'pending', '$notes_escaped', '$coupon_code', $discount_pct)";
            if (mysqli_query($conn, $sql)) {
                $booking_id = mysqli_insert_id($conn);
                // Mark coupon used (VULN: race condition possible here)
                if ($coupon_code && isset($coup_row) && $coup_row['single_use']) {
                    mysqli_query($conn, "UPDATE coupons SET used=1 WHERE code='$coupon_code'");
                }
                log_activity($conn, 'booking_created', "Booking #$booking_id for room $room_id");
                header("Location: /vuln1/invoice.php?id=$booking_id");
                exit;
            } else {
                $error = 'Booking failed: ' . mysqli_error($conn); // VULN-09
            }
        }
    }

    // Re-display with posted values
    $check_in  = $_POST['check_in']  ?? '';
    $check_out = $_POST['check_out'] ?? '';
} else {
    $check_in  = date('Y-m-d', strtotime('+1 day'));
    $check_out = date('Y-m-d', strtotime('+3 days'));
    $guests    = 1;
    $notes     = '';
}

// Compute display price (JS also recalculates client-side only — VULN-06)
$nights       = max(1, (strtotime($check_out) - strtotime($check_in)) / 86400);
$display_total = $room['price_per_night'] * $nights;

$page_title = 'Book Room';
require_once 'includes/header.php';
?>
<div class="container container-sm">
    <div class="page-header">
        <h1><i class="fa-solid fa-calendar-plus"></i> Book Room</h1>
    </div>

    <div class="booking-layout">
        <!-- Room summary -->
        <div class="booking-room-card card">
            <div class="room-img-placeholder room-type-<?= $room['type'] ?> room-img-sm">
                <?php $icons=['standard'=>'fa-bed','deluxe'=>'fa-star','suite'=>'fa-crown','conference'=>'fa-people-group']; ?>
                <i class="fa-solid <?= $icons[$room['type']] ?? 'fa-bed' ?>"></i>
            </div>
            <div class="p-md">
                <h3><?= htmlspecialchars($room['name']) ?></h3>
                <p class="room-desc"><?= htmlspecialchars($room['description']) ?></p>
                <div class="room-meta">
                    <span><i class="fa-solid fa-users"></i> Up to <?= $room['capacity'] ?> guests</span>
                    <span class="room-price">$<?= number_format($room['price_per_night'], 2) ?>/night</span>
                </div>
            </div>
        </div>

        <!-- Booking form -->
        <div class="card">
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- VULN-07: No CSRF token -->
            <form method="POST" class="booking-form" id="bookingForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="check_in"><i class="fa-solid fa-arrow-right-to-bracket"></i> Check-in</label>
                        <input type="date" id="check_in" name="check_in" class="form-control"
                               value="<?= $check_in ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="check_out"><i class="fa-solid fa-arrow-right-from-bracket"></i> Check-out</label>
                        <input type="date" id="check_out" name="check_out" class="form-control"
                               value="<?= $check_out ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="guests"><i class="fa-solid fa-users"></i> Number of Guests</label>
                    <input type="number" id="guests" name="guests" class="form-control"
                           value="<?= $guests ?>" min="1" max="<?= $room['capacity'] ?>">
                </div>
                <div class="form-group">
                    <label for="coupon_code"><i class="fa-solid fa-ticket"></i> Coupon Code <small>(optional)</small></label>
                    <input type="text" id="coupon_code" name="coupon_code" class="form-control"
                           placeholder="e.g. WELCOME10" value="<?= htmlspecialchars($coupon_code) ?>">
                    <small class="form-hint">Try: WELCOME10, VIP25, STAFF50</small>
                </div>
                <div class="form-group">
                    <label for="notes"><i class="fa-solid fa-note-sticky"></i> Special Requests / Notes</label>
                    <!-- VULN-02: Content stored and rendered without sanitisation (stored XSS) -->
                    <textarea id="notes" name="notes" class="form-control" rows="3"
                              placeholder="Any special requests? (Try: &lt;script&gt;alert('XSS')&lt;/script&gt;)"><?= htmlspecialchars($notes) ?></textarea>
                    <small class="form-hint vuln-hint">⚠ VULN-02: Notes are stored and rendered unsanitised.</small>
                </div>

                <!-- VULN-06: price is a hidden field — attacker can intercept and modify -->
                <div class="price-box">
                    <div class="price-line">
                        <span>$<?= number_format($room['price_per_night'],2) ?> × <span id="nightsDisplay"><?= $nights ?></span> nights</span>
                        <span id="priceDisplay">$<?= number_format($display_total,2) ?></span>
                    </div>
                    <div class="price-total">
                        <strong>Total</strong>
                        <strong id="totalDisplay">$<?= number_format($display_total,2) ?></strong>
                    </div>
                    <!-- VULN-06: This hidden field is trusted by the server — change it to 1.00 -->
                    <input type="hidden" id="total_price" name="total_price" value="<?= number_format($display_total,2,'.','') ?>">
                    <small class="vuln-hint">⚠ VULN-06: Intercept this form and change <code>total_price</code> to any value.</small>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg">
                    <i class="fa-solid fa-credit-card"></i> Confirm Booking
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Client-side price calculator (also vulnerable — VULN-06)
const pricePerNight = <?= $room['price_per_night'] ?>;
const checkin  = document.getElementById('check_in');
const checkout = document.getElementById('check_out');
function recalc() {
    const d1 = new Date(checkin.value), d2 = new Date(checkout.value);
    const nights = Math.max(1, (d2 - d1) / 86400000);
    const total  = (nights * pricePerNight).toFixed(2);
    document.getElementById('nightsDisplay').textContent = nights;
    document.getElementById('priceDisplay').textContent  = '$' + total;
    document.getElementById('totalDisplay').textContent  = '$' + total;
    document.getElementById('total_price').value         = total;
}
checkin.addEventListener('change',  recalc);
checkout.addEventListener('change', recalc);
</script>
<?php require_once 'includes/footer.php'; ?>
