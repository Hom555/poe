<?php
session_start();
include 'condb.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
if (!isset($_SESSION["strProductID"]) || empty($_SESSION["strProductID"])) {
    header("Location: cart.php");
    exit();
}

// ตรวจสอบข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// ตรวจสอบข้อมูลที่จำเป็น
$missing_info = [];
if (empty($user['name'])) $missing_info[] = 'ชื่อ-นามสกุล';
if (empty($user['address'])) $missing_info[] = 'ที่อยู่';
if (empty($user['province'])) $missing_info[] = 'จังหวัด';
if (empty($user['district'])) $missing_info[] = 'อำเภอ/เขต';
if (empty($user['subdistrict'])) $missing_info[] = 'ตำบล/แขวง';
if (empty($user['zipcode'])) $missing_info[] = 'รหัสไปรษณีย์';
if (empty($user['phone'])) $missing_info[] = 'เบอร์โทรศัพท์';

if (!empty($missing_info)) {
    $missing_info_str = implode(', ', $missing_info);
    echo "<script>
        alert('กรุณากรอกข้อมูลให้ครบถ้วนในหน้าโปรไฟล์: $missing_info_str');
        window.location.href = 'profile.php?return_to=sh_product';
    </script>";
    exit();
}

// ตรวจสอบสต็อกสินค้าในตะกร้า
$out_of_stock_items = [];
$insufficient_stock_items = [];
foreach ($_SESSION["strProductID"] as $key => $productId) {
    $size = $_SESSION["strSize"][$key] ?? '';
    $qty = $_SESSION["strQty"][$key];
    
    if (!empty($size)) {
        $stock_sql = "SELECT ps.amount, p.name 
                      FROM product_sizes ps 
                      JOIN products p ON ps.product_base_id = p.id 
                      WHERE ps.product_base_id = ? AND ps.size = ?";
        $stock_stmt = $conn->prepare($stock_sql);
        $stock_stmt->bind_param("is", $productId, $size);
        $stock_stmt->execute();
        $stock_result = $stock_stmt->get_result();
        
        if ($stock_row = $stock_result->fetch_assoc()) {
            if ($stock_row['amount'] == 0) {
                $out_of_stock_items[] = $stock_row['name'] . ' (ไซส์ ' . $size . ') - สินค้าหมด';
            } elseif ($stock_row['amount'] < $qty) {
                $insufficient_stock_items[] = $stock_row['name'] . ' (ไซส์ ' . $size . ') - มีในคลัง ' . $stock_row['amount'] . ' ชิ้น';
            }
        } else {
            $out_of_stock_items[] = 'สินค้าไม่พบในคลัง';
        }
    }
}

// ถ้ามีสินค้าหมด
if (!empty($out_of_stock_items)) {
    $out_of_stock_str = implode('\\n', $out_of_stock_items);
    echo "<script>
        alert('สินค้าบางรายการหมดจากคลังแล้ว:\\n$out_of_stock_str\\n\\nกรุณากลับไปตะกร้าสินค้าเพื่อลบสินค้าที่หมด');
        window.location.href = 'cart.php';
    </script>";
    exit();
}

// ถ้ามีสินค้าที่สต็อกไม่เพียงพอ
if (!empty($insufficient_stock_items)) {
    $insufficient_stock_str = implode('\\n', $insufficient_stock_items);
    echo "<script>
        alert('สินค้าบางรายการในสต็อกไม่เพียงพอ:\\n$insufficient_stock_str\\n\\nกรุณาปรับจำนวนสินค้า');
        window.location.href = 'sh_product.php';
    </script>";
    exit();
}

// คำนวณยอดรวม
$total = 0;
foreach ($_SESSION["strProductID"] as $key => $productId) {
    $size = $_SESSION["strSize"][$key] ?? '';
    $qty = $_SESSION["strQty"][$key];
    
    if (!empty($size)) {
        $price_sql = "SELECT ps.price FROM product_sizes ps WHERE ps.product_base_id = ? AND ps.size = ?";
        $price_stmt = $conn->prepare($price_sql);
        $price_stmt->bind_param("is", $productId, $size);
        $price_stmt->execute();
        $price_result = $price_stmt->get_result();
        
        if ($price_row = $price_result->fetch_assoc()) {
            $total += $price_row['price'] * $qty;
        }
    }
}

// แยกชื่อและนามสกุลจากข้อมูลที่มีอยู่
$full_name = $user['name'] ?? '';
$name_parts = explode(' ', $full_name, 2);
$first_name = $name_parts[0] ?? '';
$last_name = $name_parts[1] ?? '';

