<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If NOT logged in → login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<!-- Dynamic navbar based on login status -->
<style>
.navbar-user { display: flex !important; align-items: center; gap: 20px; }
.logout-btn { 
    background: linear-gradient(45deg, #dc3545, #c82333) !important;
    color: white !important; padding: 10px 20px !important; 
    text-decoration: none !important; border-radius: 25px !important;
    font-weight: 600 !important; box-shadow: 0 4px 15px rgba(220,53,69,0.4) !important;
}
.logout-btn:hover { background: #c82333 !important; transform: scale(1.05) !important; }
</style>
