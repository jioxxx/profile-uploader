<?php
require 'db.php';

$db = get_db();

// Simple search
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 9;
$offset = ($page - 1) * $limit;

if ($search) {
    $stmt = $db->prepare("SELECT * FROM profiles WHERE name LIKE ? OR headline LIKE ? OR skills LIKE ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
    $countStmt = $db->prepare("SELECT COUNT(*) FROM profiles WHERE name LIKE ? OR headline LIKE ? OR skills LIKE ?");
    $countStmt->execute([$like, $like, $like]);
} else {
    $stmt = $db->prepare("SELECT * FROM profiles ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute();
    $countStmt = $db->prepare("SELECT COUNT(*) FROM profiles");
    $countStmt->execute();
}

$profiles = $stmt->fetchAll();
$total    = (int)$countStmt->fetchColumn();
$pages    = ceil($total / $limit);

function avatar_url($row) {
    if ($row['avatar'] && file_exists(__DIR__ . '/uploads/avatars/' . $row['avatar'])) {
        return 'uploads/avatars/' . htmlspecialchars($row['avatar']);
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=2d6a4f&color=fff&size=120&bold=true';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ProfileGen</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --green:#2d6a4f;--green-l:#40916c;--cream:#faf8f3;--paper:#fff;
  --ink:#1a1a1a;--muted:#888;--border:#e8e4da;--accent:#d4a017;
  --r:10px;
}
body{font-family:'Lato',sans-serif;background:var(--cream);color:var(--ink);min-height:100vh}

/* NAV */
nav{background:var(--green);padding:.9rem 2rem;display:flex;align-items:center;justify-content:space-between}
.brand{font-family:'Playfair Display',serif;color:#fff;font-size:1.4rem;text-decoration:none;letter-spacing:-.5px}
.brand span{color:var(--accent)}
.nav-btn{background:var(--accent);color:#fff;text-decoration:none;padding:.45rem 1.1rem;border-radius:6px;font-size:.83rem;font-weight:700;letter-spacing:.4px;transition:opacity .15s}
.nav-btn:hover{opacity:.85}

/* HERO */
.hero{background:var(--green);padding:3.5rem 2rem 4rem;text-align:center;position:relative;overflow:hidden}
.hero::after{content:'';position:absolute;bottom:-2px;left:0;right:0;height:40px;background:var(--cream);clip-path:ellipse(55% 100% at 50% 100%)}
.hero h1{font-family:'Playfair Display',serif;color:#fff;font-size:clamp(2rem,5vw,3.2rem);line-height:1.1;margin-bottom:.8rem}
.hero h1 em{font-style:normal;color:var(--accent)}
.hero p{color:rgba(255,255,255,.7);font-size:1rem;max-width:420px;margin:0 auto 1.8rem}

/* SEARCH */
.search-bar{display:flex;max-width:400px;margin:0 auto;gap:.5rem}
.search-bar input{flex:1;padding:.6rem 1rem;border:none;border-radius:7px;font-size:.9rem;font-family:'Lato',sans-serif;outline:none}
.search-bar button{background:var(--accent);color:#fff;border:none;padding:.6rem 1.1rem;border-radius:7px;font-weight:700;cursor:pointer;font-size:.85rem}

/* SECTION */
.section{max-width:1080px;margin:0 auto;padding:2.5rem 1.5rem}
.section-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.8rem}
.section-top h2{font-family:'Playfair Display',serif;font-size:1.6rem}
.badge{background:var(--green);color:#fff;font-size:.72rem;font-weight:700;padding:.2rem .6rem;border-radius:99px}

/* GRID */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:1.3rem}

/* CARD */
.card{background:var(--paper);border:1px solid var(--border);border-radius:var(--r);padding:1.5rem;text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:.75rem;transition:box-shadow .15s,transform .15s;position:relative;overflow:hidden}
.card::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--green);transform:scaleY(0);transform-origin:bottom;transition:transform .2s}
.card:hover{box-shadow:0 8px 28px rgba(0,0,0,.09);transform:translateY(-2px)}
.card:hover::before{transform:scaleY(1)}

.card-top{display:flex;align-items:center;gap:.9rem}
.avatar{width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0}
.cname{font-weight:700;font-size:.97rem;line-height:1.2}
.chandle{font-size:.78rem;color:var(--muted)}
.cheadline{font-size:.85rem;color:#555;line-height:1.5}
.skills{display:flex;flex-wrap:wrap;gap:.35rem}
.stag{font-size:.7rem;font-weight:700;background:#edf7f2;color:var(--green-l);border:1px solid #b7e4c7;padding:.18rem .5rem;border-radius:99px}
.card-foot{font-size:.75rem;color:var(--muted);display:flex;gap:.5rem;flex-wrap:wrap;border-top:1px solid var(--border);padding-top:.65rem;margin-top:auto}

/* EMPTY */
.empty{text-align:center;padding:5rem 1rem;grid-column:1/-1}
.empty h3{font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:.5rem}
.empty p{color:var(--muted);margin-bottom:1.2rem}

/* PAGINATION */
.pages{display:flex;justify-content:center;gap:.4rem;padding:2rem 0}
.pages a,.pages span{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:7px;font-size:.85rem;font-weight:700;border:1.5px solid var(--border);text-decoration:none;color:var(--ink);transition:all .12s}
.pages a:hover{background:var(--green);color:#fff;border-color:var(--green)}
.pages .cur{background:var(--green);color:#fff;border-color:var(--green)}
.pages .disabled{opacity:.35;pointer-events:none}

footer{text-align:center;padding:2rem;font-size:.8rem;color:var(--muted);border-top:1px solid var(--border)}
</style>
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
    <h2>All Profiles <?php if($search): ?><span style="font-size:.9rem;font-weight:400;color:var(--muted)"> — "<?= htmlspecialchars($search) ?>"</span><?php endif ?></h2>
    <span class="badge"><?= $total ?></span>
  </div>

  <div class="grid">
    <?php if(empty($profiles)): ?>
      <div class="empty">
        <h3>No profiles yet.</h3>
        <p><?= $search ? 'No results for that search.' : 'Be the first to create one!' ?></p>
        <a href="create.php" style="background:var(--green);color:#fff;padding:.55rem 1.2rem;border-radius:7px;text-decoration:none;font-weight:700;font-size:.9rem">Create Profile</a>
      </div>
    <?php else: ?>
      <?php foreach($profiles as $p): ?>
        <a class="card" href="profile.php?u=<?= urlencode($p['username']) ?>">
          <div class="card-top">
            <img class="avatar" src="<?= avatar_url($p) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <div>
              <div class="cname"><?= htmlspecialchars($p['name']) ?></div>
              <div class="chandle">@<?= htmlspecialchars($p['username']) ?></div>
            </div>
          </div>
          <?php if($p['headline']): ?>
            <div class="cheadline"><?= htmlspecialchars($p['headline']) ?></div>
          <?php endif ?>
          <?php
            $skills = array_filter(array_map('trim', explode(',', $p['skills'] ?? '')));
            if($skills):
          ?>
            <div class="skills">
              <?php foreach(array_slice($skills, 0, 5) as $s): ?>
                <span class="stag"><?= htmlspecialchars($s) ?></span>
              <?php endforeach ?>
              <?php if(count($skills) > 5): ?><span class="stag">+<?= count($skills)-5 ?></span><?php endif ?>
            </div>
          <?php endif ?>
          <div class="card-foot">
            <?php if($p['location']): ?><span>📍 <?= htmlspecialchars($p['location']) ?></span><?php endif ?>
            <?php if($p['github']): ?><span>GitHub</span><?php endif ?>
            <?php if($p['linkedin']): ?><span>LinkedIn</span><?php endif ?>
          </div>
        </a>
      <?php endforeach ?>
    <?php endif ?>
  </div>

  <?php if($pages > 1): ?>
    <div class="pages">
      <?php
        $base = '?page=';
        if($search) $base .= '1&q='.urlencode($search).'&page=';
        else $base = '?page=';
      ?>
      <a class="<?= $page<=1 ? 'disabled' : '' ?>" href="<?= $base.($page-1) ?>">‹</a>
      <?php for($i=1;$i<=$pages;$i++): ?>
        <a class="<?= $i==$page ? 'cur' : '' ?>" href="<?= $base.$i ?>"><?= $i ?></a>
      <?php endfor ?>
      <a class="<?= $page>=$pages ? 'disabled' : '' ?>" href="<?= $base.($page+1) ?>">›</a>
    </div>
  <?php endif ?>
</div>

<footer>ProfileGen &mdash; Plain PHP + Laragon + MySQL &middot; <?= date('Y') ?></footer>
</body>
</html>
