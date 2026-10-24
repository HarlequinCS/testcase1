<?php
// =====================================================
// BookEase Lab - Rooms Listing
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';

$type_filter = $_GET['type'] ?? '';
$sql = "SELECT * FROM rooms WHERE status='available'";
if ($type_filter) {
    // VULN-01: SQL injection possible via type parameter
    $sql .= " AND type='$type_filter'";
}
$rooms = mysqli_query($conn, $sql);

$page_title = 'Rooms';
require_once 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-bed"></i> Available Rooms</h1>
        <p>Choose from our selection of rooms and suites.</p>
    </div>

    <div class="filter-bar">
        <span>Filter by type:</span>
        <a href="/vuln1/rooms.php" class="filter-btn <?= !$type_filter ? 'active' : '' ?>">All</a>
        <a href="/vuln1/rooms.php?type=standard"   class="filter-btn <?= $type_filter==='standard'   ? 'active' : '' ?>">Standard</a>
        <a href="/vuln1/rooms.php?type=deluxe"     class="filter-btn <?= $type_filter==='deluxe'     ? 'active' : '' ?>">Deluxe</a>
        <a href="/vuln1/rooms.php?type=suite"      class="filter-btn <?= $type_filter==='suite'      ? 'active' : '' ?>">Suite</a>
        <a href="/vuln1/rooms.php?type=conference" class="filter-btn <?= $type_filter==='conference' ? 'active' : '' ?>">Conference</a>
    </div>

    <div class="rooms-grid">
    <?php while ($room = mysqli_fetch_assoc($rooms)): ?>
        <div class="room-card">
            <div class="room-img-wrap">
                <div class="room-img-placeholder room-type-<?= $room['type'] ?>">
                    <?php
                    $icons = ['standard'=>'fa-bed','deluxe'=>'fa-star','suite'=>'fa-crown','conference'=>'fa-people-group'];
                    $icon  = $icons[$room['type']] ?? 'fa-bed';
                    ?>
                    <i class="fa-solid <?= $icon ?>"></i>
                </div>
                <span class="room-type-badge"><?= ucfirst($room['type']) ?></span>
            </div>
            <div class="room-body">
                <h3><?= htmlspecialchars($room['name']) ?></h3>
                <p class="room-desc"><?= htmlspecialchars($room['description']) ?></p>
                <div class="room-meta">
                    <span><i class="fa-solid fa-users"></i> <?= $room['capacity'] ?> guests</span>
                    <span class="room-price">$<?= number_format($room['price_per_night'], 2) ?>/night</span>
                </div>
                <a href="/vuln1/book.php?room_id=<?= $room['id'] ?>" class="btn btn-primary btn-full">
                    <i class="fa-solid fa-calendar-plus"></i> Book Now
                </a>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
