<?php
// =====================================================
// BookEase Lab - Admin: User Management
// VULN-03: Admin role only session-checked
// VULN-01: SQLi in search filter
// VULN-07: No CSRF on delete/role-change
// =====================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_admin();

$success = $error = '';

// Delete user
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // VULN-07: No CSRF — any page can trigger this with a link
    mysqli_query($conn, "DELETE FROM users WHERE id=$del_id");
    $success = "User #$del_id deleted.";
}

// Change role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $uid  = (int)$_POST['user_id'];
    $role = $_POST['role'];
    // VULN-01: $role not validated / sanitized
    mysqli_query($conn, "UPDATE users SET role='$role' WHERE id=$uid");
    $success = "Role updated for user #$uid.";
}

$search = $_GET['search'] ?? '';
// VULN-01: SQLi in admin search
$sql = "SELECT * FROM users";
if ($search) {
    $sql .= " WHERE username LIKE '%$search%' OR email LIKE '%$search%' OR full_name LIKE '%$search%'";
}
$users = mysqli_query($conn, $sql);

$page_title = 'Manage Users';
require_once '../includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-users"></i> Manage Users</h1>
        <a href="/vuln1/admin/" class="btn btn-outline">← Admin Home</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <form method="GET" class="search-form mb-md">
        <!-- VULN-01: search param injected into SQL -->
        <input type="text" name="search" class="form-control search-input"
               placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <div class="card">
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Phone</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while ($u = mysqli_fetch_assoc($users)): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td><?= htmlspecialchars($u['phone']) ?></td>
                <td>
                    <!-- VULN-07: No CSRF token on role change -->
                    <form method="POST" style="display:flex;gap:.4rem;align-items:center">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <select name="role" class="form-control form-control-sm">
                            <?php foreach (['customer','staff','admin'] as $r): ?>
                            <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button name="change_role" value="1" class="btn btn-sm btn-primary">Set</button>
                    </form>
                </td>
                <td><?= $u['created_at'] ?></td>
                <td>
                    <!-- VULN-07: Delete via GET link — no CSRF protection -->
                    <a href="/vuln1/admin/users.php?delete=<?= $u['id'] ?>"
                       onclick="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>?')"
                       class="btn btn-sm btn-danger">
                        <i class="fa-solid fa-trash"></i> Delete
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
