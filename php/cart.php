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

    $stmtUnread = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $stmtUnread->execute([$buyer_id]);
    $unreadCount = $stmtUnread->fetchColumn();

    $stmtNotif = $conn->prepare("
        SELECT message, created_at, is_read 
        FROM notifications 
        WHERE user_id=? 
        ORDER BY created_at DESC
    ");
    $stmtNotif->execute([$buyer_id]);
    $notifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_chat'])) {
        $seller_id = (int)$_POST['seller_id'];
        $message   = trim($_POST['message']);

        if ($seller_id && $message !== '') {
            $stmt = $conn->prepare("
                INSERT INTO messages (buyer_id, seller_id, message, sender, created_at, is_read)
                VALUES (?, ?, ?, 'buyer', NOW(), 0)
            ");
            $stmt->execute([$buyer_id, $seller_id, $message]);
        }

        header("Location: cart.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['send_chat'])) {

        if (isset($_POST['quantity'])) {
            foreach ($_POST['quantity'] as $cart_id => $qty) {
                $qty = max(1, (int)$qty);
                $stmt = $conn->prepare("
                    UPDATE cart 
                    SET quantity=? 
                    WHERE cart_id=? AND buyer_id=?
                ");
                $stmt->execute([$qty, $cart_id, $buyer_id]);
            }
        }

        if (isset($_POST['delete_cart_id'])) {
            $stmt = $conn->prepare("
                DELETE FROM cart 
                WHERE cart_id=? AND buyer_id=?
            ");
            $stmt->execute([$_POST['delete_cart_id'], $buyer_id]);
        }
    }

    $stmt = $conn->prepare("
        SELECT 
    c.cart_id,
    c.quantity AS cart_quantity,
    m.menu_item_id,
    m.name,
    m.description,
    m.price,
    m.quantity AS available_quantity,
    i.image,
    u.user_id AS seller_id,
    u.full_name AS seller_name
FROM cart c
JOIN menu_items m ON c.menu_item_id = m.menu_item_id
JOIN users u ON m.seller_id = u.user_id
LEFT JOIN (
    SELECT menu_item_id, MIN(image) AS image
    FROM item_images
    GROUP BY menu_item_id
) i ON i.menu_item_id = m.menu_item_id
WHERE c.buyer_id = ?
ORDER BY u.full_name
    ");
    $stmt->execute([$buyer_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cartBySeller = [];
    foreach ($items as $item) {
        $sid = $item['seller_id'];
        $cartBySeller[$sid]['seller_name'] = $item['seller_name'];
        $cartBySeller[$sid]['items'][] = $item;
    }

    $totalSelected = 0;
    if (isset($_POST['preview']) && isset($_POST['checkout_items'])) {
        foreach ($items as $item) {
            if (in_array($item['cart_id'], $_POST['checkout_items'])) {
                $qty = $_POST['quantity'][$item['cart_id']] ?? $item['cart_quantity'];
                $totalSelected += $item['price'] * $qty;
            }
        }
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

function getMessages($conn, $buyer_id, $seller_id) {
    $stmt = $conn->prepare("
        SELECT * FROM messages 
        WHERE buyer_id=? AND seller_id=? 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$buyer_id, $seller_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Cart | CampusTreats</title>
<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../bootstrap-icons/bootstrap-icons.css">
<style>
body { background-color: #fffaf2; font-family: 'Poppins', sans-serif; color: #3E2723; margin:0; padding-top:70px; }
.navbar { background: linear-gradient(90deg, #ff9800, #f57c00); box-shadow: 0 4px 10px rgba(0,0,0,0.1);}
.navbar-brand { font-weight:700; color:#fff !important;}
.nav-link { color:#fff !important; font-weight:500;}
.sidebar { position: fixed; top:60px; left:0; width:230px; height:100%; background-color:#f4cbaa; padding:25px 15px; box-shadow:2px 0 6px rgba(0,0,0,0.1);}
.sidebar h5 { font-weight:600; font-size:25px; margin-bottom:20px;}
.sidebar a { display:block; padding:10px 12px; border-radius:8px; color:#3E2723; margin-bottom:10px; text-decoration:none; transition: all 0.3s ease; font-weight:500; font-size:22px;}
.sidebar a:hover { background-color:#ff9800; color:#fff; transform:translateX(5px);}
.main-content { margin-left: 270px; padding: 25px; }
.card-item { display: flex; gap: 20px; align-items: center; padding: 20px; background: #fff; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);}
.card-item img { width: 120px; height: 120px; object-fit: cover; border-radius: 12px; border: 2px solid #ff9800; }
.card-details { flex-grow: 1; }
.quantity-controls { display: flex; align-items: center; gap: 6px; margin-top: 8px; }
.quantity-controls button { width: 32px; height: 32px; font-size: 18px; border: none; background-color: #ff9800; color: #fff; border-radius: 6px; }
.quantity-controls input { width: 50px; text-align: center; border-radius: 6px; border: 1px solid #ccc; padding: 3px; }
.cart-summary { background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 25px; margin-top: 25px; font-size: 1.1rem; }
.btn-preview, .btn-checkout { width: 100%; padding: 12px; font-weight: 600; border-radius: 10px; border: none; margin-top: 15px; font-size: 1rem; }
.btn-preview { background-color: #ff9800; color: #fff; }
.btn-checkout { background-color: #27ae60; color: #fff; }
.message.buyer { text-align: right; background: #ff9800; color: #fff; padding:5px; border-radius:10px; margin-bottom:5px; }
.message.seller { text-align: left; background: #eee; color: #333; padding:5px; border-radius:10px; margin-bottom:5px; }
.chat-box { 
    border:1px solid #ccc; 
    padding:10px; 
    border-radius:10px; 
    margin-bottom:20px; 
}
.chat-box h6 { font-weight:600; margin-bottom:5px; }
      :root{
    --primary1: #ff9800;
    --primary2: #f57c00;
    --bg: #fffaf2;
    --text: #3E2723;
  }
.sidebar a.active { background: var(--primary2); color:#fff; }

</style>
<script>
function changeQuantity(cartId, type) {
    let qtyInput = document.getElementById('qty-' + cartId);
    let current = parseInt(qtyInput.value);
    if(type==='minus' && current>1) qtyInput.value=current-1;
    if(type==='plus') qtyInput.value=current+1;
}

function toggleChat(sellerId) {
    const checkboxes = document.querySelectorAll('input[name="checkout_items[]"]');
    let show = false;
    checkboxes.forEach(cb => {
        if(cb.checked && cb.closest('.card-item').dataset.seller == sellerId) {
            show = true;
        }
    });
    const chatBox = document.getElementById('chat-' + sellerId);
    if(chatBox) chatBox.style.display = show ? 'block' : 'none';
}
</script>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="#"><i class="bi bi-basket2-fill me-2"></i>CampusTreats</a>
    <div class="d-flex align-items-center">
      <div class="dropdown ms-3">
        <button id="notifDropdown" class="btn position-relative" type="button" data-bs-toggle="dropdown">
          <i class="bi bi-bell fs-4"></i>
          <?php if ($unreadCount > 0): ?>
            <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              <?= $unreadCount ?>
            </span>
          <?php endif; ?>
        </button>

      <ul class="dropdown-menu dropdown-menu-end p-2" style="width:300px; max-height:400px; overflow-y:auto;">
        <?php if (empty($notifications)): ?>
          <li class="text-center text-muted">No notifications</li>
        <?php else: ?>
        <?php 
          $latestNotifications = array_slice($notifications, 0, 5); 
          foreach ($latestNotifications as $notif): 
        ?>
        <li class="dropdown-item <?= $notif['is_read'] ? '' : 'fw-bold' ?>">
            <?= htmlspecialchars($notif['message']) ?><br>
            <small class="text-muted"><?= date('M d, Y H:i', strtotime($notif['created_at'])) ?></small>
        </li>
        <?php endforeach; ?>
          <li><hr class="dropdown-divider"></li>
          <li class="text-center">
            <a href="buyer_notifications.php" class="text-decoration-none fw-bold">View All Notifications</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
      <a href="logout.php" class="nav-link ms-3"><i class="bi bi-box-arrow-right fs-4"></i></a>
  </div>
</nav>

<div class="sidebar">
  <h5><i class="bi bi-speedometer2 me-1"></i> Dashboard</h5>
  <a href="buyer_dashboard.php"><i class="bi bi-house-door me-2"></i> Home</a>
  <a href="cart.php" class="active"><i class="bi bi-cart me-2"></i> My Cart</a>
  <a href="buyers_order.php" ><i class="bi bi-bag-check me-2"></i> My Orders</a>
  <a href="buyer_messages.php"><i class="bi bi-person-circle me-2"></i> messages</a>
  <a href="buyer_notifications.php"><i class="bi bi-bell me-2"></i> Notification</a>
  <a href="profile.php"><i class="bi bi-person-circle me-2"></i> Profile</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right fs-4"></i> Logout</a>
</div>

<div class="main-content container">
<h2>🛒 My Cart</h2>

<?php if (!empty($cartBySeller)): ?>

<form method="POST" id="cartForm">

<?php foreach ($cartBySeller as $seller_id => $sellerData): ?>
    <?php foreach ($sellerData['items'] as $item): ?>

    <div class="card-item" data-seller="<?= $seller_id ?>">

        <input type="checkbox"
               name="checkout_items[]"
               value="<?= $item['cart_id'] ?>"
               data-seller="<?= $seller_id ?>"
               onchange="toggleChat(<?= $seller_id ?>)"
               <?php if (!empty($_POST['checkout_items']) && in_array($item['cart_id'], $_POST['checkout_items'])) echo 'checked'; ?>>

        <img src="<?= $item['image'] ? '../uploads/'.$item['image'] : '../placeholder.png' ?>" alt="Item">

        <div class="card-details">
            <h4><?= htmlspecialchars($item['name']) ?></h4>
            <p>Seller: <?= htmlspecialchars($sellerData['seller_name']) ?></p>
            <p><?= htmlspecialchars($item['description']) ?></p>

            <small>Available: <?= (int)$item['available_quantity'] ?></small><br>
            <strong>₱<?= number_format($item['price'], 2) ?></strong>

            <div class="quantity-controls">
                <button type="button" onclick="changeQuantity(<?= $item['cart_id'] ?>,'minus')">-</button>

                <input type="number"
                       id="qty-<?= $item['cart_id'] ?>"
                       name="quantity[<?= $item['cart_id'] ?>]"
                       value="<?= $item['cart_quantity'] ?>"
                       min="1"
                       max="<?= $item['available_quantity'] ?>">

                <button type="button" onclick="changeQuantity(<?= $item['cart_id'] ?>,'plus')">+</button>

                <button type="submit"
                        name="delete_cart_id"
                        value="<?= $item['cart_id'] ?>"
                        class="btn btn-danger ms-2">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>

    <?php endforeach; ?>
<?php endforeach; ?>

<?php if (isset($_POST['preview'])): ?>
<div class="cart-summary">
    <h5>Total Selected</h5>
    <p>₱<?= number_format($totalSelected, 2) ?></p>
</div>
<?php endif; ?>

<button type="submit" name="preview" class="btn btn-preview">Preview Total</button>
<button type="submit" name="checkout" formaction="check_out.php" class="btn btn-checkout">
    Confirm Checkout
</button>

</form>

<?php foreach ($cartBySeller as $seller_id => $sellerData): ?>
<div class="chat-box" id="chat-<?= $seller_id ?>" style="display:none;">
    <h6>Chat with <?= htmlspecialchars($sellerData['seller_name']) ?></h6>

    <div class="messages" style="height:200px; overflow-y:auto; background:#f9f9f9; padding:8px;">
        <?php foreach (getMessages($conn, $buyer_id, $seller_id) as $msg): ?>
            <div class="message <?= $msg['sender'] === 'buyer' ? 'buyer' : 'seller' ?>">
                <?= htmlspecialchars($msg['message']) ?><br>
                <small><?= date('M d, H:i', strtotime($msg['created_at'])) ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="POST" action="cart.php" class="mt-2">
        <input type="hidden" name="seller_id" value="<?= $seller_id ?>">
        <div class="input-group">
            <input type="text" name="message" class="form-control" required placeholder="Type a message...">
            <button class="btn btn-primary" name="send_chat">Send</button>
        </div>
    </form>
</div>
<?php endforeach; ?>

<?php else: ?>
<p>Your cart is empty.</p>
<?php endif; ?>
</div>
