<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$errors  = [];

$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_POST) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $new_username = trim($_POST['username']);
        $new_email    = trim($_POST['email']);

        if (empty($new_username) || strlen($new_username) < 3) {
            $errors[] = "Username must be at least 3 characters.";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        } else {
            $chk = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $chk->bind_param("si", $new_username, $user_id);
            $chk->execute(); $chk->store_result();

            $chk2 = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $chk2->bind_param("si", $new_email, $user_id);
            $chk2->execute(); $chk2->store_result();

            if ($chk->num_rows > 0) {
                $errors[] = "That username is already taken.";
            } elseif ($chk2->num_rows > 0) {
                $errors[] = "That email is already in use.";
            } else {
                $upd = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $upd->bind_param("ssi", $new_username, $new_email, $user_id);
                $upd->execute();
                $_SESSION['username'] = $new_username;
                $user['username']     = $new_username;
                $user['email']        = $new_email;
                $success = "✅ Profile updated successfully!";
            }
        }
    }

    if ($action === 'change_password') {
        $current  = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm  = $_POST['confirm_password'];

        $ph = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $ph->bind_param("i", $user_id);
        $ph->execute();
        $hash = $ph->get_result()->fetch_assoc()['password'];

        if (!password_verify($current, $hash)) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($new_pass) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        } elseif ($new_pass !== $confirm) {
            $errors[] = "New passwords do not match.";
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param("si", $new_hash, $user_id);
            $upd->execute();
            $success = "✅ Password changed successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - E-Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:#080b14; --bg-card:rgba(255,255,255,0.06);
            --border:rgba(255,255,255,0.09); --border-l:rgba(255,255,255,0.16);
            --accent:#7c6cfc; --accent2:#a78bfa; --glow:rgba(124,108,252,0.3);
            --text:#f1f5f9; --muted:rgba(241,245,249,0.45); --radius:14px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        body::before {
            content:''; position:fixed; width:600px; height:600px;
            background:rgba(124,108,252,0.08); border-radius:50%;
            filter:blur(120px); top:-200px; left:-150px; pointer-events:none; z-index:0;
        }
        .navbar {
            position:sticky; top:0; z-index:100;
            display:flex; align-items:center; justify-content:space-between;
            padding:0 2rem; height:66px;
            background:rgba(8,11,20,0.85); backdrop-filter:blur(24px);
            border-bottom:1px solid var(--border);
        }
        .logo {
            font-family:'Syne',sans-serif; font-size:1.2rem; font-weight:800;
            background:linear-gradient(135deg,#fff 40%,var(--accent2));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            text-decoration:none;
        }
        .nav-right { display:flex; align-items:center; gap:0.6rem; }
        .npill {
            display:inline-flex; align-items:center; gap:5px;
            padding:0.42rem 1rem; border-radius:50px; font-size:0.8rem; font-weight:600;
            text-decoration:none; transition:all 0.2s; font-family:'DM Sans',sans-serif;
        }
        .pill-back   { background:rgba(255,255,255,0.08); color:var(--text); border:1px solid var(--border); }
        .pill-back:hover { background:rgba(255,255,255,0.14); }
        .pill-logout { background:rgba(239,68,68,0.12); color:#fca5a5; border:1px solid rgba(239,68,68,0.22); }
        .pill-logout:hover { background:rgba(239,68,68,0.22); }

        .page { position:relative; z-index:1; max-width:700px; margin:2.5rem auto; padding:0 1.5rem 4rem; }

        .profile-header {
            display:flex; align-items:center; gap:1.5rem;
            background:var(--bg-card); border:1px solid var(--border);
            border-radius:var(--radius); padding:1.75rem 2rem; margin-bottom:1.5rem;
        }
        .avatar {
            width:70px; height:70px; border-radius:50%;
            background:linear-gradient(135deg,var(--accent),#9b59fc);
            display:flex; align-items:center; justify-content:center;
            font-size:1.75rem; font-weight:800; color:#fff; flex-shrink:0;
            box-shadow:0 6px 20px var(--glow);
        }
        .profile-meta h2 { font-family:'Syne',sans-serif; font-size:1.25rem; font-weight:800; }
        .profile-meta p  { color:var(--muted); font-size:0.87rem; margin-top:0.2rem; }
        .role-badge {
            display:inline-flex; align-items:center; gap:4px;
            padding:0.2rem 0.7rem; border-radius:50px; font-size:0.72rem;
            font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-top:0.4rem;
        }
        .badge-admin { background:rgba(239,68,68,0.12); color:#fca5a5; border:1px solid rgba(239,68,68,0.22); }
        .badge-user  { background:rgba(124,108,252,0.12); color:var(--accent2); border:1px solid rgba(124,108,252,0.25); }

        .flash-s { background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.25); color:#4ade80; padding:0.85rem 1.1rem; border-radius:10px; margin-bottom:1.25rem; font-size:0.9rem; font-weight:500; }
        .flash-e { background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); color:#fca5a5; padding:0.85rem 1.1rem; border-radius:10px; margin-bottom:1.25rem; font-size:0.9rem; font-weight:500; }
        .flash-e ul { margin:0.3rem 0 0 1.2rem; }

        .card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:1.75rem 2rem; margin-bottom:1.25rem; }
        .card-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; margin-bottom:1.4rem; padding-bottom:0.85rem; border-bottom:1px solid var(--border); }

        .form-group { margin-bottom:1.1rem; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        label { display:block; font-size:0.78rem; color:var(--muted); font-weight:500; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.4rem; }
        input[type=text], input[type=email], input[type=password] {
            width:100%; background:rgba(255,255,255,0.07); border:1px solid var(--border);
            border-radius:10px; padding:0.75rem 1rem; color:var(--text);
            font-size:0.92rem; font-family:'DM Sans',sans-serif; outline:none; transition:all 0.25s;
        }
        input::placeholder { color:var(--muted); }
        input:focus { border-color:var(--accent); background:rgba(124,108,252,0.07); box-shadow:0 0 0 3px rgba(124,108,252,0.1); }

        .strength-bar  { height:3px; background:rgba(255,255,255,0.08); border-radius:4px; overflow:hidden; margin-top:6px; }
        .strength-fill { height:100%; width:0; border-radius:4px; transition:width 0.3s,background 0.3s; }
        .strength-lbl  { font-size:0.72rem; color:var(--muted); margin-top:3px; }

        .btn-save {
            background:linear-gradient(135deg,var(--accent),#9b59fc); color:#fff;
            border:none; border-radius:10px; padding:0.72rem 1.8rem;
            font-size:0.9rem; font-weight:700; cursor:pointer; transition:all 0.25s;
            font-family:'DM Sans',sans-serif; display:inline-flex; align-items:center; gap:6px; margin-top:0.5rem;
        }
        .btn-save:hover { transform:translateY(-2px); box-shadow:0 6px 20px var(--glow); }

        @media(max-width:600px) {
            .form-row { grid-template-columns:1fr; }
            .profile-header { flex-direction:column; text-align:center; }
            .page { padding:0 1rem 3rem; }
            .navbar { padding:0 1rem; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="logo">⚡ E-Shop</a>
    <div class="nav-right">
        <?php if ($user['role'] === 'admin'): ?>
        <a href="admin/dashboard.php" class="npill pill-back">⚙️ Dashboard</a>
        <?php else: ?>
        <a href="index.php" class="npill pill-back">🛍️ Store</a>
        <?php endif; ?>
        <a href="orders.php" class="npill pill-back">📋 Orders</a>
        <a href="logout.php" class="npill pill-logout">🚪 Logout</a>
    </div>
</nav>

<div class="page">
    <div class="profile-header">
        <div class="avatar"><?= strtoupper(substr($user['username'],0,1)) ?></div>
        <div class="profile-meta">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <span class="role-badge <?= $user['role']==='admin'?'badge-admin':'badge-user' ?>">
                <?= $user['role']==='admin'?'🔐 Admin':'👤 User' ?>
            </span>
        </div>
    </div>

    <?php if ($success): ?><div class="flash-s"><?= $success ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?>
    <div class="flash-e">
        <?php if (count($errors)===1): ?>❌ <?= htmlspecialchars($errors[0]) ?>
        <?php else: ?>❌ Please fix:<ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- PROFILE INFO -->
    <div class="card">
        <div class="card-title">👤 Profile Information</div>
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-row">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required minlength="3">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
            </div>
            <button type="submit" class="btn-save">💾 Save Changes</button>
        </form>
    </div>

    <!-- CHANGE PASSWORD -->
    <div class="card">
        <div class="card-title">🔒 Change Password</div>
        <form method="POST" id="pwd-form">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" placeholder="Enter current password" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" id="new-pwd" placeholder="Min. 6 characters" required minlength="6">
                    <div class="strength-bar"><div class="strength-fill" id="sfill"></div></div>
                    <div class="strength-lbl" id="slbl"></div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" id="cpwd" placeholder="Repeat new password" required>
                </div>
            </div>
            <button type="submit" class="btn-save">🔑 Update Password</button>
        </form>
    </div>
</div>

<script>
document.getElementById('new-pwd').addEventListener('input', function() {
    const v=this.value; let s=0;
    if(v.length>=6)s++; if(v.length>=10)s++;
    if(/[A-Z]/.test(v))s++; if(/[0-9]/.test(v))s++; if(/[^A-Za-z0-9]/.test(v))s++;
    const L=[{w:'0',b:'transparent',l:''},{w:'20%',b:'#ef4444',l:'Weak'},{w:'45%',b:'#f97316',l:'Fair'},{w:'70%',b:'#eab308',l:'Good'},{w:'88%',b:'#22c55e',l:'Strong'},{w:'100%',b:'#16a34a',l:'Very Strong'}];
    const l=L[Math.min(s,5)];
    document.getElementById('sfill').style.width=l.w;
    document.getElementById('sfill').style.background=l.b;
    document.getElementById('slbl').textContent=l.l;
    document.getElementById('slbl').style.color=l.b;
});
document.getElementById('cpwd').addEventListener('input', function() {
    const m=this.value===document.getElementById('new-pwd').value;
    this.style.borderColor=this.value?(m?'#22c55e':'#ef4444'):'';
});
document.getElementById('pwd-form').addEventListener('submit', function(e) {
    if(document.getElementById('new-pwd').value !== document.getElementById('cpwd').value) {
        e.preventDefault();
        document.getElementById('cpwd').style.borderColor='#ef4444';
        document.getElementById('cpwd').focus();
    }
});
</script>
</body>
</html>
