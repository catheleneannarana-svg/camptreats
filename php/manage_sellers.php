<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seller_user_id'], $_POST['action'])) {
        $seller_user_id = $_POST['seller_user_id'];
        $action = $_POST['action'];

        if ($action === 'approve') {
            $conn->prepare("UPDATE users SET role='seller', approval_status='approved', is_active=1 WHERE user_id=?")
                 ->execute([$seller_user_id]);
            $conn->prepare("INSERT INTO notifications (user_id,type,message,created_at)
                             VALUES (?, 'seller', ?, NOW())")
                 ->execute([$seller_user_id, "Congratulations! Your seller application has been approved."]);
        }

        if ($action === 'reject') {
            $conn->prepare("
                UPDATE users 
                SET role='buyer', approval_status='rejected', is_active=1 
                WHERE user_id=?
            ")->execute([$seller_user_id]);

            $conn->prepare("
                INSERT INTO notifications (user_id, type, message, created_at)
                VALUES (?, 'seller', ?, NOW())
            ")->execute([
                $seller_user_id,
                "Your seller application has been rejected. You may continue using your buyer account."
            ]);
        }


        header("Location: manage_sellers.php");
        exit();
    }

    $statusFilter = $_GET['status'] ?? 'all';

$query = match($statusFilter) {
    'pending' => "
    SELECT u.*
    FROM users u
    WHERE u.approval_status = 'pending'
      AND EXISTS (
          SELECT 1 FROM seller_documents sd 
          WHERE sd.seller_id = u.user_id
      )
",

    'approved' => "
    SELECT *
    FROM users
    WHERE role='seller'
      AND approval_status='approved'
",
    'rejected' => "SELECT * FROM users WHERE approval_status='rejected'",
    default => "
    SELECT u.*
    FROM users u
    WHERE 
        u.approval_status IN ('pending','approved','rejected')
        AND (
            u.role='seller'
            OR EXISTS (
                SELECT 1 FROM seller_documents sd 
                WHERE sd.seller_id = u.user_id
            )
        )
",
};

$sellers = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Sellers | CampusTreats</title>
<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../bootstrap-icons/bootstrap-icons.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background:#fff5e6;
    margin:0;
    padding-top:70px;
}
.navbar {
    height:70px;
    background: linear-gradient(90deg, #ff9800, #f57c00);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    z-index:1050;
}
.navbar-brand { font-weight:700; color:#fff !important; }
.sidebar {
    position: fixed;
    top:70px;
    left:0;
    width:240px;
    height:calc(100vh - 70px);
    background:#f4cbaa;
    padding:20px 15px;
    box-shadow:2px 0 6px rgba(0,0,0,0.15);
}
.sidebar h5 { font-weight:600; font-size:20px; margin-bottom:20px; }
.sidebar a {
    display:block;
    padding:10px 14px;
    border-radius:8px;
    color:#3E2723;
    text-decoration:none;
    margin-bottom:10px;
    font-weight:500;
    transition:.3s;
}
.sidebar a:hover {
    background:#ff9800;
    color:#fff;
    transform:translateX(5px);
}
.main-content {
    margin-left:240px;
    padding:25px;
}
.card {
    border-radius:12px;
}
.card img {
    width:100%;
    height:200px;
    object-fit:cover;
}
.badge { font-size:0.8rem; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="#"><i class="bi bi-basket2-fill me-2"></i>CampusTreats</a>
    <a href="logout.php" class="text-white fs-4"><i class="bi bi-box-arrow-right"></i></a>
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
<h2 class="mb-4"><i class="bi bi-shop"></i> Manage Sellers</h2>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link <?=($statusFilter=='all')?'active':''?>" href="?status=all">All</a></li>
    <li class="nav-item"><a class="nav-link <?=($statusFilter=='pending')?'active':''?>" href="?status=pending">Pending</a></li>
    <li class="nav-item"><a class="nav-link <?=($statusFilter=='approved')?'active':''?>" href="?status=approved">Approved</a></li>
    <li class="nav-item"><a class="nav-link <?=($statusFilter=='rejected')?'active':''?>" href="?status=rejected">Rejected</a></li>
</ul>

<div class="row">
<?php foreach($sellers as $s): ?>
<div class="col-md-4 mb-4">
    <div class="card shadow-sm h-100">
        <img src="../<?= htmlspecialchars($s['profile_picture'] ?? 'uploads/default.png') ?>" alt="Seller" class="card-img-top">
        <div class="card-body">
            <h5><?= htmlspecialchars($s['full_name']) ?></h5>
            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($s['email']) ?></p>

            <span class="badge bg-<?= 
                $s['approval_status']=='approved'?'success':
                ($s['approval_status']=='rejected'?'danger':'warning') ?>">
                <?= ucfirst($s['approval_status']) ?>
            </span>

            <br><br>

            <button class="btn btn-info btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#docsModal<?= $s['user_id'] ?>">
                <i class="bi bi-folder"></i> Documents
            </button>

            <form method="POST">
                <input type="hidden" name="seller_user_id" value="<?= $s['user_id'] ?>">
                <?php if($s['approval_status']=='pending'): ?>
                    <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                    <button name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                <?php elseif($s['approval_status']=='approved'): ?>
                    <button class="btn btn-secondary btn-sm" disabled>Approved</button>
                <?php else: ?>
                    <button class="btn btn-secondary btn-sm" disabled>Rejected</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="docsModal<?= $s['user_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seller Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php
                $stmtDoc = $conn->prepare("SELECT * FROM seller_documents WHERE seller_id=?");
                $stmtDoc->execute([$s['user_id']]);
                $docs = $stmtDoc->fetchAll(PDO::FETCH_ASSOC);

                if($docs):
                    foreach($docs as $d):
                        $folder = match($d['file_type']){
                            'permit' => 'permits/',
                            'valid_id' => 'valid_ids/',
                            'schedule' => 'schedules/',
                        };
                        $filePath = "../uploads/".$folder.$d['file_name'];
                ?>
                <p><strong><?= htmlspecialchars($d['file_type']) ?>:</strong>
                    <a href="<?= $filePath ?>" target="_blank"><?= htmlspecialchars($d['file_name']) ?></a>
                </p>
                <?php endforeach; else: ?>
                <p>No documents uploaded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
