<?php
require 'api/config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.html');
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.html');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['name']); ?> - E-Shop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .product-detail-page { max-width: 1000px; margin: 2rem auto; padding: 2rem; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; }
        .detail-image { width: 100%; height: 400px; object-fit: cover; border-radius: 10px; }
        .detail-specs { background: #f8f9fa; padding: 1.5rem; border-radius: 10px; }
        .stock-available { color: #28a745; font-weight: bold; }
        .stock-out { color: #dc3545; }
        @media (max-width: 768px) { .detail-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <nav style="padding: 1rem; background: #333; color: white;">
        <a href="index.html" style="color: white; margin-right: 2rem;">← Back to Shop</a>
    </nav>
    
    <div class="product-detail-page">
        <div class="detail-grid">
            <img src="images/<?php echo htmlspecialchars($product['image']); ?>" 
                 class="detail-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
            
            <div>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="price" style="font-size: 2rem; color: #e74c3c;"><?php echo '$' . number_format($product['price'], 2); ?></p>
                
                <div class="detail-specs">
                    <h3>📋 Product Specifications</h3>
                    <p><strong>Stock:</strong> 
                        <span class="<?php echo $product['stock'] > 0 ? 'stock-available' : 'stock-out'; ?>">
                            <?php echo $product['stock'] > 0 ? '✅ In Stock (' . $product['stock'] . ')' : '❌ Out of Stock'; ?>
                        </span>
                    </p>
                    <p><strong>Description:</strong></p>
                    <div style="line-height: 1.6; color: #333; margin-top: 0.5rem;">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                <div style="margin-top: 2rem;">
                    <a href="index.html#add-to-cart-<?php echo $product['id']; ?>" 
                       class="add-to-cart" style="display: inline-block; padding: 1rem 2rem; font-size: 1.2rem;">
                        🛒 Add to Cart
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
