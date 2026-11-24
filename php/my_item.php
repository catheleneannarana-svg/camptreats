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

    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->execute([$seller_id]);
    $rawItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rawItems as $item) {
        $stmt2 = $conn->prepare("SELECT image FROM item_images WHERE menu_item_id = ?");
        $stmt2->execute([$item['menu_item_id']]);
        $item['images'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        $items[] = $item;
    }

    $stmtNotify = $conn->prepare("SELECT COUNT(*) FROM seller_notifications WHERE user_id = ? AND is_read = 0");
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
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Items | Seller Dashboard</title>
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

.main{margin-left:250px;padding:28px;margin-top:70px;flex:1}

 .hero {
    border-radius: 12px;
    overflow: hidden;
    margin-top: 6px;
  }
  .hero img { width:100%; height:320px; object-fit:cover; display:block; border-radius:12px; }

.card{border-radius:14px;box-shadow:0 6px 16px rgba(0,0,0,.1);transition:.3s}
.card:hover{transform:translateY(-5px)}
.card img{height:200px;object-fit:cover}

.badge-status{
    position:absolute;
    top:10px;
    right:10px;
    padding:6px 10px;
    font-size:0.75rem;
    font-weight:600;
    border-radius:8px;
    color:#fff;
    z-index:5;
}
.status-pending{background:#ffc107;color:#3E2723}
.status-approved{background:#28a745}
.status-rejected{background:#dc3545}
.status-unavailable{background:#6c757d}

.badge-stock{
    position:absolute;
    top:10px;left:10px;
    background:#000;color:#fff;
    font-size:0.75rem;
    padding:4px 8px;
    border-radius:6px;
}

footer{background:var(--primary1);color:#fff;text-align:center;padding:12px;border-radius:8px}
</style>
</head>

<body>
<div class="page-wrapper">

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
  <a href="my_item.php"  class="active"><i class="bi bi-card-list me-1"></i> My Items</a>
  <a href="add_item.php"><i class="bi bi-plus-square me-1"></i> Add Item</a>
  <a href="my_orders.php"><i class="bi bi-bag-check me-1"></i> Orders</a>
  <a href="messages.php"><i class="bi bi-chat-left-dots me-1"></i> Messages</a>
  <a href="seller_notifications.php"><i class="bi bi-bell me-1"></i> Notifications
    <?php if($unreadCount > 0): ?><span class="badge bg-danger ms-2"><?= $unreadCount ?></span><?php endif; ?>
  </a>
  <a href="profile.php"><i class="bi bi-person-circle me-1"></i> Profile</a>
  <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
</div>


<div class="main">

<div class="hero mb-4"> <img src="../uploads/seller_banner.jpg" alt="Banner" onerror="this.src='../uploads/burgers_banner.jpeg'"> </div>

<h3 class="mb-3">My Menu Items</h3>
<a href="add_item.php" class="btn btn-success mb-4">➕ Add New Item</a>

<div class="row">
<?php if(empty($items)): ?>
<p>No items found.</p>
<?php else: foreach($items as $item): ?>

<?php
$statusClass = match($item['status']){
    'pending'=>'status-pending',
    'approved'=>'status-approved',
    'rejected'=>'status-rejected',
    default=>'status-unavailable'
};
$statusLabel = match($item['status']){
    'pending'=>'Pending Approval',
    'approved'=>'Approved',
    'rejected'=>'Rejected',
    default=>ucfirst($item['status'])
};
?>

<div class="col-md-4 mb-4 position-relative">
  <div class="card" data-bs-toggle="modal" data-bs-target="#itemModal<?= $item['menu_item_id'] ?>">

    <div class="badge-status <?= $statusClass ?>"><?= $statusLabel ?></div>
    <?php if($item['quantity']<=0): ?><div class="badge-stock">Out of Stock</div><?php endif; ?>

    <?php if(!empty($item['images'])): ?>
      <img src="../uploads/<?= htmlspecialchars($item['images'][0]) ?>" class="w-100">
    <?php endif; ?>

    <div class="card-body">
      <h5><?= htmlspecialchars($item['name']) ?></h5>
      <p><?= htmlspecialchars($item['description']) ?></p>
      <strong>₱<?= number_format($item['price'],2) ?></strong>
      <p>Stock: <?= $item['quantity'] ?></p>

      <a href="edit_item.php?id=<?= $item['menu_item_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
      <a href="delete_item.php?menu_item_id=<?= $item['menu_item_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this item?')">Delete</a>
    </div>
  </div>
</div>

<?php endforeach; endif; ?>
</div>
</div>

<footer>&copy; <?= date("Y") ?> CampusTreats</footer>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
