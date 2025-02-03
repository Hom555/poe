<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'] ?? 0;
$errors = [];

// ดึงข้อมูลผู้ใช้
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: users.php");
    exit();
}

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $status = $_POST['status'];
    $new_password = trim($_POST['new_password']);
    
    // ตรวจสอบข้อมูล
    if (empty($username)) {
        $errors[] = "กรุณากรอกชื่อผู้ใช้";
    }
    if (empty($email)) {
        $errors[] = "กรุณากรอกอีเมล";
    }
    if (!empty($new_password) && strlen($new_password) < 6) {
        $errors[] = "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
    }
    
    if (empty($errors)) {
        // อัพเดทข้อมูล
        if (!empty($new_password)) {
            $sql = "UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    phone = ?,
                    status = ?,
                    password = ?
                    WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssisi", $username, $email, $phone, $status, $new_password, $user_id);
        } else {
            $sql = "UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    phone = ?,
                    status = ?
                    WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssii", $username, $email, $phone, $status, $user_id);
        }
        
        if ($stmt->execute()) {
            echo "<script>
                alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                window.location.href = 'users.php';
            </script>";
            exit();
        } else {
            $errors[] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
}

$title = "แก้ไขข้อมูลผู้ใช้";
include 'header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">แก้ไขข้อมูลผู้ใช้</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ใช้</label>
                    <input type="text" name="username" class="form-control" 
                           value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">อีเมล</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">เบอร์โทร</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?= htmlspecialchars($user['phone']) ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">สถานะ</label>
                    <select name="status" class="form-select" 
                            <?= ($user['user_id'] == $_SESSION['user_id']) ? 'disabled' : '' ?>>
                        <option value="2" <?= ($user['status'] == 2) ? 'selected' : '' ?>>
                            ผู้ใช้งานทั่วไป
                        </option>
                        <option value="1" <?= ($user['status'] == 1) ? 'selected' : '' ?>>
                            ผู้ดูแลระบบ
                        </option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" class="form-control">
                    <small class="text-muted">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</small>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                    <a href="users.php" class="btn btn-secondary">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>