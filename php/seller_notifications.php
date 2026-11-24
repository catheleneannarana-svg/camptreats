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

    $stmtMarkRead = $conn->prepare("UPDATE seller_notifications SET is_read = 1 WHERE user_id = ?");
    $stmtMarkRead->execute([$seller_id]);

    $stmt = $conn->prepare("
        SELECT notification_id, message, is_read, created_at
        FROM seller_notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$seller_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtUnread = $conn->prepare("SELECT COUNT(*) FROM seller_notifications WHERE user_id = ? AND is_read = 0");
    $stmtUnread->execute([$seller_id]);
    $unreadCount = (int)$stmtUnread->fetchColumn();

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seller Notifications</title>
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

.main { margin-left:250px; padding:28px; min-height:calc(100vh - 70px); }
.table thead { background:#ff9800; color:#fff; }
.table tbody tr.unread { font-weight:700; background:#fff3e0; }

.dropdown-menu-notif { width: 340px; max-height: 400px; overflow-y: auto; }
.dropdown-item-notif { white-space:normal; }
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
  <a href="seller_dashboard.php"><i class="bi bi-house-door me-1"></i> Dashboard</a>
  <a href="my_item.php" ><i class="bi bi-card-list me-1"></i> My Items</a>
  <a href="add_item.php" ><i class="bi bi-plus-square me-1"></i> Add Item</a>
  <a href="my_orders.php"><i class="bi bi-bag-check me-1"></i> Orders</a>
  <a href="messages.php"><i class="bi bi-chat-left-dots me-1"></i> Messages</a>
  <a href="seller_notifications.php" class="active"><i class="bi bi-bell me-1"></i> Notifications
    <?php if($unreadCount > 0): ?><span class="badge bg-danger ms-2"><?= $unreadCount ?></span><?php endif; ?>
  </a>
  <a href="profile.php"><i class="bi bi-person-circle me-1"></i> Profile</a>
  <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
</div>
<div class="main">
    <h2>Seller Notifications</h2>

    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">No notifications yet.</div>
    <?php else: ?>
        <table class="table table-hover mt-3">
            <thead>
                <tr>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $notif): ?>
                <tr class="<?= $notif['is_read'] ? '' : 'unread' ?>">
                    <td><?= htmlspecialchars($notif['message']) ?></td>
                    <td><?= date('M d, Y H:i', strtotime($notif['created_at'])) ?></td>
                    <td><?= $notif['is_read'] ? 'Read' : 'Unread' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
