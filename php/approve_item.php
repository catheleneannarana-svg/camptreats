<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

if (!isset($_GET['id'])) {
    die("Item ID required.");
}

$itemId = intval($_GET['id']);

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("UPDATE menu_items SET status = 'approved' WHERE menu_item_id = ?");
    $stmt->execute([$itemId]);

    header("Location: manage_items.php");
    exit();

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
