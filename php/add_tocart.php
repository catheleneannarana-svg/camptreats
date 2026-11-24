<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../login.html");
    exit();
}

$buyer_id = $_SESSION['user_id'];

$menu_item_id = intval($_POST['menu_item_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

if ($menu_item_id <= 0 || $quantity < 1) {
    die("Invalid request.");
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT quantity, status FROM menu_items WHERE menu_item_id = ?");
    $stmt->execute([$menu_item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Item not found.");
    }

    if ($item['status'] !== 'approved' || $item['quantity'] < 1) {
        die("out_of_stock");
    }

    $stmt = $conn->prepare("SELECT quantity FROM cart WHERE buyer_id = ? AND menu_item_id = ?");
    $stmt->execute([$buyer_id, $menu_item_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $newQty = $existing['quantity'] + $quantity;

        if ($newQty > $item['quantity']) {
            die("exceeds_stock");
        }

        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE buyer_id = ? AND menu_item_id = ?");
        $stmt->execute([$newQty, $buyer_id, $menu_item_id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (buyer_id, menu_item_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$buyer_id, $menu_item_id, $quantity]);
    }

    header("Location: buyer_dashboard.php?cart=success");
    exit();

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
