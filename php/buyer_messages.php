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

    $stmt = $conn->prepare("SELECT notification_id, message, created_at, is_read FROM notifications WHERE user_id=? ORDER BY created_at DESC");
    $stmt->execute([$buyer_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtUnreadMsg = $conn->prepare("SELECT COUNT(*) FROM messages WHERE buyer_id=? AND sender='seller' AND is_read=0");
    $stmtUnreadMsg->execute([$buyer_id]);
    $unreadMsgCount = $stmtUnreadMsg->fetchColumn();

    $stmtSellers = $conn->prepare("
        SELECT DISTINCT u.user_id AS seller_id, u.full_name AS seller_name
        FROM messages m
        JOIN users u ON m.seller_id = u.user_id
        WHERE m.buyer_id = ?
    ");
    $stmtSellers->execute([$buyer_id]);
    $sellers = $stmtSellers->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $seller_id = intval($_POST['seller_id']);
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmtMsg = $conn->prepare("
            INSERT INTO messages (buyer_id, seller_id, message, sender)
            VALUES (?, ?, ?, 'buyer')
        ");
        $stmtMsg->execute([$buyer_id, $seller_id, $message]);
    }

    header("Location: buyer_messages.php?seller_id=" . $seller_id);
    exit();
}

    $selected_seller_id = $_GET['seller_id'] ?? ($sellers[0]['seller_id'] ?? null);

    $messages = [];
    if($selected_seller_id){

      $stmtMsgFetch = $conn->prepare("SELECT * FROM messages WHERE buyer_id=? AND seller_id=? ORDER BY created_at ASC");
        $stmtMsgFetch->execute([$buyer_id, $selected_seller_id]);
        $messages = $stmtMsgFetch->fetchAll(PDO::FETCH_ASSOC);

        $stmtMark = $conn->prepare("UPDATE messages SET is_read=1 WHERE buyer_id=? AND seller_id=? AND sender='seller'");
        $stmtMark->execute([$buyer_id, $selected_seller_id]);
    }

} catch(PDOException $e){
    die("DB Error: ".$e->getMessage());
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
body { background-color: #fffaf2; font-family: 'Poppins', sans-serif; color: #3E2723; margin:0; padding-top:70px; }
.navbar { background: linear-gradient(90deg, #ff9800, #f57c00); box-shadow: 0 4px 10px rgba(0,0,0,0.1);}
.navbar-brand { font-weight:700; color:#fff !important;}
.nav-link { color:#fff !important; font-weight:500;}
.sidebar { position: fixed; top:60px; left:0; width:230px; height:100%; background-color:#f4cbaa; padding:25px 15px; box-shadow:2px 0 6px rgba(0,0,0,0.1);}
.sidebar h5 { font-weight:600; font-size:25px; margin-bottom:20px;}
.sidebar a { display:block; padding:10px 12px; border-radius:8px; color:#3E2723; margin-bottom:10px; text-decoration:none; transition: all 0.3s ease; font-weight:500; font-size:22px;}
.sidebar a:hover { background-color:#ff9800; color:#fff; transform:translateX(5px);}
.main-content { margin-left:270px; padding:25px; }
.chat-sidebar { width:200px; float:left; border-right:1px solid #ccc; padding-right:10px; }
.chat-sidebar a { display:block; padding:8px; text-decoration:none; border-radius:6px; margin-bottom:5px; }
.chat-sidebar a.active, .chat-sidebar a:hover { background:#ff9800; color:#fff; }
.chat-box { margin-left:220px; border:1px solid #ccc; padding:15px; border-radius:10px; background:#fff; }
.message.buyer {
    text-align: right;               
    float: right;                     
    background: #ff9800;
    color: #fff;
    padding: 5px 10px;
    border-radius: 10px;
    margin-bottom: 5px;
    max-width: 70%;                   
    clear: both;                    
    display: table;                   
    word-wrap: break-word;            
}

.message.seller {
    text-align: left;
    float: left;
    background: #eee;
    color: #333;
    padding: 5px 10px;
    border-radius: 10px;
    margin-bottom: 5px;
    max-width: 70%;
    clear: both;
    display: table;
    word-wrap: break-word;
}
.messages-container { height:400px; overflow-y:auto; margin-bottom:10px; }
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
  <a href="buyer_messages.php"  class="active"><i class="bi bi-person-circle me-2"></i> messages</a>
  <a href="buyer_notifications.php"><i class="bi bi-bell me-2"></i> Notification</a>
  <a href="profile.php"><i class="bi bi-person-circle me-2"></i> Profile</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right fs-4"></i> Logout</a>
</div>

<div class="main-content container">
<h2>💬 Messages</h2>

<div class="chat-sidebar">
    <h6>Sellers</h6>
    <?php foreach($sellers as $seller): ?>
        <a href="?seller_id=<?= $seller['seller_id'] ?>" class="<?= ($seller['seller_id']==$selected_seller_id)?'active':'' ?>">
            <?= htmlspecialchars($seller['seller_name']) ?>
            <?php
            $stmtUnreadSeller = $conn->prepare("SELECT COUNT(*) FROM messages WHERE buyer_id=? AND seller_id=? AND sender='seller' AND is_read=0");
            $stmtUnreadSeller->execute([$buyer_id, $seller['seller_id']]);
            $countSeller = $stmtUnreadSeller->fetchColumn();
            if($countSeller>0) echo " ($countSeller)";
            ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if($selected_seller_id): ?>
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
        <input type="hidden" name="seller_id" value="<?= $selected_seller_id ?>">
        <div class="input-group">
            <input type="text" name="message" class="form-control" placeholder="Type a message..." required>
            <button type="submit" name="send_message"
        class="btn btn-primary"
        onclick="this.disabled=true; this.form.submit();">
    Send
</button>
        </div>
    </form>
</div>
<?php else: ?>
<p>Select a seller to start chatting.</p>
<?php endif; ?>

</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>

let msgContainer = document.querySelector('.messages-container');
if(msgContainer) msgContainer.scrollTop = msgContainer.scrollHeight;
</script>
</body>
</html>
