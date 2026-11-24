<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

try {
  $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $orders = $conn->query("
    SELECT o.order_id, u.username, o.total_amount, o.status, o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_id DESC
  ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  die("DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Orders</title>
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
    .offcanvas{
      background-color:lab(94.86% 0.74 10.02 / 0.823);
      border-radius: 20px;
      margin-top: 10px;
    }
    #menu{
      font-size: 30px;
      font-family:Georgia, 'Times New Roman', Times, serif
    }
    .nav-link{
      font-size: 25px;
      line-height: 45px;
    }
    .nav-link:hover{
      background-color: rgba(0, 0, 0, 0.1);  
      transition: background-color 0.2s ease, transform 0.2s ease;
    }
    h2 {
      margin-top: 30px;
    }
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
                <li class="text-center"><a href="notification.php" class="text-decoration-none fw-bold">View All Notifications</a></li>
            <?php endif; ?>
        </ul>
      </div>
      <a href="logout.php" class="nav-link ms-3"><i class="bi bi-box-arrow-right fs-4"></i></a>
    </div>
</nav>

<div class="sidebar">
  <h5><i class="bi bi-speedometer2 me-1"></i> Dashboard</h5>
   <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
    <li class="nav-item"><a class="nav-link" href="manage_sellers.php"><i class="fas fa-store"></i> Manage Sellers</a></li>
    <li class="nav-item"><a class="nav-link" href="manage_items.php"><i class="fas fa-utensils"></i> Manage Items</a></li>
    <!-- <li class="nav-item"><a class="nav-link" href="manage_orders.php"><i class="fas fa-box"></i> Manage Orders</a></li> -->
    <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
</div>
  <div class="container mt-4">
    <h2><i class="fas fa-box"></i> Orders</h2>
    <table class="table table-bordered mt-2">
      <thead class="table-light">
        <tr>
          <th>Order ID</th>
          <th>Buyer</th>
          <th>Total Amount</th>
          <th>Status</th>
          <th>Created At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= $o['order_id'] ?></td>
            <td><?= htmlspecialchars($o['username']) ?></td>
            <td>₱<?= number_format($o['total_amount'], 2) ?></td>
            <td>
              <?php 
                if($o['status'] === 'pending') echo '<span class="badge bg-warning">Pending</span>';
                elseif($o['status'] === 'completed') echo '<span class="badge bg-success">Completed</span>';
                elseif($o['status'] === 'cancelled') echo '<span class="badge bg-danger">Cancelled</span>';
              ?>
            </td>
            <td><?= $o['created_at'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  </div>

  <script src="../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
