<?php

// ── Change these to match your Laragon setup ──
define('DB_HOST', 'localhost');
define('DB_NAME', 'profilegen');
define('DB_USER', 'root');
define('DB_PASS', '');

// ── SMTP Mail Configuration ──
// IMPORTANT: Update these with your real SMTP credentials
// For Gmail: Use App Password (not your regular password)
// Generate App Password: https://myaccount.google.com/apppasswords
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'deguzmanken1997@gmail.com');      // Your Gmail address
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');        // Your 16-char App Password
define('SMTP_FROM_EMAIL', 'noreply@profilegen.local');
define('SMTP_FROM_NAME', 'ProfileGen');

function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            die("<p style='color:red;font-family:sans-serif;padding:2rem'>
                <strong>DB Error:</strong> " . $e->getMessage() . "<br><br>
                Make sure you ran <code>setup.php</code> first and Laragon MySQL is running.
            </p>");
        }
    }
    return $pdo;
}
