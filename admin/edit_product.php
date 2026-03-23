<?php
session_start();
require '../config.php';
require '../protect.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: dashboard.php');
    exit();
}

$product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
if (!$product) {
    header('Location: dashboard.php');
    exit();
}

if ($_POST) {
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price       = floatval($_POST['price']);
    $stock       = intval($_POST['stock']);
    $image_name  = $product['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        if ($image_name && file_exists($target_dir . $image_name)) {
            unlink($target_dir . $image_name);
        }

        $image_name = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image_name);
    }

    // FIX: was "ssdissi" (7 types, 6 vars) → now "ssdisd" (6 types, 6 vars)
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssdisi", $name, $description, $price, $stock, $image_name, $id);
    $stmt->execute();

    header('Location: dashboard.php?updated=1');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .header { text-align: center; margin-bottom: 2rem; }
        .header h1 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        .product-preview {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        .product-preview img {
            width: 120px; height: 120px;
            object-fit: cover; border-radius: 10px;
            margin-bottom: 1rem; border: 3px solid #e9ecef;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .form-group { position: relative; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 1rem;
            border: 2px solid #e9ecef; border-radius: 10px;
            font-size: 1rem; transition: all 0.3s ease; background: #f8f9fa;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none; border-color: #667eea;
            background: white; box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .btn-group { display: flex; gap: 1rem; justify-content: center; }
        .btn {
            padding: 1rem 2rem; border: none; border-radius: 10px;
            font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: all 0.3s ease; text-decoration: none;
            display: inline-block; text-align: center;
        }
        .btn-primary { background: linear-gradient(135deg, #28a745, #20c997); color: white; flex: 1; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(40,167,69,0.4); }
        .btn-secondary { background: linear-gradient(135deg, #6c757d, #495057); color: white; }
        .btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(108,117,125,0.4); }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .edit-container { padding: 1.5rem; margin: 1rem; }
        }
    </style>
</head>
<body>
<div class="edit-container">
    <div class="header">
        <h1>✏️ Edit Product</h1>
        <p>Update product details below</p>
    </div>

    <div class="product-preview">
        <img src="../uploads/<?= htmlspecialchars($product['image'] ?: 'placeholder.jpg') ?>"
             alt="<?= htmlspecialchars($product['name']) ?>"
             onerror="this.src='../placeholder.jpg'">
        <h3><?= htmlspecialchars($product['name']) ?></h3>
        <p>₹<?= number_format($product['price'], 2) ?> | Stock: <?= $product['stock'] ?></p>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Price (₹) *</label>
                <input type="number" name="price" step="0.01" value="<?= $product['price'] ?>" required>
            </div>
            <div class="form-group">
                <label>Stock Quantity *</label>
                <input type="number" name="stock" value="<?= $product['stock'] ?>" required min="0">
            </div>
            <div class="form-group">
                <label>New Image (optional)</label>
                <input type="file" name="image" accept="image/*">
                <small>Current image will be replaced</small>
            </div>
        </div>

        <div class="form-group full-width">
            <label>Description</label>
            <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">💾 Update Product</button>
            <a href="dashboard.php" class="btn btn-secondary">❌ Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
