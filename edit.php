<?php
require 'db.php';

$username = trim($_GET['u'] ?? '');
$db       = get_db();
$stmt     = $db->prepare("SELECT * FROM profiles WHERE username = ?");
$stmt->execute([$username]);
$p        = $stmt->fetch();
if (!$p) { header('Location: index.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $new_username= trim($_POST['username'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $headline    = trim($_POST['headline'] ?? '');
    $bio         = trim($_POST['bio'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $website     = trim($_POST['website'] ?? '');
    $github      = trim($_POST['github'] ?? '');
    $twitter     = trim($_POST['twitter'] ?? '');
    $linkedin    = trim($_POST['linkedin'] ?? '');
    $skills      = trim($_POST['skills'] ?? '');

    if (!$name)        $errors['name']     = 'Name is required.';
    if (!$new_username)$errors['username'] = 'Username is required.';
    elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $new_username)) $errors['username'] = 'Only letters, numbers, - and _ allowed.';
    if (!$email)       $errors['email']    = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';
    if ($website && !filter_var($website, FILTER_VALIDATE_URL)) $errors['website'] = 'Invalid URL.';

    if (!$errors) {
        // Check uniqueness (excluding current row)
        $u = $db->prepare("SELECT id FROM profiles WHERE username = ? AND id != ?");
        $u->execute([$new_username, $p['id']]);
        if ($u->fetch()) $errors['username'] = 'Username already taken.';

        $e = $db->prepare("SELECT id FROM profiles WHERE email = ? AND id != ?");
        $e->execute([$email, $p['id']]);
        if ($e->fetch()) $errors['email'] = 'Email already registered.';
    }

    // Avatar
    $avatar = $p['avatar'];
    if (!$errors && isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0) {
        $file    = $_FILES['avatar'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'], $allowed)) {
            $errors['avatar'] = 'Only JPG, PNG, GIF, WEBP allowed.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors['avatar'] = 'Max 2MB.';
        } else {
            if ($p['avatar']) @unlink(__DIR__ . '/uploads/avatars/' . $p['avatar']);
            $ext    = pathinfo($file['name'], PATHINFO_EXTENSION);
            $avatar = uniqid('av_') . '.' . strtolower($ext);
            move_uploaded_file($file['tmp_name'], __DIR__ . '/uploads/avatars/' . $avatar);
        }
    }

    if (!$errors) {
        $upd = $db->prepare("UPDATE profiles SET name=?,username=?,email=?,headline=?,bio=?,location=?,website=?,avatar=?,github=?,twitter=?,linkedin=?,skills=? WHERE id=?");
        $upd->execute([$name,$new_username,$email,$headline,$bio,$location,$website,$avatar,$github,$twitter,$linkedin,$skills,$p['id']]);
        header('Location: profile.php?u=' . urlencode($new_username));
        exit;
    }

    // Re-populate $p for the form
    $p = array_merge($p, compact('name','username','email','headline','bio','location','website','github','twitter','linkedin','skills'));
    $p['username'] = $new_username;
}

function avatar_url($row) {
    if ($row['avatar'] && file_exists(__DIR__ . '/uploads/avatars/' . $row['avatar'])) {
        return 'uploads/avatars/' . htmlspecialchars($row['avatar']);
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=2d6a4f&color=fff&size=144&bold=true';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit — <?= htmlspecialchars($p['name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <a class="brand" href="index.php">Profile<span>Gen</span></a>
  <a class="back" href="profile.php?u=<?= urlencode($username) ?>">← Back to profile</a>
</nav>

<div class="wrap">
  <h1 class="page-title">Edit Profile</h1>

  <?php if($errors): ?>
    <div class="flash-err">Please fix the errors below.</div>
  <?php endif ?>

  <form method="POST" enctype="multipart/form-data">

    <div class="card">
      <div class="sec-label">Photo</div>
      <div class="av-box" onclick="document.getElementById('avatar').click()">
        <img id="av-preview" src="<?= avatar_url($p) ?>" alt="preview">
        <div class="hint">Click to change photo</div>
      </div>
      <input type="file" id="avatar" name="avatar" accept="image/*" style="display:none">
      <?php if(isset($errors['avatar'])): ?><div class="err"><?= $errors['avatar'] ?></div><?php endif ?>
    </div>

    <div class="card">
      <div class="sec-label">Basic Info</div>
      <div class="row">
        <div class="fg">
          <label>Full Name *</label>
          <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" placeholder="Jane Dela Cruz">
          <?php if(isset($errors['name'])): ?><div class="err"><?= $errors['name'] ?></div><?php endif ?>
        </div>
        <div class="fg">
          <label>Username *</label>
          <input type="text" name="username" value="<?= htmlspecialchars($p['username']) ?>">
          <?php if(isset($errors['username'])): ?><div class="err"><?= $errors['username'] ?></div><?php endif ?>
        </div>
      </div>
      <div class="fg">
        <label>Email *</label>
        <input type="email" name="email" value="<?= htmlspecialchars($p['email']) ?>">
        <?php if(isset($errors['email'])): ?><div class="err"><?= $errors['email'] ?></div><?php endif ?>
      </div>
      <div class="fg">
        <label>Headline</label>
        <input type="text" name="headline" value="<?= htmlspecialchars($p['headline'] ?? '') ?>">
      </div>
      <div class="fg">
        <label>Bio</label>
        <textarea name="bio"><?= htmlspecialchars($p['bio'] ?? '') ?></textarea>
      </div>
      <div class="row">
        <div class="fg">
          <label>Location</label>
          <input type="text" name="location" value="<?= htmlspecialchars($p['location'] ?? '') ?>">
        </div>
        <div class="fg">
          <label>Website</label>
          <input type="url" name="website" value="<?= htmlspecialchars($p['website'] ?? '') ?>">
          <?php if(isset($errors['website'])): ?><div class="err"><?= $errors['website'] ?></div><?php endif ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="sec-label">Skills</div>
      <div class="chips" id="chips"></div>
      <input type="text" id="skill-input" placeholder="Type a skill, press Enter…">
      <input type="hidden" id="skills" name="skills" value="<?= htmlspecialchars($p['skills'] ?? '') ?>">
    </div>

    <div class="card">
      <div class="sec-label">Social Links</div>
      <div class="fg">
        <label>GitHub</label>
        <div class="prefix-wrap"><span class="prefix">github.com/</span><input type="text" name="github" value="<?= htmlspecialchars($p['github'] ?? '') ?>"></div>
      </div>
      <div class="fg">
        <label>Twitter / X</label>
        <div class="prefix-wrap"><span class="prefix">x.com/</span><input type="text" name="twitter" value="<?= htmlspecialchars($p['twitter'] ?? '') ?>"></div>
      </div>
      <div class="fg">
        <label>LinkedIn</label>
        <div class="prefix-wrap"><span class="prefix">linkedin.com/in/</span><input type="text" name="linkedin" value="<?= htmlspecialchars($p['linkedin'] ?? '') ?>"></div>
      </div>
    </div>

    <div class="actions">
      <button type="submit" class="btn btn-green">✓ Save Changes</button>
      <a href="profile.php?u=<?= urlencode($username) ?>" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>

<script>
document.getElementById('avatar').addEventListener('change', function(){
  const f = this.files[0]; if(!f) return;
  const r = new FileReader();
  r.onload = e => document.getElementById('av-preview').src = e.target.result;
  r.readAsDataURL(f);
});
const input  = document.getElementById('skill-input');
const hidden = document.getElementById('skills');
const chips  = document.getElementById('chips');
let skills   = hidden.value ? hidden.value.split(',').map(s=>s.trim()).filter(Boolean) : [];
function render(){
  chips.innerHTML='';
  skills.forEach((s,i)=>{
    const c=document.createElement('span');c.className='chip';
    c.innerHTML=`${s} <button type="button" data-i="${i}">×</button>`;
    chips.appendChild(c);
  });
  hidden.value=skills.join(', ');
}
chips.addEventListener('click',e=>{if(e.target.dataset.i!==undefined){skills.splice(+e.target.dataset.i,1);render();}});
input.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===','){e.preventDefault();add(input.value);input.value='';}});
input.addEventListener('blur',()=>{if(input.value.trim()){add(input.value);input.value='';}});
function add(v){v=v.trim().replace(/,$/,'').trim();if(v&&!skills.includes(v)){skills.push(v);render();}}
render();
</script>
</body>
</html>
