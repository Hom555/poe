<?php
session_start();
include 'condb.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
if (!isset($_SESSION["strProductID"]) || count($_SESSION["strProductID"]) == 0) {
    echo "<script>
        alert('ตะกร้าสินค้าว่างเปล่า! กลับไปเลือกสินค้า');
        window.location.href = 'sh_product.php';
    </script>";
    exit();
}

// ตรวจสอบการลบสินค้า
if (isset($_GET['remove'])) {
    $key = intval($_GET['remove']);
    unset($_SESSION["strProductID"][$key]);
    unset($_SESSION["strQty"][$key]);

    // จัดเรียง index ใหม่
    $_SESSION["strProductID"] = array_values($_SESSION["strProductID"]);
    $_SESSION["strQty"] = array_values($_SESSION["strQty"]);
    
    header("Location: cart.php");
    exit();
}

// ตรวจสอบการเพิ่ม/ลดจำนวนสินค้า
if (isset($_GET['changeQty']) && isset($_GET['key'])) {
    $key = intval($_GET['key']);
    $change = $_GET['changeQty'];

    if ($change == 'add') {
        $_SESSION["strQty"][$key]++;
    } elseif ($change == 'sub' && $_SESSION["strQty"][$key] > 1) {
        $_SESSION["strQty"][$key]--;
    }
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .quantity-control .btn {
            padding: 2px 8px;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">ร้านค้า</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="sh_product.php">กลับไปเลือกสินค้า</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- ตะกร้าสินค้า -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>ตะกร้าสินค้า
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>สินค้า</th>
                                        <th class="text-center">ราคา</th>
                                        <th class="text-center">จำนวน</th>
                                        <th class="text-end">รวม</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total = 0;
                                    foreach ($_SESSION["strProductID"] as $key => $productId) {
                                        $sql = "SELECT * FROM product WHERE po_id = ?";
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("s", $productId);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $row = $result->fetch_assoc();

                                        $qty = $_SESSION["strQty"][$key];
                                        $subtotal = $row['price'] * $qty;
                                        $total += $subtotal;
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="img/<?= $row['image'] ?>" class="product-image me-3">
                                                    <div>
                                                        <h6 class="mb-0"><?= $row['po_name'] ?></h6>
                                                        <small class="text-muted">รหัส: <?= $row['po_id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center"><?= number_format($row['price'], 2) ?></td>
                                            <td class="text-center">
                                                <div class="quantity-control justify-content-center">
                                                    <a href="?changeQty=sub&key=<?= $key ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-minus"></i>
                                                    </a>
                                                    <span class="mx-2"><?= $qty ?></span>
                                                    <a href="?changeQty=add&key=<?= $key ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                            <td class="text-end"><?= number_format($subtotal, 2) ?></td>
                                            <td class="text-center">
                                                <a href="?remove=<?= $key ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('ต้องการลบสินค้านี้?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>รวมทั้งหมด</strong></td>
                                        <td class="text-end"><strong><?= number_format($total, 2) ?></strong></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ฟอร์มข้อมูลจัดส่ง -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shipping-fast me-2"></i>ข้อมูลจัดส่ง
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // ดึงข้อมูลผู้ใช้
                        $user_id = $_SESSION['user_id'];
                        $sql = "SELECT * FROM users WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $user = $stmt->get_result()->fetch_assoc();
                        ?>
                        <form action="insert_cart.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">ชื่อ-นามสกุล</label>
                                <input type="text" name="cus_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['name'] ?? $user['username']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ที่อยู่</label>
                                <input type="text" name="cus_add" class="form-control" 
                                       value="<?= htmlspecialchars($user['address'] ?? '') ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">จังหวัด</label>
                                    <input type="text" name="province" class="form-control" 
                                           value="<?= htmlspecialchars($user['province'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">อำเภอ/เขต</label>
                                    <input type="text" name="district" class="form-control" 
                                           value="<?= htmlspecialchars($user['district'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ตำบล/แขวง</label>
                                    <input type="text" name="subdistrict" class="form-control" 
                                           value="<?= htmlspecialchars($user['subdistrict'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">รหัสไปรษณีย์</label>
                                    <input type="text" name="zipcode" class="form-control" 
                                           value="<?= htmlspecialchars($user['zipcode'] ?? '') ?>" 
                                           pattern="[0-9]{5}" maxlength="5" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">เบอร์โทรศัพท์</label>
                                <input type="tel" name="cus_tel" class="form-control" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                            </div>

                            <!-- เพิ่มส่วนการชำระเงิน -->
                            <div class="card mt-3">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-money-bill-wave me-2"></i>การชำระเงิน
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <img src="img/Screenshot 2025-02-03 133753.png" alt="QR Code" class="img-fluid mb-2" style="max-width: 200px;">
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <span class="fw-bold">พร้อมเพย์:</span>
                                            <span>xxx-xxx-xxxx</span>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyPromptPay()">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">ยอดชำระ</label>
                                        <div class="input-group">
                                            <span class="input-group-text">฿</span>
                                            <input type="text" class="form-control text-end" 
                                                   value="<?= number_format($total, 2) ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">แนบสลิปการโอนเงิน</label>
                                        <input type="file" name="payment_slip" class="form-control" accept="image/*" required 
                                               onchange="previewSlip(this)">
                                        <div class="form-text">รองรับไฟล์ภาพ jpg, jpeg, png ขนาดไม่เกิน 2MB</div>
                                        <div id="slip-preview" class="mt-2 text-center" style="display: none;">
                                            <img src="" alt="ตัวอย่างสลิป" class="img-fluid" style="max-height: 200px;">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">วันที่โอน</label>
                                        <?php
                                        date_default_timezone_set('Asia/Bangkok');
                                        $thai_year = date('Y') + 543;  // แปลงเป็น พ.ศ.
                                        $thai_date = date('d/m/') . $thai_year . date(' H:i');
                                        ?>
                                        <input type="text" class="form-control" value="<?= $thai_date ?>" readonly>
                                        <input type="hidden" name="payment_date" value="<?= date('Y-m-d H:i:s') ?>">
                                    </div>

                                    <div class="alert alert-warning">
                                        <i class="fas fa-info-circle me-2"></i>
                                        กรุณาชำระเงินและแนบสลิปก่อนกดยืนยันการสั่งซื้อ
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 mt-3">
                                <i class="fas fa-check-circle me-2"></i>ยืนยันการสั่งซื้อ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    function copyPromptPay() {
        // เพิ่มเลขพร้อมเพย์ที่ต้องการ
        const promptPay = "xxx-xxx-xxxx";
        navigator.clipboard.writeText(promptPay).then(() => {
            alert("คัดลอกเลขพร้อมเพย์แล้ว");
        });
    }

    function previewSlip(input) {
        const preview = document.getElementById('slip-preview');
        const previewImg = preview.querySelector('img');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
        }
    }
    </script>
</body>
</html>

