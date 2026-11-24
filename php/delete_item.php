<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seller') {
    header("Location: ../login.html");
    exit();
}

if (!isset($_GET['menu_item_id']) || !is_numeric($_GET['menu_item_id'])) {
    header("Location: my_item.php?error=invalid");
    exit();
}

$seller_id = $_SESSION['user_id'];
$menu_item_id = (int)$_GET['menu_item_id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("
        SELECT menu_item_id 
        FROM menu_items 
        WHERE menu_item_id = ? AND seller_id = ?
    ");
    $stmt->execute([$menu_item_id, $seller_id]);

    if (!$stmt->fetch()) {
        header("Location: my_item.php?error=unauthorized");
        exit();
    }

    $stmt = $conn->prepare("
        SELECT image 
        FROM item_images 
        WHERE menu_item_id = ?
    ");
    $stmt->execute([$menu_item_id]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $uploadDir = realpath(__DIR__ . '/../uploads/') . '/';
    foreach ($images as $img) {
        $filePath = $uploadDir . $img;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $stmt = $conn->prepare("DELETE FROM item_images WHERE menu_item_id = ?");
    $stmt->execute([$menu_item_id]);

    $stmt = $conn->prepare("
        DELETE FROM menu_items 
        WHERE menu_item_id = ? AND seller_id = ?
    ");
    $stmt->execute([$menu_item_id, $seller_id]);

    header("Location: my_item.php?deleted=1");
    exit();

} catch (PDOException $e) {
    die("Delete failed: " . $e->getMessage());
}
