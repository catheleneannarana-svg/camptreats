<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.html");
    exit();
}

$seller_id = $_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtBuyers = $conn->prepare("
        SELECT DISTINCT u.user_id AS buyer_id, u.full_name AS buyer_name
        FROM users u
        LEFT JOIN messages m ON u.user_id = m.buyer_id AND m.seller_id = :seller_id
        LEFT JOIN orders o ON u.user_id = o.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.menu_item_id AND mi.seller_id = :seller_id
        WHERE m.message_id IS NOT NULL OR mi.menu_item_id IS NOT NULL
        ORDER BY u.full_name ASC
    ");
    $stmtBuyers->execute(['seller_id' => $seller_id]);
    $buyers = $stmtBuyers->fetchAll(PDO::FETCH_ASSOC);

    $selected_buyer_id = $_GET['buyer_id'] ?? ($buyers[0]['buyer_id'] ?? null);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
        $buyer_id = intval($_POST['buyer_id']);
        $message = trim($_POST['message']);
        if (!empty($message)) {
            $stmtMsg = $conn->prepare("
                INSERT INTO messages (buyer_id, seller_id, message, sender, created_at, is_read)
                VALUES (?,?,?,?,NOW(),0)
            ");
            $stmtMsg->execute([$buyer_id, $seller_id, $message, 'seller']);

            header("Location: messages.php?buyer_id=$buyer_id");
            exit();
        }
    }

    $messages = [];
    if ($selected_buyer_id) {
        $stmtMsgFetch = $conn->prepare("
            SELECT * FROM messages
            WHERE buyer_id=? AND seller_id=?
            ORDER BY created_at ASC
        ");
        $stmtMsgFetch->execute([$selected_buyer_id, $seller_id]);
        $messages = $stmtMsgFetch->fetchAll(PDO::FETCH_ASSOC);

        $stmtMark = $conn->prepare("
            UPDATE messages
            SET is_read=1
            WHERE buyer_id=? AND seller_id=? AND sender='buyer'
        ");
        $stmtMark->execute([$selected_buyer_id, $seller_id]);
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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages | CampusTreats</title>
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
.main-content { margin-left:270px; padding:25px; }
.chat-sidebar { width:200px; float:left; border-right:1px solid #ccc; padding-right:10px; }
.chat-sidebar a { display:block; padding:8px; text-decoration:none; border-radius:6px; margin-bottom:5px; }
.chat-sidebar a.active, .chat-sidebar a:hover { background:#ff9800; color:#fff; }
.chat-box { margin-left:220px; border:1px solid #ccc; padding:15px; border-radius:10px; background:#fff; }
.message.buyer { text-align:left; float:left; background:#eee; color:#333; padding:5px 10px; border-radius:10px; margin-bottom:5px; max-width:70%; clear:both; display:table; word-wrap:break-word; }
.message.seller { text-align:right; float:right; background:#ff9800; color:#fff; padding:5px 10px; border-radius:10px; margin-bottom:5px; max-width:70%; clear:both; display:table; word-wrap:break-word; }
.messages-container { height:400px; overflow-y:auto; margin-bottom:10px; }
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
  <a href="seller_dashboard.php" ><i class="bi bi-house-door me-1"></i> Dashboard</a>

  <a href="my_item.php"><i class="bi bi-card-list me-1"></i> My Items</a>
  <a href="add_item.php"><i class="bi bi-plus-square me-1"></i> Add Item</a>
  <a href="my_orders.php"><i class="bi bi-bag-check me-1"></i> Orders</a>
  <a href="messages.php" class="active"><i class="bi bi-chat-left-dots me-1"></i> Messages</a>
  <a href="seller_notifications.php"><i class="bi bi-bell me-1"></i> Notifications
    <?php if($unreadCount > 0): ?><span class="badge bg-danger ms-2"><?= $unreadCount ?></span><?php endif; ?>
  </a>
  <a href="profile.php"><i class="bi bi-person-circle me-1"></i> Profile</a>
  <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
</div>


<div class="main-content container">
<h2>💬 Messages</h2>

<div class="chat-sidebar">
    <h6>Buyers</h6>
    <?php foreach($buyers as $buyer): ?>
        <a href="?buyer_id=<?= $buyer['buyer_id'] ?>" class="<?= ($buyer['buyer_id']==$selected_buyer_id)?'active':'' ?>">
            <?= htmlspecialchars($buyer['buyer_name']) ?>
            <?php
            $stmtUnreadBuyer = $conn->prepare("
                SELECT COUNT(*) FROM messages
                WHERE seller_id=? AND buyer_id=? AND sender='buyer' AND is_read=0
            ");
            $stmtUnreadBuyer->execute([$seller_id, $buyer['buyer_id']]);
            $countBuyer = $stmtUnreadBuyer->fetchColumn();
            if($countBuyer>0) echo " ($countBuyer)";
            ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if($selected_buyer_id): ?>
<div class="chat-box">
  <div class="messages-container">
    <?php foreach($messages as $msg): ?>
      <div class="message <?= $msg['sender']=='buyer'?'buyer':'seller' ?>">
        <?= htmlspecialchars($msg['message']) ?><br>
        <small><?= date('M d, H:i', strtotime($msg['created_at'])) ?></small>
      </div>
    <?php endforeach; ?>
  </div>
  <form method="POST">
    <input type="hidden" name="buyer_id" value="<?= $selected_buyer_id ?>">
    <div class="input-group">
      <input type="text" name="message" class="form-control" placeholder="Type a message..." required>
      <button type="submit" name="send_message" class="btn btn-primary">Send</button>
    </div>
  </form>
</div>
<?php else: ?>
<p>Select a buyer to start chatting.</p>
<?php endif; ?>

</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
let msgContainer = document.querySelector('.messages-container');
if(msgContainer) msgContainer.scrollTop = msgContainer.scrollHeight;
</script>
</body>
</html>
