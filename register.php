<?php
// =====================================================
// BookEase Lab - Registration
// VULN-04: Accepts any password (no complexity rules)
// VULN-09: Reveals whether username/email already exists (user enumeration)
// VULN-07: No CSRF token
// =====================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone']     ?? '');

    // VULN-04: No password strength check whatsoever
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'All fields are required.';
    } else {
        // VULN-09: Specific messages reveal which field is already taken (user enumeration)
        $check = mysqli_query($conn, "SELECT id, username, email FROM users WHERE username='$username' OR email='$email'");
        if ($row = mysqli_fetch_assoc($check)) {
            if ($row['username'] === $username) {
                $error = "Username <strong>$username</strong> is already registered.";
            } else {
                $error = "Email <strong>$email</strong> is already registered.";
            }
        } else {
            $hashed = hash_password($password);  // VULN-04: MD5, no salt
            // VULN-01: No parameterized query
            $sql = "INSERT INTO users (username, email, password, full_name, phone, role)
                    VALUES ('$username', '$email', '$hashed', '$full_name', '$phone', 'customer')";
            if (mysqli_query($conn, $sql)) {
                $success = 'Account created! <a href="/vuln1/login.php">Log in now</a>.';
            } else {
                $error = "Registration failed: " . mysqli_error($conn); // VULN-09
            }
        }
    }
}

$page_title = 'Register';
require_once 'includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card auth-card-wide">
        <div class="auth-header">
            <i class="fa-solid fa-user-plus auth-icon"></i>
            <h2>Create Account</h2>
            <p>Join BookEase Lab to make reservations</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- VULN-07: No CSRF token -->
        <form method="POST" class="auth-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           placeholder="e.g. Alice Walker"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" class="form-control"
                           placeholder="e.g. 555-0100"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="Choose a username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="your@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <!-- VULN-04: No minlength, no pattern — accepts "1" as a valid password -->
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Any password (no rules enforced)">
                <small class="form-hint vuln-hint">⚠ VULN-04: No password complexity requirements.</small>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Create Account</button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="/vuln1/login.php">Sign in</a></p>
        </div>

        <div class="vuln-note">
            <strong><i class="fa-solid fa-flask"></i> VULN-04 + VULN-09:</strong><br>
            Register with password "1" — accepted.<br>
            Try username "alice" — reveals that it's already taken (user enumeration).
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
