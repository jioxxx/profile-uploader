<?php
require 'db.php';

$username = trim($_GET['u'] ?? '');
$p = get_profile($username);

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
        // Check uniqueness (excluding current profile)
        $profiles = get_all_profiles();
        foreach ($profiles as $existing) {
            if ($existing['username'] === $new_username && $existing['username'] !== $username) {
                $errors['username'] = 'Username already taken.';
                break;
            }
            if (strtolower($existing['email']) === strtolower($email) && $existing['email'] !== $p['email']) {
                $errors['email'] = 'Email already registered.';
                break;
            }
        }
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
            $uploadDir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            move_uploaded_file($file['tmp_name'], $uploadDir . $avatar);
        }
    }

    if (!$errors) {
        $profileData = [
            'name' => $name,
            'username' => $new_username,
            'email' => $email,
            'headline' => $headline,
            'bio' => $bio,
            'location' => $location,
            'website' => $website,
            'avatar' => $avatar,
            'github' => $github,
            'twitter' => $twitter,
            'linkedin' => $linkedin,
            'skills' => $skills
        ];
        
        update_profile($username, $profileData);

        // If username changed, redirect to new username
        $redirect_username = ($new_username !== $username) ? $new_username : $username;
        header('Location: profile.php?u=' . urlencode($redirect_username) . '&edited=1');
        exit;
    }
    
    // Update $p with new values for form display
    $p = array_merge($p, $_POST);
    if (isset($avatar) && $avatar !== $p['avatar']) {
        $p['avatar'] = $avatar;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit <?= htmlspecialchars($p['name']) ?> — ProfileGen</title>
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
    <div class="flash-err">Please fix the errors below before continuing.</div>
  <?php endif ?>

  <form method="POST" enctype="multipart/form-data">

    <div class="card">
      <div class="sec-label">Photo</div>
      <div class="av-box" onclick="document.getElementById('avatar').click()">
        <img id="av-preview" src="<?= isset($p['avatar']) && $p['avatar'] ? 'uploads/avatars/'.htmlspecialchars($p['avatar']) : 'https://ui-avatars.com/api/?name='.urlencode($p['name']).'&background=2d6a4f&color=fff&size=144' ?>" alt="preview">
        <div class="hint">Click to upload — JPG, PNG, GIF, WEBP · max 2MB</div>
      </div>
      <input type="file" id="avatar" name="avatar" accept="image/*" style="display:none">
      <?php if(isset($errors['avatar'])): ?><div class="err"><?= $errors['avatar'] ?></div><?php endif ?>
    </div>

    <div class="card">
      <div class="sec-label">Basic Info</div>
      <div class="row">
        <div class="fg">
          <label for="name">Full Name *</label>
          <input type="text" id="name" name="name" value="<?= htmlspecialchars($p['name'] ?? '') ?>">
          <?php if(isset($errors['name'])): ?><div class="err"><?= $errors['name'] ?></div><?php endif ?>
        </div>
        <div class="fg">
          <label for="username">Username *</label>
          <input type="text" id="username" name="username" value="<?= htmlspecialchars($p['username'] ?? '') ?>">
          <?php if(isset($errors['username'])): ?><div class="err"><?= $errors['username'] ?></div><?php endif ?>
        </div>
      </div>
      <div class="fg">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($p['email'] ?? '') ?>">
        <?php if(isset($errors['email'])): ?><div class="err"><?= $errors['email'] ?></div><?php endif ?>
      </div>
      <div class="fg">
        <label for="headline">Headline</label>
        <input type="text" id="headline" name="headline" value="<?= htmlspecialchars($p['headline'] ?? '') ?>">
      </div>
      <div class="fg">
        <label for="bio">Bio</label>
        <textarea id="bio" name="bio"><?= htmlspecialchars($p['bio'] ?? '') ?></textarea>
      </div>
      <div class="row">
        <div class="fg">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" value="<?= htmlspecialchars($p['location'] ?? '') ?>">
        </div>
        <div class="fg">
          <label for="website">Website</label>
          <input type="url" id="website" name="website" value="<?= htmlspecialchars($p['website'] ?? '') ?>">
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
