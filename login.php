<?php
session_start();
include 'condb.php';

// ถ้ามีการล็อกอินอยู่แล้ว ให้ redirect ไปหน้าที่เหมาะสม
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] == 1) {  // 1 = admin
        header("Location: admin_orders.php");
    } else {  // 2 = user
        header("Location: sh_product.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if ($password === $user['password']) {
            if ($user['status'] == 0) {
                $error = "บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ";
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['status']; // 1 = admin, 2 = user

                if ($user['status'] == 1) {
                    header("Location: yaz.php");
                } else {
                    header("Location: sh_product.php");
                }
                exit();
            }
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบอีเมลนี้ในระบบ";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .card-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
        }
        .btn {
            border-radius: 8px;
            padding: 12px;
        }
        .social-login {
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card">
            <div class="card-header text-center">
                <h4 class="mb-0">เข้าสู่ระบบ</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['register_success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['register_success'];
                        unset($_SESSION['register_success']); 
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">อีเมล</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="example@email.com" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="รหัสผ่านอย่างน้อย 6 ตัวอักษร" required>
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">จดจำฉัน</label>
                        <a href="#" class="float-end">ลืมรหัสผ่าน?</a>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <p>ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
                </div>

                <div class="social-login text-center">
                    <p class="text-muted">หรือเข้าสู่ระบบด้วย</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-outline-primary">
                            <i class="fab fa-facebook"></i>
                        </button>
                        <button class="btn btn-outline-danger">
                            <i class="fab fa-google"></i>
                        </button>
                        <button class="btn btn-outline-dark">
                            <i class="fab fa-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html> 