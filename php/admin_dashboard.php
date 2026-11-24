<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $totalSellers = $conn->query("SELECT COUNT(*) FROM users WHERE role='seller'")->fetchColumn();
    $totalUsers   = $conn->query("SELECT COUNT(*) FROM users WHERE role='buyer'")->fetchColumn();
    $totalItems   = $conn->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();

    $recentSellers = $conn->query("
        SELECT user_id, username AS seller_name, email, approval_status, profile_picture, department
        FROM users
        WHERE role = 'seller'
        ORDER BY user_id DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);

    $users = $conn->query("
        SELECT user_id, username, email, role, department, profile_picture
        FROM users
        ORDER BY department, user_id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $usersByDepartment = [];
    foreach ($users as $u) {
        $dept = $u['department'] ?: 'No Department';
        $usersByDepartment[$dept][] = $u;
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>

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
    overflow-y: auto;
}

.sidebar h5 {
    font-weight: 600;
    font-size: 22px;
    margin-bottom: 18px;
}

.sidebar .nav-link {
    display: block;
    padding: 10px 14px;
    border-radius: 8px;
    color: #3E2723 !important;
    margin-bottom: 10px;
    font-weight: 500;
    font-size: 16px;
    transition: .3s;
}

.sidebar .nav-link:hover {
    background-color: #ff9800;
    color: #fff !important;
    transform: translateX(5px);
}

.main-content {
    margin-left: 240px;
    padding: 25px;
}

.dashboard-cards .card {
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,.12);
    transition: .3s;
}

.dashboard-cards .card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 25px rgba(0,0,0,.2);
}

.dashboard-cards h3 {
    font-weight: 700;
    font-size: 2rem;
}

.card-floating {
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,.15);
    transition: .3s;
    overflow: hidden;
}

.card-floating:hover {
    transform: translateY(-5px);
}

.card-img-top {
    height: 200px;
    object-fit: cover;
}

.badge-role {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: .8rem;
    padding: .5em .7em;
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

<h1 class="mb-4"><i class="bi bi-bar-chart"></i> Admin Dashboard</h1>

<div class="row dashboard-cards text-center mb-5">
    <div class="col-md-4 mb-4">
        <div class="card p-4">
            <h3><?= $totalSellers ?></h3>
            <p>Sellers</p>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card p-4">
            <h3><?= $totalUsers ?></h3>
            <p>Buyers</p>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card p-4">
            <h3><?= $totalItems ?></h3>
            <p>Menu Items</p>
        </div>
    </div>
</div>

<h4 class="mb-3"><i class="bi bi-shop"></i> Recent Sellers</h4>

<div id="recentSellersCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
<div class="carousel-inner">

<?php foreach (array_chunk($recentSellers, 3) as $i => $chunk): ?>
<div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
    <div class="row">
        <?php foreach ($chunk as $s): ?>
        <div class="col-md-4 mb-4">
            <div class="card-floating position-relative text-center">
                <span class="badge bg-success badge-role"><?= ucfirst($s['approval_status']) ?></span>
                <img src="../<?= htmlspecialchars($s['profile_picture'] ?? 'uploads/default.png') ?>" class="card-img-top">
                <div class="card-body">
                    <h6><?= htmlspecialchars($s['seller_name']) ?></h6>
                    <p><?= htmlspecialchars($s['email']) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

</div>

<button class="carousel-control-prev" type="button" data-bs-target="#recentSellersCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
</button>
<button class="carousel-control-next" type="button" data-bs-target="#recentSellersCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
</button>
</div>

<h4 class="mt-5 mb-3"><i class="bi bi-people"></i> Users by Department</h4>

<?php foreach ($usersByDepartment as $dept => $group): ?>
<h5 class="mt-4"><?= htmlspecialchars($dept) ?></h5>

<div id="dept<?= md5($dept) ?>" class="carousel slide mb-5" data-bs-ride="carousel">
<div class="carousel-inner">

<?php foreach (array_chunk($group, 3) as $i => $chunk): ?>
<div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
    <div class="row">
        <?php foreach ($chunk as $u): ?>
        <div class="col-md-4 mb-4">
            <div class="card-floating text-center position-relative">
                <span class="badge badge-role <?= $u['role']=='seller'?'bg-success':'bg-primary' ?>">
                    <?= ucfirst($u['role']) ?>
                </span>
                <img src="../<?= htmlspecialchars($u['profile_picture'] ?? 'uploads/default.png') ?>" class="card-img-top">
                <div class="card-body">
                    <h6><?= htmlspecialchars($u['username']) ?></h6>
                    <p><?= htmlspecialchars($u['email']) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

</div>

<button class="carousel-control-prev" type="button" data-bs-target="#dept<?= md5($dept) ?>" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
</button>
<button class="carousel-control-next" type="button" data-bs-target="#dept<?= md5($dept) ?>" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
</button>
</div>
<?php endforeach; ?>

</div>
</div>

<script src="../bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
