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

    
  $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
  $stmt->execute([$buyer_id]);
  $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmtUnread = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
  $stmtUnread->execute([$buyer_id]);
  $unreadCount = $stmtUnread->fetchColumn();

    $stmt = $conn->prepare("
        SELECT o.order_id, o.status, o.created_at,
            oi.menu_item_id, oi.quantity, oi.price, m.name AS item_name, m.description,
            (SELECT image FROM item_images i WHERE i.menu_item_id = m.menu_item_id LIMIT 1) AS image
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN menu_items m ON oi.menu_item_id = m.menu_item_id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$buyer_id]);
    $ordersRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];
    foreach ($ordersRaw as $row) {
        $orderId = $row['order_id'];
        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'order_id' => $orderId,
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'items' => []
            ];
        }
        $orders[$orderId]['items'][] = [
            'menu_item_id' => $row['menu_item_id'],
            'name' => $row['item_name'],
            'description' => $row['description'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'image' => $row['image']
        ];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
        $notif_id = intval($_POST['notification_id']);
        $stmtMark = $conn->prepare("UPDATE notifications SET is_read=1 WHERE notification_id=? AND user_id=?");
        $stmtMark->execute([$notif_id, $buyer_id]);
    }

    $stmt = $conn->prepare("
        SELECT notification_id, message, created_at, is_read
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$buyer_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unreadCount = 0;
    foreach ($notifications as $n) {
        if ($n['is_read'] == 0) $unreadCount++;
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders | CampusTreats</title>
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
.main-content {
    margin-left:24%;
    padding: 20px;
}

.order-card {
    background: linear-gradient(145deg, #fff, #fff5e1);
    border-radius: 16px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 25px;
    width: 85%;
    transition: all 0.3s ease;
}
.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    margin-bottom: 12px;
    font-size: 20px;

}
.order-header small {
    font-size: 20px;
    color: #5D4037;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #fff;
}
.status-pending { background-color: #ffb74d; }
.status-completed { background-color: #66bb6a; }
.status-cancelled { background-color: #ef5350; }

.item-card {
    background: #fff;
    border-radius: 10px;
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    transition: all 0.3s ease;
}
.item-card:hover {
    background-color: #fff3e0;
}
.item-info {
    flex: 1;
    margin-left: 15px;
}
.item-info h6 {
    margin: 0;
    font-weight: 600;
    font-size: 20px;
}
.item-info p {
    margin: 4px 0;
    font-size: 16px;
}
.item-img {
    width: 110px;
    height: 110px;
    border-radius: 10px;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.item-img:hover {
    transform: scale(1.05);
}

.total-row {
    text-align: right;
    font-weight: bold;
    font-size: 18px;
    margin-top: 12px;
    color: #E64A19;
}

.order-meta {
    font-size: 0.9rem;
    color: #6d4c41;
    margin-bottom: 10px;
}

      :root{
    --primary1: #ff9800;
    --primary2: #f57c00;
    --bg: #fffaf2;
    --text: #3E2723;
  }
.sidebar a.active { background: var(--primary2); color:#fff; }

</style>
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
  <a href="cart.php"><i class="bi bi-cart me-2"></i> My Cart</a>
  <a href="buyers_order.php" class="active"><i class="bi bi-bag-check me-2"></i> My Orders</a>
  <a href="buyer_messages.php"><i class="bi bi-person-circle me-2"></i> messages</a>
  <a href="buyer_notifications.php"><i class="bi bi-bell me-2"></i> Notification</a>
  <a href="profile.php"><i class="bi bi-person-circle me-2"></i> Profile</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right fs-4"></i> Logout</a>
</div>

<div class="main-content">
    <h2 class="mb-4">📦 My Orders</h2>

    <?php if (empty($orders)): ?>
        <div class="alert alert-warning">You haven’t placed any orders yet.</div>
    <?php else: ?>
        <?php foreach ($orders as $order): 
            $orderTotal = 0; ?>
            <div class="order-card">
                <div class="order-header">
                    <span>Order #<?= $order['order_id'] ?></span>
                    <?php 
                        $statusClass = strtolower($order['status']);
                        if ($statusClass == "pending") $badge = "status-pending";
                        elseif ($statusClass == "completed") $badge = "status-completed";
                        elseif ($statusClass == "cancelled") $badge = "status-cancelled";
                        else $badge = "status-pending";
                    ?>
                    <span class="status-badge <?= $badge ?>"><?= ucfirst($order['status']) ?></span>
                </div>
                <div class="order-meta">Placed on: <?= htmlspecialchars($order['created_at']) ?></div>

                <?php foreach ($order['items'] as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $orderTotal += $subtotal;
                ?>
                <div class="item-card">
                    <div class="item-info">
                        <h6><?= htmlspecialchars($item['name']) ?></h6>
                        <p><?= htmlspecialchars($item['description']) ?></p>
                        <p>Qty: <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></p>
                        <p><strong>Subtotal:</strong> ₱<?= number_format($subtotal, 2) ?></p>
                    </div>
                    <?php if ($item['image']): ?>
                        <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" class="item-img" alt="<?= htmlspecialchars($item['name']) ?>">
                    <?php else: ?>
                        <img src="../placeholder.png" class="item-img" alt="No Image">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <div class="total-row">Total: ₱<?= number_format($orderTotal, 2) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
