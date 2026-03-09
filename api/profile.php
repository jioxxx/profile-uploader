<?php
require 'db.php';
require 'mailer.php';

$username = trim($_GET['u'] ?? '');
if (!$username) { header('Location: index.php'); exit; }

$p = get_profile($username);

if (!$p) { http_response_code(404); die("<p style='font-family:sans-serif;padding:2rem'>Profile not found. <a href='index.php'>Go home</a></p>"); }

$skills = array_filter(array_map('trim', explode(',', $p['skills'] ?? '')));

// Get visitor information for notification
$visitor_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$referrer = $_SERVER['HTTP_REFERER'] ?? '';

// Send profile visit notification
$already_notified = false;
if ($visitor_ip && $visitor_ip !== '127.0.0.1' && $visitor_ip !== '::1') {
    session_start();
    $notify_key = 'notified_' . ($p['id'] ?? $p['username']);
    if (!isset($_SESSION[$notify_key])) {
        notify_profile_visited($p, $visitor_ip, $referrer);
        $_SESSION[$notify_key] = true;
        $already_notified = true;
    }
}

function avatar_url($row) {
    if ($row['avatar'] && file_exists(__DIR__ . '/uploads/avatars/' . $row['avatar'])) {
        return 'uploads/avatars/' . htmlspecialchars($row['avatar']);
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=2d6a4f&color=fff&size=200&bold=true';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($p['name']) ?> — ProfileGen</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
  <a class="brand" href="index.php">Profile<span>Gen</span></a>
  <a class="back" href="index.php">← All Profiles</a>
</nav>

<div class="hero-pd">
  <div class="hero-inner">
    <img class="avatar" src="<?= avatar_url($p) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
    <div class="pname"><?= htmlspecialchars($p['name']) ?></div>
    <div class="phandle">@<?= htmlspecialchars($p['username']) ?></div>
    <?php if($p['headline']): ?>
      <div class="pheadline"><?= htmlspecialchars($p['headline']) ?></div>
    <?php endif ?>

    <div class="meta-row">
      <?php if($p['location']): ?>
        <span class="meta-item">📍 <?= htmlspecialchars($p['location']) ?></span>
      <?php endif ?>
      <?php if($p['website']): ?>
        <span class="meta-item"><a href="<?= htmlspecialchars($p['website']) ?>" target="_blank" rel="noopener">🔗 <?= htmlspecialchars(parse_url($p['website'], PHP_URL_HOST)) ?></a></span>
      <?php endif ?>
    </div>

    <?php if($p['github'] || $p['twitter'] || $p['linkedin']): ?>
      <div class="socials">
        <?php if($p['github']): ?>
          <a href="https://github.com/<?= urlencode($p['github']) ?>" target="_blank" rel="noopener" class="spill">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.44 9.8 8.21 11.39.6.11.82-.26.82-.58l-.01-2.04c-3.34.72-4.04-1.61-4.04-1.61-.55-1.39-1.34-1.76-1.34-1.76-1.09-.74.08-.73.08-.73 1.21.09 1.84 1.24 1.84 1.24 1.07 1.84 2.81 1.31 3.5 1 .11-.78.42-1.31.76-1.61-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.12-.3-.54-1.52.12-3.18 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 3-.4c1.02 0 2.04.13 3 .4 2.28-1.55 3.29-1.23 3.29-1.23.66 1.66.24 2.88.12 3.18.77.84 1.24 1.91 1.24 3.22 0 4.61-2.81 5.63-5.48 5.92.43.37.81 1.1.81 2.22l-.01 3.29c0 .32.22.7.83.58C20.56 21.8 24 17.3 24 12c0-6.63-5.37-12-12-12z"/></svg>
            GitHub
          </a>
        <?php endif ?>
        <?php if($p['twitter']): ?>
          <a href="https://x.com/<?= urlencode($p['twitter']) ?>" target="_blank" rel="noopener" class="spill">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            Twitter / X
          </a>
        <?php endif ?>
        <?php if($p['linkedin']): ?>
          <a href="https://linkedin.com/in/<?= urlencode($p['linkedin']) ?>" target="_blank" rel="noopener" class="spill">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            LinkedIn
          </a>
        <?php endif ?>
      </div>
    <?php endif ?>
  </div>
</div>

<div class="body">

  <?php if(isset($_GET['created'])): ?>
    <div class="flash-ok">✅ Profile created successfully!</div>
  <?php endif ?>

  <div class="actions">
    <a class="btn btn-outline" href="edit.php?u=<?= urlencode($p['username']) ?>">✏️ Edit</a>
    <form method="POST" action="delete.php" style="display:inline" onsubmit="return confirm('Delete this profile? This cannot be undone.')">
      <input type="hidden" name="username" value="<?= htmlspecialchars($p['username']) ?>">
      <button type="submit" class="btn btn-del">🗑 Delete</button>
    </form>
  </div>

  <?php if($p['bio']): ?>
    <div class="pcard">
      <div class="clabel">About</div>
      <p class="bio"><?= htmlspecialchars($p['bio']) ?></p>
    </div>
  <?php endif ?>

  <?php if($skills): ?>
    <div class="pcard">
      <div class="clabel">Skills</div>
      <div class="skill-cloud">
        <?php foreach($skills as $s): ?>
          <span class="stag"><?= htmlspecialchars($s) ?></span>
        <?php endforeach ?>
      </div>
    </div>
  <?php endif ?>

  <div class="pcard">
    <div class="clabel">Contact</div>
    <div class="detail-row">
      <div class="drow">
        <span class="dicon">✉️</span>
        <a href="mailto:<?= htmlspecialchars($p['email']) ?>"><?= htmlspecialchars($p['email']) ?></a>
      </div>
      <?php if($p['website']): ?>
        <div class="drow">
          <span class="dicon">🔗</span>
          <a href="<?= htmlspecialchars($p['website']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($p['website']) ?></a>
        </div>
      <?php endif ?>
      <?php if($p['location']): ?>
        <div class="drow">
          <span class="dicon">📍</span>
          <span><?= htmlspecialchars($p['location']) ?></span>
        </div>
      <?php endif ?>
      <div class="drow" style="color:var(--muted);font-size:.8rem">
        Member since <?= date('F Y', strtotime($p['created_at'] ?? 'now')) ?>
      </div>
    </div>
  </div>

</div>

<footer>ProfileGen &mdash; Plain PHP + Vercel &middot; <?= date('Y') ?></footer>
</body>
</html>
