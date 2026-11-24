<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.html");
    exit();
}

$seller_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die("No item specified!");
}

$item_id = $_GET['id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE menu_item_id = ? AND seller_id = ?");
    $stmt->execute([$item_id, $seller_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Item not found!");
    }

    $stmt2 = $conn->prepare("SELECT * FROM item_images WHERE menu_item_id = ?");
    $stmt2->execute([$item_id]);
    $images = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $stmtNotify = $conn->prepare("SELECT COUNT(*) FROM seller_notifications WHERE user_id = ? AND is_read = 0");
    $stmtNotify->execute([$seller_id]);
    $unreadCount = (int)$stmtNotify->fetchColumn();

    $stmtListNotify = $conn->prepare("SELECT notification_id AS id, message, is_read, created_at FROM seller_notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmtListNotify->execute([$seller_id]);
    $notifications = $stmtListNotify->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $price       = floatval($_POST['price']);
    $category_id = $_POST['category_id'] ?? NULL;
    $new_quantity = intval($_POST['quantity']); 

    try {
        $status = $new_quantity <= 0 ? 'unavailable' : 'approved';

        $stmt = $conn->prepare("
            UPDATE menu_items
            SET name = ?, description = ?, price = ?, category_id = ?, quantity = ?, status = ?
            WHERE menu_item_id = ? AND seller_id = ?
        ");
        $stmt->execute([$name, $description, $price, $category_id, $new_quantity, $status, $item_id, $seller_id]);

        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = realpath(__DIR__ . '/../uploads/') . "/";
            foreach ($_FILES['images']['name'] as $key => $filename) {
                $tmpFile = $_FILES['images']['tmp_name'][$key];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $uniqueName = $item_id . '_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
                if (move_uploaded_file($tmpFile, $uploadDir . $uniqueName)) {
                    $stmtImg = $conn->prepare("INSERT INTO item_images (menu_item_id, image) VALUES (?, ?)");
                    $stmtImg->execute([$item_id, $uniqueName]);
                }
            }
        }

        header("Location: my_item.php?success=2");
        exit();

    } catch (PDOException $e) {
        $error = "Error updating item: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Item</title>
<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../bootstrap-icons/bootstrap-icons.css">
<style>
:root {
    --primary1: #ff9800;
    --primary2: #f57c00;
    --bg: #fffaf2;
    --text: #3E2723;
}
body { margin:0; font-family:'Poppins', sans-serif; background: var(--bg); color: var(--text); padding-top:70px; }
.navbar { background: linear-gradient(90deg, var(--primary1), var(--primary2)); box-shadow: 0 4px 10px rgba(0,0,0,0.1); position: fixed; top:0; left:0; right:0; z-index:1030; }
.navbar .navbar-brand { color:#fff; font-weight:700; font-size:1.2rem; }
.sidebar { position: fixed; top: 65px; left: 0; width: 230px; height: 100vh; background-color: #f4cbaa; padding: 25px 15px; box-shadow: 2px 0 6px rgba(0,0,0,0.08); overflow-y: auto; }
.sidebar h5 { font-weight:600; font-size:22px; margin-bottom:18px; color:var(--text); }
.sidebar a { display:block; padding: 10px 12px; border-radius: 8px; color: var(--text); margin-bottom: 8px; text-decoration:none; transition: all .2s ease; font-size: 20px; }
.sidebar a i { margin-right: 10px; }
.sidebar a:hover { background: var(--primary1); color: #fff; transform: translateX(6px); }
.sidebar a.active { background: var(--primary2); color:#fff; }
.main { margin-left: 260px; margin-top: 80px; width: 80%; }
.form-card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 6px 18px rgba(0,0,0,0.1); }
.form-card h3 { font-weight: 700; color: var(--text); }
input, textarea, select { border-radius: 8px !important; padding: 10px !important; }
button { background: var(--primary1); border: none; padding: 10px 20px; font-weight: 600; border-radius: 10px; color: white; }
button:hover { background: var(--primary2); }
.img-preview { width: 90px; height: 90px; object-fit: cover; border-radius: 10px; margin: 5px; }
footer { background: var(--primary1); color: #fff; text-align: center; padding: 12px; margin-top: auto; }
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
            <span id="sellerNotifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unreadCount ?></span>
          <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end p-2 dropdown-menu-notif" aria-labelledby="sellerNotifBtn">
          <?php if (empty($notifications)): ?>
            <li class="text-center text-muted">No notifications</li>
          <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
              <li class="dropdown-item dropdown-item-notif <?= $notif['is_read'] ? '' : 'fw-bold' ?>">
                <?= htmlspecialchars($notif['message']) ?>
                <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($notif['created_at'])) ?></small>
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
  <a href="add_item.php" class="active"><i class="bi bi-plus-square me-1"></i> Add Item</a>
  <a href="my_orders.php"><i class="bi bi-bag-check me-1"></i> Orders</a>
  <a href="messages.php"><i class="bi bi-chat-left-dots me-1"></i> Messages</a>
  <a href="seller_notifications.php"><i class="bi bi-bell me-1"></i> Notifications
    <?php if($unreadCount > 0): ?><span class="badge bg-danger ms-2"><?= $unreadCount ?></span><?php endif; ?>
  </a>
  <a href="profile.php"><i class="bi bi-person-circle me-1"></i> Profile</a>
  <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
</div>

<div class="main">
    <div class="form-card">
        <h3>Edit Item</h3>
        <hr>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <label>Item Name</label>
            <input type="text" name="item_name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>" required>

            <label>Description</label>
            <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($item['description']) ?></textarea>

            <label>Price</label>
            <input type="number" name="price" class="form-control" step="0.01" value="<?= $item['price'] ?>" required>

            <label>Category</label>
            <select name="category_id" class="form-control" required>
                <option value="">Select Category</option>
                <option value="1" <?= $item['category_id']==1?'selected':'' ?>>Drinks</option>
                <option value="2" <?= $item['category_id']==2?'selected':'' ?>>Snacks</option>
                <option value="3" <?= $item['category_id']==3?'selected':'' ?>>Meals</option>
            </select>

            <label class="mt-3">Stock Quantity</label>
            <input type="number" name="quantity" class="form-control" min="0" value="<?= $item['quantity'] ?>" required>

            <label class="mt-3">Existing Images</label><br>
            <?php foreach($images as $img): ?>
                <img src="../uploads/<?= $img['image'] ?>" class="img-preview">
            <?php endforeach; ?>

            <label class="mt-3">Upload New Images</label>
            <input type="file" name="images[]" multiple class="form-control">

            <button type="submit" class="mt-4">Update Item</button>
        </form>
    </div>
</div>

<footer>&copy; <?= date("Y") ?> CampusTreats. All Rights Reserved.</footer>
</div>
<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
