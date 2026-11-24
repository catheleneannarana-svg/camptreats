<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) die("User not found.");

    $role = $user['role'];

    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtUnread = $conn->prepare("SELECT COUNT(*) FROM seller_notifications WHERE user_id = ? AND is_read = 0");
    $stmtUnread->execute([$user_id]);
    $unreadCount = (int)$stmtUnread->fetchColumn();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile | CampusTreats</title>
<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../bootstrap-icons/bootstrap-icons.css">
<style>
body { background:#fffaf2; font-family:'Poppins',sans-serif; color:#3E2723; margin:0; padding-top:70px; }
.navbar { background:linear-gradient(90deg,#ff9800,#f57c00); box-shadow:0 4px 10px rgba(0,0,0,0.1); }
.navbar-brand { font-weight:700; color:#fff !important; }
.nav-link { color:#fff !important; font-weight:500; }

.sidebar { position:fixed; top:65px; left:0; width:230px; height:100vh; background:#f4cbaa; padding:25px 15px; box-shadow:2px 0 6px rgba(0,0,0,0.08); overflow-y:auto; }
.sidebar h5 { font-weight:600; font-size:22px; margin-bottom:18px; }
.sidebar a { display:block; padding:10px 12px; border-radius:8px; color:#3E2723; margin-bottom:8px; text-decoration:none; transition: all .2s ease; font-size:20px; }
.sidebar a i { margin-right:10px; }
.sidebar a:hover { background:#ff9800; color:#fff; transform:translateX(6px); }
.sidebar a.active { background:#ff6b35; color:#fff; }

.profile-container { max-width:900px; margin-left:30%;margin-top:5%; background:#fff; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.1); overflow:hidden; }
.profile-header { text-align:center; background: linear-gradient(135deg,#ff9800,#ff6b35); color:#fff; padding:50px 20px; position:relative; }
.profile-header img { width:140px; height:140px; border-radius:50%; object-fit:cover; border:4px solid #fff; margin-bottom:15px; }
.profile-header h3 { font-weight:600; }
.profile-header p { font-size:0.95rem; opacity:0.85; }
.profile-body { padding:30px; }
.info-card { background:#fff3e0; border-radius:12px; padding:20px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; }
.info-label { font-weight:600; color:#6d4c41; }
.info-value { font-weight:500; }
.btn-edit, .btn-seller { border-radius:12px; padding:10px 25px; font-weight:500; transition:.3s; }
.btn-edit { background:#ff9800; color:#fff; border:none; }
.btn-edit:hover { background:#f57c00; }
.btn-seller { background:#ff6b35; color:#fff; border:none; display:block; text-align:center; text-decoration:none; }
.btn-seller:hover { background:#e64a19; }

.dropdown-menu-notif { width:350px; max-height:400px; overflow-y:auto; border-radius:12px; padding:0; }
.dropdown-item-notif { padding:12px 15px; border-bottom:1px solid #eee; font-size:14px; line-height:1.4; cursor:pointer; }
.dropdown-item-notif.unread { background:#fff3e0; font-weight:600; }
.dropdown-item-notif:last-child { border-bottom:none; }
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
            <?php if($unreadCount>0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unreadCount ?></span>
            <?php endif; ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-notif">
            <?php if(empty($notifications)): ?>
              <li class="text-center text-muted p-3">No notifications</li>
            <?php else: ?>
              <?php foreach($notifications as $notif): ?>
                <li class="dropdown-item-notif <?= $notif['is_read'] ? '' : 'unread' ?>">
                  <?= htmlspecialchars($notif['message']) ?><br>
                  <small class="text-muted"><?= date('M d, Y H:i', strtotime($notif['created_at'])) ?></small>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
        <a href="logout.php" class="nav-link ms-3"><i class="bi bi-box-arrow-right fs-4"></i></a>
    </div>
  </div>
</nav>

<div class="sidebar">
  <h5><i class="bi bi-speedometer2 me-1"></i> Dashboard</h5>
  <?php if($role==='seller'): ?>
    <a href="seller_dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
    <a href="my_item.php"><i class="bi bi-card-list"></i> My Items</a>
    <a href="add_item.php"><i class="bi bi-plus-square"></i> Add Item</a>
    <a href="my_orders.php"><i class="bi bi-bag-check"></i> Orders</a>
    <a href="messages.php"><i class="bi bi-chat-left-dots"></i> Messages</a>
    <a href="seller_notifications.php"><i class="bi bi-bell"></i> Notifications <?php if($unreadCount>0): ?><span class="badge bg-danger ms-2"><?= $unreadCount ?></span><?php endif; ?></a>
  <a href="profile.php" class="active"><i class="bi bi-person-circle"></i> Profile</a>

  <?php else: ?>
  <a href="buyer_dashboard.php"><i class="bi bi-house-door me-2"></i> Home</a>
  <a href="cart.php"><i class="bi bi-cart me-2"></i> My Cart</a>
  <a href="buyers_order.php"><i class="bi bi-bag-check me-2"></i> My Orders</a>
  <a href="profile.php"><i class="bi bi-person-circle me-2"></i> Profile</a>
  <a href="buyer_notifications.php"><i class="bi bi-bell me-2"></i> Notification</a>
  <?php endif; ?>
  <a href="../login.html"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="profile-container">
  <div class="profile-header">
    <img src="<?= !empty($user['profile_picture']) && file_exists('../'.$user['profile_picture'])?'../'.htmlspecialchars($user['profile_picture']):'../placeholder.png' ?>" alt="Profile Picture">
    <h3><?= htmlspecialchars($user['full_name']) ?></h3>
    <p><?= ucfirst(htmlspecialchars($role)) ?></p>
    <?php if(!empty($user['description'])): ?><p class="fst-italic">“<?= htmlspecialchars($user['description']) ?>”</p><?php endif; ?>
  </div>

  <div class="profile-body">
    <div class="info-card"><div class="info-label"><i class="bi bi-envelope me-2"></i> Email</div><div class="info-value"><?= htmlspecialchars($user['email']) ?></div></div>
    <div class="info-card"><div class="info-label"><i class="bi bi-telephone me-2"></i> Phone</div><div class="info-value"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></div></div>
    <div class="info-card"><div class="info-label"><i class="bi bi-building me-2"></i> Department</div><div class="info-value"><?= htmlspecialchars($user['department'] ?? 'N/A') ?></div></div>
    <div class="info-card"><div class="info-label"><i class="bi bi-journal-bookmark me-2"></i> Course</div><div class="info-value"><?= htmlspecialchars($user['course'] ?? 'N/A') ?></div></div>
    <div class="info-card"><div class="info-label"><i class="bi bi-card-checklist me-2"></i> Year/Section</div><div class="info-value"><?= htmlspecialchars($user['section'] ?? 'N/A') ?></div></div>

    <button class="btn-edit w-100 mb-3" onclick="window.location.href='edit_profile.php'"><i class="bi bi-pencil-square me-2"></i> Edit Profile</button>

    <?php if($role!=='seller'): ?>
      <a href="upgrade_seller.php" class="btn-seller w-100 mb-3"><i class="bi bi-shop me-2"></i> Apply as Seller</a>
    <?php else: ?>
      <!-- <div class="info-card"><div class="info-label"><i class="bi bi-shop me-2"></i> Store Name</div><div class="info-value"><?= htmlspecialchars($user['store_name'] ?? 'N/A') ?></div></div> -->
    <?php endif; ?>
  </div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
