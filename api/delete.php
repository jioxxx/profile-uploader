<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    
    if ($username) {
        $profile = get_profile($username);
        if ($profile && $profile['avatar']) {
            $avatarPath = __DIR__ . '/uploads/avatars/' . $profile['avatar'];
            if (file_exists($avatarPath)) {
                @unlink($avatarPath);
            }
        }
        
        delete_profile($username);
    }
}

header('Location: index.php');
exit;
