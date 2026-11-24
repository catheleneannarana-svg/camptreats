<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM users ORDER BY role DESC, full_name ASC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | CampusTreats</title>

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
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
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
    box-shadow: 2px 0 6px rgba(0,0,0,0.15);
    overflow-y: auto;
}

.sidebar h5 {
    font-weight: 600;
    font-size: 22px;
    margin-bottom: 18px;
}

.sidebar a {
    display: block;
    padding: 10px 14px;
    border-radius: 8px;
    color: #3E2723;
    margin-bottom: 10px;
    text-decoration: none;
    font-weight: 500;
    font-size: 16px;
    transition: 0.3s;
}

.sidebar a:hover {
    background-color: #ff9800;
    color: #fff;
    transform: translateX(5px);
}

.main-content {
    margin-left: 240px; /* same as sidebar width */
    padding: 25px;
}

.card-user {
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    overflow: hidden;
    background: #fff;
    transition: 0.3s;
    height: 100%;
}

.card-user:hover {
    transform: translateY(-5px);
}

.card-user img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.card-user .card-body {
    padding: 15px;
}

.card-title {
    font-size: 1.15rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.card-text {
    font-size: 0.95rem;
    margin-bottom: 8px;
}

.badge-role {
    font-size: 0.8rem;
    padding: 6px 10px;
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="#">
      <i class="bi bi-basket2-fill me-2"></i>CampusTreats
    </a>
  </div>
</nav>

<div class="sidebar">
  <h5><i class="bi bi-speedometer2"></i> Dashboard</h5>

  <a href="admin_dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
  <a href="manage_users.php"><i class="bi bi-people"></i> Manage Users</a>
  <a href="manage_sellers.php"><i class="bi bi-shop"></i> Manage Sellers</a>
  <a href="manage_items.php"><i class="bi bi-egg-fried"></i> Manage Items</a>
  <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="main-content">
<div class="container-fluid">

<h2 class="mb-4"><i class="bi bi-people"></i> Users</h2>

<div class="row">
<?php foreach ($users as $u): ?>
    <div class="col-md-4 mb-4">
        <div class="card-user">
            <img src="../<?= htmlspecialchars($u['profile_picture'] ?? 'uploads/default.png') ?>" alt="Profile">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($u['full_name']) ?></h5>
                <p class="card-text">
                    <strong>Email:</strong> <?= htmlspecialchars($u['email']) ?>
                </p>
                <span class="badge bg-<?= 
                    $u['role']=='admin' ? 'primary' :
                    ($u['role']=='seller' ? 'success' : 'secondary')
                ?> badge-role">
                    <?= ucfirst($u['role']) ?>
                </span>
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
