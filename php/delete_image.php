<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.html");
    exit();
}

$seller_id = $_SESSION['user_id'];

if (!isset($_GET['menu_item_id'])) {
    die("No item specified!");
}

$menu_item_id = $_GET['menu_item_id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT image FROM item_images WHERE menu_item_id = ?");
    $stmt->execute([$menu_item_id]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($images as $img) {
        $filePath = __DIR__ . "/uploads/" . $img;
        if (file_exists($filePath)) {
            unlink($filePath); 
        }
    }

    $stmt = $conn->prepare("DELETE FROM item_images WHERE menu_item_id = ?");
    $stmt->execute([$menu_item_id]);

    $stmt2 = $conn->prepare("DELETE FROM menu_items WHERE menu_item_id = ? AND seller_id = ?");
    $stmt2->execute([$menu_item_id, $seller_id]);

    header("Location: my_item.php?deleted=1");
    exit();

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
