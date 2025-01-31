<?php
session_start();
include 'condb.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// อัพเดทข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $province = trim($_POST['province']);
    $district = trim($_POST['district']);
    $subdistrict = trim($_POST['subdistrict']);
    $zipcode = trim($_POST['zipcode']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $errors = [];

    // ตรวจสอบรหัสผ่านปัจจุบัน
    if (!empty($current_password)) {
        if ($current_password === $user['password']) {
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $errors[] = "รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร";
                }
            }
        } else {
            $errors[] = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
        }
    }

    if (empty($errors)) {
        // อัพเดทข้อมูล
        $update_sql = "UPDATE users SET 
            username = ?, 
            email = ?, 
            phone = ?, 
            name = ?,
            address = ?,
            province = ?,
            district = ?,
            subdistrict = ?,
            zipcode = ?
            WHERE user_id = ?";
            
        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("sssssssssi",
                $username,
                $email,
                $phone,
                $name,
                $address,
                $province,
                $district,
                $subdistrict,
                $zipcode,
                $user_id
            );
            
            if ($stmt->execute()) {
                // อัพเดทรหัสผ่านถ้ามีการเปลี่ยน
                if (!empty($new_password)) {
                    $pass_sql = "UPDATE users SET password = ? WHERE user_id = ?";
                    $pass_stmt = $conn->prepare($pass_sql);
                    if ($pass_stmt) {
                        $pass_stmt->bind_param("si", $new_password, $user_id);
                        $pass_stmt->execute();
                        $pass_stmt->close();
                    }
                }
                
                $_SESSION['username'] = $username;
                echo "<script>
                    alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                    window.location = 'profile.php';
                </script>";
                exit();
            }
            $stmt->close();
        }
        $errors[] = "ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลของฉัน</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- เพิ่ม style ที่เราได้แก้ไขก่อนหน้านี้ -->
    <style>
        body {
            background: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%);
            font-family: 'Prompt', sans-serif;
            min-height: 100vh;
        }

        .profile-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            padding: 30px;
            border: none;
            position: relative;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='rgba(255,255,255,0.1)' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .card-header h4 {
            color: white;
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
            position: relative;
        }

        .card-body {
            padding: 40px;
        }

        .form-label {
            color: #495057;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-group {
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .input-group-text {
            background-color: white;
            border: 2px solid #e9ecef;
            border-right: none;
            color: #0d6efd;
            padding: 0.6rem 1rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-left: none;
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
        }

        .input-group:hover .input-group-text,
        .input-group:hover .form-control {
            border-color: #0d6efd;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }

        textarea.form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
        }

        .btn {
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .alert-danger {
            background-color: #fff5f5;
            color: #dc3545;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.1);
        }

        .text-muted {
            font-size: 0.85rem;
            color: #6c757d !important;
        }

        hr {
            opacity: 0.1;
            margin: 2rem 0;
        }

        @media (max-width: 768px) {
            .profile-container {
                margin: 20px auto;
            }
            .card-header {
                padding: 20px;
            }
            .card-body {
                padding: 20px;
            }
            .card-header h4 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">ข้อมูลของฉัน</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">ชื่อผู้ใช้</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" name="username" 
                                           value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">อีเมล</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">เบอร์โทรศัพท์</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="tel" class="form-control" name="phone" 
                                           value="<?= htmlspecialchars($user['phone']) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">ชื่อ-นามสกุล</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" name="name" 
                                           value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">ที่อยู่</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">จังหวัด</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="province" 
                                       value="<?= htmlspecialchars($user['province'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">เขต/อำเภอ</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="district" 
                                       value="<?= htmlspecialchars($user['district'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">แขวง/ตำบล</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="subdistrict" 
                                       value="<?= htmlspecialchars($user['subdistrict'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">รหัสไปรษณีย์</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="zipcode" 
                                       value="<?= htmlspecialchars($user['zipcode'] ?? '') ?>">
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">รหัสผ่านปัจจุบัน</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" name="current_password">
                                </div>
                                <small class="text-muted">กรอกเฉพาะเมื่อต้องการเปลี่ยนรหัสผ่าน</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">รหัสผ่านใหม่</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" class="form-control" name="new_password">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                                </button>
                                <a href="sh_product.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> กลับ
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>