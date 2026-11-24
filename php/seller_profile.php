<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

$seller_id = $_GET['id'] ?? null;
if (!$seller_id) die("Seller ID is required.");

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND is_seller = 1");
    $stmt->execute([$seller_id]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$seller) die("Seller not found.");

    $stmt = $conn->prepare("
        SELECT DISTINCT m.menu_item_id, m.name, m.description, m.price, m.quantity, m.created_at,
               c.category_name
        FROM menu_items m
        LEFT JOIN categories c ON m.category_id = c.category_id
        WHERE m.seller_id = ? AND m.status = 'approved'
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$seller_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $stmt2 = $conn->prepare("SELECT DISTINCT image FROM item_images WHERE menu_item_id = ?");
        $stmt2->execute([$item['menu_item_id']]);
        $item['images'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    }
    unset($item);
    
    $buyer_id = $_SESSION['user_id'];

    $stmtDocs = $conn->prepare("
        SELECT doc_id, file_name, file_type, uploaded_at 
        FROM seller_documents 
        WHERE seller_id = ? 
        ORDER BY uploaded_at DESC
    ");
    $stmtDocs->execute([$seller_id]);
    $documents = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$buyer_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtUnread = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtUnread->execute([$buyer_id]);
    $unreadCount = $stmtUnread->fetchColumn();

    

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($seller['full_name']) ?> | Seller Profile</title>
<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../bootstrap-icons/bootstrap-icons.css">
<style>
      :root{
    --primary1: #ff9800;
    --primary2: #f57c00;
    --bg: #fffaf2;
    --text: #3E2723;
  }
.sidebar a.active { background: var(--primary2); color:#fff; }
  
body { background-color: #fffaf2; font-family: 'Poppins', sans-serif; color: #3E2723; margin:0; padding-top:70px; }
.navbar { background: linear-gradient(90deg, #ff9800, #f57c00); box-shadow: 0 4px 10px rgba(0,0,0,0.1);}
.navbar-brand { font-weight:700; color:#fff !important;}
.nav-link { color:#fff !important; font-weight:500;}
.sidebar { position: fixed; top:60px; left:0; width:230px; height:100%; background-color:#f4cbaa; padding:25px 15px; box-shadow:2px 0 6px rgba(0,0,0,0.1);}
.sidebar h5 { font-weight:600; font-size:25px; margin-bottom:20px;}
.sidebar a { display:block; padding:10px 12px; border-radius:8px; color:#3E2723; margin-bottom:10px; text-decoration:none; transition: all 0.3s ease; font-weight:500; font-size:22px;}
.sidebar a:hover { background-color:#ff9800; color:#fff; transform:translateX(5px);}

.main-content { margin-left: 250px; padding: 20px 30px; max-width: 95%; }

.seller-info { display: flex; background-color: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 14px rgba(0,0,0,0.1); margin-bottom: 30px; }
.seller-info img { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; margin-right: 20px; }
.seller-info h3 { margin-bottom: 10px; }

.section-title { font-size:1.5rem; font-weight:700; color:#3E2723; margin-top:40px; border-left:6px solid #ff9800; padding-left:10px; }

.documents-section { margin-top: 30px; }
.documents-container { max-height: 350px; overflow-y: auto; padding-right: 5px; margin-top: 10px; }
.document-card { background: #fff; border-radius: 15px; box-shadow: 0 4px 14px rgba(0,0,0,0.1); padding: 15px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; transition: 0.3s; }
.document-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
.document-name { font-weight: 600; color: #3E2723; text-transform: capitalize; }
.btn-view { background: #ff9800; color: #fff; border: none; padding: 5px 12px; border-radius: 8px; }
.btn-view:hover { background: #f57c00; }

.menu-section .card { border: none; border-radius: 16px; transition: 0.3s; box-shadow: 0 4px 14px rgba(0,0,0,0.1); overflow: hidden; }
.menu-section .card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
.menu-section .card img { height: 180px; object-fit: cover; }
.menu-section .card-body { background-color: #fff; padding: 15px; }
.menu-section .card-title { font-size: 1.1rem; font-weight: 600; }
.price { color: #e65100; font-weight: bold; }
.btn-buy { background-color: #ff9800; color: white; border: none; padding: 5px 10px; border-radius: 8px; }
.btn-buy:hover { background-color: #f57c00; }
.badge-new { position: absolute; top: 10px; left: 10px; background-color: #ff9800; color: white; font-size: 0.75rem; border-radius: 5px; padding: 3px 7px; }
.badge-out { position: absolute; top: 10px; left: 10px; background-color: #b71c1c; color: white; font-size: 0.75rem; border-radius: 5px; padding: 3px 7px; }
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
  <a href="buyer_dashboard.php"  class="active"><i class="bi bi-house-door me-2"></i> Home</a>
  <a href="cart.php"><i class="bi bi-cart me-2"></i> My Cart</a>
  <a href="buyers_order.php"><i class="bi bi-bag-check me-2"></i> My Orders</a>
  <a href="buyer_messages.php"><i class="bi bi-person-circle me-2"></i> messages</a>
  <a href="buyer_notifications.php"><i class="bi bi-bell me-2"></i> Notification</a>
  <a href="profile.php"><i class="bi bi-person-circle me-2"></i> Profile</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right fs-4"></i> Logout</a>
</div>
<div class="main-content">

  <div class="seller-info">
      <img src="../<?= htmlspecialchars($seller['profile_picture'] ?? 'placeholder.png') ?>" alt="Profile Picture">
      <div>
          <h3><?= htmlspecialchars($seller['full_name']) ?></h3>
          <p class="text-muted mb-1">Username: <?= htmlspecialchars($seller['username']) ?></p>
          <p class="text-muted mb-1">Email: <?= htmlspecialchars($seller['email']) ?></p>
          <!-- <p class="text-muted mb-1">Phone: <?= htmlspecialchars($seller['phone']) ?></p> -->
          <p class="text-muted mb-1">Department: <?= htmlspecialchars($seller['department']) ?></p>
          <p class="text-muted mb-1">Course: <?= htmlspecialchars($seller['course']) ?></p>
          <p class="text-muted mb-1">Section: <?= htmlspecialchars($seller['section']) ?></p>
          <p class="text-muted mb-0">Description: <?= htmlspecialchars($seller['description']) ?></p>
      </div>
  </div>

  <div class="documents-section">
      <h3 class="section-title">Seller Documents</h3>
      <?php if(empty($documents)): ?>
          <p class="text-center text-muted mt-3">No documents uploaded by this seller.</p>
      <?php else: ?>
          <div class="documents-container mt-3">
              <?php foreach($documents as $doc): 
                  $folder = match ($doc['file_type']) {
    'permit'   => 'permits',
    'schedule' => 'schedules',
    'valid_id' => 'valid_ids',
    default    => 'others'
};

                  $filepath = "../uploads/$folder/" . $doc['file_name'];
              ?>
                  <div class="document-card">
                      <div class="document-name"><?= htmlspecialchars($doc['file_type']) ?></div>
                      <a href="<?= htmlspecialchars($filepath) ?>" target="_blank" class="btn-view"><i class="bi bi-eye me-1"></i> View</a>
                  </div>
              <?php endforeach; ?>
          </div>
      <?php endif; ?>
  </div>

  <div class="menu-section my-5">
  <?php if(empty($items)): ?>
      <p class="text-center text-muted">This seller has no items yet.</p>
  <?php else: ?>
      <?php
      $groupedItems = [];
      foreach ($items as $item) {
          $groupedItems[$item['category_name']][] = $item;
      }
      foreach ($groupedItems as $categoryName => $categoryItems):
      ?>
          <h3 class="section-title"><?= htmlspecialchars($categoryName ?? 'Others') ?></h3>
          <div class="row g-4 mt-3">
          <?php foreach ($categoryItems as $item):
              $isOut = ($item['quantity'] <= 0);
          ?>
              <div class="col-md-3">
              <div class="card position-relative">
                  <?php
                  $created = new DateTime($item['created_at']);
                  $now = new DateTime();
                  if ($now->diff($created)->days <= 7) echo '<span class="badge-new">NEW</span>';
                  if ($isOut) echo '<span class="badge-out">OUT OF STOCK</span>';
                  ?>
                  <?php if (!empty($item['images'])): ?>
                      <img src="../uploads/<?= htmlspecialchars($item['images'][0]) ?>" class="card-img-top" alt="<?= htmlspecialchars($item['name']) ?>">
                  <?php else: ?>
                      <img src="../placeholder.png" class="card-img-top" alt="No image">
                  <?php endif; ?>
                  <div class="card-body">
                      <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                      <p class="small text-muted mb-1"><?= htmlspecialchars($item['description']) ?></p>
                      <p class="small text-muted mb-1">Available Quantity: <?= $item['quantity'] ?></p>
                      <div class="d-flex justify-content-between align-items-center">
                          <div class="price">₱<?= number_format($item['price'],2) ?></div>
                          <?php if(!$isOut): ?>
                          <form action="add_tocart.php" method="POST" class="d-flex align-items-center">
                              <input type="hidden" name="menu_item_id" value="<?= $item['menu_item_id'] ?>">
                              <input type="number" name="quantity" value="1" min="1" class="form-control form-control-sm me-2" style="width:70px;">
                              <button type="submit" class="btn-buy"><i class="bi bi-cart-plus"></i></button>
                          </form>
                          <?php endif; ?>
                      </div>
                  </div>
              </div>
              </div>
          <?php endforeach; ?>
          </div>
      <?php endforeach; ?>
  <?php endif; ?>
  </div>

</div> 

<footer style="margin-left:250px; padding:20px;">&copy; <?= date("Y") ?> CampusTreats | All rights reserved.</footer>
<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
