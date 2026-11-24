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

    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$buyer_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtUnread = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtUnread->execute([$buyer_id]);
    $unreadCount = $stmtUnread->fetchColumn();

    $category = $_GET['category'] ?? 'all';

    $sql = "
    SELECT m.menu_item_id, m.name, m.description, m.price, m.status, m.quantity, c.category_name, m.created_at,
           u.user_id AS seller_id, u.full_name AS seller_name
    FROM menu_items m
    LEFT JOIN categories c ON m.category_id = c.category_id
    LEFT JOIN users u ON m.seller_id = u.user_id
    WHERE m.status IN ('approved','unavailable')
";

    if ($category !== 'all') {
        $sql .= " AND c.category_name = :category";
    }

    $sql .= " ORDER BY m.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($category !== 'all') {
        $stmt->bindParam(':category', $category);
    }
    $stmt->execute();
    $rawItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rawItems as $item) {
        $stmt2 = $conn->prepare("SELECT image FROM item_images WHERE menu_item_id = ?");
        $stmt2->execute([$item['menu_item_id']]);
        $images = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        $item['images'] = $images;
        $items[] = $item;
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CampusTreats | Buyer Dashboard</title>
<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../bootstrap-icons/bootstrap-icons.css">
<style>
    :root{
    --primary1: #ff9800;
    --primary2: #f57c00;
    --bg: #fffaf2;
    --text: #3E2723;
  }
body { background-color: #fffaf2; font-family: 'Poppins', sans-serif; color: #3E2723; margin:0; padding-top:70px; }
.navbar { background: linear-gradient(90deg, #ff9800, #f57c00); box-shadow: 0 4px 10px rgba(0,0,0,0.1);}
.navbar-brand { font-weight:700; color:#fff !important;}
.nav-link { color:#fff !important; font-weight:500;}
.sidebar { position: fixed; top:60px; left:0; width:230px; height:100%; background-color:#f4cbaa; padding:25px 15px; box-shadow:2px 0 6px rgba(0,0,0,0.1);}
.sidebar h5 { font-weight:600; font-size:25px; margin-bottom:20px;}
.sidebar a { display:block; padding:10px 12px; border-radius:8px; color:#3E2723; margin-bottom:10px; text-decoration:none; transition: all 0.3s ease; font-weight:500; font-size:22px;}
.sidebar a:hover { background-color:#ff9800; color:#fff; transform:translateX(5px);}
.sidebar a.active { background: var(--primary2); color:#fff; }

.hero { margin-left:22%; auto; max-width:1150px; border-radius:15px; overflow:hidden; box-shadow:0 6px 20px rgba(0,0,0,0.15);}
.carousel-item img { height:400px; object-fit:cover;}
.section-title { font-size:1.8rem; font-weight:700; color:#3E2723; margin-top:60px; border-left:6px solid #ff9800; padding-left:10px;}
.menu-section { margin-left:20%; max-width:1200px; padding:0 30px;}
.card { border:none; border-radius:16px; transition:0.3s ease-in-out; box-shadow:0 4px 14px rgba(0,0,0,0.1); overflow:hidden; cursor:pointer;}
.card:hover { transform:translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,0.15);}
.card img { height:180px; object-fit:cover;}
.card-body { background-color:#fff; padding:15px;}
.card-title { font-size:1.3rem; font-weight:700; color:#2E1A0F;}
.price { color:#e65100; font-weight:bold;}
.btn-buy { background-color:#ff9800; color:white; border:none; padding:5px 10px; border-radius:8px;}
.btn-buy:hover { background-color:#f57c00;}
.badge-new { position:absolute; top:10px; left:10px; background-color:#ff9800; color:white; font-size:0.75rem; border-radius:5px; padding:3px 7px;}
.badge-unavail { position:absolute; top:10px; right:10px; background-color:#b71c1c; color:white; font-size:0.75rem; border-radius:5px; padding:4px 7px; z-index:10;}
.card.disabled { opacity:0.5; pointer-events:none; box-shadow:none;}
.modal-title { font-size:1.8rem; font-weight:800; color:#2E1A0F;}
.modal-description { font-size:1.1rem; color:#3E2723; margin-bottom:15px;}
.modal-price { font-size:1.3rem; font-weight:700; color:#e65100;}
footer { border-radius:15px; margin-left:22%; max-width:1140px; background-color:#ff9800; color:#fff; text-align:center; padding:15px 0; margin-top:60px;}
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
  <a href="buyer_dashboard.php" class="active"><i class="bi bi-house-door me-2"></i> Home</a>
  <a href="cart.php"><i class="bi bi-cart me-2"></i> My Cart</a>
  <a href="buyers_order.php" ><i class="bi bi-bag-check me-2"></i> My Orders</a>
  <a href="buyer_messages.php"><i class="bi bi-person-circle me-2"></i> messages</a>
  <a href="buyer_notifications.php"><i class="bi bi-bell me-2"></i> Notification</a>
  <a href="profile.php"><i class="bi bi-person-circle me-2"></i> Profile</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right fs-4"></i> Logout</a>
</div>

<div id="heroCarousel" class="carousel slide hero container mt-4 rounded shadow" data-bs-ride="carousel" style="overflow: hidden;">
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="../uploads/burgers_banner.jpeg" class="d-block w-100" alt="Burgers">
      <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
        <h3>Burgers Galore</h3>
        <p>Delicious, juicy, and made fresh daily!</p>
      </div>
    </div>
    <div class="carousel-item">
      <img src="../uploads/fries_banner.jpeg" class="d-block w-100" alt="Fries">
      <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
        <h3>Golden Fries</h3>
        <p>Perfectly crispy and seasoned to perfection.</p>
      </div>
    </div>
    <div class="carousel-item">
      <img src="../uploads/drinks_banner.jpeg" class="d-block w-100" alt="Drinks">
      <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
        <h3>Refreshing Drinks</h3>
        <p>Quench your thirst with our cool beverages.</p>
      </div>
    </div>
  </div>

  <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
  <div class="carousel-indicators mt-2">
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
  </div>
</div>

<div class="menu-section my-5">
  <?php
    $groupedItems = [];
  foreach ($items as $item) {
    $groupedItems[$item['category_name']][] = $item;
  }

  foreach ($groupedItems as $categoryName => $categoryItems): ?>
    <h3 class="section-title"><?= htmlspecialchars($categoryName ?? 'Others') ?></h3>
    <div class="row g-4 mt-3">
      <?php foreach ($categoryItems as $item):
          $isUnavailable = ($item['status'] === 'unavailable');
      ?>
        <div class="col-md-4">
          <div class="card h-100 shadow position-relative <?= $isUnavailable ? 'disabled' : '' ?>" 
            <?= $isUnavailable ? '' : 'data-bs-toggle="modal" data-bs-target="#itemModal'.$item['menu_item_id'].'"' ?>>
            <?php if ($isUnavailable): ?>
              <span class="badge bg-danger position-absolute top-0 end-0 m-2">Unavailable</span>
            <?php endif; ?>
            <?php
            $created = new DateTime($item['created_at']);
            $now = new DateTime();
            if ($now->diff($created)->days <= 7): ?>
              <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">NEW</span>
            <?php endif; ?>

            <?php if (!empty($item['images'])): ?>
              <img src="../uploads/<?= htmlspecialchars($item['images'][0]) ?>" class="card-img-top" style="height:250px; object-fit:cover;">
            <?php else: ?>
              <img src="../placeholder.png" class="card-img-top" style="height:250px; object-fit:cover;">
            <?php endif; ?>

            <div class="card-body">
              <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
              <p class="text-muted small mb-1"><?= htmlspecialchars($item['description']) ?></p>
              <p class="text-muted small mb-2">Seller: 
                <a href="seller_profile.php?id=<?= $item['seller_id'] ?>" class="text-decoration-none">
                  <?= htmlspecialchars($item['seller_name']) ?>
                </a>
              </p>
              <p class="mb-2"><strong>Available:</strong> <?= number_format($item['quantity'] ?? 0) ?></p>
              <p class="price fw-bold">₱<?= number_format($item['price'], 2) ?></p>

              <div id="cartAlert" class="alert alert-success text-center" 
                style="display:none; position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999;">
                Added to cart!
            </div>

              <?php if (!$isUnavailable): ?>
              <form action="add_tocart.php" method="POST" class="d-flex align-items-center mt-2">
                <input type="hidden" name="menu_item_id" value="<?= $item['menu_item_id'] ?>">
              <input type="number" name="quantity" value="1" min="1" max="<?= $item['quantity'] ?? 0 ?>" class="form-control form-control-sm me-2" style="width:80px;">
                <button type="submit" onclick="showAddedToCart()" 
                  class="btn btn-warning flex-grow-1">
                  <i class="bi bi-cart-plus"></i> Add
              </button>
              </form>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!$isUnavailable): ?>
          <div class="modal fade" id="itemModal<?= $item['menu_item_id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title"><?= htmlspecialchars($item['name']) ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <?php if (!empty($item['images'])): ?>
                    <div id="modalCarousel<?= $item['menu_item_id'] ?>" class="carousel slide mb-3" data-bs-ride="carousel">
                      <div class="carousel-inner">
                        <?php foreach ($item['images'] as $index => $img): ?>
                          <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="../uploads/<?= htmlspecialchars($img) ?>" class="d-block w-100" style="height:400px; object-fit:cover;">
                          </div>
                        <?php endforeach; ?>
                      </div>
                      <?php if (count($item['images']) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#modalCarousel<?= $item['menu_item_id'] ?>" data-bs-slide="prev">
                          <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#modalCarousel<?= $item['menu_item_id'] ?>" data-bs-slide="next">
                          <span class="carousel-control-next-icon"></span>
                        </button>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <p class="modal-description"><?= htmlspecialchars($item['description']) ?></p>
                  <p><strong>Seller:</strong> 
                    <a href="seller_profile.php?id=<?= $item['seller_id'] ?>"><?= htmlspecialchars($item['seller_name']) ?></a>
                  </p>
                  <p><strong>Available:</strong> <?= number_format($item['quantity']) ?></p>
                  <p class="modal-price fw-bold">₱<?= number_format($item['price'], 2) ?></p>
                </div>
                <div class="modal-footer">
                  <form action="add_tocart.php" method="POST" class="d-flex align-items-center w-100">
                    <input type="hidden" name="menu_item_id" value="<?= $item['menu_item_id'] ?>">
                    <input type="number" name="quantity" value="1" min="1" max="<?= $item['quantity'] ?>" class="form-control form-control-sm me-2" style="width:80px;">
                    <button type="submit" class="btn btn-warning flex-grow-1">Add to Cart</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>
<script>
function showAddedToCart() {
    let alertBox = document.getElementById("cartAlert");

    alertBox.style.display = "block";
    alertBox.style.opacity = "1";

    setTimeout(() => {
        alertBox.style.opacity = "0";
        setTimeout(() => alertBox.style.display = "none", 5000);
    }, 1500);
}
</script>



<footer>
  &copy; <?= date("Y") ?> CampusTreats | All rights reserved.
</footer>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
