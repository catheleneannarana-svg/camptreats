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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name        = trim($_POST['item_name']);
        $description = trim($_POST['description']);
        $price       = floatval($_POST['price']);
        $category_id = $_POST['category_id'] ?? NULL;
        $quantity    = intval($_POST['quantity']);

        if (!$name || !$description || !$price || !$category_id || $quantity < 0) {
            $error = "All fields are required.";
        } else {

            $stmt = $conn->prepare("
                INSERT INTO menu_items 
                (seller_id, name, description, price, category_id, quantity, created_at, status) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pending')
            ");
            
            $stmt->execute([$seller_id, $name, $description, $price, $category_id, $quantity]);

            $item_id = $conn->lastInsertId();

            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = realpath(__DIR__ . '/../uploads/') . '/';
                $allowed = ['jpg','jpeg','png','gif'];

                foreach ($_FILES['images']['name'] as $key => $filename) {
                    $tmpFile = $_FILES['images']['tmp_name'][$key];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (!in_array($ext, $allowed) || $_FILES['images']['size'][$key] > 2*1024*1024) {
                        continue;
                    }

                    $uniqueName = $item_id . '_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;

                    if (move_uploaded_file($tmpFile, $uploadDir . $uniqueName)) {
                        $stmt = $conn->prepare("INSERT INTO item_images (menu_item_id, image) VALUES (?, ?)");
                        $stmt->execute([$item_id, $uniqueName]);
                    }
                }
            }

            header("Location: my_item.php?success=1");
            exit;
        }
    }

    $stmtNotify = $conn->prepare("SELECT COUNT(*) FROM seller_notifications WHERE user_id = ? AND is_read = 0");
    $stmtNotify->execute([$seller_id]);
    $unreadCount = (int)$stmtNotify->fetchColumn();

    $stmtListNotify = $conn->prepare("SELECT notification_id AS id, message, is_read, created_at FROM seller_notifications WHERE user_id = ? ORDER BY created_at DESC");
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
<title>Add New Item | Seller Dashboard</title>
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

/* Navbar */
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

.main { margin-left:250px; padding:28px; min-height: calc(100vh - 70px - 60px); }

.form-card {
    background: #fff;
    padding: 25px 30px;
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    max-width: 650px;
    margin: 40px auto;
    border-top: 6px solid #ff9800;
    transition: transform 0.3s, box-shadow 0.3s;
}

.form-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 40px rgba(0,0,0,0.18);
}

.form-card h3 {
    margin-bottom: 25px;
    font-weight: 700;
    border-left: 8px solid #ff9800;
    padding-left: 14px;
    font-size: 1.6rem;
    color: #3E2723;
}

input, textarea, select {
    width: 100%;
    padding: 12px 14px;
    margin: 12px 0;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 1rem;
    transition: border-color 0.3s, box-shadow 0.3s;
}

input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: #f57c00;
    box-shadow: 0 0 8px rgba(245,124,0,0.25);
}

textarea { min-height: 100px; resize: vertical; }

button {
    background: linear-gradient(90deg, #ff9800, #f57c00);
    color: #fff;
    padding: 12px 18px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
}

button:hover {
    background: linear-gradient(90deg, #f57c00, #ff9800);
    transform: translateY(-2px);
}

.text-success {
    font-weight: 600;
    margin-bottom: 15px;
}

.text-danger {
    font-weight: 600;
    margin-bottom: 15px;
}

.page-wrapper { display:flex; flex-direction:column; min-height:100vh; }
footer { background: var(--primary1); color:#fff; text-align:center; padding:12px 0; margin-top:auto; border-radius:8px; }

@media(max-width:991px){ .sidebar{display:none;} .main{margin-left:0; width:100%; padding:16px;} }
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
      <h3>Add New Item</h3>

      <?php if(!empty($error)) echo "<p class='text-danger'>$error</p>"; ?>
      <?php if(isset($_GET['success'])) echo "<p class='text-success'>Item added successfully!</p>"; ?>

      <form method="POST" enctype="multipart/form-data">
          <input type="text" name="item_name" placeholder="Item Name" required>
          <textarea name="description" placeholder="Description(put the ingredients used)" required></textarea>
          <input type="number" step="0.01" name="price" placeholder="Price" required>
          <select name="category_id" required>
              <option value="">Select Category</option>
              <option value="1">Drinks</option>
              <option value="2">Snacks</option>
              <option value="3">Meals</option>
          </select>
          <input type="number" step="0.01" name="quantity" placeholder="Quantity" required>
          <input type="file" name="images[]" accept="image/*" multiple required>
          <button type="submit">Add Item</button>
      </form>
  </div>
</div>

<footer>&copy; <?= date("Y") ?> CampusTreats | All rights reserved.</footer>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
