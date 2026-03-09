<?php
require 'db.php';
require 'mailer.php';
$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $headline = trim($_POST['headline'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $website  = trim($_POST['website'] ?? '');
    $github   = trim($_POST['github'] ?? '');
    $twitter  = trim($_POST['twitter'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $skills   = trim($_POST['skills'] ?? '');

    // Validate
    if (!$name)     $errors['name']     = 'Name is required.';
    if (!$username) $errors['username'] = 'Username is required.';
    elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) $errors['username'] = 'Only letters, numbers, - and _ allowed.';
    if (!$email)    $errors['email']    = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';
    if ($website && !filter_var($website, FILTER_VALIDATE_URL)) $errors['website'] = 'Invalid URL.';

    // Unique checks
    if (!$errors) {
        $db = get_db();
        $u = $db->prepare("SELECT id FROM profiles WHERE username = ?");
        $u->execute([$username]);
        if ($u->fetch()) $errors['username'] = 'Username already taken.';

        $e = $db->prepare("SELECT id FROM profiles WHERE email = ?");
        $e->execute([$email]);
        if ($e->fetch()) $errors['email'] = 'Email already registered.';
    }

    // Avatar upload
    $avatar = null;
    if (!$errors && isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0) {
        $file = $_FILES['avatar'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'], $allowed)) {
            $errors['avatar'] = 'Only JPG, PNG, GIF, WEBP allowed.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors['avatar'] = 'Max file size is 2MB.';
        } else {
            $ext    = pathinfo($file['name'], PATHINFO_EXTENSION);
            $avatar = uniqid('av_') . '.' . strtolower($ext);
            move_uploaded_file($file['tmp_name'], __DIR__ . '/uploads/avatars/' . $avatar);
        }
    }

    if (!$errors) {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO profiles (name,username,email,headline,bio,location,website,avatar,github,twitter,linkedin,skills)
                               VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name,$username,$email,$headline,$bio,$location,$website,$avatar,$github,$twitter,$linkedin,$skills]);

        // Send email notification to the user
        $new_profile = [
            'name' => $name,
            'username' => $username,
            'email' => $email
        ];
        notify_profile_created($new_profile);

        header('Location: profile.php?u=' . urlencode($username) . '&created=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Profile — ProfileGen</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
  <a class="brand" href="index.php">Profile<span>Gen</span></a>
  <a class="back" href="index.php">← Back to profiles</a>
</nav>

<div class="wrap">
  <h1 class="page-title">Create Profile</h1>

  <?php if($errors): ?>
    <div class="flash-err">Please fix the errors below before continuing.</div>
  <?php endif ?>

  <form method="POST" enctype="multipart/form-data">

    <!-- Photo -->
    <div class="card">
      <div class="sec-label">Photo</div>
      <div class="av-box" onclick="document.getElementById('avatar').click()">
        <img id="av-preview" src="https://ui-avatars.com/api/?name=You&background=2d6a4f&color=fff&size=144" alt="preview">
        <div class="hint">Click to upload &mdash; JPG, PNG, GIF, WEBP · max 2MB</div>
      </div>
      <input type="file" id="avatar" name="avatar" accept="image/*" style="display:none">
      <?php if(isset($errors['avatar'])): ?><div class="err"><?= $errors['avatar'] ?></div><?php endif ?>
    </div>

    <!-- Basic -->
    <div class="card">
      <div class="sec-label">Basic Info</div>
      <div class="row">
        <div class="fg">
          <label for="name">Full Name *</label>
          <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" placeholder="Jane Dela Cruz">
          <?php if(isset($errors['name'])): ?><div class="err"><?= $errors['name'] ?></div><?php endif ?>
        </div>
        <div class="fg">
          <label for="username">Username *</label>
          <input type="text" id="username" name="username" value="<?= htmlspecialchars($old['username'] ?? '') ?>" placeholder="janedelacruz">
          <div class="hint">Your URL: /profile.php?u=username</div>
          <?php if(isset($errors['username'])): ?><div class="err"><?= $errors['username'] ?></div><?php endif ?>
        </div>
      </div>
      <div class="fg">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="jane@example.com">
        <?php if(isset($errors['email'])): ?><div class="err"><?= $errors['email'] ?></div><?php endif ?>
      </div>
      <div class="fg">
        <label for="headline">Headline</label>
        <input type="text" id="headline" name="headline" value="<?= htmlspecialchars($old['headline'] ?? '') ?>" placeholder="Full-Stack Developer · Coffee Lover">
      </div>
      <div class="fg">
        <label for="bio">Bio</label>
        <textarea id="bio" name="bio" placeholder="A little about yourself…"><?= htmlspecialchars($old['bio'] ?? '') ?></textarea>
      </div>
      <div class="row">
        <div class="fg">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" value="<?= htmlspecialchars($old['location'] ?? '') ?>" placeholder="Cebu, Philippines">
        </div>
        <div class="fg">
          <label for="website">Website</label>
          <input type="url" id="website" name="website" value="<?= htmlspecialchars($old['website'] ?? '') ?>" placeholder="https://yoursite.com">
          <?php if(isset($errors['website'])): ?><div class="err"><?= $errors['website'] ?></div><?php endif ?>
        </div>
      </div>
    </div>

    <!-- Skills -->
    <div class="card">
      <div class="sec-label">Skills</div>
      <div class="chips" id="chips"></div>
      <input type="text" id="skill-input" placeholder="Type a skill, press Enter or comma…">
      <input type="hidden" id="skills" name="skills" value="<?= htmlspecialchars($old['skills'] ?? '') ?>">
      <div class="hint">e.g. PHP, MySQL, HTML, CSS</div>
    </div>

    <!-- Social -->
    <div class="card">
      <div class="sec-label">Social Links</div>
      <div class="fg">
        <label>GitHub</label>
        <div class="prefix-wrap"><span class="prefix">github.com/</span><input type="text" name="github" value="<?= htmlspecialchars($old['github'] ?? '') ?>" placeholder="username"></div>
      </div>
      <div class="fg">
        <label>Twitter / X</label>
        <div class="prefix-wrap"><span class="prefix">x.com/</span><input type="text" name="twitter" value="<?= htmlspecialchars($old['twitter'] ?? '') ?>" placeholder="username"></div>
      </div>
      <div class="fg">
        <label>LinkedIn</label>
        <div class="prefix-wrap"><span class="prefix">linkedin.com/in/</span><input type="text" name="linkedin" value="<?= htmlspecialchars($old['linkedin'] ?? '') ?>" placeholder="username"></div>
      </div>
    </div>

    <div class="actions">
      <button type="submit" class="btn btn-green">🚀 Create Profile</button>
      <a href="index.php" class="btn btn-outline">Cancel</a>
    </div>

  </form>
</div>

<script>
// Avatar preview
document.getElementById('avatar').addEventListener('change', function(){
  const f = this.files[0]; if(!f) return;
  const r = new FileReader();
  r.onload = e => document.getElementById('av-preview').src = e.target.result;
  r.readAsDataURL(f);
});

// Skills chips
const input  = document.getElementById('skill-input');
const hidden = document.getElementById('skills');
const chips  = document.getElementById('chips');
let skills   = hidden.value ? hidden.value.split(',').map(s=>s.trim()).filter(Boolean) : [];

function render() {
  chips.innerHTML = '';
  skills.forEach((s,i) => {
    const c = document.createElement('span');
    c.className = 'chip';
    c.innerHTML = `${s} <button type="button" data-i="${i}">×</button>`;
    chips.appendChild(c);
  });
  hidden.value = skills.join(', ');
}

chips.addEventListener('click', e => {
  if(e.target.dataset.i !== undefined){ skills.splice(+e.target.dataset.i,1); render(); }
});

input.addEventListener('keydown', e => {
  if(e.key==='Enter'||e.key===','){ e.preventDefault(); add(input.value); input.value=''; }
});
input.addEventListener('blur', () => { if(input.value.trim()){ add(input.value); input.value=''; } });

function add(v){ v=v.trim().replace(/,$/,'').trim(); if(v&&!skills.includes(v)){ skills.push(v); render(); } }
render();
</script>
</body>
</html>
