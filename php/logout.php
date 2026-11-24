<?php
session_start();

session_unset();

session_destroy();

$roles = ['ADMIN_SESSION', 'SELLER_SESSION', 'BUYER_SESSION'];
foreach ($roles as $cookie) {
    setcookie($cookie, '', time() - 3600, '/');
}

header("Location: ../login.html");
exit();
?>
