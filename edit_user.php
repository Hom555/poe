<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "12345678";
$database = "dro";

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$title = "แก้ไขข้อมูลผู้ใช้งาน";
include 'header.php';

// Initialize variables
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$errors = [];
$user = null;

// Fetch user data
if ($user_id) {
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']); // เพิ่มที่อยู่

    // Validate inputs
    if (empty($username)) {
        $errors[] = "ชื่อผู้ใช้งานห้ามเว้นว่าง";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "อีเมลไม่ถูกต้อง";
    }
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก";
    }

    // Update user data if no errors
    if (empty($errors)) {
        $update_sql = "UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssi", $username, $email, $phone, $address, $user_id);

        if ($stmt->execute()) {
            echo "<script>
                alert('อัพเดทข้อมูลเรียบร้อย');
                window.location.href = 'yaz.php';
            </script>";
        } else {
            $errors[] = "เกิดข้อผิดพลาดในการอัพเดทข้อมูล";
        }
        $stmt->close();
    }
}
?>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">แก้ไขข้อมูลผู้ใช้งาน</h4>
    </div>
    <div class="card-body">
        <?php if ($user): ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้งาน</label>
                            <input type="text" id="username" name="username" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" id="email" name="email" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" id="phone" name="phone" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="address" class="form-label">ที่อยู่</label>
                            <textarea id="address" name="address" 
                                      class="form-control" 
                                      rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                    <a href="yaz.php" class="btn btn-secondary">ยกเลิก</a>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                ไม่พบข้อมูลผู้ใช้งาน
                <a href="yaz.php" class="btn btn-secondary ms-3">กลับหน้าหลัก</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>