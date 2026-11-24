<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

$seller_id = $_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['status'];

        $stmtUpdate = $conn->prepare("
            UPDATE orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN menu_items m ON oi.menu_item_id = m.menu_item_id
            SET o.status = ?
            WHERE o.order_id = ? AND m.seller_id = ?
        ");
        $stmtUpdate->execute([$newStatus, $orderId, $seller_id]);

        header("Location: my_orders.php");
        exit();
    }


$stmt = $conn->prepare("
    SELECT o.order_id, o.user_id AS buyer_id, u.full_name AS buyer_name, o.total_amount, o.status AS order_status, o.created_at AS order_date,
           oi.menu_item_id, oi.quantity, oi.price AS item_price, m.name
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN menu_items m ON oi.menu_item_id = m.menu_item_id
    WHERE m.seller_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$seller_id]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $orders = [];
foreach ($orderItems as $item) {
    $orderId = $item['order_id'];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'order_id' => $item['order_id'],
            'buyer_id' => $item['buyer_id'],
            'buyer_name' => $item['buyer_name'],
            'total_amount' => $item['total_amount'],
            'order_status' => $item['order_status'],
            'order_date' => $item['order_date'],
            'items' => []
        ];
    }
    $orders[$orderId]['items'][] = [
        'name' => $item['name'],
        'quantity' => $item['quantity'],
        'price' => $item['item_price']
    ];
}
    

    
    $stmtNotify = $conn->prepare("SELECT COUNT(*) FROM seller_notifications WHERE user_id = ? AND is_read = 0");
    $stmtNotify->execute([$seller_id]);
    $unreadCount = (int)$stmtNotify->fetchColumn();

    $stmtListNotify = $conn->prepare("SELECT notification_id AS id, message, is_read, created_at FROM seller_notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmtListNotify->execute([$seller_id]);
    $notifications = $stmtListNotify->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders | Seller Dashboard</title>
<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../bootstrap-icons/bootstrap-icons.css">
<style>
:root {
    --primary1: #ff9800;
    --primary2: #f57c00;
    --bg: #fffaf2;
    --text: #3E2723;
}
body {
    margin:0;
    font-family:'Poppins', sans-serif;
    background: var(--bg);
    color: var(--text);
    padding-top:70px;
}

/* Navbar */
.navbar {
    background: linear-gradient(90deg, var(--primary1), var(--primary2));
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    position: fixed;
    top:0;
    left:0;
    right:0;
    z-index:1030;
}
.navbar .navbar-brand { color:#fff; font-weight:700; font-size:1.2rem; }

.sidebar {
    position: fixed;
    top: 65px; 
    left: 0;
    width: 230px;
    height: 100vh;
    background-color: #f4cbaa;
    padding: 25px 15px;
    box-shadow: 2px 0 6px rgba(0,0,0,0.08);
    overflow-y: auto;
}
.sidebar h5 { font-weight:600; font-size:22px; margin-bottom:18px; color:var(--text); }
.sidebar a {
    display:block;
    padding: 10px 12px;
    border-radius: 8px;
    color: var(--text);
    margin-bottom: 8px;
    text-decoration:none;
    transition: all .2s ease;
    font-size: 20px;
}
.sidebar a i { margin-right: 10px; }
.sidebar a:hover { background: var(--primary1); color: #fff; transform: translateX(6px); }
.sidebar a.active { background: var(--primary2); color:#fff; }

.main { margin-left:250px; padding:28px; min-height: calc(100vh - 70px - 60px); }

.card {
    background:#fff;
    border-radius:14px;
    box-shadow:0 8px 20px rgba(0,0,0,0.12);
    margin-bottom:25px;
    padding:20px;
    transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow:0 16px 40px rgba(0,0,0,0.18);
}
.card h5 {
    font-weight:700;
    border-left:6px solid var(--primary1);
    padding-left:12px;
    margin-bottom:12px;
    font-size:1.2rem;
}
.card p { margin-bottom:8px; font-size:0.95rem; }

.item-row {
    display:flex;
    justify-content:space-between;
    padding:6px 0;
    border-bottom:1px solid #eee;
}
.item-row:last-child { border-bottom:none; }

.badge-status {
    padding:5px 10px;
    font-size:0.85rem;
    font-weight:600;
    border-radius:6px;
    text-transform:capitalize;
}
.badge-pending { background:#ffc107; color:#212529; }
.badge-preparing { background:#fd7e14; color:#fff; }
.badge-completed { background:#28a745; color:#fff; }
.badge-cancelled { background:#dc3545; color:#fff; }

.order-actions button {
    margin-right:6px;
    border-radius:6px;
    padding:6px 12px;
    font-size:0.85rem;
    font-weight:600;
    transition: all 0.2s;
}
.order-actions button:hover { transform: translateY(-2px); }

 .dropdown-menu-notif { width: 340px; max-height: 400px; overflow-y: auto; }
  .dropdown-item-notif { white-space:normal; }

@media(max-width:991px){ .sidebar{display:none;} .main{margin-left:0; width:100%; padding:16px;} }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#"><i class="bi bi-basket2-fill me-2"></i>CampusTreats</a>
    <div class="d-flex align-items-center ms-auto">
      <div class="dropdown ms-3">
        <button id="sellerNotifBtn" class="btn position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-bell fs-4" style="color:#fff;"></i>
          <?php if ($unreadCount > 0): ?>
            <span id="sellerNotifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              <?= $unreadCount ?>
            </span>
          <?php endif; ?>
        </button>

        <ul class="dropdown-menu dropdown-menu-end p-2 dropdown-menu-notif" aria-labelledby="sellerNotifBtn">
            <?php if (empty($notifications)): ?>
                <li class="text-center text-muted">No notifications</li>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <li class="dropdown-item dropdown-item-notif <?= $notif['is_read'] ? '' : 'fw-bold' ?>">
                        <?= htmlspecialchars($notif['message']) ?><br>
                        <small class="text-muted"><?= date('M d, Y H:i', strtotime($notif['created_at'])) ?></small>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
      </div>
      <a href="logout.php" class="nav-link ms-3" style="color:#fff;"><i class="bi bi-box-arrow-right fs-4"></i></a>
    </div>
  </div>
</nav>

<div class="sidebar">
  <h5><i class="bi bi-speedometer2 me-1"></i> Seller</h5>
  <a href="seller_dashboard.php"><i class="bi bi-house-door me-1"></i> Dashboard</a>
  <a href="my_item.php" ><i class="bi bi-card-list me-1"></i> My Items</a>
  <a href="add_item.php" ><i class="bi bi-plus-square me-1"></i> Add Item</a>
  <a href="my_orders.php"class="active"><i class="bi bi-bag-check me-1"></i> Orders</a>
  <a href="messages.php"><i class="bi bi-chat-left-dots me-1"></i> Messages</a>
  <a href="seller_notifications.php"><i class="bi bi-bell me-1"></i> Notifications
    <?php if($unreadCount > 0): ?><span class="badge bg-danger ms-2"><?= $unreadCount ?></span><?php endif; ?>
  </a>
  <a href="profile.php"><i class="bi bi-person-circle me-1"></i> Profile</a>
  <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
</div>

<div class="main">
    <h2 class="mb-4">My Orders</h2>

<?php if (empty($orders)): ?>
    <div class="alert alert-info">You have no orders yet.</div>
<?php else: ?>
    <div class="accordion" id="ordersAccordion">
        <?php foreach ($orders as $index => $order): ?>
        <div class="accordion-item mb-3 shadow-sm rounded">
            <h2 class="accordion-header" id="heading<?= $index ?>">
                <button class="accordion-button d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="true" aria-controls="collapse<?= $index ?>">
                    <span>
                        <strong>Order #<?= $order['order_id'] ?></strong> 
                        | <small>Buyer: <?= htmlspecialchars($order['buyer_name']) ?></small>

                    </span>
                    <span class="badge-status 
                        <?= $order['order_status'] === 'pending' ? 'badge-pending' : '' ?>
                        <?= $order['order_status'] === 'preparing' ? 'badge-preparing' : '' ?>
                        <?= $order['order_status'] === 'completed' ? 'badge-completed' : '' ?>
                        <?= $order['order_status'] === 'cancelled' ? 'badge-cancelled' : '' ?>
                    ">
                        <?= ucfirst($order['order_status']) ?>
                    </span>
                </button>
            </h2>
            <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $index ?>" data-bs-parent="#ordersAccordion">
                <div class="accordion-body">
                    <p><strong>Date:</strong> <?= $order['order_date'] ?></p>
                    <div class="list-group mb-2">
                        <?php foreach ($order['items'] as $item): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-basket3-fill me-2"></i><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</span>
                            <span>₱<?= number_format($item['price'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="fw-bold">Total: ₱<?= number_format($order['total_amount'], 2) ?></p>

                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                        <?php if ($order['order_status'] === 'pending'): ?>
                            <button type="submit" name="status" value="preparing" class="btn btn-warning btn-sm"><i class="bi bi-hourglass-split me-1"></i> Preparing</button>
                            <button type="submit" name="status" value="cancelled" class="btn btn-danger btn-sm"><i class="bi bi-x-circle me-1"></i> Cancel</button>
                        <?php elseif ($order['order_status'] === 'preparing'): ?>
                            <button type="submit" name="status" value="completed" class="btn btn-success btn-sm"><i class="bi bi-check-circle me-1"></i> Complete</button>
                            <button type="button" class="btn btn-warning btn-sm" disabled><i class="bi bi-hourglass-split me-1"></i> Preparing...</button>
                        <?php elseif ($order['order_status'] === 'completed'): ?>
                            <button type="button" class="btn btn-success btn-sm" disabled><i class="bi bi-check-circle me-1"></i> Completed</button>
                        <?php elseif ($order['order_status'] === 'cancelled'): ?>
                            <button type="button" class="btn btn-danger btn-sm" disabled><i class="bi bi-x-circle me-1"></i> Cancelled</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
