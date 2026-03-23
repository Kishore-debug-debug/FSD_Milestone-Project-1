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

if (!isset($_SESSION['username']) || !isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = intval($_GET['order_id']);
$username = $_SESSION['username'];

$stmt = $conn->prepare("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND u.username = ?
");
$stmt->bind_param("is", $order_id, $username);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit;
}

$items_stmt = $conn->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed! - E-Online Shopping</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
            font-family: 'Segoe UI', sans-serif;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .success-card {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            max-width: 600px;
            width: 100%;
            text-align: center;
            animation: fadeInUp 0.6s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .checkmark {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(102,126,234,0.4);
        }
        h1 { font-size: 1.8rem; margin-bottom: 0.5rem; }
        .subtitle { color: rgba(255,255,255,0.5); margin-bottom: 2rem; }
        .order-id {
            background: rgba(167,139,250,0.15);
            border: 1px solid rgba(167,139,250,0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        .order-id span { color: #a78bfa; font-weight: 700; font-size: 1.3rem; }
        .items-list { text-align: left; margin-bottom: 2rem; }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.7rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            font-size: 0.95rem;
        }
        .order-item:last-child { border-bottom: none; }
        .order-item-name { color: rgba(255,255,255,0.8); }
        .order-item-price { color: #a78bfa; font-weight: 600; }
        .order-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.1rem;
            font-weight: 700;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.15);
            margin-bottom: 2rem;
        }
        .order-total span:last-child { color: #a78bfa; }
        .delivery-info {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 1rem 1.2rem;
            text-align: left;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            line-height: 1.8;
            color: rgba(255,255,255,0.7);
        }
        .delivery-info strong { color: #fff; }
        .btn-group { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102,126,234,0.4); }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body>
<div class="success-card">
    <div class="checkmark">✅</div>
    <h1>Order Confirmed!</h1>
    <p class="subtitle">Thank you, <?= htmlspecialchars($order['username']) ?>! Your order is being processed.</p>

    <div class="order-id">
        Order ID: <span>#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></span>
    </div>

    <div class="items-list">
        <?php foreach ($order_items as $item): ?>
        <div class="order-item">
            <span class="order-item-name"><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
            <span class="order-item-price">₹<?= indian_number_format($item['price'] * $item['quantity']) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="order-total">
            <span>Total Paid</span>
            <span>₹<?= indian_number_format($order['total_price']) ?></span>
        </div>
    </div>

    <div class="delivery-info">
        📦 <strong>Delivering to:</strong><br>
        <?= htmlspecialchars($order['full_name']) ?><br>
        <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?> — <?= htmlspecialchars($order['pincode']) ?><br>
        📞 <?= htmlspecialchars($order['phone']) ?>
    </div>

    <div class="btn-group">
        <a href="index.php" class="btn-primary">🛍️ Continue Shopping</a>
        <a href="orders.php" class="btn-secondary">📋 My Orders</a>
    </div>
</div>
</body>
</html>
