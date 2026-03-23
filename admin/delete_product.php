<?php
session_start();
require '../config.php';

$id = (int)$_GET['id'];

// FIX: check $_SESSION['role'] == 'admin' (not $_SESSION['admin'])
if (!$id || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// DELETE in correct order (FK constraints):
// 1. Remove from carts
$conn->query("DELETE FROM carts WHERE product_id = $id");

// 2. Remove from order_items (orders don't have product_id — order_items does)
$conn->query("DELETE FROM order_items WHERE product_id = $id");

// 3. Delete product image from uploads/
$img = $conn->query("SELECT image FROM products WHERE id = $id")->fetch_assoc()['image'] ?? '';
if ($img && file_exists('../uploads/' . $img)) {
    unlink('../uploads/' . $img);
}

// 4. Delete the product
$conn->query("DELETE FROM products WHERE id = $id");

header('Location: dashboard.php?success=1');
exit();
?>
