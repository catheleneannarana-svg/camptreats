<?php
session_start();

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats","root","");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection Failed: " . $e->getMessage());
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $fname = $_POST['fname'];
    $num = $_POST['num'];
    $role = 'buyer'; 

    $check = $conn->prepare("SELECT * FROM users WHERE email=? OR username=?");
    $check->execute([$email, $username]);
    if($check->rowCount() > 0){
        die("Email or Username already exists!");
    }

    $sql = $conn->prepare("INSERT INTO users (email, username, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    if($sql->execute([$email, $username, $password, $fname, $num, $role])){
        $user_id = $conn->lastInsertId(); 
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

        header("Location: buyer_dashboard.php");
        exit();
    } else {
        echo "Registration error!";
    }
}
?>
