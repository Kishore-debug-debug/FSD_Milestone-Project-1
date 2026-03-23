<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') header('Location: admin/dashboard.php');
    else header('Location: index.php');
    exit();
}

$error = '';
if ($_POST) {
    $email        = trim($_POST['email']);
    $password     = $_POST['password'];
    $selected_role = $_POST['selected_role'] ?? 'user'; // which tab was active

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {

            // ROLE ENFORCEMENT: tab must match actual role
            if ($selected_role === 'admin' && $user['role'] !== 'admin') {
                $error = "❌ This account is not an admin. Please use the User login.";
            } elseif ($selected_role === 'user' && $user['role'] === 'admin') {
                $error = "❌ Admin accounts must login via the Admin tab.";
            } else {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                if ($user['role'] == 'admin') header('Location: admin/dashboard.php');
                else header('Location: index.php');
                exit();
            }
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Shopping</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .login-container { 
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(10px);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            width: 100%; max-width: 420px;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header h1 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            font-size: 2.2rem; margin-bottom: 0.4rem;
        }
        .login-header p { color: #666; font-size: 1rem; }

        /* TABS */
        .role-tabs {
            display: flex; background: #f1f3f9;
            border-radius: 50px; padding: 5px;
            margin-bottom: 1.75rem;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.08);
        }
        .role-tab {
            flex: 1; padding: 0.85rem 1.2rem;
            border: none; background: transparent;
            border-radius: 50px; cursor: pointer;
            font-weight: 600; font-size: 0.95rem;
            transition: all 0.3s ease; color: #888;
        }
        .role-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 14px rgba(102,126,234,0.45);
        }
        .role-tab.admin-tab.active {
            background: linear-gradient(135deg, #f97316, #dc2626);
            box-shadow: 0 4px 14px rgba(220,38,38,0.35);
        }

        /* ROLE HINT */
        .role-hint {
            text-align: center; font-size: 0.82rem;
            padding: 0.5rem 1rem; border-radius: 10px;
            margin-bottom: 1.25rem; font-weight: 500;
        }
        .hint-user  { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; }
        .hint-admin { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }

        /* FORM */
        .form-group { position: relative; margin-bottom: 1.25rem; }
        .form-group input {
            width: 100%; padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e9ecef; border-radius: 12px;
            font-size: 1rem; transition: all 0.3s;
            background: #f8f9fa; color: #333;
        }
        .form-group input:focus {
            outline: none; border-color: #667eea;
            background: white; box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .form-group i {
            position: absolute; left: 1rem; top: 50%;
            transform: translateY(-50%); color: #aaa;
        }

        .login-btn {
            width: 100%; padding: 1.1rem;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white; border: none; border-radius: 12px;
            font-size: 1.05rem; font-weight: 600; cursor: pointer;
            transition: all 0.3s; margin-bottom: 1rem;
        }
        .login-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(40,167,69,0.4); }
        .login-btn.admin-btn {
            background: linear-gradient(135deg, #f97316, #dc2626);
        }
        .login-btn.admin-btn:hover { box-shadow: 0 8px 22px rgba(220,38,38,0.35); }

        .error {
            background: #fff0f0; color: #c0392b;
            padding: 0.9rem 1rem; border-radius: 10px;
            margin-bottom: 1.25rem; border-left: 4px solid #e74c3c;
            font-size: 0.92rem; font-weight: 500;
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%,100%{transform:translateX(0)} 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)}
        }

        .register-link { text-align: center; font-size: 0.9rem; color: #888; }
        .register-link a { color: #667eea; font-weight: 600; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }

        .floating-shapes { position: fixed; inset: 0; pointer-events: none; z-index: -1; }
        .shape {
            position: absolute; background: rgba(255,255,255,0.1);
            border-radius: 50%; animation: float 6s ease-in-out infinite;
        }
        .shape:nth-child(1){width:80px;height:80px;top:20%;left:10%;animation-delay:0s}
        .shape:nth-child(2){width:120px;height:120px;top:60%;right:10%;animation-delay:2s}
        .shape:nth-child(3){width:60px;height:60px;top:80%;left:20%;animation-delay:4s}
        @keyframes float {
            0%,100%{transform:translateY(0) rotate(0deg)}
            50%{transform:translateY(-20px) rotate(180deg)}
        }
    </style>
</head>
<body>
<div class="floating-shapes">
    <div class="shape"></div><div class="shape"></div><div class="shape"></div>
</div>

<div class="login-container">
    <div class="login-header">
        <h1>🛒 E-Shopping</h1>
        <p>Welcome back! Please sign in</p>
    </div>

    <!-- TABS -->
    <div class="role-tabs">
        <button class="role-tab user-tab active" onclick="setRole('user')" type="button">👤 User</button>
        <button class="role-tab admin-tab" onclick="setRole('admin')" type="button">🔐 Admin</button>
    </div>

    <!-- ROLE HINT -->
    <div class="role-hint hint-user" id="role-hint">
        👤 Login with your customer account
    </div>

    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
        <!-- Hidden field to send selected tab to PHP -->
        <input type="hidden" name="selected_role" id="selected_role" value="user">

        <div class="form-group">
            <i>📧</i>
            <input type="email" name="email" placeholder="Enter your email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <i>🔒</i>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="login-btn" id="login-btn">🚀 Sign In as User</button>
    </form>

    <div class="register-link">
        Don't have an account? <a href="register.php">Create one now</a>
    </div>
</div>

<script>
let currentRole = 'user';

// If there was a POST error, restore the selected tab
<?php if ($_POST && isset($_POST['selected_role'])): ?>
setRole('<?= $_POST['selected_role'] ?>');
<?php endif; ?>

function setRole(role) {
    currentRole = role;
    document.getElementById('selected_role').value = role;

    const tabs    = document.querySelectorAll('.role-tab');
    const hint    = document.getElementById('role-hint');
    const btn     = document.getElementById('login-btn');

    tabs.forEach(t => t.classList.remove('active'));

    if (role === 'admin') {
        document.querySelector('.admin-tab').classList.add('active');
        hint.className   = 'role-hint hint-admin';
        hint.textContent = '🔐 Admin access only — restricted area';
        btn.className    = 'login-btn admin-btn';
        btn.textContent  = '🔐 Sign In as Admin';
    } else {
        document.querySelector('.user-tab').classList.add('active');
        hint.className   = 'role-hint hint-user';
        hint.textContent = '👤 Login with your customer account';
        btn.className    = 'login-btn';
        btn.textContent  = '🚀 Sign In as User';
    }
}

document.querySelectorAll('input').forEach(input => {
    input.addEventListener('focus',  () => input.parentElement.style.transform = 'scale(1.02)');
    input.addEventListener('blur',   () => input.parentElement.style.transform = 'scale(1)');
});
</script>
</body>
</html>
