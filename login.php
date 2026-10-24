<?php
// =====================================================
// BookEase Lab - Login
// VULN-01: SQL Injection via string concatenation
// VULN-04: No rate limiting / brute-force protection
// VULN-04: Session ID not regenerated after login
// VULN-09: Verbose error messages on failed queries
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // VULN-01: Raw string concatenation — classic SQLi
    // Payload: admin'-- or ' OR '1'='1
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '" . md5($password) . "'";

    // VULN-09: On query failure, full error + SQL is shown
    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        die("Query failed: " . mysqli_error($conn) . "<br>SQL: <pre>$sql</pre>");
    }

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        // VULN-04: No session_regenerate_id() — session fixation possible
        $_SESSION['user'] = $user;
        log_activity($conn, 'login', "User {$user['username']} logged in");
        header('Location: /vuln1/dashboard.php');
        exit;
    } else {
        // VULN-09: Reveals whether user exists vs. wrong password
        $check_user = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
        if (mysqli_num_rows($check_user) === 0) {
            $error = "No account found with username: <strong>" . htmlspecialchars($username) . "</strong>";
        } else {
            $error = "Incorrect password for user: <strong>" . htmlspecialchars($username) . "</strong>";
        }
    }
}

$page_title = 'Login';
require_once 'includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fa-solid fa-right-to-bracket auth-icon"></i>
            <h2>Sign In</h2>
            <p>Access your BookEase Lab account</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- VULN-07: No CSRF token in this form -->
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="username"><i class="fa-solid fa-user"></i> Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="Enter username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Enter password">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Sign In</button>
        </form>

        <div class="auth-footer">
            <p>No account? <a href="/vuln1/register.php">Register here</a></p>
            <p><a href="/vuln1/reset_password.php">Forgot password?</a></p>
        </div>

        <div class="vuln-note">
            <strong><i class="fa-solid fa-flask"></i> VULN-01 — SQL Injection:</strong><br>
            Try username: <code>admin'--</code> with any password.<br>
            Or: username <code>' OR '1'='1'--</code>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
