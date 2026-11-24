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
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $old_pic = $_POST['old_profile_pic'] ?? '';

    $profile_pic = $old_pic;

    if (!empty($_FILES['profile_pic']['tmp_name']) && is_uploaded_file($_FILES['profile_pic']['tmp_name'])) {
        if ($_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $check = getimagesize($_FILES['profile_pic']['tmp_name']);
            if ($check !== false) {
                $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext, $allowed)) {
                    $uploadDir = __DIR__ . '/../uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $newName = 'pfp_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadDir . $newName)) {
                        $profile_pic = $newName;
                        if (!empty($old_pic) && $old_pic !== 'default.png' && file_exists($uploadDir . $old_pic)) {
                            @unlink($uploadDir . $old_pic);
                        }
                    }
                }
            }
        }
    }

    $sql = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, profile_pic=? WHERE user_id=?");
    $sql->execute([$full_name, $email, $phone, $profile_pic, $user_id]);

    echo "<script>
            alert('Profile successfully updated!');
            window.location.href = 'profile.php';
          </script>";
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