$title = "การชำระเงิน";

// ฟังก์ชันแปลงชื่อสีเป็นรหัสสี
function getColorCode($colorName) {
    $colors = [
        'ขาว' => '#FFFFFF',
        'ดำ' => '#000000',
        'แดง' => '#FF0000',
        'น้ำเงิน' => '#0000FF',
        'เขียว' => '#008000',
        'เหลือง' => '#FFFF00',
        'ส้ม' => '#FFA500',
        'ม่วง' => '#800080',
        'ชมพู' => '#FFC0CB',
        'เทา' => '#808080',
        'น้ำตาล' => '#A52A2A',
        'ครีม' => '#F5F5DC',
        'เบจ' => '#F5F5DC',
        'ฟ้า' => '#87CEEB',
        'เขียวอ่อน' => '#90EE90',
        'อื่นๆ' => '#E0E0E0'
    ];
    return $colors[$colorName] ?? '#E0E0E0';
}

// ฟังก์ชันกำหนดสีข้อความให้เหมาะสมกับสีพื้นหลัง
function getTextColor($colorName) {
    $lightColors = ['ขาว', 'เหลือง', 'ครีม', 'เบจ', 'ฟ้า', 'เขียวอ่อน', 'ชมพู'];
    return in_array($colorName, $lightColors) ? '#000000' : '#FFFFFF';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 600;
        }
        .btn-back {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        .btn-back:hover {
            background-color: #5a6268;
            border-color: #545b62;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar แบบง่าย -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="sh_product.php">
                <i class="fas fa-shopping-bag me-2"></i>Yaz Shop
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="cart.php">
                    <i class="fas fa-shopping-cart me-1"></i>ตะกร้าสินค้า
                </a>
                <a class="nav-link" href="profile.php?return_to=sh_product">
                    <i class="fas fa-user me-1"></i>โปรไฟล์
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>


<div class="container mt-4">
    <div class="row">
        <!-- สรุปการสั่งซื้อ -->
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>สรุปการสั่งซื้อ
                    </h5>
                    <small class="opacity-75">
                        <i class="fas fa-info-circle me-1"></i>ตรวจสอบสินค้า ไซส์ สี และราคาก่อนชำระเงิน
                    </small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                        <i class="fas fa-box me-1"></i>สินค้า
                                    </th>
                                    <th>
                                        <i class="fas fa-tshirt me-1"></i>ไซส์/สี
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-hashtag me-1"></i>จำนวน
                                    </th>
                                    <th class="text-end">
                                        <i class="fas fa-tag me-1"></i>ราคา
                                    </th>
                                    <th class="text-end">
                                        <i class="fas fa-calculator me-1"></i>รวม
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($_SESSION["strProductID"] as $key => $productId) {
                                    $size = $_SESSION["strSize"][$key] ?? '';
                                    $qty = $_SESSION["strQty"][$key];
                                    $color = $_SESSION["strColor"][$key] ?? '';
                                    
                                    // Debug: ดูข้อมูลที่ส่งมา
                                    echo "<!-- Debug: Product ID: $productId, Size: $size, Color: $color, Qty: $qty -->";
                                    
                                    // ดึงข้อมูลสินค้า
                                    $product_sql = "SELECT p.name, p.image, ps.price, ps.color, ps.image as color_image
                                                   FROM products p 
                                                   LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                                                   WHERE p.id = ? AND ps.size = ? AND ps.color = ?";
                                    $product_stmt = $conn->prepare($product_sql);
                                    $product_stmt->bind_param("iss", $productId, $size, $color);
                                    $product_stmt->execute();
                                    $product_result = $product_stmt->get_result();
                                    
                                    if ($product_row = $product_result->fetch_assoc()) {
                                        $subtotal = $product_row['price'] * $qty;
                                        
                                        // Debug: ดูข้อมูลที่ได้จากฐานข้อมูล
                                        echo "<!-- Debug: Found product: " . $product_row['name'] . ", Color: " . $product_row['color'] . ", Color Image: " . $product_row['color_image'] . " -->";
                                        
                                        // ดึงรูปภาพที่ตรงกับสีที่เลือก
                                        $image_src = '';
                                        
                                        // ลำดับความสำคัญ: รูปภาพสำหรับสี > รูปภาพหลัก > รูปภาพ default
                                        if (!empty($product_row['color_image'])) {
                                            // ใช้รูปภาพสำหรับสีที่เลือก
                                            if (strpos($product_row['color_image'], 'http') === 0) {
                                                $image_src = $product_row['color_image'];
                                            } else {
                                                $image_src = 'img/' . $product_row['color_image'];
                                            }
                                        } elseif (!empty($product_row['image'])) {
                                            // ใช้รูปภาพหลัก
                                            if (strpos($product_row['image'], 'http') === 0) {
                                                $image_src = $product_row['image'];
                                            } else {
                                                $image_src = 'img/' . $product_row['image'];
                                            }
                                        } else {
                                            // ใช้รูปภาพ default
                                            $image_src = 'img/no-image.svg';
                                        }
                                        
                                        // Debug: ดูรูปภาพที่เลือก
                                        echo "<!-- Debug: Final image: " . $image_src . " -->";
                                        
                                        // Debug: ดูข้อมูลทั้งหมด
                                        echo "<!-- Debug: Product: " . $product_row['name'] . ", Size: " . $size . ", Color: " . $color . ", Image: " . $image_src . " -->";
                                        
                                        // Debug: ดูข้อมูลจากฐานข้อมูล
                                        echo "<!-- Debug: DB Color: " . $product_row['color'] . ", DB Color Image: " . $product_row['color_image'] . " -->";
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= htmlspecialchars($image_src) ?>" 
                                                     alt="<?= htmlspecialchars($product_row['name']) ?>"
                                                     class="product-image me-3"
                                                     onerror="this.src='img/no-image.svg'; this.alt='ไม่มีรูปภาพ';">
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($product_row['name']) ?></h6>
                                                   
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php if (!empty($size)): ?>
                                                    <span class="badge bg-primary" style="font-size: 0.7rem;">
                                                        <i class="fas fa-tshirt me-1"></i><?= htmlspecialchars($size) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($color)): ?>
                                                    <span class="badge" style="font-size: 0.7rem; background-color: <?= getColorCode($color) ?>; color: <?= getTextColor($color) ?>;">
                                                        <i class="fas fa-palette me-1"></i><?= htmlspecialchars($color) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">
                                                <?= $qty ?>
                                            </span>
                                        </td>
                                        <td class="text-end">฿<?= number_format($product_row['price'], 2) ?></td>
                                        <td class="text-end fw-bold">฿<?= number_format($subtotal, 2) ?></td>
                                    </tr>
                                <?php 
                                    } else {
                                        // Debug: ถ้าไม่พบข้อมูล
                                        echo "<!-- Debug: No product found for ID: $productId, Size: $size, Color: $color -->";
                                    }
                                } 
                                ?>
                                <tr class="table-light">
                                    <td colspan="4" class="text-end">
                                        <strong>
                                            <i class="fas fa-calculator me-1"></i>รวมทั้งหมด
                                        </strong>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-primary fs-5">
                                            <i class="fas fa-baht-sign me-1"></i>฿<?= number_format($total, 2) ?>
                                        </strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ข้อมูลจัดส่งและชำระเงิน -->
        <div class="col-md-4">
            <!-- ข้อมูลจัดส่ง -->
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-shipping-fast me-2"></i>ข้อมูลจัดส่ง
                    </h5>
                    <small class="opacity-75">
                        <i class="fas fa-map-marker-alt me-1"></i>ที่อยู่สำหรับจัดส่งสินค้า
                    </small>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>ชื่อ</strong><br>
                        <?= htmlspecialchars($first_name) ?> <?= htmlspecialchars($last_name) ?>
                    </div>
                    <div class="mb-2">
                        <strong>ที่อยู่</strong><br>
                        <?= htmlspecialchars($user['address'] ?? '') ?>
                    </div>
                    <div class="mb-2">
                        <strong>จังหวัด</strong><br>
                        <?= htmlspecialchars($user['province'] ?? '') ?>
                    </div>
                    <div class="mb-2">
                        <strong>อำเภอ/เขต</strong><br>
                        <?= htmlspecialchars($user['district'] ?? '') ?>
                    </div>
                    <div class="mb-2">
                        <strong>ตำบล/แขวง</strong><br>
                        <?= htmlspecialchars($user['subdistrict'] ?? '') ?>
                    </div>
                    <div class="mb-2">
                        <strong>รหัสไปรษณีย์</strong><br>
                        <?= htmlspecialchars($user['zipcode'] ?? '') ?>
                    </div>
                    <div class="mb-0">
                        <strong>เบอร์โทรศัพท์</strong><br>
                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($user['phone'] ?? '') ?>
                    </div>
                </div>
            </div>

            <!-- การชำระเงิน -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>การชำระเงิน
                    </h5>
                    <small class="opacity-75">
                        <i class="fas fa-credit-card me-1"></i>ชำระเงินผ่านพร้อมเพย์และแนบสลิป
                    </small>
                </div>
                <div class="card-body">
                    <form action="insert_cart.php" method="POST" enctype="multipart/form-data">
                        <!-- ข้อมูลผู้รับ -->
                        <input type="hidden" name="first_name" value="<?= htmlspecialchars($first_name) ?>">
                        <input type="hidden" name="last_name" value="<?= htmlspecialchars($last_name) ?>">
                        <input type="hidden" name="cus_add" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                        <input type="hidden" name="province" value="<?= htmlspecialchars($user['province'] ?? '') ?>">
                        <input type="hidden" name="district" value="<?= htmlspecialchars($user['district'] ?? '') ?>">
                        <input type="hidden" name="subdistrict" value="<?= htmlspecialchars($user['subdistrict'] ?? '') ?>">
                        <input type="hidden" name="zipcode" value="<?= htmlspecialchars($user['zipcode'] ?? '') ?>">
                        <input type="hidden" name="cus_tel" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        
                        <div class="text-center mb-3">
                            <img src="image/VegMQd_qrcode.png" alt="QR Code" class="img-fluid mb-2" style="max-width: 200px;">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <span class="fw-bold">พร้อมเพย์:</span>
                                <span>09x-xxx-xxxx</span>
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
                            <input type="file" name="payment_slip" class="form-control" 
                                   accept="image/jpeg,image/png,image/webp" required 
                                   onchange="validateFile(this)">
                            <div class="form-text">รองรับไฟล์ภาพ jpg, jpeg, png, webp ขนาดไม่เกิน 2MB</div>
                            
                            <div id="slip-preview" class="mt-2 text-center" style="display: none;">
                                <img id="preview-img" src="" alt="ตัวอย่างสลิป" class="img-fluid" style="max-height: 200px;">
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
                        
                        <div class="alert alert-info">
                            <i class="fas fa-palette me-2"></i>
                            <strong>หมายเหตุ:</strong> สินค้าที่สั่งซื้อจะส่งตามสีและไซส์ที่เลือกไว้
                        </div>

                        <button type="submit" class="btn btn-success w-100" onclick="return validateOrder()">
                            <i class="fas fa-check-circle me-2"></i>ยืนยันการสั่งซื้อ
                        </button>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                ข้อมูลการสั่งซื้อจะถูกบันทึกและส่งตามสีที่เลือก
                            </small>
                        </div>
                        
                        <a href="cart.php" class="btn btn-secondary w-100 mt-2">
                            <i class="fas fa-arrow-left me-2"></i>กลับไปตะกร้าสินค้า
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 5px;
}

