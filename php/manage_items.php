<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $items = $conn->query("
        SELECT m.menu_item_id, m.name, m.price, m.category_id, m.status, 
               c.category_name
        FROM menu_items m
        LEFT JOIN categories c ON m.category_id = c.category_id
        ORDER BY m.menu_item_id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $imagesStmt = $conn->prepare("SELECT image FROM item_images WHERE menu_item_id=?");

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Items | CampusTreats</title>

<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../bootstrap-icons/bootstrap-icons.css">

<style>
body {
    background-color: #fffaf2;
    font-family: 'Poppins', sans-serif;
    color: #3E2723;
    margin: 0;
    padding-top: 70px;
}

.navbar {
    height: 70px;
    background: linear-gradient(90deg, #ff9800, #f57c00);
    box-shadow: 0 4px 10px rgba(0,0,0,.1);
    z-index: 1050;
}
.navbar-brand {
    font-weight: 700;
    color: #fff !important;
}

.sidebar {
    position: fixed;
    top: 70px;
    left: 0;
    width: 240px;
    height: calc(100vh - 70px);
    background-color: #f4cbaa;
    padding: 20px 15px;
    box-shadow: 2px 0 6px rgba(0,0,0,.15);
}
.sidebar h5 {
    font-weight: 600;
    font-size: 20px;
    margin-bottom: 20px;
}
.sidebar a {
    display: block;
    padding: 10px 14px;
    border-radius: 8px;
    color: #3E2723;
    text-decoration: none;
    margin-bottom: 10px;
    font-weight: 500;
    transition: .3s;
}
.sidebar a:hover {
    background-color: #ff9800;
    color: #fff;
    transform: translateX(5px);
}

.main-content {
    margin-left: 240px;
    padding: 25px;
}

.card-item {
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,.1);
    background: #fff;
    overflow: hidden;
    transition: transform .2s;
    height: 100%;
}
.card-item:hover {
    transform: translateY(-6px);
}
.carousel-inner img {
    height: 180px;
    object-fit: cover;
}
.card-title {
    font-size: 1.15rem;
    font-weight: 600;
}
.card-text {
    font-size: 0.95rem;
}

.carousel-control-prev,
.carousel-control-next {
    width: 8%;
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="#">
            <i class="bi bi-basket2-fill me-2"></i>CampusTreats
        </a>
        <a href="logout.php" class="nav-link text-white">
            <i class="bi bi-box-arrow-right fs-4"></i>
        </a>
    </div>
</nav>

<div class="sidebar">
    <h5><i class="bi bi-speedometer2"></i> Dashboard</h5>

    <a class="nav-link" href="admin_dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
    <a class="nav-link" href="manage_users.php"><i class="bi bi-people"></i> Manage Users</a>
    <a class="nav-link" href="manage_sellers.php"><i class="bi bi-shop"></i> Manage Sellers</a>
    <a class="nav-link" href="manage_items.php"><i class="bi bi-egg-fried"></i> Manage Items</a>
    <a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="main-content">
<div class="container-fluid">

<h2 class="mb-4"><i class="bi bi-egg-fried me-2"></i>Menu Items</h2>

<div class="row">
<?php foreach($items as $item): ?>
<div class="col-lg-4 col-md-6 mb-4">
<div class="card-item h-100">

<div id="carousel<?= $item['menu_item_id'] ?>" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php
        $imagesStmt->execute([$item['menu_item_id']]);
        $images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$images):
        ?>
            <div class="carousel-item active">
                <img src="../uploads/no-image.png" class="d-block w-100" alt="No image">
            </div>
        <?php
        else:
            $first = true;
            foreach ($images as $img):
        ?>
            <div class="carousel-item<?= $first ? ' active' : '' ?>">
                <img src="../uploads/<?= htmlspecialchars($img['image']) ?>" class="d-block w-100">
            </div>
        <?php
            $first = false;
            endforeach;
        endif;
        ?>
    </div>

    <?php if (count($images) > 1): ?>
    <button class="carousel-control-prev" type="button"
        data-bs-target="#carousel<?= $item['menu_item_id'] ?>" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button"
        data-bs-target="#carousel<?= $item['menu_item_id'] ?>" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
    <?php endif; ?>
</div>

<div class="card-body">
    <div class="card-title"><?= htmlspecialchars($item['name']) ?></div>
    <div class="card-text">Category: <?= htmlspecialchars($item['category_name']) ?></div>
    <div class="card-text fw-bold">₱<?= number_format($item['price'],2) ?></div>

    <div class="mt-2">
        <?php if ($item['status'] === 'approved'): ?>
            <span class="badge bg-success">Approved</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark">Pending</span>
        <?php endif; ?>
    </div>

    <div class="mt-3">
        <?php if ($item['status'] === 'pending'): ?>
            <a href="approve_item.php?id=<?= $item['menu_item_id'] ?>" class="btn btn-success btn-sm">
                Approve
            </a>
        <?php else: ?>
            <button class="btn btn-secondary btn-sm" disabled>Approved</button>
        <?php endif; ?>
    </div>
</div>

</div>
</div>
<?php endforeach; ?>
</div>

</div>
</div>

<script src="../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
