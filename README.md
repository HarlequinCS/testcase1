# BookEase Lab — Installation Guide

> ⚠️ **FOR ETHICAL HACKING & PENETRATION TESTING ONLY**
> Run on `localhost` only. Never expose to a public network.

---

## Prerequisites

Install one of the following local PHP/MySQL stacks:

- **Laragon** (recommended for Windows) — https://laragon.org
- **XAMPP** — https://www.apachefriends.org
- **WAMP** — https://www.wampserver.com

Requires: **PHP 8.0+**, **MySQL 5.7+ / MariaDB 10.4+**, **Apache 2.4+**

---

## Installation

### Step 1 — Copy Files

Place the `vuln1/` folder in your web root:

| Stack   | Web Root Path |
|---------|--------------|
| Laragon | `C:\laragon\www\vuln1\` |
| XAMPP   | `C:\xampp\htdocs\vuln1\` |
| WAMP    | `C:\wamp64\www\vuln1\` |

### Step 2 — Create the Database

**Option A — via phpMyAdmin**
1. Open `http://localhost/phpmyadmin`
2. Click **Import** → **Choose File** → select `vuln1/db/schema.sql`
3. Click **Go**

**Option B — via Laragon Quick Create**
1. Right-click Laragon tray → Database → Open HeidiSQL
2. Create a new database named `bookease_lab`
3. Run the contents of `db/schema.sql`

**Option C — via MySQL CLI**
```bash
mysql -u root -p < C:\laragon\www\vuln1\db\schema.sql
```

### Step 3 — Configure Database Credentials

Edit `includes/db.php` if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Change if your root has a password
define('DB_NAME', 'bookease_lab');
```

### Step 4 — Set Permissions

Ensure the `uploads/` folder is writable by the web server:
```bash
# Linux / WSL
chmod 777 /path/to/vuln1/uploads/

# Windows — usually already writable under Laragon/XAMPP
```

### Step 5 — Start the Server

- **Laragon:** Click "Start All" in the Laragon control panel
- **XAMPP:** Start Apache and MySQL in the XAMPP control panel
- **WAMP:** Left-click the WAMP tray icon → Start All Services

### Step 6 — Open the Application

Navigate to: **http://localhost/vuln1/**

---

## Test Accounts

| Role      | Username | Password  | Notes |
|-----------|----------|-----------|-------|
| Admin     | `admin`  | `admin123`| Full access |
| Staff     | `jstaff` | `staff2024`| Manage bookings |
| Customer  | `alice`  | `password1`| Owns bookings #1, #4 |
| Customer  | `bob`    | `letmein` | Owns bookings #2, #6 |
| Customer  | `carol`  | `test1234`| Owns booking #3, #7 |
| Customer  | `dave`   | `password1`| Owns bookings #5, #8 |

---

## Quick Vulnerability Testing Checklist

| # | Vulnerability | Quick Test URL |
|---|--------------|----------------|
| 01 | SQL Injection | Login with `admin'--` |
| 02 | XSS | `search.php?q=<script>alert(1)</script>` |
| 03 | Broken Access Control | Browse to `/vuln1/admin/` as a customer |
| 04 | Weak Auth | Register with password `1`; brute-force login |
| 05 | IDOR | `invoice.php?id=2` (while logged in as alice) |
| 06 | Price Manipulation | Edit hidden `total_price` field in DevTools |
| 07 | CSRF | Submit a forged cancellation form from another page |
| 08 | File Upload | Upload `shell.php` as avatar |
| 09 | Info Disclosure | Break DB connection; observe detailed errors |
| 10 | Missing Headers | `curl -I http://localhost/vuln1/` |

---

## Recommended Tools

| Tool | Purpose |
|------|---------|
| **Burp Suite Community** | Intercept and modify HTTP requests (VULN-06, VULN-07) |
| **OWASP ZAP** | Automated vulnerability scanning |
| **sqlmap** | Automated SQL injection testing (VULN-01) |
| **Hydra** | Brute-force login testing (VULN-04) |
| **Browser DevTools** | Edit form fields, inspect cookies, DOM XSS (VULN-02, 06) |
| **Hashcat** | Crack MD5 password hashes (VULN-04) |

### sqlmap Example
```bash
# Test login endpoint
sqlmap -u "http://localhost/vuln1/login.php" \
       --data="username=admin&password=test" \
       --method=POST --dbs --batch

# Test search endpoint
sqlmap -u "http://localhost/vuln1/search.php?q=test" --dbs --batch
```

---

## Project Structure

```
vuln1/
├── index.php               # Landing page + vulnerability index
├── login.php               # VULN-01, VULN-04, VULN-09
├── register.php            # VULN-04, VULN-09
├── logout.php              # VULN-04 (weak session invalidation)
├── reset_password.php      # VULN-04 (predictable token)
├── dashboard.php           # User home
├── rooms.php               # Room listing
├── search.php              # VULN-01 (SQLi), VULN-02 (Reflected XSS)
├── book.php                # VULN-02 (Stored XSS), VULN-06, VULN-07
├── bookings.php            # VULN-02 (Stored XSS), VULN-05
├── invoice.php             # VULN-03, VULN-05 (IDOR), VULN-02
├── cancel.php              # VULN-03, VULN-05 (IDOR), VULN-07
├── profile.php             # VULN-07, VULN-08 (file upload), VULN-09
├── admin/
│   ├── index.php           # VULN-03 (BAC)
│   ├── users.php           # VULN-01, VULN-03, VULN-07
│   ├── bookings.php        # VULN-02, VULN-07
│   ├── rooms.php           # VULN-01, VULN-07
│   └── logs.php            # VULN-09 (info disclosure)
├── includes/
│   ├── db.php              # VULN-09 (verbose DB errors)
│   ├── auth.php            # VULN-04 (MD5, no regeneration)
│   ├── header.php          # VULN-10 (no security headers)
│   └── footer.php
├── assets/
│   ├── css/style.css
│   └── js/app.js           # VULN-06 (client-side price calc)
├── uploads/                # VULN-08 (PHP shells land here)
├── db/schema.sql
├── VULNERABILITIES.md      # Detailed testing guide
└── README.md               # This file
```

---

## Legal Notice

This application is provided for **educational purposes only**.
You must only test it against systems you own or have explicit written permission to test.
Misuse of this software to attack systems without authorization is illegal.
