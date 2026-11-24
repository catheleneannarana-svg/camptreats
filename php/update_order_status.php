<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$order_id || !$action) {
        header("Location: my_orders.php?msg=Invalid request");
        exit();
    }

    try {
        $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$action, $order_id]);

        header("Location: my_orders.php?msg=Order status updated to $action");
        exit();

    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }
}
?>
