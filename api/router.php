<?php
// Centralized router to keep all Vercel PHP requests inside a single 
// Serverless Function instance. This ensures the /tmp storage is shared 
// across different endpoints (like create.php -> profile.php redirect) 
// instead of spinning up isolated lambda containers.

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = trim($path, '/');

if ($path === '' || $path === 'index.php') {
    require __DIR__ . '/index.php';
} elseif ($path === 'create.php') {
    require __DIR__ . '/create.php';
} elseif ($path === 'profile.php') {
    require __DIR__ . '/profile.php';
} elseif ($path === 'edit.php') {
    require __DIR__ . '/edit.php';
} elseif ($path === 'delete.php') {
    require __DIR__ . '/delete.php';
} else {
    // fallback
    require __DIR__ . '/index.php';
}
