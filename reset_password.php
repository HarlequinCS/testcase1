<?php
// =====================================================
// BookEase Lab - Password Reset
// VULN-04: Predictable reset tokens (MD5 of username+timestamp)
// VULN-09: Confirms whether email exists (user enumeration)
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';

$step    = $_GET['step'] ?? 'request';
$token   = $_GET['token'] ?? '';
$message = '';
$error   = '';

if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    // VULN-09: Reveals if email is registered (user enumeration)
    $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($res) === 0) {
        $error = "No account found with email: <strong>$email</strong>";
    } else {
        $user  = mysqli_fetch_assoc($res);
        // VULN-04: Token is predictable — MD5(username + current minute)
        $token = md5($user['username'] . date('YmdHi'));
        mysqli_query($conn, "UPDATE users SET reset_token='$token' WHERE id=" . $user['id']);
        // In a real app this would be emailed; here we just display it
        $message = "Reset link (simulated email): <a href='/vuln1/reset_password.php?step=reset&token=$token'>/vuln1/reset_password.php?step=reset&amp;token=$token</a><br>
                    <small class='vuln-hint'>⚠ VULN-04: Token is MD5(username+minute) — predictable.</small>";
    }
}

if ($step === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['new_password'] ?? '';
    $res = mysqli_query($conn, "SELECT * FROM users WHERE reset_token='$token'");
    if (mysqli_num_rows($res) === 0) {
        $error = 'Invalid or expired token.';
    } else {
        $user = mysqli_fetch_assoc($res);
        $hashed = hash_password($new_pass); // VULN-04: MD5 again
        mysqli_query($conn, "UPDATE users SET password='$hashed', reset_token=NULL WHERE id=" . $user['id']);
        $message = 'Password reset! <a href="/vuln1/login.php">Log in</a>.';
        $step = 'done';
    }
}

$page_title = 'Reset Password';
require_once 'includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fa-solid fa-key auth-icon"></i>
            <h2>Reset Password</h2>
        </div>

        <?php if ($error):  ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if ($message):?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

        <?php if ($step === 'request'): ?>
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Registered Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Send Reset Link</button>
        </form>

        <?php elseif ($step === 'reset'): ?>
        <form method="POST" class="auth-form">
            <input type="hidden" name="new_password" value="">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control"
                       placeholder="New password (no rules)">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Set New Password</button>
        </form>
        <?php endif; ?>

        <div class="vuln-note">
            <strong><i class="fa-solid fa-flask"></i> VULN-04:</strong> Token = MD5(username + YmdHi).<br>
            Brute-force the minute window to predict any user's token without email access.
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
