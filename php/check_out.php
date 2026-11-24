<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../login.html");
    exit();
}

$buyer_id = $_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_items'])) {

        $checkoutItems = $_POST['checkout_items'];
        $quantities = $_POST['quantity'] ?? [];

        $itemsToCheckout = [];
        $totalAmount = 0;

        $conn->beginTransaction();

        foreach ($checkoutItems as $cart_id) {

            $stmtCart = $conn->prepare("
                SELECT c.menu_item_id, c.quantity AS cart_qty, m.name, m.price, m.quantity AS stock_qty
                FROM cart c
                JOIN menu_items m ON c.menu_item_id = m.menu_item_id
                WHERE c.cart_id = ? AND c.buyer_id = ?
                FOR UPDATE
            ");
            $stmtCart->execute([$cart_id, $buyer_id]);
            $cartItem = $stmtCart->fetch(PDO::FETCH_ASSOC);

            if (!$cartItem) continue;

            $checkoutQty = intval($quantities[$cart_id] ?? $cartItem['cart_qty']);

            if ($cartItem['stock_qty'] < $checkoutQty) {
                $conn->rollBack();
                die("Not enough stock for item: " . htmlspecialchars($cartItem['name']));
            }

            $itemsToCheckout[] = [
                'menu_item_id' => $cartItem['menu_item_id'],
                'cart_id' => $cart_id,
                'qty' => $checkoutQty,
                'price' => $cartItem['price']
            ];

            $totalAmount += $checkoutQty * $cartItem['price'];
        }

        if (empty($itemsToCheckout)) {
            $conn->rollBack();
            die("No items selected for checkout.");
        }

        foreach ($itemsToCheckout as $item) {
            $stmtUpdate = $conn->prepare("UPDATE menu_items SET quantity = quantity - ? WHERE menu_item_id = ?");
            $stmtUpdate->execute([$item['qty'], $item['menu_item_id']]);

            $stmtStatus = $conn->prepare("
                UPDATE menu_items 
                SET status = CASE WHEN quantity <= 0 THEN 'unavailable' ELSE 'approved' END
                WHERE menu_item_id = ?
            ");
            $stmtStatus->execute([$item['menu_item_id']]);

            $stmtDelete = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND buyer_id = ?");
            $stmtDelete->execute([$item['cart_id'], $buyer_id]);
        }

        $stmtOrder = $conn->prepare("INSERT INTO orders (user_id, total_amount, created_at) VALUES (?, ?, NOW())");
        $stmtOrder->execute([$buyer_id, $totalAmount]);
        $orderId = $conn->lastInsertId();

        foreach ($itemsToCheckout as $item) {
            $stmtOrderItems = $conn->prepare("
                INSERT INTO order_items (order_id, menu_item_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmtOrderItems->execute([$orderId, $item['menu_item_id'], $item['qty'], $item['price']]);
        }

        $conn->commit();
        echo "<script>alert('Checkout successful!'); window.location='buyer_dashboard.php';</script>";

    } else {
        die("No items selected for checkout.");
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    die("DB Error: " . $e->getMessage());
}
?>
