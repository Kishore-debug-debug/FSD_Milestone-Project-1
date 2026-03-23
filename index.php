<?php require 'protect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Online Shopping</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-deep:     #080b14;
            --bg-card:     rgba(255,255,255,0.055);
            --border:      rgba(255,255,255,0.09);
            --border-lit:  rgba(255,255,255,0.18);
            --accent:      #7c6cfc;
            --accent2:     #a78bfa;
            --accent-glow: rgba(124,108,252,0.35);
            --text:        #f1f5f9;
            --text-muted:  rgba(241,245,249,0.45);
            --radius:      16px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg-deep);
            color: var(--text); min-height:100vh; overflow-x:hidden;
        }
        body::before, body::after {
            content:''; position:fixed; border-radius:50%;
            filter:blur(130px); pointer-events:none; z-index:0;
        }
        body::before { width:600px; height:600px; background:rgba(124,108,252,0.1); top:-200px; left:-150px; }
        body::after  { width:500px; height:500px; background:rgba(167,139,250,0.07); bottom:-100px; right:-100px; }

        /* NAV */
        .navbar {
            position:sticky; top:0; z-index:100;
            display:flex; align-items:center; justify-content:space-between;
            padding:0 2rem; height:68px;
            background:rgba(8,11,20,0.8); backdrop-filter:blur(24px);
            border-bottom:1px solid var(--border);
        }
        .logo {
            font-family:'Syne',sans-serif; font-size:1.25rem; font-weight:800;
            background:linear-gradient(135deg,#fff 40%,var(--accent2));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        }
        .nav-right { display:flex; align-items:center; gap:0.6rem; }
        .nav-user { font-size:0.85rem; color:var(--text-muted); }
        .nav-pill {
            display:inline-flex; align-items:center; gap:5px;
            padding:0.42rem 1rem; border-radius:50px;
            font-size:0.8rem; font-weight:600; text-decoration:none;
            transition:all 0.2s; font-family:'DM Sans',sans-serif; border:none; cursor:pointer;
        }
        .pill-orders { background:rgba(124,108,252,0.15); color:var(--accent2); border:1px solid rgba(124,108,252,0.3); }
        .pill-orders:hover { background:rgba(124,108,252,0.28); }
        .pill-logout { background:rgba(239,68,68,0.12); color:#fca5a5; border:1px solid rgba(239,68,68,0.22); }
        .pill-logout:hover { background:rgba(239,68,68,0.22); }
        .pill-cart {
            background:linear-gradient(135deg,var(--accent),#9b59fc); color:#fff;
            box-shadow:0 4px 16px var(--accent-glow);
        }
        .pill-cart:hover { transform:translateY(-1px); box-shadow:0 6px 22px var(--accent-glow); }

        /* HERO */
        .hero {
            position:relative; z-index:1;
            text-align:center; padding:4rem 1.5rem 2.5rem;
        }
        .hero-eyebrow {
            display:inline-flex; align-items:center; gap:7px;
            background:rgba(124,108,252,0.1); border:1px solid rgba(124,108,252,0.22);
            border-radius:50px; padding:0.28rem 1rem;
            font-size:0.75rem; font-weight:600; color:var(--accent2);
            letter-spacing:0.08em; text-transform:uppercase; margin-bottom:1.2rem;
        }
        .hero h1 {
            font-family:'Syne',sans-serif;
            font-size:clamp(1.9rem,5vw,3.2rem); font-weight:800;
            line-height:1.1; letter-spacing:-0.03em; margin-bottom:0.7rem;
        }
        .hero h1 span {
            background:linear-gradient(135deg,var(--accent) 0%,var(--accent2) 55%,#e879f9 100%);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        }
        .hero p { color:var(--text-muted); font-size:1rem; max-width:440px; margin:0 auto 2rem; }

        /* FILTERS */
        .filters-bar {
            position:relative; z-index:1;
            max-width:860px; margin:0 auto 2rem; padding:0 1.5rem;
            display:flex; flex-direction:column; gap:0.85rem;
        }
        .search-wrap { position:relative; }
        .search-icon {
            position:absolute; left:1rem; top:50%; transform:translateY(-50%);
            color:var(--text-muted); pointer-events:none; font-size:0.95rem;
        }
        #search-input {
            width:100%; background:var(--bg-card); border:1px solid var(--border);
            border-radius:50px; padding:0.8rem 1.2rem 0.8rem 2.8rem;
            color:var(--text); font-size:0.92rem; font-family:'DM Sans',sans-serif;
            outline:none; transition:all 0.25s;
        }
        #search-input::placeholder { color:var(--text-muted); }
        #search-input:focus { border-color:var(--accent); background:rgba(124,108,252,0.06); box-shadow:0 0 0 3px rgba(124,108,252,0.1); }

        .filter-row { display:flex; gap:0.6rem; flex-wrap:wrap; align-items:center; }
        .filter-label { font-size:0.78rem; color:var(--text-muted); font-weight:500; }
        .filter-chip {
            background:var(--bg-card); border:1px solid var(--border);
            border-radius:50px; padding:0.35rem 0.9rem;
            font-size:0.8rem; color:var(--text-muted);
            cursor:pointer; transition:all 0.2s; font-family:'DM Sans',sans-serif;
        }
        .filter-chip:hover, .filter-chip.active {
            background:rgba(124,108,252,0.15); border-color:rgba(124,108,252,0.38); color:var(--accent2);
        }
        .price-range { display:flex; align-items:center; gap:0.45rem; margin-left:auto; }
        .price-input {
            width:85px; background:var(--bg-card); border:1px solid var(--border);
            border-radius:8px; padding:0.35rem 0.65rem;
            color:var(--text); font-size:0.8rem; font-family:'DM Sans',sans-serif; outline:none;
        }
        .price-input:focus { border-color:var(--accent); }
        .price-sep { color:var(--text-muted); font-size:0.8rem; }

        /* RESULTS BAR */
        .results-bar {
            position:relative; z-index:1;
            max-width:1260px; margin:0 auto 1rem; padding:0 2rem;
            display:flex; justify-content:space-between; align-items:center;
        }
        .results-count { font-size:0.82rem; color:var(--text-muted); }
        .sort-select {
            background:var(--bg-card); border:1px solid var(--border);
            border-radius:8px; padding:0.32rem 0.75rem;
            color:var(--text); font-size:0.8rem; font-family:'DM Sans',sans-serif; outline:none; cursor:pointer;
        }
        .sort-select option { background:#0d1220; }

        /* GRID */
        .product-grid {
            position:relative; z-index:1;
            max-width:1260px; margin:0 auto;
            padding:0 2rem 4rem;
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
            gap:1.4rem;
        }

        /* CARD */
        .product-card {
            background:var(--bg-card); border:1px solid var(--border);
            border-radius:var(--radius); overflow:hidden;
            display:flex; flex-direction:column; position:relative;
            transition:transform 0.25s, border-color 0.25s, box-shadow 0.25s;
            animation:cardIn 0.4s ease both;
        }
        .product-card:hover {
            transform:translateY(-5px); border-color:var(--border-lit);
            box-shadow:0 18px 40px rgba(0,0,0,0.45), 0 0 0 1px rgba(124,108,252,0.1);
        }
        @keyframes cardIn { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }

        .card-img {
            background:rgba(255,255,255,0.03); height:190px;
            display:flex; align-items:center; justify-content:center; overflow:hidden;
        }
        .card-img img { max-width:82%; max-height:165px; object-fit:contain; transition:transform 0.3s; }
        .product-card:hover .card-img img { transform:scale(1.06); }

        .badge {
            position:absolute; top:10px; right:10px;
            padding:3px 10px; border-radius:50px;
            font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em;
        }
        .badge-oos { background:#ef4444; color:#fff; }
        .badge-low { background:#f97316; color:#fff; }

        .card-body { padding:1rem 1.1rem; flex:1; display:flex; flex-direction:column; gap:0.35rem; }
        .card-name { font-family:'Syne',sans-serif; font-size:0.97rem; font-weight:700; line-height:1.3; }
        .card-desc { font-size:0.8rem; color:var(--text-muted); line-height:1.5; flex:1; }
        .card-price { font-family:'Syne',sans-serif; font-size:1.2rem; font-weight:800; color:var(--accent2); margin-top:0.2rem; }

        .card-actions { display:flex; gap:0.5rem; padding:0 1.1rem 1.1rem; }
        .btn-atc {
            flex:1; padding:0.58rem 0.8rem;
            background:linear-gradient(135deg,var(--accent),#9b59fc);
            color:#fff; border:none; border-radius:10px;
            font-size:0.82rem; font-weight:600; cursor:pointer;
            transition:all 0.2s; font-family:'DM Sans',sans-serif;
        }
        .btn-atc:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 5px 16px var(--accent-glow); }
        .btn-atc:disabled { background:rgba(255,255,255,0.07); color:var(--text-muted); cursor:not-allowed; }
        .btn-view {
            padding:0.58rem 0.85rem; background:rgba(255,255,255,0.07);
            color:var(--text); border:1px solid var(--border); border-radius:10px;
            font-size:0.82rem; cursor:pointer; transition:all 0.2s;
            text-decoration:none; display:inline-flex; align-items:center;
            font-family:'DM Sans',sans-serif;
        }
        .btn-view:hover { background:rgba(255,255,255,0.13); }

        .qty-controls { display:flex; align-items:center; gap:0.45rem; padding:0 1.1rem 0.9rem; }
        .qty-btn {
            width:28px; height:28px; background:rgba(124,108,252,0.14);
            border:1px solid rgba(124,108,252,0.28); border-radius:7px;
            color:var(--accent2); font-size:1rem; font-weight:700;
            cursor:pointer; transition:all 0.15s;
            display:flex; align-items:center; justify-content:center;
        }
        .qty-btn:hover { background:rgba(124,108,252,0.28); }
        .qty-input {
            width:40px; text-align:center;
            background:rgba(255,255,255,0.06); border:1px solid var(--border);
            border-radius:7px; color:var(--text); font-size:0.88rem; padding:0.2rem;
        }

        /* EMPTY */
        .empty-state {
            grid-column:1/-1; text-align:center; padding:5rem 2rem; color:var(--text-muted);
        }
        .empty-state .ei { font-size:3.5rem; margin-bottom:1rem; }
        .empty-state h3 { font-family:'Syne',sans-serif; font-size:1.2rem; color:var(--text); margin-bottom:0.4rem; }

        /* MODALS */
        .modal {
            display:none; position:fixed; inset:0; z-index:200;
            background:rgba(0,0,0,0.65); backdrop-filter:blur(8px);
            align-items:center; justify-content:center; padding:1rem;
        }
        .modal.open { display:flex; }
        .modal-box {
            background:#0e1421; border:1px solid var(--border-lit);
            border-radius:22px; padding:2rem; width:100%; max-width:460px;
            max-height:88vh; overflow-y:auto; position:relative;
            animation:mIn 0.22s ease;
        }
        .modal-box.large { max-width:700px; }
        @keyframes mIn { from{opacity:0;transform:scale(0.95) translateY(12px)} to{opacity:1;transform:scale(1) translateY(0)} }
        .modal-close {
            position:absolute; top:1rem; right:1.1rem;
            background:rgba(255,255,255,0.08); border:none; color:var(--text);
            width:30px; height:30px; border-radius:50%; cursor:pointer; font-size:0.9rem;
            display:flex; align-items:center; justify-content:center; transition:background 0.2s;
        }
        .modal-close:hover { background:rgba(255,255,255,0.15); }
        .modal-title { font-family:'Syne',sans-serif; font-size:1.15rem; font-weight:700; margin-bottom:1.4rem; }

        /* CART */
        .cart-row {
            display:flex; justify-content:space-between; align-items:center;
            padding:0.7rem 0; border-bottom:1px solid var(--border);
        }
        .cart-row:last-of-type { border-bottom:none; }
        .cart-iname { font-weight:500; font-size:0.88rem; }
        .cart-iqty  { color:var(--text-muted); font-size:0.8rem; }
        .cart-iprice { color:var(--accent2); font-weight:700; font-size:0.9rem; }
        .cart-rm {
            background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2);
            color:#fca5a5; border-radius:6px; padding:0.18rem 0.55rem;
            font-size:0.72rem; cursor:pointer; margin-left:0.6rem; transition:background 0.2s;
        }
        .cart-rm:hover { background:rgba(239,68,68,0.22); }
        .cart-total {
            display:flex; justify-content:space-between; margin-top:1.2rem;
            padding-top:1rem; border-top:1px solid var(--border-lit);
            font-family:'Syne',sans-serif; font-weight:700; font-size:1rem;
        }
        .cart-total span:last-child { color:var(--accent2); }
        .btn-checkout {
            width:100%; margin-top:1.2rem; padding:0.85rem;
            background:linear-gradient(135deg,var(--accent),#9b59fc);
            color:#fff; border:none; border-radius:12px;
            font-size:0.95rem; font-weight:700; cursor:pointer;
            transition:all 0.2s; font-family:'DM Sans',sans-serif;
        }
        .btn-checkout:hover { transform:translateY(-2px); box-shadow:0 8px 22px var(--accent-glow); }

        /* DETAIL */
        .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.75rem; }
        .detail-imgbox {
            background:rgba(255,255,255,0.03); border-radius:14px;
            display:flex; align-items:center; justify-content:center;
            min-height:220px; padding:1.5rem;
        }
        .detail-imgbox img { max-width:100%; max-height:220px; object-fit:contain; }
        .detail-info { display:flex; flex-direction:column; gap:0.6rem; }
        .detail-name { font-family:'Syne',sans-serif; font-size:1.35rem; font-weight:800; line-height:1.2; }
        .detail-price { font-family:'Syne',sans-serif; font-size:1.7rem; font-weight:800; color:var(--accent2); }
        .detail-desc { color:var(--text-muted); font-size:0.88rem; line-height:1.6; }
        .sbadge {
            display:inline-flex; align-items:center; gap:5px;
            padding:0.3rem 0.85rem; border-radius:50px; font-size:0.8rem; font-weight:600;
        }
        .sb-in  { background:rgba(34,197,94,0.1);  color:#4ade80; border:1px solid rgba(34,197,94,0.22); }
        .sb-low { background:rgba(249,115,22,0.1); color:#fb923c; border:1px solid rgba(249,115,22,0.22); }
        .sb-out { background:rgba(239,68,68,0.1);  color:#fca5a5; border:1px solid rgba(239,68,68,0.22); }
        .btn-datc {
            margin-top:0.6rem; padding:0.78rem;
            background:linear-gradient(135deg,var(--accent),#9b59fc);
            color:#fff; border:none; border-radius:12px;
            font-size:0.92rem; font-weight:700; cursor:pointer;
            transition:all 0.2s; font-family:'DM Sans',sans-serif;
        }
        .btn-datc:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 8px 22px var(--accent-glow); }
        .btn-datc:disabled { background:rgba(255,255,255,0.07); color:var(--text-muted); cursor:not-allowed; }

        /* TOAST */
        #toast {
            position:fixed; bottom:2rem; right:2rem; z-index:999;
            background:linear-gradient(135deg,#7c6cfc,#9b59fc);
            color:#fff; padding:0.7rem 1.3rem; border-radius:50px;
            font-weight:600; font-size:0.87rem; font-family:'DM Sans',sans-serif;
            box-shadow:0 8px 24px rgba(124,108,252,0.4);
            opacity:0; transition:opacity 0.3s; pointer-events:none;
        }

        @media(max-width:640px) {
            .product-grid { padding:0 1rem 3rem; }
            .filters-bar, .results-bar { padding:0 1rem; }
            .price-range { margin-left:0; }
            .detail-grid { grid-template-columns:1fr; }
            .navbar { padding:0 1rem; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">⚡ E-Shop</div>
    <div class="nav-right">
        <?php if(isset($_SESSION['username'])): ?>
        <span class="nav-user">👋 <?= htmlspecialchars($_SESSION['username']) ?></span>
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="admin/dashboard.php" class="nav-pill pill-orders" style="background:rgba(34,197,94,0.14);color:#4ade80;border:1px solid rgba(34,197,94,0.25);">⚙️ Dashboard</a>
        <?php else: ?>
        <a href="orders.php" class="nav-pill pill-orders">📋 Orders</a>
        <?php endif; ?>
        <?php if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
        <a href="profile.php" class="nav-pill pill-orders" style="background:rgba(255,255,255,0.08);color:var(--text);border:1px solid var(--border);">👤 Profile</a>
        <?php endif; ?>
        <a href="logout.php" class="nav-pill pill-logout">🚪 Logout</a>
        <?php endif; ?>
        <?php if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
        <button class="nav-pill pill-cart" onclick="eshop.openCart()">
            🛒 <span id="cart-count">0</span>
        </button>
        <?php endif; ?>
    </div>
</nav>

<div class="hero">
    <div class="hero-eyebrow">✨ New Arrivals Available</div>
    <h1>Shop the <span>Future</span><br>Today</h1>
    <p>Premium products curated for you — great prices, fast delivery.</p>
</div>

<div class="filters-bar">
    <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" id="search-input" placeholder="Search products…">
    </div>
    <div class="filter-row">
        <span class="filter-label">Show:</span>
        <button class="filter-chip active" data-filter="all">All</button>
        <button class="filter-chip" data-filter="instock">In Stock</button>
        <button class="filter-chip" data-filter="oos">Out of Stock</button>
        <div class="price-range">
            <span class="filter-label">₹</span>
            <input type="number" class="price-input" id="price-min" placeholder="Min" min="0">
            <span class="price-sep">–</span>
            <input type="number" class="price-input" id="price-max" placeholder="Max" min="0">
        </div>
    </div>
</div>

<div class="results-bar">
    <span class="results-count" id="results-count">Loading…</span>
    <select class="sort-select" id="sort-select">
        <option value="default">Sort: Default</option>
        <option value="price-asc">Price ↑</option>
        <option value="price-desc">Price ↓</option>
        <option value="name-asc">Name A→Z</option>
    </select>
</div>

<section id="products" class="product-grid"></section>

<!-- CART MODAL -->
<div id="cart-modal" class="modal">
    <div class="modal-box">
        <button class="modal-close" onclick="eshop.closeCart()">✕</button>
        <div class="modal-title">🛒 Your Cart</div>
        <div id="cart-items"></div>
        <div class="cart-total"><span>Total</span><span>₹<span id="total-price">0.00</span></span></div>
        <button class="btn-checkout" onclick="window.location.href='checkout.php'">Proceed to Checkout →</button>
    </div>
</div>

<!-- DETAIL MODAL -->
<div id="product-modal" class="modal">
    <div class="modal-box large">
        <button class="modal-close" id="product-close">✕</button>
        <div class="detail-grid">
            <div class="detail-imgbox"><img id="detail-image" src="" alt=""></div>
            <div class="detail-info">
                <h2 class="detail-name" id="detail-title"></h2>
                <div class="detail-price" id="detail-price"></div>
                <div id="detail-stock"></div>
                <p class="detail-desc" id="detail-description"></p>
                <button class="btn-datc" id="detail-add-cart">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<div id="toast"></div>

<script>
class EShop {
    constructor() {
        this.allProducts = [];
        this.cartItems = new Map();
        this.init();
    }
    async init() {
        await this.fetchProducts();
        await this.loadCartState();
        this.renderProducts();
        this.bindEvents();
    }
    async fetchProducts() {
        const res = await fetch('api/products.php');
        this.allProducts = await res.json();
    }
    getFiltered() {
        const q      = document.getElementById('search-input').value.toLowerCase();
        const filter = document.querySelector('.filter-chip.active')?.dataset.filter || 'all';
        const minP   = parseFloat(document.getElementById('price-min').value) || 0;
        const maxP   = parseFloat(document.getElementById('price-max').value) || Infinity;
        const sort   = document.getElementById('sort-select').value;

        let list = this.allProducts.filter(p => {
            const mQ = p.name.toLowerCase().includes(q) || (p.description||'').toLowerCase().includes(q);
            const mF = filter==='all' || (filter==='instock'&&p.stock>0) || (filter==='oos'&&p.stock===0);
            const mP = p.price >= minP && p.price <= maxP;
            return mQ && mF && mP;
        });
        if (sort==='price-asc')  list.sort((a,b)=>a.price-b.price);
        if (sort==='price-desc') list.sort((a,b)=>b.price-a.price);
        if (sort==='name-asc')   list.sort((a,b)=>a.name.localeCompare(b.name));
        return list;
    }
    renderProducts() {
        const list = this.getFiltered();
        const grid = document.getElementById('products');
        document.getElementById('results-count').textContent =
            `${list.length} product${list.length!==1?'s':''} found`;

        if (!list.length) {
            grid.innerHTML = `<div class="empty-state">
                <div class="ei">🔍</div><h3>No products found</h3>
                <p>Try adjusting your search or filters</p></div>`;
            return;
        }
        grid.innerHTML = list.map((p, i) => {
            const qty  = this.cartItems.get(parseInt(p.id)) || 0;
            const oos  = p.stock === 0;
            const low  = p.stock > 0 && p.stock <= 5;
            const desc = (p.description||'').substring(0,90) + (p.description?.length>90?'…':'');
            return `
            <div class="product-card" style="animation-delay:${(i%8)*0.05}s">
                <div class="card-img">
                    <img src="/ecommerce/${p.image}" alt="${p.name}"
                         style="${oos?'opacity:0.45;filter:grayscale(50%)':''}">
                    ${oos?'<span class="badge badge-oos">Out of Stock</span>':''}
                    ${low?`<span class="badge badge-low">Only ${p.stock} left</span>`:''}
                </div>
                <div class="card-body">
                    <div class="card-name">${p.name}</div>
                    <div class="card-desc">${desc}</div>
                    <div class="card-price">₹${p.price.toLocaleString('en-IN')}</div>
                </div>
                <div class="card-actions">
                    <button class="btn-atc" ${oos?'disabled':''}
                        onclick="${oos?'':'eshop.addToCart('+p.id+')'}">
                        ${oos?'🚫 Unavailable':'🛒 Add to Cart'}
                    </button>
                    <a href="#" class="btn-view" onclick="eshop.showDetail(${p.id});return false;">👁</a>
                </div>
                ${qty>0&&!oos?`<div class="qty-controls">
                    <button class="qty-btn" onclick="eshop.changeQty(${p.id},-1)">−</button>
                    <input class="qty-input" value="${qty}" readonly>
                    <button class="qty-btn" onclick="eshop.changeQty(${p.id},1)">+</button>
                </div>`:''}
            </div>`;
        }).join('');
    }
    showDetail(id) {
        const p = this.allProducts.find(x=>x.id==id); if(!p) return;
        const oos = p.stock===0, low = p.stock>0&&p.stock<=5;
        document.getElementById('detail-image').src = `/ecommerce/${p.image}`;
        document.getElementById('detail-image').style.filter = oos?'grayscale(40%)':'';
        document.getElementById('detail-title').textContent = p.name;
        document.getElementById('detail-price').textContent = `₹${p.price.toLocaleString('en-IN')}`;
        document.getElementById('detail-description').textContent = p.description;
        const s = document.getElementById('detail-stock');
        s.innerHTML = oos ? `<span class="sbadge sb-out">🚫 Out of Stock</span>`
                    : low ? `<span class="sbadge sb-low">⚠️ Only ${p.stock} left!</span>`
                          : `<span class="sbadge sb-in">✅ In Stock — ${p.stock} available</span>`;
        const btn = document.getElementById('detail-add-cart');
        btn.textContent = oos?'🚫 Out of Stock':'Add to Cart';
        btn.disabled = oos;
        btn.onclick = oos ? null : ()=>this.addToCart(p.id);
        document.getElementById('product-modal').classList.add('open');
    }
    async loadCartState() {
        try {
            const res = await fetch('api/getcart.php');
            const items = await res.json();
            this.cartItems.clear();
            items.forEach(i=>this.cartItems.set(parseInt(i.product_id), parseInt(i.quantity)));
            this.updateCartCount();
        } catch(e){}
    }
    async addToCart(id) {
        id = parseInt(id);
        const product = this.allProducts.find(p => parseInt(p.id) === id);
        const stock   = product ? parseInt(product.stock) : 0;
        const current = this.cartItems.get(id) || 0;
        if (current >= stock) {
            this.toast('⚠️ Only ' + stock + ' in stock!');
            return;
        }
        try {
            const res = await fetch('api/cart.php',{
                method:'POST', headers:{'Content-Type':'application/json'},
                body:JSON.stringify({product_id:id, quantity:1})
            });
            if(res.ok){
                await this.loadCartState();
                this.renderProducts();
                this.toast('✅ Added to cart!');
            }
        } catch(e){}
    }
    changeQty(id, delta) {
        id = parseInt(id);
        const product = this.allProducts.find(p => parseInt(p.id) === id);
        const stock   = product ? parseInt(product.stock) : 99;
        const current = this.cartItems.get(id) || 0;
        const nq      = Math.max(0, Math.min(stock, current + delta));
        if (nq === current) {
            if (delta > 0) this.toast('⚠️ Only ' + stock + ' in stock!');
            return;
        }
        fetch('api/updatecart.php',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({product_id:id,quantity:nq})
        }).then(()=>{ this.cartItems.set(id,nq); this.updateCartCount(); this.renderProducts(); });
    }
    openCart() { document.getElementById('cart-modal').classList.add('open'); this.renderCart(); }
    closeCart(){ document.getElementById('cart-modal').classList.remove('open'); }
    async renderCart() {
        try {
            const res = await fetch('api/getcart.php');
            const items = await res.json();
            const box = document.getElementById('cart-items');
            if(!items.length){
                box.innerHTML='<p style="color:var(--text-muted);text-align:center;padding:2rem 0">Cart is empty 🛒</p>';
                document.getElementById('total-price').textContent='0.00'; return;
            }
            box.innerHTML = items.map(i=>`
                <div class="cart-row">
                    <div><div class="cart-iname">${i.name}</div></div>
                    <div style="display:flex;align-items:center;gap:0.5rem">
                        <button class="qty-btn" style="width:26px;height:26px;font-size:0.9rem" onclick="eshop.cartChangeQty(${i.product_id},-1)">−</button>
                        <span style="min-width:20px;text-align:center;font-weight:700;font-size:0.9rem">${i.quantity}</span>
                        <button class="qty-btn" style="width:26px;height:26px;font-size:0.9rem" onclick="eshop.cartChangeQty(${i.product_id},1)">+</button>
                        <span class="cart-iprice" style="margin-left:0.4rem">₹${(i.price*i.quantity).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2})}</span>
                        <button class="cart-rm" onclick="eshop.removeFromCart(${i.product_id})">✕</button>
                    </div>
                </div>`).join('');
            document.getElementById('total-price').textContent =
                items.reduce((s,i)=>s+i.price*i.quantity,0).toFixed(2);
        } catch(e){}
    }
    cartChangeQty(id, delta) {
        id = parseInt(id);
        const product = this.allProducts.find(p => parseInt(p.id) === id);
        const stock   = product ? parseInt(product.stock) : 99;
        const current = this.cartItems.get(id) || 0;
        const nq      = Math.max(0, Math.min(stock, current + delta));
        if (nq === current && delta > 0) {
            this.toast('⚠️ Only ' + stock + ' in stock!');
            return;
        }
        if (nq === 0) { this.removeFromCart(id); return; }
        fetch('api/updatecart.php',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({product_id:id,quantity:nq})
        }).then(()=>{
            this.cartItems.set(id, nq);
            this.updateCartCount();
            this.renderProducts();
            this.renderCart();
        });
    }
    removeFromCart(id) {
        fetch('api/removecart.php',{method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({product_id:id})
        }).then(()=>{ this.loadCartState().then(()=>{ this.renderProducts(); this.renderCart(); }); });
    }
    updateCartCount() {
        document.getElementById('cart-count').textContent =
            Array.from(this.cartItems.values()).reduce((s,v)=>s+v,0);
    }
    toast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg; t.style.opacity='1';
        clearTimeout(this._t);
        this._t = setTimeout(()=>t.style.opacity='0', 2200);
    }
    bindEvents() {
        ['search-input','price-min','price-max'].forEach(id=>
            document.getElementById(id).addEventListener('input',()=>this.renderProducts()));
        document.getElementById('sort-select').addEventListener('change',()=>this.renderProducts());
        document.querySelectorAll('.filter-chip').forEach(b=>b.addEventListener('click',()=>{
            document.querySelectorAll('.filter-chip').forEach(x=>x.classList.remove('active'));
            b.classList.add('active'); this.renderProducts();
        }));
        document.getElementById('product-close').onclick = ()=>
            document.getElementById('product-modal').classList.remove('open');
        window.addEventListener('click', e=>{
            if(e.target.id==='cart-modal') this.closeCart();
            if(e.target.id==='product-modal') document.getElementById('product-modal').classList.remove('open');
        });
    }
}
const eshop = new EShop();
</script>
</body>
</html>
