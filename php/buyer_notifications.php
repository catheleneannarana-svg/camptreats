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
    die("DB Error: ".$e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications | CampusTreats</title>
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
.main-content { margin-left:250px; padding:20px 30px; max-width:95%; }

.notification-card { background:#fff; padding:15px; margin-bottom:12px; border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,0.1); transition:0.3s; display:flex; justify-content:space-between; align-items:center; }
.notification-card.unread { border-left:5px solid #ff9800; }
.notification-card:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.15); }
.notification-date { font-size:0.8rem; color:#999; }
.btn-mark-read { background:#ff9800; color:#fff; border:none; padding:5px 12px; border-radius:8px; }
.btn-mark-read:hover { background:#f57c00; }
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
  <a href="buyers_order.php"><i class="bi bi-bag-check me-2"></i> My Orders</a>
  <a href="buyer_messages.php"><i class="bi bi-person-circle me-2"></i> messages</a>
  <a href="buyer_notifications.php" class="active"><i class="bi bi-bell me-2"></i> Notification</a>
  <a href="profile.php"><i class="bi bi-person-circle me-2"></i> Profile</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right fs-4"></i> Logout</a>
</div>
<div class="main-content">
  <h3 class="mb-4">Your Notifications</h3>
  <?php if(empty($notifications)): ?>
      <p class="text-muted">No notifications yet.</p>
  <?php else: ?>
      <div class="notifications-container" style="max-height:400px; overflow-y:auto;">
      <?php foreach($notifications as $n): ?>
          <div class="notification-card <?= $n['is_read']==0 ? 'unread' : '' ?>">
              <div>
                  <div><?= htmlspecialchars($n['message']) ?></div>
                  <div class="notification-date"><?= date("M d, Y H:i", strtotime($n['created_at'])) ?></div>
              </div>
              <?php if($n['is_read']==0): ?>
              <form method="POST" style="margin:0;">
                  <input type="hidden" name="notification_id" value="<?= $n['notification_id'] ?>">
                  <button type="submit" class="btn-mark-read">Mark Read</button>
              </form>
              <?php endif; ?>
          </div>
      <?php endforeach; ?>
      </div>
  <?php endif; ?>
</div>

<footer style="margin-left:250px; padding:20px;">&copy; <?= date("Y") ?> CampusTreats | All rights reserved.</footer>
<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
