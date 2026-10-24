-- =====================================================
-- BookEase Lab - Vulnerable Booking System
-- Database Schema + Sample Data
-- WARNING: FOR ETHICAL HACKING LAB USE ONLY
-- DO NOT DEPLOY ON PUBLIC SERVERS
-- =====================================================

CREATE DATABASE IF NOT EXISTS bookease_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookease_lab;

-- -------------------------------------------------------
-- Users Table (intentionally weak: MD5 passwords, no salts)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(80)  NOT NULL UNIQUE,
    email       VARCHAR(120) NOT NULL UNIQUE,
    password    VARCHAR(64)  NOT NULL,          -- VULN: MD5 hash, no salt
    full_name   VARCHAR(150) NOT NULL,
    phone       VARCHAR(20),
    role        ENUM('customer','staff','admin') NOT NULL DEFAULT 'customer',
    avatar      VARCHAR(255) DEFAULT NULL,       -- VULN: insecure upload path stored here
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    reset_token VARCHAR(64) DEFAULT NULL         -- VULN: predictable token
);

-- -------------------------------------------------------
-- Rooms Table
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS rooms (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(120) NOT NULL,
    type         ENUM('standard','deluxe','suite','conference') NOT NULL DEFAULT 'standard',
    description  TEXT,
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity     INT NOT NULL DEFAULT 2,
    image        VARCHAR(255) DEFAULT 'default-room.jpg',
    status       ENUM('available','maintenance','inactive') NOT NULL DEFAULT 'available'
);

-- -------------------------------------------------------
-- Bookings Table
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    room_id       INT NOT NULL,
    check_in      DATE NOT NULL,
    check_out     DATE NOT NULL,
    guests        INT NOT NULL DEFAULT 1,
    total_price   DECIMAL(10,2) NOT NULL,        -- VULN: taken from client POST, not recalculated
    status        ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    notes         TEXT DEFAULT NULL,              -- VULN: stored XSS
    coupon_code   VARCHAR(30) DEFAULT NULL,
    discount_pct  INT DEFAULT 0,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- -------------------------------------------------------
-- Coupons Table
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30) NOT NULL UNIQUE,
    discount    INT NOT NULL,                     -- percentage
    single_use  TINYINT(1) DEFAULT 0,
    used        TINYINT(1) DEFAULT 0,
    expires_at  DATE
);

-- -------------------------------------------------------
-- Audit / Activity Log
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT DEFAULT NULL,
    action      VARCHAR(120) NOT NULL,
    detail      TEXT,
    ip_address  VARCHAR(45),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =======================================================
-- SAMPLE DATA
-- =======================================================

-- USERS  (passwords = MD5 of shown values, no salt)
-- admin123  → 0192023a7bbd73250516f069df18b500
-- password1 → 7c6a180b36896a0a8c02787eeafb0e4c
-- letmein   → 0d107d09f5bbe40cade3de5c71e9e9b7
-- test1234  → b59c67bf196a4758191e42f76670ceba
-- staff2024 → e573f7aa1c1bdb67d7abb9f3ced65da3

INSERT INTO users (username, email, password, full_name, phone, role) VALUES
('admin',    'admin@bookease.lab',   '0192023a7bbd73250516f069df18b500', 'Alice Admin',    '555-0100', 'admin'),
('jstaff',   'staff@bookease.lab',   'e573f7aa1c1bdb67d7abb9f3ced65da3', 'James Staff',    '555-0101', 'staff'),
('alice',    'alice@mail.lab',       '7c6a180b36896a0a8c02787eeafb0e4c', 'Alice Walker',   '555-0102', 'customer'),
('bob',      'bob@mail.lab',         '0d107d09f5bbe40cade3de5c71e9e9b7', 'Bob Martinez',   '555-0103', 'customer'),
('carol',    'carol@mail.lab',       'b59c67bf196a4758191e42f76670ceba', 'Carol Chen',     '555-0104', 'customer'),
('dave',     'dave@mail.lab',        '7c6a180b36896a0a8c02787eeafb0e4c', 'Dave Kumar',     '555-0105', 'customer');

-- ROOMS
INSERT INTO rooms (name, type, description, price_per_night, capacity, image) VALUES
('Ocean View Standard', 'standard',   'Comfortable room with partial ocean view, queen bed, and en-suite bathroom.', 89.00,  2, 'room1.jpg'),
('Garden Deluxe',       'deluxe',     'Spacious room overlooking the garden with king bed and premium amenities.',   139.00, 2, 'room2.jpg'),
('Executive Suite',     'suite',      'Luxury suite with living area, panoramic views, and butler service.',         249.00, 3, 'room3.jpg'),
('Family Suite',        'suite',      'Two interconnected rooms ideal for families, sleeps up to five guests.',       199.00, 5, 'room4.jpg'),
('Budget Single',       'standard',   'Clean, compact room with single bed — perfect for solo travellers.',          59.00,  1, 'room5.jpg'),
('Conference Room A',   'conference', '20-person boardroom with AV equipment, whiteboard, and catering options.',    299.00, 20,'room6.jpg'),
('Conference Room B',   'conference', '10-person meeting room with projector and video conferencing setup.',         199.00, 10,'room7.jpg'),
('Penthouse Suite',     'suite',      'Top-floor suite with rooftop terrace, jacuzzi, and panoramic city views.',    499.00, 4, 'room8.jpg');

-- BOOKINGS (mix of statuses; intentionally overlapping rooms for race-condition demos)
INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_price, status, notes) VALUES
(3, 1, '2026-11-01', '2026-11-04', 2, 267.00,  'confirmed',  'Early check-in requested.'),
(4, 2, '2026-11-05', '2026-11-08', 1, 417.00,  'confirmed',  'Need airport pickup.'),
(5, 3, '2026-11-10', '2026-11-12', 2, 498.00,  'pending',    'Allergic to feather pillows.'),
(3, 4, '2026-11-15', '2026-11-17', 4, 398.00,  'confirmed',  ''),
(6, 5, '2026-11-20', '2026-11-22', 1,  118.00, 'cancelled',  'Trip cancelled.'),
(4, 6, '2026-11-25', '2026-11-26', 8, 299.00,  'completed',  'Board meeting.'),
(5, 1, '2026-12-01', '2026-12-03', 2, 178.00,  'pending',    ''),
(6, 8, '2026-12-10', '2026-12-14', 2, 1996.00, 'confirmed',  'Anniversary stay — surprise setup please.');

-- COUPONS
INSERT INTO coupons (code, discount, single_use, used, expires_at) VALUES
('WELCOME10', 10, 0, 0, '2027-12-31'),
('VIP25',     25, 1, 0, '2027-06-30'),
('STAFF50',   50, 1, 0, '2027-12-31'),
('USED20',    20, 1, 1, '2027-12-31');  -- already used single-use coupon
