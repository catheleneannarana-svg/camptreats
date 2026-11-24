<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../login.html");
    exit();
}

if (!isset($_POST['cart_id'])) {
    die("Invalid request.");
}

$buyer_id = $_SESSION['user_id'];
$cart_id = $_POST['cart_id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $check = $conn->prepare("SELECT * FROM cart WHERE cart_id = ? AND buyer_id = ?");
    $check->execute([$cart_id, $buyer_id]);

    if ($check->rowCount() === 0) {
        die("Unauthorized or item not found.");
    }

    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND buyer_id = ?");
    $stmt->execute([$cart_id, $buyer_id]);

    header("Location: cart.php?removed=1");
    exit();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
