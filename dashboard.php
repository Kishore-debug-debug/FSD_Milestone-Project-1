<?php 
require '../config.php'; 
require '../protect.php'; 

if ($_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

// Stats
$total_products = $conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
$total_orders   = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$pending_orders = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$total_revenue  = $conn->query("SELECT SUM(total_price) r FROM orders WHERE status != 'cancelled'")->fetch_assoc()['r'] ?? 0;
$low_stock      = $conn->query("SELECT COUNT(*) c FROM products WHERE stock > 0 AND stock <= 5")->fetch_assoc()['c'];
$out_of_stock   = $conn->query("SELECT COUNT(*) c FROM products WHERE stock = 0")->fetch_assoc()['c'];

$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:       #080b14;
            --bg-card:  rgba(255,255,255,0.06);
            --border:   rgba(255,255,255,0.09);
            --border-l: rgba(255,255,255,0.16);
            --accent:   #7c6cfc;
            --accent2:  #a78bfa;
            --glow:     rgba(124,108,252,0.3);
            --green:    #22c55e;
            --orange:   #f97316;
            --red:      #ef4444;
            --yellow:   #eab308;
            --text:     #f1f5f9;
            --muted:    rgba(241,245,249,0.45);
            --radius:   14px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg); color: var(--text);
            min-height: 100vh;
        }
        body::before {
            content:''; position:fixed; width:600px; height:600px;
            background:rgba(124,108,252,0.08); border-radius:50%;
            filter:blur(120px); top:-200px; left:-150px;
            pointer-events:none; z-index:0;
        }

        /* NAV */
        .navbar {
            position:sticky; top:0; z-index:100;
            display:flex; align-items:center; justify-content:space-between;
            padding:0 2rem; height:66px;
            background:rgba(8,11,20,0.85); backdrop-filter:blur(24px);
            border-bottom:1px solid var(--border);
        }
        .nav-left { display:flex; align-items:center; gap:1rem; }
        .logo {
            font-family:'Syne',sans-serif; font-size:1.2rem; font-weight:800;
            background:linear-gradient(135deg,#fff 40%,var(--accent2));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        }
        .admin-badge {
            background:rgba(124,108,252,0.15); border:1px solid rgba(124,108,252,0.3);
            color:var(--accent2); padding:0.2rem 0.7rem; border-radius:50px;
            font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em;
        }
        .nav-right { display:flex; align-items:center; gap:0.6rem; }
        .nav-user { font-size:0.85rem; color:var(--muted); }
        .npill {
            display:inline-flex; align-items:center; gap:5px;
            padding:0.42rem 1rem; border-radius:50px;
            font-size:0.8rem; font-weight:600; text-decoration:none;
            transition:all 0.2s; font-family:'DM Sans',sans-serif;
            border:none; cursor:pointer;
        }
        .pill-store   { background:rgba(34,197,94,0.14); color:#4ade80; border:1px solid rgba(34,197,94,0.25); }
        .pill-store:hover { background:rgba(34,197,94,0.25); }
        .pill-orders  { background:rgba(124,108,252,0.14); color:var(--accent2); border:1px solid rgba(124,108,252,0.28); }
        .pill-orders:hover { background:rgba(124,108,252,0.26); }
        .pill-logout  { background:rgba(239,68,68,0.12); color:#fca5a5; border:1px solid rgba(239,68,68,0.22); }
        .pill-logout:hover { background:rgba(239,68,68,0.22); }

        /* PAGE */
        .page { position:relative; z-index:1; max-width:1280px; margin:0 auto; padding:2rem; }

        /* STATS GRID */
        .stats-grid {
            display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
            gap:1rem; margin-bottom:2rem;
        }
        .stat-card {
            background:var(--bg-card); border:1px solid var(--border);
            border-radius:var(--radius); padding:1.2rem 1.4rem;
            transition:border-color 0.25s, transform 0.25s;
        }
        .stat-card:hover { border-color:var(--border-l); transform:translateY(-2px); }
        .stat-label { font-size:0.75rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.07em; margin-bottom:0.5rem; }
        .stat-value { font-family:'Syne',sans-serif; font-size:1.9rem; font-weight:800; line-height:1; }
        .stat-sub   { font-size:0.75rem; color:var(--muted); margin-top:0.3rem; }
        .c-purple { color:var(--accent2); }
        .c-green  { color:#4ade80; }
        .c-yellow { color:#fbbf24; }
        .c-red    { color:#fca5a5; }
        .c-orange { color:#fb923c; }
        .c-blue   { color:#60a5fa; }

        /* SECTION TITLE */
        .section-title {
            font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:700;
            margin-bottom:1.25rem; display:flex; align-items:center; gap:0.6rem;
        }
        .section-title span { color:var(--muted); font-size:0.85rem; font-family:'DM Sans',sans-serif; font-weight:400; }

        /* ADD PRODUCT CARD */
        .add-card {
            background:var(--bg-card); border:1px solid var(--border);
            border-radius:var(--radius); padding:1.75rem; margin-bottom:2rem;
        }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
        .form-group { display:flex; flex-direction:column; gap:0.4rem; }
        .form-group.full { grid-column:1/-1; }
        label { font-size:0.78rem; color:var(--muted); font-weight:500; text-transform:uppercase; letter-spacing:0.05em; }
        input[type=text], input[type=number], textarea, input[type=file] {
            background:rgba(255,255,255,0.07); border:1px solid var(--border);
            border-radius:10px; padding:0.7rem 0.9rem;
            color:var(--text); font-size:0.9rem; font-family:'DM Sans',sans-serif;
            outline:none; transition:all 0.25s; width:100%;
        }
        input:focus, textarea:focus {
            border-color:var(--accent); background:rgba(124,108,252,0.07);
            box-shadow:0 0 0 3px rgba(124,108,252,0.1);
        }
        input::placeholder, textarea::placeholder { color:var(--muted); }
        textarea { resize:vertical; min-height:80px; }
        input[type=file] { cursor:pointer; }
        input[type=file]::file-selector-button {
            background:rgba(124,108,252,0.2); border:none; border-radius:7px;
            color:var(--accent2); padding:0.3rem 0.8rem; font-size:0.8rem;
            font-weight:600; cursor:pointer; margin-right:0.75rem; transition:background 0.2s;
        }
        input[type=file]::file-selector-button:hover { background:rgba(124,108,252,0.35); }

        .btn-add {
            background:linear-gradient(135deg,var(--accent),#9b59fc);
            color:#fff; border:none; border-radius:10px;
            padding:0.75rem 2rem; font-size:0.92rem; font-weight:700;
            cursor:pointer; transition:all 0.25s; font-family:'DM Sans',sans-serif;
            display:inline-flex; align-items:center; gap:6px;
        }
        .btn-add:hover { transform:translateY(-2px); box-shadow:0 6px 20px var(--glow); }

        /* SUCCESS/ERROR FLASH */
        .flash {
            padding:0.75rem 1rem; border-radius:10px; margin-bottom:1.5rem;
            font-size:0.88rem; font-weight:500;
        }
        .flash-success { background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.25); color:#4ade80; }
        .flash-error   { background:rgba(239,68,68,0.1);  border:1px solid rgba(239,68,68,0.25);  color:#fca5a5; }

        /* TABLE */
        .table-card {
            background:var(--bg-card); border:1px solid var(--border);
            border-radius:var(--radius); overflow:hidden;
        }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        thead tr { background:rgba(255,255,255,0.04); border-bottom:1px solid var(--border); }
        th {
            padding:0.9rem 1.1rem; font-size:0.75rem; font-weight:700;
            text-transform:uppercase; letter-spacing:0.07em; color:var(--muted);
            text-align:left; white-space:nowrap;
        }
        td { padding:0.85rem 1.1rem; font-size:0.88rem; border-bottom:1px solid rgba(255,255,255,0.04); vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:rgba(255,255,255,0.025); }

        .prod-img {
            width:46px; height:46px; object-fit:cover;
            border-radius:9px; background:rgba(255,255,255,0.05);
        }
        .prod-name { font-weight:600; color:var(--text); }
        .prod-desc { color:var(--muted); font-size:0.82rem; margin-top:2px; }
        .price-cell { font-family:'Syne',sans-serif; font-weight:700; color:var(--accent2); }

        .stock-pill {
            display:inline-flex; align-items:center; gap:4px;
            padding:0.22rem 0.7rem; border-radius:50px; font-size:0.75rem; font-weight:700;
        }
        .sp-in     { background:rgba(34,197,94,0.12);  color:#4ade80;  border:1px solid rgba(34,197,94,0.22); }
        .sp-low    { background:rgba(249,115,22,0.12); color:#fb923c; border:1px solid rgba(249,115,22,0.22); }
        .sp-out    { background:rgba(239,68,68,0.12);  color:#fca5a5; border:1px solid rgba(239,68,68,0.22); }

        .btn-edit, .btn-del {
            display:inline-flex; align-items:center; gap:4px;
            padding:0.35rem 0.85rem; border-radius:8px;
            font-size:0.78rem; font-weight:700; text-decoration:none;
            transition:all 0.2s; border:none; cursor:pointer; font-family:'DM Sans',sans-serif;
        }
        .btn-edit { background:rgba(234,179,8,0.15); color:#fbbf24; border:1px solid rgba(234,179,8,0.28); }
        .btn-edit:hover { background:rgba(234,179,8,0.28); }
        .btn-del  { background:rgba(239,68,68,0.12); color:#fca5a5; border:1px solid rgba(239,68,68,0.22); }
        .btn-del:hover  { background:rgba(239,68,68,0.25); }

        .empty-row td { text-align:center; padding:3rem; color:var(--muted); }

        @media(max-width:768px) {
            .stats-grid { grid-template-columns:repeat(2,1fr); }
            .form-grid  { grid-template-columns:1fr; }
            .page { padding:1rem; }
            .navbar { padding:0 1rem; }
            .nav-user { display:none; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-left">
        <div class="logo">⚡ E-Shop</div>
        <span class="admin-badge">Admin</span>
    </div>
    <div class="nav-right">
        <span class="nav-user">👋 <?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="../orders.php"  class="npill pill-orders">📋 Orders</a>
        <a href="../profile.php" class="npill pill-store" style="background:rgba(255,255,255,0.08);color:var(--text);border:1px solid var(--border);">👤 Profile</a>
        <a href="../index.php"  class="npill pill-store">🛍️ Store</a>
        <a href="../logout.php" class="npill pill-logout">🚪 Logout</a>
    </div>
</nav>

<div class="page">

    <?php if (isset($_GET['success'])): ?>
    <div class="flash flash-success">✅ Product added successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
    <div class="flash flash-success">✅ Product updated successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <div class="flash flash-success">🗑️ Product deleted.</div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Products</div>
            <div class="stat-value c-purple"><?= $total_products ?></div>
            <div class="stat-sub"><?= $out_of_stock ?> out of stock</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value c-blue"><?= $total_orders ?></div>
            <div class="stat-sub"><?= $pending_orders ?> pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Revenue</div>
            <div class="stat-value c-green">$<?= number_format($total_revenue, 0) ?></div>
            <div class="stat-sub">Excl. cancelled</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Low Stock</div>
            <div class="stat-value c-orange"><?= $low_stock ?></div>
            <div class="stat-sub">≤ 5 units left</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value c-yellow"><?= $pending_orders ?></div>
            <div class="stat-sub"><a href="../orders.php" style="color:var(--accent2);text-decoration:none;font-size:0.8rem;">View all →</a></div>
        </div>
    </div>

    <!-- ADD PRODUCT -->
    <div class="add-card">
        <div class="section-title">➕ Add New Product</div>
        <form method="POST" action="add_product.php" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" placeholder="e.g. iPhone 15 Pro" required>
                </div>
                <div class="form-group">
                    <label>Price ($) *</label>
                    <input type="number" name="price" placeholder="0.00" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Stock Quantity *</label>
                    <input type="number" name="stock" placeholder="0" min="0" required>
                </div>
                <div class="form-group">
                    <label>Product Image *</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>
                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description" placeholder="Describe the product..."></textarea>
                </div>
            </div>
            <button type="submit" class="btn-add">➕ Add Product</button>
        </form>
    </div>

    <!-- PRODUCTS TABLE -->
    <div class="section-title">
        📦 All Products <span>(<?= $total_products ?>)</span>
    </div>
    <div class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($products->num_rows === 0): ?>
                    <tr class="empty-row"><td colspan="6">No products yet. Add your first product above!</td></tr>
                <?php else: ?>
                <?php while ($p = $products->fetch_assoc()): ?>
                    <tr>
                        <td style="color:var(--muted);font-size:0.8rem;">#<?= $p['id'] ?></td>
                        <td>
                            <?php if ($p['image']): ?>
                            <img src="../uploads/<?= htmlspecialchars($p['image']) ?>" class="prod-img" alt="">
                            <?php else: ?>
                            <div class="prod-img" style="display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📦</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="prod-desc"><?= htmlspecialchars(substr($p['description'] ?? '', 0, 55)) ?>...</div>
                        </td>
                        <td class="price-cell">$<?= number_format($p['price'], 2) ?></td>
                        <td>
                            <?php if ($p['stock'] == 0): ?>
                                <span class="stock-pill sp-out">🚫 Out of Stock</span>
                            <?php elseif ($p['stock'] <= 5): ?>
                                <span class="stock-pill sp-low">⚠️ <?= $p['stock'] ?> left</span>
                            <?php else: ?>
                                <span class="stock-pill sp-in">✅ <?= $p['stock'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:0.5rem;">
                                <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn-edit">✏️ Edit</a>
                                <a href="delete_product.php?id=<?= $p['id'] ?>"
                                   class="btn-del"
                                   onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($p['name'])) ?>\'? This cannot be undone.')">
                                   🗑️ Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>
