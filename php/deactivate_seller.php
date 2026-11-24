<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

if (!isset($_GET['id'])) {
    die("wala ngani User ID .");
}

$user_id = (int) $_GET['id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("UPDATE users 
                            SET is_active = 0 
                            WHERE user_id = ? AND is_seller = 1");
    $stmt->execute([$user_id]);

    header("Location: manage_sellers.php");
    exit();

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
