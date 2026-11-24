<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=camptreats","root","");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) die("User not found.");

    if($_SERVER['REQUEST_METHOD'] === 'POST') {

        $full_name   = trim($_POST['full_name']);
        $phone       = trim($_POST['phone']);
        $department  = trim($_POST['department']);
        $course      = trim($_POST['course']);
        $section     = trim($_POST['section']);
        $description = trim($_POST['description']);

        if (!$full_name || !$phone || !$department || !$course || !$section || !$description) {
            echo "<script>alert('Please fill out all fields.'); window.history.back();</script>";
            exit();
        }

        $uploadBase = "../uploads/";

       
        if (empty($_FILES['valid_id']['name'])) {
            echo "<script>alert('Valid ID is required.'); window.history.back();</script>";
            exit();
        }
        if (empty($_FILES['schedule']['name'])) {
            echo "<script>alert('Schedule is required.'); window.history.back();</script>";
            exit();
        }

        
        $stmt = $conn->prepare("
            UPDATE users 
            SET full_name=?, phone=?, department=?, course=?, section=?, description=?, 
                is_seller=1, approval_status='pending', is_active=0 
            WHERE user_id=?
        ");
        $stmt->execute([$full_name, $phone, $department, $course, $section, $description, $user_id]);

      
        $documents = [
            'business_permit' => 'permit',
            'valid_id' => 'valid_id',
            'schedule' => 'schedule'
        ];

        foreach ($documents as $inputName => $type) {
            if (!empty($_FILES[$inputName]['name'])) {
                $fileName = time() . "_{$type}_" . basename($_FILES[$inputName]['name']);
                $folder = $uploadBase . ($type === 'permit' ? "permits/" : ($type === 'valid_id' ? "valid_ids/" : "schedules/"));
                if (!is_dir($folder)) mkdir($folder, 0777, true);
                $filePath = $folder . $fileName;
                move_uploaded_file($_FILES[$inputName]['tmp_name'], $filePath);

                $stmtDoc = $conn->prepare("
                    INSERT INTO seller_documents (seller_id, file_name, file_type, uploaded_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmtDoc->execute([$user_id, $fileName, $type]);
            }
        }

       
        $stmtNotif = $conn->prepare("
            INSERT INTO notifications (user_id, type, message, created_at) 
            VALUES (?, 'buyer', ?, NOW())
        ");
        $message = "You have applied to become a seller. Waiting for admin approval.";
        $stmtNotif->execute([$user_id, $message]);

        echo "<script>
                alert('Request submitted successfully! Waiting for admin approval.');
                window.location.href='profile.php';
              </script>";
        exit();
    }

} catch (PDOException $e) {
    die("DB Error: ".$e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upgrade as Seller | CampusTreats</title>
<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../bootstrap-icons/bootstrap-icons.css">
<style>
body { font-family: 'Poppins', sans-serif; background:#fff5e6; }
.container { max-width:600px; margin:60px auto; padding:25px; background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.1); }
.form-label span { color:red; }
button { background:#ff6b35; color:#fff; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; }
button:hover { background:#e64a19; }
.btn-back { position: absolute; top: 20px; left: 20px; border-radius: 12px; padding: 8px 16px; background: #fff; color: #6d4c41; border: none; font-weight: 500; }
.btn-back:hover { background: #ffe0b2; }
.preview-img { display:none; max-width:150px; margin-top:10px; border-radius:8px; }
</style>
</head>
<body>

<button class="btn-back" onclick="window.history.back()"><i class="bi bi-box-arrow-left"></i> Back</button>

<div class="container">
    <h3 class="mb-4">Upgrade as Seller</h3>

    <form method="POST" enctype="multipart/form-data">

        <div class="mb-3">
            <label class="form-label fw-semibold">Full Name <span>*</span></label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Phone <span>*</span></label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Department <span>*</span></label>
            <select name="department" class="form-control" required>
                <option value="">-- Select Department --</option>
                <option value="SOICT" <?= ($user['department']==='SOICT')?'selected':'' ?>>SOICT</option>
                <option value="SOM" <?= ($user['department']==='SOM')?'selected':'' ?>>SOM</option>
                <option value="COE" <?= ($user['department']==='COE')?'selected':'' ?>>COE</option>
                <option value="CCJE" <?= ($user['department']==='CCJE')?'selected':'' ?>>CCJE</option>
                <option value="SOIT" <?= ($user['department']==='SOIT')?'selected':'' ?>>SOIT</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Course <span>*</span></label>
            <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($user['course'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Year_level/Section <span>*</span></label>
            <input type="text" name="section" class="form-control" value="<?= htmlspecialchars($user['section'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Shop Description <span>*</span> <i>(include your vacant schedule)</i></label>
            <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($user['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Certificate of Registration(COR)<span>*</span></label>
            <input type="file" name="business_permit" class="form-control" accept="image/*" onchange="previewFile(this,'permitPreview')">
            <img id="permitPreview" class="preview-img" alt="Permit Preview">
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">School ID <span>*</span></label>
            <input type="file" name="valid_id" class="form-control" accept="image/*" required onchange="previewFile(this,'idPreview')">
            <img id="idPreview" class="preview-img" alt="ID Preview">
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Schedule <span>*</span></label>
            <input type="file" name="schedule" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png" required onchange="previewFile(this,'schedulePreview')">
            <div id="schedulePreview" style="margin-top:10px;"></div>
            <small class="text-muted">Upload your available schedule (PDF, DOC, or image).</small>
        </div>

        <button type="submit" class="w-100">Submit Request</button>
    </form>
</div>

<script>
function previewFile(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = "";
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileType = file.type;

        if (fileType.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if(preview.tagName === 'IMG'){
                    preview.style.display = 'block';
                    preview.src = e.target.result;
                } else {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '150px';
                    img.style.borderRadius = '8px';
                    preview.appendChild(img);
                }
            }
            reader.readAsDataURL(file);
        } else {
            const fileName = document.createElement("p");
            fileName.textContent = "Selected file: " + file.name;
            preview.appendChild(fileName);
        }
    }
}
</script>

</body>
</html>
