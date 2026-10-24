<?php
// =====================================================
// BookEase Lab - Room Search
// VULN-01: SQL Injection via search query
// VULN-02: Reflected XSS — search term echoed unescaped
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';

$q       = $_GET['q'] ?? '';
$results = [];
$sql_run = '';

if ($q !== '') {
    // VULN-01 + VULN-02: $q injected into SQL AND reflected raw into HTML
    $sql_run = "SELECT * FROM rooms WHERE name LIKE '%$q%' OR description LIKE '%$q%'";
    $res     = mysqli_query($conn, $sql_run);
    if ($res === false) {
        // VULN-09: Shows full SQL error + query
        $sql_error = mysqli_error($conn);
    } else {
        while ($r = mysqli_fetch_assoc($res)) {
            $results[] = $r;
        }
    }
}

$page_title = 'Search';
require_once 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-magnifying-glass"></i> Search Rooms</h1>
    </div>

    <!-- VULN-07: No CSRF token -->
    <form method="GET" class="search-form">
        <input type="text" name="q" class="form-control search-input"
               placeholder="Search rooms by name or description..."
               value="<?= $q /* VULN-02: raw echo — Reflected XSS */ ?>">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </form>

    <?php if ($q !== ''): ?>
    <div class="search-meta">
        <!-- VULN-02: Raw reflected XSS — $q not escaped -->
        Results for: <strong><?= $q ?></strong>
        (<?= count($results) ?> found)
    </div>
    <?php endif; ?>

    <?php if (!empty($sql_error)): ?>
    <!-- VULN-09: Verbose SQL error exposed to user -->
    <div class="alert alert-danger">
        <strong>Database error:</strong> <?= $sql_error ?><br>
        <small>Query: <code><?= htmlspecialchars($sql_run) ?></code></small>
    </div>
    <?php endif; ?>

    <div class="rooms-grid">
    <?php foreach ($results as $room): ?>
        <div class="room-card">
            <div class="room-img-wrap">
                <div class="room-img-placeholder room-type-<?= $room['type'] ?>">
                    <?php $icons = ['standard'=>'fa-bed','deluxe'=>'fa-star','suite'=>'fa-crown','conference'=>'fa-people-group'];?>
                    <i class="fa-solid <?= $icons[$room['type']] ?? 'fa-bed' ?>"></i>
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
                <a href="/vuln1/book.php?room_id=<?= $room['id'] ?>" class="btn btn-primary btn-full">Book Now</a>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <?php if ($q !== '' && count($results) === 0 && empty($sql_error)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-face-frown"></i>
        <p>No rooms found matching your search.</p>
    </div>
    <?php endif; ?>

    <div class="vuln-note">
        <strong><i class="fa-solid fa-flask"></i> VULN-01 + VULN-02:</strong><br>
        Try: <code>?q=&lt;script&gt;alert('XSS')&lt;/script&gt;</code><br>
        Try: <code>?q=' UNION SELECT 1,2,3,4,5,6,7,8--</code>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
