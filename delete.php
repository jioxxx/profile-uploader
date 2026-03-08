<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$username = trim($_POST['username'] ?? '');
$db       = get_db();
$stmt     = $db->prepare("SELECT * FROM profiles WHERE username = ?");
$stmt->execute([$username]);
$p        = $stmt->fetch();

if ($p) {
    if ($p['avatar']) {
        @unlink(__DIR__ . '/uploads/avatars/' . $p['avatar']);
    }
    $db->prepare("DELETE FROM profiles WHERE id = ?")->execute([$p['id']]);
}

header('Location: index.php?deleted=1');
exit;
