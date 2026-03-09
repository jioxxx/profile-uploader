<?php
require 'db.php';

$token = trim($_GET['token'] ?? '');
if (!$token) {
    die("<p style='font-family:sans-serif;padding:2rem'>Invalid token. <a href='index.php'>Go home</a></p>");
}

$profiles = get_all_profiles();
$verified_username = null;

foreach ($profiles as $p) {
    if (isset($p['verification_token']) && $p['verification_token'] === $token && isset($p['verified']) && $p['verified'] === false) {
        // We found them!
        update_profile($p['username'], [
            'verified' => true,
            'verification_token' => null
        ]);
        $verified_username = $p['username'];
        break;
    }
}

if ($verified_username) {
    header('Location: profile.php?u=' . urlencode($verified_username) . '&verified=1');
    exit;
} else {
    die("<p style='font-family:sans-serif;padding:2rem'>Invalid or expired verification link. <a href='index.php'>Go home</a></p>");
}
