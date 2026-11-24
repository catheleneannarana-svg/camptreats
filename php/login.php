<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_start();

    try {
        $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("DB Connection Failed: " . $e->getMessage());
    }

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<script>alert('Invalid username'); window.location.href = '../login.html';</script>";
        exit();
    }

    if ($user['is_active'] != 1) {
        echo "<script>alert('Account is not active. Contact admin.'); window.location.href = '../login.html';</script>";
        exit();
    }

    // if ($user['approval_status'] != 'approved') {
    //     echo "<script>alert('Account not approved yet.'); window.location.href = '../login.html';</script>";
    //     exit();
    // }

    if (!password_verify($password, $user['password'])) {
        echo "<script>alert('Incorrect password'); window.location.href = '../login.html';</script>";
        exit();
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    switch ($user['role']) {
        case 'admin':
            header("Location: admin_dashboard.php");
            break;
        case 'seller':
            header("Location: seller_dashboard.php");
            break;
        case 'buyer':
        default:
            header("Location: buyer_dashboard.php");
    }
    exit();
} else {
    header("Location: ../login.html");
    exit();
}
?>
