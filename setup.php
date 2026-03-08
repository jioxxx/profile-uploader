<?php
// Run this ONCE to create the database and table.
// Visit: http://localhost/profilegen/setup.php

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS profilegen CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE profilegen");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS profiles (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(100) NOT NULL,
            username   VARCHAR(50)  NOT NULL UNIQUE,
            email      VARCHAR(150) NOT NULL UNIQUE,
            headline   VARCHAR(150),
            bio        TEXT,
            location   VARCHAR(100),
            website    VARCHAR(255),
            avatar     VARCHAR(255),
            github     VARCHAR(100),
            twitter    VARCHAR(100),
            linkedin   VARCHAR(100),
            skills     TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "
    <style>body{font-family:sans-serif;padding:2rem;background:#f4f4f0}</style>
    <h2 style='color:green'>✅ Setup complete!</h2>
    <p>Database <strong>profilegen</strong> and table <strong>profiles</strong> created.</p>
    <a href='index.php' style='color:#e8410a'>→ Go to the app</a>
    ";

} catch (PDOException $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
