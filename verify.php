<?php
require 'db.php';

$token = trim($_GET['token'] ?? '');
if (!$token) {
    die("<p style='font-family:sans-serif;padding:2rem'>Invalid token. <a href='index.php'>Go home</a></p>");
}

$db = get_db();
$stmt = $db->prepare("SELECT id, username FROM profiles WHERE verification_token = ? AND verified = 0");
$stmt->execute([$token]);
$profile = $stmt->fetch();

if ($profile) {
    // We found them!
    $update = $db->prepare("UPDATE profiles SET verified = 1, verification_token = NULL WHERE id = ?");
    $update->execute([$profile['id']]);

    header('Location: profile.php?u=' . urlencode($profile['username']) . '&verified=1');
    exit;
} else {
    die("<p style='font-family:sans-serif;padding:2rem'>Invalid or expired verification link. <a href='index.php'>Go home</a></p>");
}
