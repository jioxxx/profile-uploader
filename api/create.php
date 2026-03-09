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

    // Unique checks - using JSON storage
    if (!$errors) {
        $existing = get_profile($username);
        if ($existing) $errors['username'] = 'Username already taken.';

        $profiles = get_all_profiles();
        foreach ($profiles as $p) {
            if (strtolower($p['email']) === strtolower($email)) {
                $errors['email'] = 'Email already registered.';
                break;
            }
        }
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
            'username' => $username,
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
        
        create_profile($profileData);

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
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--green:#2d6a4f;--green-l:#40916c;--cream:#faf8f3;--paper:#fff;--ink:#1a1a1a;--muted:#888;--border:#e8e4da;--accent:#d4a017;--r:10px}
body{font-family:'Lato',sans-serif;background:var(--cream);color:var(--ink)}
nav{background:var(--green);padding:.9rem 2rem;display:flex;align-items:center;justify-content:space-between}
.brand{font-family:'Playfair Display',serif;color:#fff;font-size:1.4rem;text-decoration:none}
.brand span{color:var(--accent)}
.back{color:rgba(255,255,255,.7);text-decoration:none;font-size:.85rem}
.back:hover{color:#fff}

.wrap{max-width:620px;margin:2.5rem auto;padding:0 1.5rem 3rem}
.page-title{font-family:'Playfair Display',serif;font-size:2rem;margin-bottom:1.8rem}

.card{background:var(--paper);border:1px solid var(--border);border-radius:var(--r);padding:2rem;margin-bottom:1.2rem}
.sec-label{font-size:.68rem;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);padding-bottom:.4rem;margin-bottom:1.2rem}

.row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:500px){.row{grid-template-columns:1fr}}
.fg{margin-bottom:1rem}
label{display:block;font-size:.78rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem}
input[type=text],input[type=email],input[type=url],textarea{width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:7px;font-family:'Lato',sans-serif;font-size:.92rem;background:var(--cream);color:var(--ink);outline:none;transition:border-color .15s}
input:focus,textarea:focus{border-color:var(--green-l);background:#fff}
textarea{resize:vertical;min-height:100px}
.hint{font-size:.75rem;color:var(--muted);margin-top:.25rem}
.err{font-size:.75rem;color:#c0392b;margin-top:.25rem}

.prefix-wrap{display:flex;border:1.5px solid var(--border);border-radius:7px;overflow:hidden;background:var(--cream)}
.prefix-wrap:focus-within{border-color:var(--green-l)}
.prefix{padding:.6rem .8rem;font-size:.82rem;color:var(--muted);background:#f0ede6;border-right:1.5px solid var(--border);white-space:nowrap;flex-shrink:0}
.prefix-wrap input{border:none;border-radius:0;background:transparent;flex:1}
.prefix-wrap input:focus{border-color:transparent}

/* avatar upload */
.av-box{border:2px dashed var(--border);border-radius:var(--r);padding:1.5rem;text-align:center;cursor:pointer;transition:border-color .15s}
.av-box:hover{border-color:var(--green-l)}
#av-preview{width:72px;height:72px;border-radius:50%;object-fit:cover;margin:0 auto .6rem;display:block;border:2px solid var(--border)}

/* skills */
.chips{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.5rem;min-height:24px}
.chip{display:inline-flex;align-items:center;gap:.3rem;background:var(--green);color:#fff;font-size:.75rem;font-weight:700;padding:.22rem .6rem;border-radius:99px}
.chip button{background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:.95rem;line-height:1;padding:0}
.chip button:hover{color:#fff}

.actions{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.5rem}
.btn{display:inline-flex;align-items:center;padding:.6rem 1.3rem;border-radius:7px;font-family:'Lato',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;text-decoration:none;border:none;transition:all .15s}
.btn-green{background:var(--green);color:#fff}
.btn-green:hover{background:#1e4d39}
.btn-outline{background:transparent;color:var(--ink);border:1.5px solid var(--border)}
.btn-outline:hover{border-color:var(--ink)}

.flash-err{background:#fdecea;border-left:4px solid #c0392b;color:#922b21;padding:.75rem 1rem;border-radius:0 7px 7px 0;margin-bottom:1.5rem;font-size:.88rem}
</style>
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
