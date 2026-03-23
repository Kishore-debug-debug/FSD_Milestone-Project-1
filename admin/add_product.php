<?php
session_start(); require '../config.php'; require '../protect.php';
if ($_POST) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    
    // Handle image upload
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $image_name = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image_name);
    }
    
    $conn->query("INSERT INTO products (name, description, price, stock, image) VALUES ('$name', '$description', $price, $stock, '$image_name')");
    header('Location: dashboard.php');
    exit();
}
header('Location: dashboard.php');
?>
