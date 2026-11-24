<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seller') {
    header("Location: ../login.html");
    exit();
}
$seller_id = $_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtTotalOrders = $conn->prepare("
        SELECT COUNT(DISTINCT o.order_id) AS total_orders
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN menu_items m ON oi.menu_item_id = m.menu_item_id
        WHERE m.seller_id = ?
    ");
    $stmtTotalOrders->execute([$seller_id]);
    $totalOrders = (int)$stmtTotalOrders->fetchColumn();

    $stmtTotalItems = $conn->prepare("
    SELECT COUNT(*) 
    FROM menu_items
    WHERE seller_id = ?
");

$stmtTotalItems->execute([$seller_id]);
$totalPostedItems = (int) $stmtTotalItems->fetchColumn();

    $stmt = $conn->prepare("
        SELECT o.order_id, o.user_id, o.total_amount, o.status, o.created_at,
               oi.menu_item_id, oi.quantity, oi.price AS item_price,
               m.name
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN menu_items m ON oi.menu_item_id = m.menu_item_id
        WHERE m.seller_id = ?
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$seller_id]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];
    foreach ($orderItems as $item) {
        $oid = $item['order_id'];
        if (!isset($orders[$oid])) {
            $orders[$oid]['order_info'] = [
                'order_id' => $item['order_id'],
                'user_id' => $item['user_id'],
                'total_amount' => $item['total_amount'],
                'status' => $item['status'],
                'created_at' => $item['created_at']
            ];
            $orders[$oid]['items'] = [];
        }
        $orders[$oid]['items'][] = [
            'item_name' => $item['name'],
            'quantity' => $item['quantity'],
            'price' => $item['item_price']
        ];
    }

    $stmtNotify = $conn->prepare("
        SELECT COUNT(*) FROM seller_notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmtNotify->execute([$seller_id]);
    $unreadCount = (int)$stmtNotify->fetchColumn();

    $stmtListNotify = $conn->prepare("
        SELECT notification_id AS id, message, is_read, created_at
        FROM seller_notifications
        WHERE user_id = ?
        ORDER BY created_at DESC

    ");
    $stmtListNotify->execute([$seller_id]);
    $notifications = $stmtListNotify->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>CampusTreats | Seller Dashboard</title>
<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../bootstrap-icons/bootstrap-icons.css">
<style>
  :root{
    --primary1: #ff9800;
    --primary2: #f57c00;
    --bg: #fffaf2;
    --text: #3E2723;
  }
  body {
    margin: 0;
    padding-top: 70px;
    font-family: 'Poppins', sans-serif;
    background-color: var(--bg);
    color: var(--text);
  }

  .navbar {
    background: linear-gradient(90deg, var(--primary1), var(--primary2));
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
  }
  .navbar .container-fluid { padding-left: 28px; padding-right: 28px; }
  .navbar-brand { color: #fff; font-weight:700; font-size: 1.15rem; }
  .nav-link { color: #fff !important; }

  .sidebar {
    position: fixed;
    top: 65px; 
    left: 0;
    width: 230px;
    height: calc(100% - 70px);
    background-color: #f4cbaa;
    padding: 25px 15px;
    box-shadow: 2px 0 6px rgba(0,0,0,0.08);
    overflow-y: auto;
  }
  .sidebar h5 {
    font-weight: 600;
    font-size: 22px;
    margin-bottom: 18px;
    color: var(--text);
  }
  .sidebar a {
    display:block;
    padding: 10px 12px;
    border-radius: 8px;
    color: var(--text);
    margin-bottom: 8px;
    text-decoration:none;
    transition: all .2s ease;
    /* font-weight: 600; */
    font-size: 20px;
  }
  .sidebar a i { margin-right: 10px; }
  .sidebar a:hover { background: var(--primary1); color: #fff; transform: translateX(6px); }

  .sidebar a.active { background: var(--primary2); color:#fff; }

  .main {
    margin-left: 250px;
    padding: 28px;
  }

  .hero {
    border-radius: 12px;
    overflow: hidden;
    margin-top: 6px;
  }
  .hero img { width:100%; height:320px; object-fit:cover; display:block; border-radius:12px; }

  .section-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin-top: 30px;
    color: var(--text);
    border-left: 6px solid var(--primary1);
    padding-left: 12px;
  }
  .card {
    border: none;
    border-radius: 14px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    overflow: hidden;
  }
  .card img { height: 160px; object-fit:cover; }
  .card-body { background: #fff; padding: 14px; }
  .card-title { font-size: 1.05rem; font-weight: 600; color: var(--text); }
  .price { color: #e65100; font-weight: 700; }

  .btn-buy {
    background-color: var(--primary1);
    color: #fff;
    border: none;
    padding: 6px 10px;
    border-radius: 8px;
  }
  .btn-buy:hover { background-color: var(--primary2); }

  .badge-new {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: var(--primary1);
    color: #fff;
    font-size: 0.75rem;
    border-radius: 6px;
    padding: 4px 8px;
  }

  footer {
    background-color: var(--primary1);
    color: #fff;
    text-align:center;
    padding: 12px 0;
    margin-top: 40px;
    border-radius: 8px;
  }

  .stats-row { display:flex; gap:18px; flex-wrap:wrap; margin-top: 18px; }
  .stat { flex:1; min-width:200px; background:#fff; border-radius:12px; padding:18px; box-shadow:0 6px 14px rgba(0,0,0,0.06); }
  .stat h3 { margin:0; font-size:2rem; color:var(--text); }
  .stat p { margin:0; color:#6b4b3d; }

  .status-badge { padding:6px 12px; border-radius:8px; color:#fff; font-weight:700; text-transform:capitalize; }
  .status-pending { background:#ffc107; }
  .status-completed { background:#28a745; }
  .status-cancelled { background:#dc3545; }

  .dropdown-menu-notif { width: 340px; max-height: 400px; overflow-y: auto; }
  .dropdown-item-notif { white-space:normal; }

  @media (max-width: 991px) {
    .sidebar { display:none; }
    .main { margin-left: 0; padding: 16px; }
    body { padding-top: 100px; }
  }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <i class="bi bi-basket2-fill me-2"></i>CampusTreats
    </a>

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
                <?= htmlspecialchars($notif['message']) ?>
                <br>
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
  <a href="seller_dashboard.php" class="active"><i class="bi bi-house-door me-1"></i> Dashboard</a>
  <a href="my_item.php"><i class="bi bi-card-list me-1"></i> My Items</a>
  <a href="add_item.php"><i class="bi bi-plus-square me-1"></i> Add Item</a>
  <a href="my_orders.php"><i class="bi bi-bag-check me-1"></i> Orders</a>
  <a href="messages.php"><i class="bi bi-chat-left-dots me-1"></i> Messages</a>
  <a href="seller_notifications.php"><i class="bi bi-bell me-1"></i> Notifications
    <?php if($unreadCount > 0): ?><span class="badge bg-danger ms-2"><?= $unreadCount ?></span><?php endif; ?>
  </a>
  <a href="profile.php"><i class="bi bi-person-circle me-1"></i> Profile</a>
  <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
</div>

<div class="main container-fluid" style="width:80%;">

  <div class="row">
    <div class="col-12">
      <div id="sellerHero" class="hero">
        <img src="../uploads/seller_banner.jpg" alt="Banner" onerror="this.src='../uploads/burgers_banner.jpeg'">
      </div>
    </div>
  </div>

  <div class="stats-row mt-4">
    <div class="stat">
      <p class="mb-1">Total Orders</p>
      <h3><?= number_format($totalOrders) ?></h3>
    </div>
    <div class="stat">
      <p class="mb-1">Items</p>
      <h3><?= number_format($totalPostedItems) ?></h3>
    </div>
    <div class="stat">
      <p class="mb-1">Unread Notifications</p>
      <h3><?= number_format($unreadCount) ?></h3>
    </div>
  </div>

  <h3 class="section-title">Recent Orders</h3>

  <?php if (empty($orders)): ?>
    <div class="card mt-3 p-3">
      <div class="card-body">
        <p class="mb-0">No recent orders found.</p>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($orders as $ord): 
      $info = $ord['order_info'];
      $status = strtolower($info['status']);
      $statusClass = 'status-' . $status;
      ?>
      <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div><strong>Order #<?= htmlspecialchars($info['order_id']) ?></strong> — Buyer #<?= htmlspecialchars($info['user_id']) ?></div>
          <div class="status-badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst($info['status'])) ?></div>
        </div>
        <div class="card-body">
          <p class="mb-1"><strong>Total:</strong> ₱<?= number_format($info['total_amount'],2) ?></p>
          <p class="mb-2"><strong>Ordered at:</strong> <?= htmlspecialchars($info['created_at']) ?></p>

          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>Item</th>
                  <th style="width:120px">Quantity</th>
                  <th style="width:120px">Price</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ord['items'] as $it): ?>
                  <tr>
                    <td><?= htmlspecialchars($it['item_name']) ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td>₱<?= number_format($it['price'],2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <footer class="mt-4">
    &copy; <?= date("Y") ?> CampusTreats | All rights reserved.
  </footer>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sellerNotifBtn = document.getElementById('sellerNotifBtn');
    const sellerNotifBadge = document.getElementById('sellerNotifBadge');

    if (!sellerNotifBtn) return;

    sellerNotifBtn.addEventListener('click', function() {
        if (!sellerNotifBadge) return;

        fetch('mark_seller_notifications_read.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sellerNotifBadge.remove();
                    const items = sellerNotifBtn.nextElementSibling.querySelectorAll('li.dropdown-item');
                    items.forEach(li => li.classList.remove('fw-bold'));
                }
            })
            .catch(err => console.error(err));
    });
});
</script>
</body>
</html>
