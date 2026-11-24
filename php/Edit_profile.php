<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
  $conn = new PDO("mysql:host=localhost;dbname=camptreats", "root", "");
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $description = trim($_POST['description']);
    $department = trim($_POST['department']);
    $course = trim($_POST['course']);
    $section = trim($_POST['section']);

    $profile_picture = null;
    if (!empty($_FILES['profile_picture']['name'])) {
      $target_dir = "../uploads/";
      if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

      $file_name = uniqid() . "_" . basename($_FILES["profile_picture"]["name"]);
      $target_file = $target_dir . $file_name;
      $db_path = "uploads/" . $file_name;

      $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
      if (in_array($ext, ['jpg','jpeg','png','gif']) && move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
        $stmt_old = $conn->prepare("SELECT profile_picture FROM users WHERE user_id=?");
        $stmt_old->execute([$user_id]);
        $old = $stmt_old->fetch(PDO::FETCH_ASSOC);
        if ($old && !empty($old['profile_picture']) && file_exists('../'.$old['profile_picture'])) unlink('../'.$old['profile_picture']);
        $profile_picture = $db_path;
      }
    }

    if ($profile_picture) {
      $stmt = $conn->prepare("UPDATE users SET full_name=?, description=?, department=?, course=?, section=?, profile_picture=? WHERE user_id=?");
      $stmt->execute([$full_name, $description, $department, $course, $section, $profile_picture, $user_id]);
    } else {
      $stmt = $conn->prepare("UPDATE users SET full_name=?, description=?, department=?, course=?, section=? WHERE user_id=?");
      $stmt->execute([$full_name, $description, $department, $course, $section, $user_id]);
    }

    header("Location: profile.php?updated=1");
    exit();
  }

  $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$user) die("User not found.");

} catch (PDOException $e) {
  die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile | CampusTreats</title>
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
      background: linear-gradient(90deg, #ff9800, #f57c00);
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);

    }
    .navbar-brand {
      font-weight: 700;
      color: #fff !important;
    }
    .nav-link {
      color: #fff !important;
      font-weight: 500;
    }
    .sidebar {
      position: fixed;
      top: 45px;
      left: 0;
      width: 230px;
      height: 100%;
      background-color: #f4cbaa;
      padding: 25px 15px;
      box-shadow: 2px 0 6px rgba(0,0,0,0.1);
    }
    .sidebar h5 {
      font-weight: 600;
      font-size: 25px;
      margin-bottom: 20px;
    }
    .sidebar a {
      display: block;
      padding: 10px 12px;
      border-radius: 8px;
      color: #3E2723;
      margin-bottom: 10px;
      text-decoration: none;
      transition: all 0.3s ease;
      font-weight: 500;
      font-size: 22px;

    }
    .sidebar a:hover {
      background-color: #ff9800;
      color: #fff;
      transform: translateX(5px);
    }
  .profile-container { 
    max-width:900px; 
    margin:60px auto; 
    background:#fff; 
    border-radius:20px; 
    box-shadow:0 10px 25px rgba(0,0,0,0.1); 
    overflow:hidden; 
  }
  .profile-header { 
    text-align:center; 
    background:linear-gradient(135deg,#ff9800,#ff6b35); 
    color:#fff; 
    padding:50px 20px; 
  }
  .profile-header img { 
    width:140px; 
    height:140px; 
    border-radius:50%; 
    object-fit:cover; 
    border:4px solid #fff; 
    margin-bottom:15px; 
  }
  .profile-header h3 { 
    font-weight:600; 
  }
  .profile-header p { 
    font-size:0.95rem; 
    opacity:0.85; 
  }
  .profile-body { 
    padding:30px; 
  }
  .form-control { 
    border-radius:12px; 
  }
  .btn-save { 
    background:#ff9800; 
    color:#fff; 
    border:none; 
    border-radius:12px; 
    padding:10px 25px;
    font-weight:500;
  }
  .btn-save:hover { 
    background:#f57c00; 
  }
  .btn-cancel { 
    background:#ddd; 
    color:#333; 
    border:none; 
    border-radius:12px; 
    padding:10px 25px; 
    font-weight:500; 
  }
  .btn-cancel:hover { 
    background:#ccc; 
  }
</style>
</head>
<body>
  

  <div class="profile-container">
    <div class="profile-header">
      <h3>Edit Profile</h3>
    </div>
    <div class="profile-body">
      <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label fw-semibold">Full Name</label>
          <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold"><i>include your Vacant sched.</i>Description</label>
          <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($user['description']) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Department</label>
          <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($user['department'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Course</label>
          <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($user['course'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Year_level/Section</label>
          <input type="text" name="section" class="form-control" value="<?= htmlspecialchars($user['section'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Profile Picture</label>
          <input type="file" name="profile_picture" class="form-control" accept="image/*">
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn-save w-50"><i class="bi bi-save"></i> Save Changes</button>
          <button type="button" class="btn-cancel w-50" onclick="window.location.href='profile.php'">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