.quantity-control .btn {
    padding: 2px 8px;
}

.quantity-control input[type="number"] {
    border: 1px solid #ced4da;
    border-radius: 4px;
    text-align: center;
    font-size: 0.875rem;
}

.quantity-control input[type="number"]:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.quantity-control input[type="number"]::-webkit-inner-spin-button,
.quantity-control input[type="number"]::-webkit-outer-spin-button {
    opacity: 1;
}
</style>

<script>
function copyPromptPay() {
    const promptPay = "09x-xxx-xxxx";
    navigator.clipboard.writeText(promptPay).then(() => {
        alert("คัดลอกเลขพร้อมเพย์แล้ว");
    });
}

function validateFile(input) {
    const file = input.files[0];
    const maxSize = 2 * 1024 * 1024; // 2MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const preview = document.getElementById("slip-preview");
    const previewImg = document.getElementById("preview-img");

    if (file) {
        // ตรวจสอบประเภทไฟล์
        if (!allowedTypes.includes(file.type)) {
            alert("อนุญาตเฉพาะไฟล์ JPG, PNG หรือ WEBP เท่านั้น");
            input.value = ""; // ล้างค่า input
            preview.style.display = "none";
            return;
        }

        // ตรวจสอบขนาดไฟล์
        if (file.size > maxSize) {
            alert("ขนาดไฟล์ต้องไม่เกิน 2MB");
            input.value = ""; // ล้างค่า input
            preview.style.display = "none";
            return;
        }

        // แสดงตัวอย่างรูป
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            preview.style.display = "block";
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = "none";
    }
}

function validateOrder() {
    // ตรวจสอบว่ามีการแนบสลิปหรือไม่
    const slipInput = document.querySelector('input[name="payment_slip"]');
    if (!slipInput.files || slipInput.files.length === 0) {
        alert('กรุณาแนบสลิปการโอนเงิน');
        return false;
    }
    
    return confirm('ยืนยันการสั่งซื้อ?');
}
</script>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
