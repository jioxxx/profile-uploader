<?php
require 'db.php';

// Get profiles from JSON storage
$profiles = get_all_profiles();

// Simple search
$search = trim($_GET['q'] ?? '');

if ($search) {
  $searchLower = strtolower($search);
  $profiles = array_filter($profiles, function ($p) use ($searchLower) {
    return strpos(strtolower($p['name'] ?? ''), $searchLower) !== false
      || strpos(strtolower($p['headline'] ?? ''), $searchLower) !== false
      || strpos(strtolower($p['skills'] ?? ''), $searchLower) !== false;
  });
  $profiles = array_values($profiles);
}

// Sort by created_at descending
usort($profiles, function ($a, $b) {
  return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
});

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;
$total = count($profiles);
$pages = ceil($total / $limit);
$profiles = array_slice($profiles, $offset, $limit);

function avatar_url($row)
{
  if (!empty($row['avatar'])) {
    if (strpos($row['avatar'], 'data:image') === 0) {
      return $row['avatar'];
    }
    if (file_exists(__DIR__ . '/uploads/avatars/' . $row['avatar'])) {
      return 'uploads/avatars/' . htmlspecialchars($row['avatar']);
    }
  }
  return 'https://ui-avatars.com/api/?name=' . urlencode($row['name'] ?? 'U') . '&background=2d6a4f&color=fff&size=120&bold=true';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ProfileGen</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <nav>
    <a class="brand" href="index.php">Profile<span>Gen</span></a>
    <a class="nav-btn" href="create.php">+ New Profile</a>
  </nav>

  <div class="hero">
    <h1>Your profile,<br><em>your story.</em></h1>
    <p>Create a simple developer profile and share it with the world.</p>
    <form class="search-bar" method="GET">
      <input type="text" name="q" placeholder="Search by name, skill…" value="<?= htmlspecialchars($search) ?>">
      <button type="submit">Search</button>
    </form>
  </div>

  <div class="section">
    <div class="section-top">
      <h2>All Profiles <?php if ($search): ?><span style="font-size:.9rem;font-weight:400;color:var(--muted)"> —
            "<?= htmlspecialchars($search) ?>"</span><?php endif ?></h2>
      <span class="badge"><?= $total ?></span>
    </div>

    <div class="grid">
      <?php if (empty($profiles)): ?>
        <div class="empty">
          <h3>No profiles yet.</h3>
          <p><?= $search ? 'No results for that search.' : 'Be the first to create one!' ?></p>
          <a href="create.php"
            style="background:var(--green);color:#fff;padding:.55rem 1.2rem;border-radius:7px;text-decoration:none;font-weight:700;font-size:.9rem">Create
            Profile</a>
        </div>
      <?php else: ?>
        <?php foreach ($profiles as $p): ?>
          <a class="card" href="profile.php?u=<?= urlencode($p['username']) ?>">
            <div class="card-top">
              <img class="avatar-sm" src="<?= avatar_url($p) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
              <div>
                <div class="cname"><?= htmlspecialchars($p['name']) ?></div>
                <div class="chandle">@<?= htmlspecialchars($p['username']) ?></div>
              </div>
            </div>
            <?php if ($p['headline']): ?>
              <div class="cheadline"><?= htmlspecialchars($p['headline']) ?></div>
            <?php endif ?>
            <?php
            $skills = array_filter(array_map('trim', explode(',', $p['skills'] ?? '')));
            if ($skills):
              ?>
              <div class="skills">
                <?php foreach (array_slice($skills, 0, 5) as $s): ?>
                  <span class="stag"><?= htmlspecialchars($s) ?></span>
                <?php endforeach ?>
                <?php if (count($skills) > 5): ?><span class="stag">+<?= count($skills) - 5 ?></span><?php endif ?>
              </div>
            <?php endif ?>
            <div class="card-foot">
              <?php if ($p['location']): ?><span>📍 <?= htmlspecialchars($p['location']) ?></span><?php endif ?>
              <?php if ($p['github']): ?><span>GitHub</span><?php endif ?>
              <?php if ($p['linkedin']): ?><span>LinkedIn</span><?php endif ?>
            </div>
          </a>
        <?php endforeach ?>
      <?php endif ?>
    </div>

    <?php if ($pages > 1): ?>
      <div class="pages">
        <?php
        $base = '?page=';
        if ($search)
          $base .= '1&q=' . urlencode($search) . '&page=';
        else
          $base = '?page=';
        ?>
        <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $base . ($page - 1) ?>">‹</a>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <a class="<?= $i == $page ? 'cur' : '' ?>" href="<?= $base . $i ?>"><?= $i ?></a>
        <?php endfor ?>
        <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= $base . ($page + 1) ?>">›</a>
      </div>
    <?php endif ?>
  </div>

  <footer>ProfileGen &mdash; Plain PHP + Vercel &middot; <?= date('Y') ?></footer>
</body>

</html>