<?php
function indian_number_format($num) {
    $num   = (float)$num;
    $whole = (string)(int)$num;
    $cents = round(fmod(abs($num), 1) * 100);
    $dec   = '.' . str_pad($cents, 2, '0', STR_PAD_LEFT);
    if (strlen($whole) <= 3) return $whole . $dec;
    $last3 = substr($whole, -3);
    $rest  = substr($whole, 0, -3);
    $formatted = '';
    $len = strlen($rest);
    for ($i = 0; $i < $len; $i++) {
        if ($i > 0 && ($len - $i) % 2 === 0) $formatted .= ',';
        $formatted .= $rest[$i];
    }
    return $formatted . ',' . $last3 . $dec;
}

 
session_start();
require 'config.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Admins should not access checkout
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
}

$username = $_SESSION['username'];
$user_stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username = ?");
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

$session_id = 'user_' . $_SESSION['user_id'];
$cart_stmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.name, p.price, p.image 
    FROM carts c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.session_id = ?
");
$cart_stmt->bind_param("s", $session_id);
$cart_stmt->execute();
$cart_items = $cart_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart_items));

if (empty($cart_items)) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - E-Online Shopping</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
            font-family: 'Segoe UI', sans-serif;
            color: #fff;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo { font-size: 1.4rem; font-weight: 700; }
        .back-btn {
            background: rgba(255,255,255,0.1);
            color: #fff;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.2); }

        .checkout-wrapper {
            max-width: 1100px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            gap: 2rem;
        }

        .glass-card {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 2rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #e0e0ff;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .cart-item:last-of-type { border-bottom: none; }
        .cart-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            background: rgba(255,255,255,0.05);
        }
        .item-info { flex: 1; }
        .item-name { font-weight: 600; font-size: 0.95rem; }
        .item-qty { color: rgba(255,255,255,0.5); font-size: 0.85rem; margin-top: 2px; }
        .item-price { font-weight: 700; color: #a78bfa; font-size: 1rem; }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.15);
            font-size: 1.1rem;
            font-weight: 700;
        }
        .total-amount { color: #a78bfa; font-size: 1.3rem; }

        .form-group { margin-bottom: 1.2rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        label {
            display: block;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.6);
            margin-bottom: 0.4rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        input, textarea {
            width: 100%;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.3s;
            outline: none;
        }
        input::placeholder, textarea::placeholder { color: rgba(255,255,255,0.3); }
        input:focus, textarea:focus {
            border-color: #a78bfa;
            background: rgba(167,139,250,0.08);
            box-shadow: 0 0 0 3px rgba(167,139,250,0.1);
        }
        textarea { resize: vertical; min-height: 80px; }

        .place-order-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.3s;
        }
        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
        }
        .place-order-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .secure-note {
            text-align: center;
            color: rgba(255,255,255,0.4);
            font-size: 0.8rem;
            margin-top: 0.8rem;
        }

        .error-msg {
            background: rgba(220,53,69,0.15);
            border: 1px solid rgba(220,53,69,0.3);
            color: #ff6b6b;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: none;
        }

        @media (max-width: 768px) {
            .checkout-wrapper { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">🛍️ E-Online Shopping</div>
    <a href="index.php" class="back-btn">← Back to Store</a>
</nav>

<div class="checkout-wrapper">

    <!-- ORDER SUMMARY -->
    <div class="glass-card">
        <div class="section-title">🛒 Order Summary</div>

        <?php foreach ($cart_items as $item): ?>
        <div class="cart-item">
            <img src="uploads/<?= htmlspecialchars($item['image']) ?>" 
                 alt="<?= htmlspecialchars($item['name']) ?>"
                 style="width:60px;height:60px;object-fit:cover;border-radius:10px;"
                 onerror="this.style.display='none'">
            <div class="item-info">
                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                <div class="item-qty">Qty: <?= $item['quantity'] ?></div>
            </div>
            <div class="item-price">₹<?= indian_number_format($item['price'] * $item['quantity']) ?></div>
        </div>
        <?php endforeach; ?>

        <div class="total-row">
            <span>Total</span>
            <span class="total-amount">₹<?= indian_number_format($total) ?></span>
        </div>
    </div>

    <!-- DELIVERY FORM -->
    <div class="glass-card">
        <div class="section-title">📦 Delivery Details</div>

        <div class="error-msg" id="error-msg"></div>

        <form id="checkout-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="John Doe" 
                           value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="you@email.com"
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="+91 98765 43210" required>
            </div>

            <div class="form-group">
                <label>Street Address</label>
                <textarea name="address" placeholder="House no., Street, Area..." required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" placeholder="Chennai" required>
                </div>
                <div class="form-group">
                    <label>Pincode</label>
                    <input type="text" name="pincode" placeholder="600001" required>
                </div>
            </div>

            <button type="submit" class="place-order-btn" id="place-btn">
                🎉 Place Order — $<?= indian_number_format($total) ?>
            </button>
            <p class="secure-note">🔒 Your information is secure</p>
        </form>
    </div>

</div>

<script>
document.getElementById('checkout-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('place-btn');
    const errorMsg = document.getElementById('error-msg');
    errorMsg.style.display = 'none';

    const formData = {
        full_name: this.full_name.value.trim(),
        email: this.email.value.trim(),
        phone: this.phone.value.trim(),
        address: this.address.value.trim(),
        city: this.city.value.trim(),
        pincode: this.pincode.value.trim()
    };

    btn.disabled = true;
    btn.textContent = '⏳ Placing Order...';

    try {
        const response = await fetch('api/placeorder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            errorMsg.textContent = 'Server error: ' + text.substring(0, 200);
            errorMsg.style.display = 'block';
            btn.disabled = false;
            btn.textContent = '🎉 Place Order — $<?= indian_number_format($total) ?>';
            return;
        }

        if (data.success) {
            window.location.href = `order_success.php?order_id=${data.order_id}`;
        } else {
            errorMsg.textContent = data.message || 'Something went wrong. Please try again.';
            errorMsg.style.display = 'block';
            btn.disabled = false;
            btn.textContent = '🎉 Place Order — $<?= indian_number_format($total) ?>';
        }
    } catch (err) {
        // Show actual error for debugging
        errorMsg.textContent = 'Error: ' + err.message;
        errorMsg.style.display = 'block';
        btn.disabled = false;
        btn.textContent = '🎉 Place Order — $<?= indian_number_format($total) ?>';
        console.error('Fetch error:', err);
    }
});
</script>

</body>
</html>
