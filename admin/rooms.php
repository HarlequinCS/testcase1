<?php
// =====================================================
// BookEase Lab - Admin: Manage Rooms
// =====================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

$success = $error = '';

// Toggle room status — VULN-07: No CSRF
if (isset($_GET['toggle'])) {
    $rid = (int)$_GET['toggle'];
    $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM rooms WHERE id=$rid"));
    $new = ($cur['status'] === 'available') ? 'maintenance' : 'available';
    mysqli_query($conn, "UPDATE rooms SET status='$new' WHERE id=$rid");
    $success = "Room #$rid status set to $new.";
}

// Add room — VULN-01: No parameterised query, VULN-07: No CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $name  = $_POST['name']  ?? '';
    $type  = $_POST['type']  ?? 'standard';
    $desc  = $_POST['desc']  ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $cap   = (int)($_POST['capacity'] ?? 2);
    $sql   = "INSERT INTO rooms (name, type, description, price_per_night, capacity)
              VALUES ('$name','$type','$desc',$price,$cap)";
    if (mysqli_query($conn, $sql)) {
        $success = 'Room added.';
    } else {
        $error = mysqli_error($conn); // VULN-09
    }
}

$rooms = mysqli_query($conn, "SELECT * FROM rooms ORDER BY id");
$page_title = 'Manage Rooms';
require_once '../includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-bed"></i> Manage Rooms</h1>
        <a href="/vuln1/admin/" class="btn btn-outline">← Admin Home</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <!-- Add Room Form -->
    <div class="card mb-md">
        <div class="card-header"><h3><i class="fa-solid fa-plus"></i> Add Room</h3></div>
        <form method="POST" class="booking-form p-md">
            <div class="form-row">
                <div class="form-group">
                    <label>Room Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Room name">
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="standard">Standard</option>
                        <option value="deluxe">Deluxe</option>
                        <option value="suite">Suite</option>
                        <option value="conference">Conference</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price/Night ($)</label>
                    <input type="number" name="price" class="form-control" placeholder="99.00" step="0.01">
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" class="form-control" placeholder="2" min="1">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="desc" class="form-control" rows="2"></textarea>
            </div>
            <button name="add_room" value="1" class="btn btn-primary">Add Room</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Price/Night</th><th>Capacity</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php while ($r = mysqli_fetch_assoc($rooms)): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= ucfirst($r['type']) ?></td>
                <td>$<?= number_format($r['price_per_night'],2) ?></td>
                <td><?= $r['capacity'] ?></td>
                <td><span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td>
                    <a href="/vuln1/admin/rooms.php?toggle=<?= $r['id'] ?>" class="btn btn-sm btn-outline">Toggle Status</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
