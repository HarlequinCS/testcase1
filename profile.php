<?php
// =====================================================
// BookEase Lab - User Profile + Avatar Upload
// VULN-08: Insecure file upload — accepts any file type (PHP shells, etc.)
// VULN-07: No CSRF token
// VULN-09: Shows full server path and extension info on error
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();

$user    = current_user();
$uid     = (int)$user['id'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Update profile info ---
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'] ?? '';
        $phone     = $_POST['phone']     ?? '';
        $email     = $_POST['email']     ?? '';
        // VULN-01: No parameterised query
        $sql = "UPDATE users SET full_name='$full_name', phone='$phone', email='$email' WHERE id=$uid";
        if (mysqli_query($conn, $sql)) {
            // Refresh session
            $res = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
            $_SESSION['user'] = mysqli_fetch_assoc($res);
            $user = $_SESSION['user'];
            $success = 'Profile updated successfully.';
        } else {
            $error = 'Update failed: ' . mysqli_error($conn); // VULN-09
        }
    }

    // --- Upload avatar ---
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $upload_dir = __DIR__ . '/uploads/';
        $filename   = $_FILES['avatar']['name'];      // VULN-08: original name kept (path traversal risk)
        $tmp        = $_FILES['avatar']['tmp_name'];

        // VULN-08: No file type or extension validation whatsoever
        // Accepts: .php, .phtml, .php5, .phar — any file extension
        $dest = $upload_dir . $filename;
        if (move_uploaded_file($tmp, $dest)) {
            $rel_path = '/vuln1/uploads/' . $filename;
            mysqli_query($conn, "UPDATE users SET avatar='$rel_path' WHERE id=$uid");
            $_SESSION['user']['avatar'] = $rel_path;
            $user['avatar'] = $rel_path;
            $success = "Avatar uploaded to: <code>$dest</code>";  // VULN-09: exposes server path
        } else {
            $error = "Upload failed. Server path: <code>$upload_dir</code>";  // VULN-09
        }
    }
}

$page_title = 'My Profile';
require_once 'includes/header.php';
?>
<div class="container container-sm">
    <div class="page-header">
        <h1><i class="fa-solid fa-user-pen"></i> My Profile</h1>
    </div>

    <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="profile-layout">
        <!-- Avatar card -->
        <div class="card profile-avatar-card">
            <div class="avatar-display">
                <?php if ($user['avatar']): ?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="avatar-img">
                <?php else: ?>
                <div class="avatar-placeholder"><i class="fa-solid fa-user fa-3x"></i></div>
                <?php endif; ?>
            </div>
            <h3><?= htmlspecialchars($user['full_name']) ?></h3>
            <span class="role-badge role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>

            <!-- VULN-07: No CSRF token. VULN-08: No file-type restriction -->
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <label class="btn btn-outline btn-full" style="cursor:pointer">
                    <i class="fa-solid fa-upload"></i> Upload Avatar
                    <input type="file" name="avatar" style="display:none" onchange="this.form.submit()">
                </label>
            </form>
            <div class="vuln-note mt-sm">
                <strong>⚠ VULN-08:</strong> Upload <code>shell.php</code> — it will be stored in
                <code>/uploads/</code> and is directly executable via the web server.
            </div>
        </div>

        <!-- Profile form -->
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-id-card"></i> Account Information</h3></div>
            <!-- VULN-07: No CSRF token -->
            <form method="POST" class="auth-form">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label>Username <small>(cannot change)</small></label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           value="<?= htmlspecialchars($user['full_name']) ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($user['email']) ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" class="form-control"
                           value="<?= htmlspecialchars($user['phone']) ?>">
                </div>
                <div class="form-group">
                    <label>Role <small>(stored in session — VULN-04)</small></label>
                    <input type="text" class="form-control vuln-field" value="<?= $user['role'] ?>" disabled>
                    <small class="vuln-hint">⚠ Role is only checked from session; try editing it in browser DevTools.</small>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
            </form>
        </div>
    </div>

    <?php if ($user['avatar'] && str_ends_with($user['avatar'], '.php')): ?>
    <div class="alert alert-danger mt-md">
        <strong><i class="fa-solid fa-skull"></i> VULN-08 Demonstrated:</strong>
        A PHP file was uploaded. Execute it at:
        <a href="<?= htmlspecialchars($user['avatar']) ?>" target="_blank"><?= htmlspecialchars($user['avatar']) ?></a>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
