<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error   = '';
$success = '';

if ($_POST) {
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            // Check if username taken
            $check2 = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check2->bind_param("s", $username);
            $check2->execute();
            $check2->store_result();

            if ($check2->num_rows > 0) {
                $error = "This username is already taken.";
            } else {
                // Create account
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt   = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->bind_param("sss", $username, $email, $hashed);

                if ($stmt->execute()) {
                    $success = "Account created successfully! You can now log in.";
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - E-Shopping</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .register-container {
            background: rgba(255,255,255,0.97);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%; max-width: 440px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity:0; transform:translateY(30px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .header { text-align: center; margin-bottom: 2rem; }
        .header h1 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            font-size: 2rem; margin-bottom: 0.4rem;
        }
        .header p { color: #888; font-size: 0.95rem; }

        .form-group { position: relative; margin-bottom: 1.1rem; }
        .form-group label {
            display: block; font-size: 0.82rem; font-weight: 600;
            color: #555; margin-bottom: 0.4rem; text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .form-group input {
            width: 100%; padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 2px solid #e9ecef; border-radius: 12px;
            font-size: 0.95rem; background: #f8f9fa;
            color: #333; transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none; border-color: #667eea;
            background: #fff; box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .form-group .icon {
            position: absolute; left: 0.9rem;
            top: calc(50% + 10px); transform: translateY(-50%);
            font-size: 0.95rem; color: #aaa;
        }

        /* Password strength */
        .strength-bar {
            height: 4px; border-radius: 4px;
            background: #e9ecef; margin-top: 6px; overflow: hidden;
        }
        .strength-fill {
            height: 100%; border-radius: 4px;
            width: 0; transition: width 0.3s, background 0.3s;
        }
        .strength-text { font-size: 0.75rem; color: #888; margin-top: 3px; }

        .register-btn {
            width: 100%; padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; border: none; border-radius: 12px;
            font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: all 0.3s; margin-top: 0.5rem; margin-bottom: 1rem;
        }
        .register-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(102,126,234,0.4); }

        .error {
            background: #fff0f0; color: #c0392b;
            padding: 0.85rem 1rem; border-radius: 10px;
            margin-bottom: 1.2rem; border-left: 4px solid #e74c3c;
            font-size: 0.9rem; font-weight: 500;
            animation: shake 0.4s ease;
        }
        .success {
            background: #f0fff4; color: #276749;
            padding: 0.85rem 1rem; border-radius: 10px;
            margin-bottom: 1.2rem; border-left: 4px solid #38a169;
            font-size: 0.9rem; font-weight: 500;
        }
        @keyframes shake {
            0%,100%{transform:translateX(0)} 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)}
        }

        .login-link { text-align: center; font-size: 0.9rem; color: #888; }
        .login-link a { color: #667eea; font-weight: 600; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }

        .divider {
            display: flex; align-items: center; gap: 0.75rem;
            margin: 1.25rem 0; color: #ccc; font-size: 0.82rem;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: #e9ecef;
        }

        .floating-shapes { position: fixed; inset: 0; pointer-events: none; z-index: -1; }
        .shape {
            position: absolute; background: rgba(255,255,255,0.1);
            border-radius: 50%; animation: float 6s ease-in-out infinite;
        }
        .shape:nth-child(1){width:80px;height:80px;top:15%;left:8%;animation-delay:0s}
        .shape:nth-child(2){width:110px;height:110px;top:65%;right:8%;animation-delay:2s}
        .shape:nth-child(3){width:55px;height:55px;top:82%;left:18%;animation-delay:4s}
        @keyframes float {
            0%,100%{transform:translateY(0)} 50%{transform:translateY(-18px)}
        }
    </style>
</head>
<body>
<div class="floating-shapes">
    <div class="shape"></div><div class="shape"></div><div class="shape"></div>
</div>

<div class="register-container">
    <div class="header">
        <h1>🛒 E-Shopping</h1>
        <p>Create your account to start shopping</p>
    </div>

    <?php if ($error): ?>
    <div class="error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="success">
        ✅ <?= htmlspecialchars($success) ?>
        <br><a href="login.php" style="color:#276749;font-weight:700;">→ Go to Login</a>
    </div>
    <?php else: ?>

    <form method="POST" id="registerForm">
        <div class="form-group">
            <label>Username</label>
            <span class="icon">👤</span>
            <input type="text" name="username" placeholder="Choose a username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required minlength="3">
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <span class="icon">📧</span>
            <input type="email" name="email" placeholder="Enter your email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <span class="icon">🔒</span>
            <input type="password" name="password" id="password"
                   placeholder="Min. 6 characters" required minlength="6">
            <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
            <div class="strength-text" id="strength-text"></div>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <span class="icon">🔑</span>
            <input type="password" name="confirm_password" id="confirm"
                   placeholder="Repeat your password" required>
        </div>

        <button type="submit" class="register-btn">✨ Create Account</button>
    </form>

    <?php endif; ?>

    <div class="divider">or</div>
    <div class="login-link">
        Already have an account? <a href="login.php">Sign in here</a>
    </div>
</div>

<script>
// Password strength meter
document.getElementById('password')?.addEventListener('input', function() {
    const val  = this.value;
    const fill = document.getElementById('strength-fill');
    const text = document.getElementById('strength-text');
    let strength = 0;

    if (val.length >= 6)  strength++;
    if (val.length >= 10) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const levels = [
        { w:'0%',   bg:'#e9ecef', label:'' },
        { w:'25%',  bg:'#ef4444', label:'Weak' },
        { w:'50%',  bg:'#f97316', label:'Fair' },
        { w:'75%',  bg:'#eab308', label:'Good' },
        { w:'90%',  bg:'#22c55e', label:'Strong' },
        { w:'100%', bg:'#16a34a', label:'Very Strong' },
    ];
    const l = levels[Math.min(strength, 5)];
    fill.style.width      = l.w;
    fill.style.background = l.bg;
    text.textContent      = l.label;
    text.style.color      = l.bg;
});

// Confirm password match indicator
document.getElementById('confirm')?.addEventListener('input', function() {
    const pwd = document.getElementById('password').value;
    this.style.borderColor = this.value === pwd ? '#22c55e' : '#ef4444';
});
</script>
</body>
</html>
