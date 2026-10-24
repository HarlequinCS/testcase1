<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
$page_title = 'Welcome';
require_once 'includes/header.php';

// Fetch available room count
$rooms_result = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM rooms WHERE status='available'");
$rooms_count  = mysqli_fetch_assoc($rooms_result)['cnt'];

// Fetch booking count
$bk_result   = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM bookings WHERE status='confirmed'");
$bk_count    = mysqli_fetch_assoc($bk_result)['cnt'];
?>

<!-- HERO SECTION -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge"><i class="fa-solid fa-flask"></i> Penetration Testing Lab Environment</div>
        <h1>BookEase<span class="brand-lab">Lab</span></h1>
        <p class="hero-sub">A deliberately vulnerable hotel &amp; room booking system for ethical hacking practice, security research, and vulnerability scanner validation.</p>
        <div class="hero-actions">
            <a href="/vuln1/rooms.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-bed"></i> Browse Rooms</a>
            <?php if (!current_user()): ?>
            <a href="/vuln1/register.php" class="btn btn-outline btn-lg"><i class="fa-solid fa-user-plus"></i> Create Account</a>
            <?php else: ?>
            <a href="/vuln1/bookings.php" class="btn btn-outline btn-lg"><i class="fa-solid fa-calendar-check"></i> My Bookings</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-stats">
        <div class="stat-card">
            <div class="stat-num"><?= $rooms_count ?></div>
            <div class="stat-label">Available Rooms</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $bk_count ?></div>
            <div class="stat-label">Active Bookings</div>
        </div>
        <div class="stat-card">
            <div class="stat-num">10</div>
            <div class="stat-label">Vulnerabilities</div>
        </div>
    </div>
</section>

<!-- VULNERABILITY OVERVIEW CARDS -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Vulnerability Index</h2>
        <p class="section-sub">Each vulnerability is reproducible. Click a card to view the affected page.</p>
        <div class="vuln-grid">
            <a href="/vuln1/login.php" class="vuln-card">
                <span class="vuln-id">VULN-01</span>
                <i class="fa-solid fa-database vuln-icon"></i>
                <h3>SQL Injection</h3>
                <p>Login &amp; Search — raw string concatenation in queries.</p>
            </a>
            <a href="/vuln1/search.php?q=<script>alert(1)</script>" class="vuln-card">
                <span class="vuln-id">VULN-02</span>
                <i class="fa-solid fa-code vuln-icon"></i>
                <h3>Cross-Site Scripting</h3>
                <p>Stored &amp; Reflected XSS in booking notes and search.</p>
            </a>
            <a href="/vuln1/invoice.php?id=1" class="vuln-card">
                <span class="vuln-id">VULN-03</span>
                <i class="fa-solid fa-lock-open vuln-icon"></i>
                <h3>Broken Access Control / IDOR</h3>
                <p>Invoices &amp; bookings accessible by sequential ID.</p>
            </a>
            <a href="/vuln1/login.php" class="vuln-card">
                <span class="vuln-id">VULN-04</span>
                <i class="fa-solid fa-key vuln-icon"></i>
                <h3>Weak Authentication</h3>
                <p>MD5 passwords, no lockout, session fixation possible.</p>
            </a>
            <a href="/vuln1/bookings.php" class="vuln-card">
                <span class="vuln-id">VULN-05</span>
                <i class="fa-solid fa-arrow-up-right-dots vuln-icon"></i>
                <h3>Insecure Direct Object Reference</h3>
                <p>Booking records exposed via predictable IDs.</p>
            </a>
            <a href="/vuln1/book.php?room_id=1" class="vuln-card">
                <span class="vuln-id">VULN-06</span>
                <i class="fa-solid fa-tag vuln-icon"></i>
                <h3>Price Manipulation</h3>
                <p>Total price taken from hidden form field without server-side validation.</p>
            </a>
            <a href="/vuln1/bookings.php" class="vuln-card">
                <span class="vuln-id">VULN-07</span>
                <i class="fa-solid fa-shield-halved vuln-icon"></i>
                <h3>Missing CSRF Protection</h3>
                <p>All state-changing forms lack CSRF tokens.</p>
            </a>
            <a href="/vuln1/profile.php" class="vuln-card">
                <span class="vuln-id">VULN-08</span>
                <i class="fa-solid fa-file-arrow-up vuln-icon"></i>
                <h3>Insecure File Upload</h3>
                <p>Avatar upload accepts any file type including PHP scripts.</p>
            </a>
            <a href="/vuln1/login.php" class="vuln-card">
                <span class="vuln-id">VULN-09</span>
                <i class="fa-solid fa-terminal vuln-icon"></i>
                <h3>Information Disclosure</h3>
                <p>Verbose errors expose database, stack trace, and config details.</p>
            </a>
            <a href="/vuln1/search.php" class="vuln-card">
                <span class="vuln-id">VULN-10</span>
                <i class="fa-solid fa-helmet-safety vuln-icon"></i>
                <h3>Missing Security Headers</h3>
                <p>No CSP, X-Frame-Options, X-Content-Type-Options set.</p>
            </a>
        </div>
    </div>
</section>

<!-- QUICK LOGIN FOR LAB -->
<section class="section section-alt">
    <div class="container">
        <h2 class="section-title">Lab Test Accounts</h2>
        <p class="section-sub">Use these pre-seeded accounts to quickly test different roles.</p>
        <div class="accounts-grid">
            <div class="account-card account-admin">
                <i class="fa-solid fa-user-shield"></i>
                <h4>Administrator</h4>
                <code>admin / admin123</code>
                <p>Full system access</p>
            </div>
            <div class="account-card account-staff">
                <i class="fa-solid fa-user-tie"></i>
                <h4>Staff</h4>
                <code>jstaff / staff2024</code>
                <p>Manage reservations</p>
            </div>
            <div class="account-card account-user">
                <i class="fa-solid fa-user"></i>
                <h4>Customer A</h4>
                <code>alice / password1</code>
                <p>Bookings #1 and #4</p>
            </div>
            <div class="account-card account-user">
                <i class="fa-solid fa-user"></i>
                <h4>Customer B</h4>
                <code>bob / letmein</code>
                <p>Bookings #2 and #6</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
