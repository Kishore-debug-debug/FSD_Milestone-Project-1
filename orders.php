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

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$is_admin = ($role === 'admin');

// Admin sees all orders; user sees only their own
if ($is_admin) {
    $stmt = $conn->prepare("
        SELECT o.*, u.username, u.email AS user_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        SELECT o.*, u.username, u.email AS user_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// For each order, fetch its items
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$order_items_map = [];
foreach ($orders as $order) {
    $items_stmt->bind_param("i", $order['id']);
    $items_stmt->execute();
    $order_items_map[$order['id']] = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle status update (admin: any status | user: cancel only if pending/processing)
$update_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $upd_id     = intval($_POST['order_id']);
    $upd_status = $_POST['status'];

    if ($is_admin) {
        // Admin can set any status
        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (in_array($upd_status, $allowed)) {
            if ($upd_status === 'cancelled') {
                $upd = $conn->prepare("UPDATE orders SET status = 'cancelled', cancelled_by = 'admin' WHERE id = ?");
                $upd->bind_param("i", $upd_id);
            } else {
                $upd = $conn->prepare("UPDATE orders SET status = ?, cancelled_by = NULL WHERE id = ?");
                $upd->bind_param("si", $upd_status, $upd_id);
            }
            $upd->execute();
            header("Location: orders.php?updated=$upd_id");
            exit;
        }
    } else {
        // User can only cancel their own order if pending or processing
        if ($upd_status === 'cancelled') {
            $check = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
            $check->bind_param("ii", $upd_id, $user_id);
            $check->execute();
            $order_row = $check->get_result()->fetch_assoc();
            if ($order_row && in_array($order_row['status'], ['pending', 'processing'])) {
                $upd = $conn->prepare("UPDATE orders SET status = 'cancelled', cancelled_by = 'user' WHERE id = ?");
                $upd->bind_param("i", $upd_id);
                $upd->execute();
                header("Location: orders.php?cancelled=$upd_id");
                exit;
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
    <title><?= $is_admin ? 'All Orders — Admin' : 'My Orders' ?> - E-Online Shopping</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
            font-family: 'Segoe UI', sans-serif;
            color: #fff;
        }

        /* NAV */
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
        .nav-links { display: flex; gap: 1rem; align-items: center; }
        .nav-btn {
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .btn-back {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-back:hover { background: rgba(255,255,255,0.2); }
        .btn-logout {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: #fff;
        }

        /* PAGE */
        .page-wrapper {
            max-width: 1000px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }
        .page-header h1 { font-size: 1.8rem; font-weight: 700; }
        .page-header p { color: rgba(255,255,255,0.5); margin-top: 0.3rem; }

        /* TOAST */
        .toast {
            background: rgba(102,126,234,0.2);
            border: 1px solid rgba(102,126,234,0.4);
            color: #a78bfa;
            padding: 0.75rem 1.2rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .empty-state .icon { font-size: 4rem; margin-bottom: 1rem; }
        .empty-state h2 { font-size: 1.4rem; margin-bottom: 0.5rem; }
        .empty-state p { color: rgba(255,255,255,0.5); margin-bottom: 1.5rem; }
        .btn-shop {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-shop:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102,126,234,0.4); }

        /* ORDER CARD */
        .order-card {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: border-color 0.3s;
        }
        .order-card:hover { border-color: rgba(167,139,250,0.3); }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            flex-wrap: wrap;
            gap: 0.75rem;
            cursor: pointer;
            user-select: none;
        }

        .order-meta { display: flex; flex-direction: column; gap: 0.2rem; }
        .order-id { font-weight: 700; font-size: 1rem; color: #e0e0ff; }
        .order-date { font-size: 0.82rem; color: rgba(255,255,255,0.45); }
        .order-customer { font-size: 0.85rem; color: rgba(255,255,255,0.6); margin-top: 0.1rem; }

        .order-right { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }

        .order-total { font-weight: 700; color: #a78bfa; font-size: 1.05rem; }

        /* STATUS BADGE */
        .status-badge {
            padding: 0.3rem 0.9rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-pending    { background: rgba(255,193,7,0.15);  color: #ffc107; border: 1px solid rgba(255,193,7,0.3); }
        .status-processing { background: rgba(13,202,240,0.15); color: #0dcaf0; border: 1px solid rgba(13,202,240,0.3); }
        .status-shipped    { background: rgba(102,126,234,0.15);color: #a78bfa; border: 1px solid rgba(102,126,234,0.3); }
        .status-delivered  { background: rgba(25,135,84,0.15);  color: #20c997; border: 1px solid rgba(25,135,84,0.3); }
        .status-cancelled  { background: rgba(220,53,69,0.15);  color: #ff6b6b; border: 1px solid rgba(220,53,69,0.3); }

        .chevron { font-size: 0.9rem; color: rgba(255,255,255,0.4); transition: transform 0.3s; }
        .order-card.open .chevron { transform: rotate(180deg); }

        /* ORDER BODY (collapsible) */
        .order-body {
            display: none;
            padding: 1.2rem 1.5rem;
        }
        .order-card.open .order-body { display: block; }

        /* ITEMS */
        .order-items-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.4);
            margin-bottom: 0.8rem;
        }
        .order-item-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.6rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .order-item-row:last-child { border-bottom: none; }
        .order-item-row img {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
        }
        .oi-name { flex: 1; font-size: 0.9rem; }
        .oi-qty  { color: rgba(255,255,255,0.45); font-size: 0.85rem; }
        .oi-price { color: #a78bfa; font-weight: 600; font-size: 0.9rem; }

        /* DELIVERY INFO */
        .delivery-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.07);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
        }
        .delivery-section span { display: block; }
        .delivery-section strong { color: #fff; }

        /* ADMIN STATUS UPDATE */
        .status-update-form {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.07);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .status-update-form label {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-select {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #fff;
            padding: 0.45rem 0.8rem;
            font-size: 0.9rem;
            outline: none;
            cursor: pointer;
        }
        .status-select option { background: #1a1a2e; }
        .update-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            padding: 0.45rem 1.2rem;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .update-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(102,126,234,0.4); }

        @media (max-width: 600px) {
            .order-header { flex-direction: column; align-items: flex-start; }
            .delivery-section { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">🛍️ E-Online Shopping</div>
    <div class="nav-links">
        <span style="color:rgba(255,255,255,0.6); font-size:0.9rem;">👋 <?= htmlspecialchars($username) ?></span>
        <?php if ($is_admin): ?>
            <a href="admin/dashboard.php" class="nav-btn btn-back">⚙️ Admin Panel</a>
        <?php else: ?>
            <a href="index.php" class="nav-btn btn-back">🛍️ Store</a>
        <?php endif; ?>
        <a href="logout.php" class="nav-btn btn-logout">🚪 Logout</a>
    </div>
</nav>

<div class="page-wrapper">

    <div class="page-header">
        <h1><?= $is_admin ? '📋 All Orders' : '📦 My Orders' ?></h1>
        <p><?= $is_admin
            ? count($orders) . ' total order' . (count($orders) != 1 ? 's' : '') . ' across all customers'
            : 'Your personal order history' ?>
        </p>
    </div>

    <?php if (isset($_GET['updated'])): ?>
    <div class="toast">✅ Order #<?= str_pad(intval($_GET['updated']), 6, '0', STR_PAD_LEFT) ?> status updated successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['cancelled'])): ?>
    <div class="toast" style="background:rgba(239,68,68,0.2);border-color:rgba(239,68,68,0.4);color:#fca5a5;">
        ❌ Order #<?= str_pad(intval($_GET['cancelled']), 6, '0', STR_PAD_LEFT) ?> has been cancelled.
    </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <div class="icon">🛒</div>
        <h2>No orders yet</h2>
        <p>Looks like you haven't placed any orders. Start shopping!</p>
        <a href="index.php" class="btn-shop">Browse Products</a>
    </div>

    <?php else: ?>
        <?php foreach ($orders as $order):
            $status = $order['status'];
        ?>
        <div class="order-card" id="card-<?= $order['id'] ?>">

            <!-- HEADER (click to expand) -->
            <div class="order-header" onclick="toggleOrder(<?= $order['id'] ?>)">
                <div class="order-meta">
                    <span class="order-id">Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                    <span class="order-date">📅 <?= date('M j, Y — g:i A', strtotime($order['created_at'])) ?></span>
                    <?php if ($is_admin): ?>
                    <span class="order-customer">👤 <?= htmlspecialchars($order['username']) ?> · <?= htmlspecialchars($order['user_email']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="order-right">
                    <span class="order-total">₹<?= indian_number_format($order['total_price']) ?></span>
                    <span class="status-badge status-<?= $status ?>"><?= ucfirst($status) ?></span>
                    <span class="chevron">▼</span>
                </div>
            </div>

            <!-- BODY -->
            <div class="order-body">

                <div class="order-items-title">Items</div>
                <?php if ($status === 'cancelled'): ?>
                <div style="margin-bottom:0.75rem;padding:0.5rem 0.85rem;
                    background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);
                    border-radius:8px;font-size:0.82rem;color:#fca5a5;">
                    <?php
                    $cb = $order['cancelled_by'] ?? '';
                    if ($is_admin) {
                        echo $cb === 'user'
                            ? '&#10060; Cancelled by customer: <strong>' . htmlspecialchars($order['username']) . '</strong>'
                            : '&#10060; Cancelled by: <strong>Admin</strong>';
                    } else {
                        echo $cb === 'user'
                            ? '&#10060; You cancelled this order.'
                            : '&#10060; This order was cancelled by the store.';
                    }
                    ?>
                </div>
                <?php endif; ?>
                <?php foreach ($order_items_map[$order['id']] as $item): ?>
                <div class="order-item-row">
                    <img src="uploads/<?= htmlspecialchars($item['image']) ?>"
                         alt="<?= htmlspecialchars($item['name']) ?>"
                         onerror="this.style.opacity='0.2'">
                    <span class="oi-name"><?= htmlspecialchars($item['name']) ?></span>
                    <span class="oi-qty">× <?= $item['quantity'] ?></span>
                    <span class="oi-price">₹<?= indian_number_format($item['price'] * $item['quantity']) ?></span>
                </div>
                <?php endforeach; ?>

                <!-- DELIVERY INFO -->
                <div class="delivery-section">
                    <div>
                        <strong>📦 Deliver to</strong>
                        <span><?= htmlspecialchars($order['full_name']) ?></span>
                        <span><?= htmlspecialchars($order['address']) ?></span>
                        <span><?= htmlspecialchars($order['city']) ?> — <?= htmlspecialchars($order['pincode']) ?></span>
                    </div>
                    <div>
                        <strong>📞 Contact</strong>
                        <span><?= htmlspecialchars($order['phone']) ?></span>
                        <span><?= htmlspecialchars($order['user_email']) ?></span>
                    </div>
                </div>

                <!-- ADMIN: STATUS UPDATE -->
                <?php if ($is_admin): ?>
                <form method="POST" class="status-update-form">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <label>Update Status:</label>
                    <select name="status" class="status-select">
                        <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="update_status" class="update-btn">Save</button>
                </form>
                <?php endif; ?>

                <!-- USER: CANCEL BUTTON (only if pending or processing) -->
                <?php if (!$is_admin && in_array($status, ['pending', 'processing'])): ?>
                <form method="POST" class="status-update-form" onsubmit="return confirm('Cancel this order? This cannot be undone.')">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="status" value="cancelled">
                    <span style="font-size:0.82rem;color:var(--text-muted);">
                        You can cancel this order before it is shipped.
                    </span>
                    <button type="submit" name="update_status" style="
                        background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3);
                        color:#fca5a5; border-radius:8px; padding:0.4rem 1.1rem;
                        font-size:0.82rem; font-weight:700; cursor:pointer;
                        font-family:inherit; transition:all 0.2s;
                    " onmouseover="this.style.background='rgba(239,68,68,0.28)'"
                       onmouseout="this.style.background='rgba(239,68,68,0.15)'">
                        ❌ Cancel Order
                    </button>
                </form>
                <?php elseif (!$is_admin && $status === 'shipped'): ?>
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);
                    font-size:0.82rem;color:var(--text-muted);">
                    🚚 Your order has been shipped and can no longer be cancelled.
                </div>
                <?php elseif (!$is_admin && $status === 'cancelled'): ?>
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);font-size:0.82rem;">
                    <?php if (($order['cancelled_by'] ?? '') === 'user'): ?>
                    <span style="color:#fca5a5;">❌ You cancelled this order.</span>
                    <?php else: ?>
                    <span style="color:#fca5a5;">❌ This order was cancelled by the store.</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<script>
function toggleOrder(id) {
    const card = document.getElementById('card-' + id);
    card.classList.toggle('open');
}

// Auto-open first order on page load
window.addEventListener('DOMContentLoaded', () => {
    const first = document.querySelector('.order-card');
    if (first) first.classList.add('open');
});
</script>

</body>
</html>
