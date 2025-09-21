<?php
session_start();
include 'condb.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
$low_stock_items = [];
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
                $out_of_stock_items[] = $stock_row['name'] . ' (ไซส์ ' . $size . ') - มีในคลัง ' . $stock_row['amount'] . ' ชิ้น';
            } elseif ($stock_row['amount'] <= 5) {
                $low_stock_items[] = $stock_row['name'] . ' (ไซส์ ' . $size . ') - เหลือในคลัง ' . $stock_row['amount'] . ' ชิ้น';
            }
        } else {
            $out_of_stock_items[] = 'สินค้าไม่พบในคลัง';
        }
    }
}

// ถ้ามีสินค้าหมด ให้ลบออกจากตะกร้าและแจ้งเตือน
if (!empty($out_of_stock_items)) {
    $out_of_stock_str = implode('\\n', $out_of_stock_items);
    echo "<script>
        alert('สินค้าบางรายการหมดจากคลังแล้ว:\\n$out_of_stock_str\\n\\nสินค้าเหล่านี้จะถูกลบออกจากตะกร้าของคุณ');
        window.location.href = 'remove_out_of_stock.php';
    </script>";
    exit();
}

// ถ้ามีสินค้าที่สต็อกไม่เพียงพอ
if (!empty($out_of_stock_items)) {
    $out_of_stock_str = implode('\\n', $out_of_stock_items);
    echo "<script>
        alert('สินค้าบางรายการในสต็อกไม่เพียงพอ:\\n$out_of_stock_str\\n\\nกรุณาปรับจำนวนสินค้า');
        window.location.href = 'sh_product.php';
    </script>";
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

// ตรวจสอบความถูกต้องของข้อมูลในตะกร้า
if (!isset($_SESSION["strQty"]) || !isset($_SESSION["strSize"]) || 
    count($_SESSION["strProductID"]) != count($_SESSION["strQty"]) || 
    count($_SESSION["strProductID"]) != count($_SESSION["strSize"])) {
    // รีเซ็ตตะกร้าถ้าข้อมูลไม่ถูกต้อง
    unset($_SESSION["strProductID"]);
    unset($_SESSION["strQty"]);
    unset($_SESSION["strSize"]);
    unset($_SESSION["strColor"]);
    echo "<script>
        alert('ข้อมูลตะกร้าไม่ถูกต้อง! กรุณาเลือกสินค้าใหม่');
        window.location.href = 'sh_product.php';
    </script>";
    exit();
}

// ตรวจสอบว่ามีข้อมูลสีหรือไม่ ถ้าไม่มีให้สร้าง array ว่าง
if (!isset($_SESSION["strColor"])) {
    $_SESSION["strColor"] = array_fill(0, count($_SESSION["strProductID"]), '');
}

// ตรวจสอบการลบสินค้า
if (isset($_GET['remove'])) {
    $key = intval($_GET['remove']);
    unset($_SESSION["strProductID"][$key]);
    unset($_SESSION["strQty"][$key]);
    unset($_SESSION["strSize"][$key]);
    unset($_SESSION["strColor"][$key]);

    // จัดเรียง index ใหม่
    $_SESSION["strProductID"] = array_values($_SESSION["strProductID"]);
    $_SESSION["strQty"] = array_values($_SESSION["strQty"]);
    $_SESSION["strSize"] = array_values($_SESSION["strSize"]);
    $_SESSION["strColor"] = array_values($_SESSION["strColor"]);
    
    header("Location: cart.php");
    exit();
}

// ตรวจสอบการเพิ่ม/ลดจำนวนสินค้า
if (isset($_GET['changeQty']) && isset($_GET['key'])) {
    $key = intval($_GET['key']);
    $change = $_GET['changeQty'];

    if ($change == 'add') {
        // ตรวจสอบสต็อกก่อนเพิ่มจำนวน
        $productId = $_SESSION["strProductID"][$key];
        $size = $_SESSION["strSize"][$key] ?? '';
        
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
                    echo "<script>
                        alert('สินค้านี้หมดจากคลังแล้ว กรุณาลบออกจากตะกร้า');
                        window.location.href = 'cart.php';
                    </script>";
                    exit();
                }
                
                $current_qty = $_SESSION["strQty"][$key];
                if ($current_qty < $stock_row['amount']) {
                    $_SESSION["strQty"][$key]++;
                } else {
                    echo "<script>
                        alert('ไม่สามารถเพิ่มจำนวนได้ เนื่องจากมีสินค้าในคลังเพียง " . $stock_row['amount'] . " ชิ้น');
                        window.location.href = 'cart.php';
                    </script>";
                    exit();
                }
            }
        } else {
        $_SESSION["strQty"][$key]++;
        }
    } elseif ($change == 'sub' && $_SESSION["strQty"][$key] > 1) {
        $_SESSION["strQty"][$key]--;
    }
    header("Location: cart.php");
    exit();
}

// ตรวจสอบการอัปเดตจำนวนสินค้า
if (isset($_GET['updateQty']) && isset($_GET['qty'])) {
    $key = $_GET['updateQty'];
    $new_qty = intval($_GET['qty']);
    
    if (isset($_SESSION["strProductID"][$key]) && $new_qty > 0) {
        $productId = $_SESSION["strProductID"][$key];
        $size = $_SESSION["strSize"][$key] ?? '';
        
        // ตรวจสอบสต็อกสินค้า
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
                    echo "<script>
                        alert('สินค้านี้หมดจากคลังแล้ว กรุณาลบออกจากตะกร้า');
                        window.location.href = 'cart.php';
                    </script>";
                    exit();
                }
                
                if ($new_qty <= $stock_row['amount']) {
                    $_SESSION["strQty"][$key] = $new_qty;
                } else {
                    echo "<script>
                        alert('ไม่สามารถอัปเดตจำนวนได้ เนื่องจากมีสินค้าในคลังเพียง " . $stock_row['amount'] . " ชิ้น');
                        window.location.href = 'cart.php';
                    </script>";
                    exit();
                }
            }
        } else {
            $_SESSION["strQty"][$key] = $new_qty;
        }
        
        header("Location: cart.php");
        exit();
    }
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
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        /* สไตล์สำหรับ dropdown */
        select.form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        select.form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        select.form-control:hover {
            border-color: #0d6efd;
        }
        
        select.form-control option {
            padding: 8px;
        }
        
        select.form-control option:hover {
            background-color: #f8f9fa;
        }
        
        /* สไตล์สำหรับ readonly input */
        input[readonly] {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #6c757d;
            cursor: not-allowed;
        }
        
        input[readonly]:focus {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            box-shadow: none;
        }
        
        /* สไตล์สำหรับ readonly select */
        select[readonly] {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #6c757d;
            cursor: not-allowed;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        
        select[readonly]:focus {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            box-shadow: none;
        }
        
        /* ซ่อนลูกศรของ dropdown เมื่อเป็น readonly */
        select[readonly]::-ms-expand {
            display: none;
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
        <!-- แสดงสถานะการตรวจสอบ -->
        <div class="row mb-3">

        </div>
        
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
                                        // ดึงข้อมูลสินค้าจากตารางใหม่
                                        $sql = "SELECT p.*, ps.size, ps.price, ps.amount, ps.color, ps.image as color_image, t.type_name 
                                                FROM products p 
                                                LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                                                LEFT JOIN type t ON p.type_id = t.type_id 
                                                WHERE p.id = ?";
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("i", $productId);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $row = $result->fetch_assoc();

                                        $qty = $_SESSION["strQty"][$key];
                                        $size = $_SESSION["strSize"][$key] ?? '';
                                        $color = $_SESSION["strColor"][$key] ?? '';
                                        
                                        // ดึงราคาจาก product_sizes ตามไซส์
                                        $price = $row['price']; // ราคาเริ่มต้น
                                        if (!empty($size)) {
                                            $price_sql = "SELECT price FROM product_sizes WHERE product_base_id = ? AND size = ?";
                                            $price_stmt = $conn->prepare($price_sql);
                                            $price_stmt->bind_param("is", $productId, $size);
                                            $price_stmt->execute();
                                            $price_result = $price_stmt->get_result();
                                            if ($price_row = $price_result->fetch_assoc()) {
                                                $price = $price_row['price'];
                                            }
                                        }
                                        
                                        $subtotal = $price * $qty;
                                        $total += $subtotal;
                                        
                                        // ตรวจสอบสต็อกสินค้า
                                        $stock_available = true;
                                        if (!empty($size)) {
                                            $stock_sql = "SELECT amount FROM product_sizes WHERE product_base_id = ? AND size = ?";
                                            $stock_stmt = $conn->prepare($stock_sql);
                                            $stock_stmt->bind_param("is", $productId, $size);
                                            $stock_stmt->execute();
                                            $stock_result = $stock_stmt->get_result();
                                            if ($stock_row = $stock_result->fetch_assoc()) {
                                                $stock_available = $stock_row['amount'] >= $qty;
                                            }
                                        }
                                    ?>
                                        <tr class="<?= !$stock_available ? 'table-warning' : '' ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    // ดึงรูปภาพที่ตรงกับสีที่เลือก
                                                    $image_src = '';
                                                    if (!empty($color)) {
                                                        // หารูปภาพสำหรับสีที่เลือก
                                                        $color_image_sql = "SELECT image FROM product_sizes WHERE product_base_id = ? AND color = ? AND image IS NOT NULL AND image != '' LIMIT 1";
                                                        $color_image_stmt = $conn->prepare($color_image_sql);
                                                        $color_image_stmt->bind_param("is", $productId, $color);
                                                        $color_image_stmt->execute();
                                                        $color_image_result = $color_image_stmt->get_result();
                                                        
                                                        if ($color_image_row = $color_image_result->fetch_assoc()) {
                                                            $color_image = $color_image_row['image'];
                                                            if (strpos($color_image, 'http') === 0) {
                                                                $image_src = $color_image;
                                                            } else {
                                                                $image_src = 'img/' . $color_image;
                                                            }
                                                        }
                                                    }
                                                    
                                                    // ถ้าไม่มีรูปภาพสำหรับสีนี้ ให้ใช้รูปภาพหลัก
                                                    if (empty($image_src)) {
                                                        $image_src = strpos($row['image'], 'http') === 0 ? 
                                                            $row['image'] : 
                                                            'img/' . $row['image'];
                                                    }
                                                    ?>
                                                    <img src="<?= htmlspecialchars($image_src) ?>" class="product-image me-3" 
                                                         alt="<?= htmlspecialchars($row['name']) ?>"
                                                         onerror="this.src='img/no-image.svg'; this.alt='ไม่มีรูปภาพ';">
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($row['name']) ?></h6>
                                                        <small class="text-muted">
                                                            รหัส: <?= $row['id'] ?>
                                                            <?php if (!empty($size)): ?>
                                                                | ไซส์: <?= htmlspecialchars($size) ?>
                                                            <?php endif; ?>
                                                            <?php if (!empty($color)): ?>
                                                                | สี: <?= htmlspecialchars($color) ?>
                                                            <?php endif; ?>
                                                        </small>
                                                        <?php if (!$stock_available): ?>
                                                            <div class="text-danger small">
                                                                <i class="fas fa-exclamation-triangle"></i>
                                                                สินค้าในสต็อกไม่เพียงพอ
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center"><?= number_format($price, 2) ?></td>
                                            <td class="text-center">
                                                <div class="quantity-control justify-content-center">
                                                    <a href="?changeQty=sub&key=<?= $key ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-minus"></i>
                                                    </a>
                                                    <input type="number" class="form-control form-control-sm mx-2" 
                                                           style="width: 60px; text-align: center;" 
                                                           value="<?= $qty ?>" min="1" 
                                                           onchange="updateQuantity(<?= $key ?>, this.value)"
                                                           data-product-id="<?= $productId ?>" 
                                                           data-size="<?= $size ?>">
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
                                                <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>หมายเหตุ:</strong> ข้อมูลจัดส่งจะใช้ข้อมูลจากโปรไฟล์ของคุณ หากต้องการแก้ไข กรุณาไปที่หน้า <a href="profile.php?return_to=sh_product" class="alert-link">ข้อมูลของฉัน</a>
                        </div>
                        <?php
                        // ดึงข้อมูลผู้ใช้
                        $user_id = $_SESSION['user_id'];
                        $sql = "SELECT * FROM users WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $user = $stmt->get_result()->fetch_assoc();
                        
                        // แยกชื่อและนามสกุลจากข้อมูลที่มีอยู่
                        $full_name = $user['name'] ?? '';
                        $name_parts = explode(' ', $full_name, 2);
                        $first_name = $name_parts[0] ?? '';
                        $last_name = $name_parts[1] ?? '';
                        ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ชื่อ</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?= htmlspecialchars($first_name) ?>" readonly required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">นามสกุล</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?= htmlspecialchars($last_name) ?>" readonly required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ที่อยู่</label>
                                <input type="text" name="cus_add" class="form-control" 
                                       value="<?= htmlspecialchars($user['address'] ?? '') ?>" readonly required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">จังหวัด</label>
                                                                       <select name="province" id="province" class="form-control" required readonly>
                                       <?php if (!empty($user['province'])): ?>
                                           <option value="<?= htmlspecialchars($user['province']) ?>" selected>
                                               <?= htmlspecialchars($user['province']) ?>
                                           </option>
                                       <?php else: ?>
                                           <option value="">ไม่มีข้อมูล</option>
                                       <?php endif; ?>
                                   </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">อำเภอ/เขต</label>
                                                                       <select name="district" id="district" class="form-control" required readonly>
                                       <?php if (!empty($user['district'])): ?>
                                           <option value="<?= htmlspecialchars($user['district']) ?>" selected>
                                               <?= htmlspecialchars($user['district']) ?>
                                           </option>
                                       <?php else: ?>
                                           <option value="">ไม่มีข้อมูล</option>
                                       <?php endif; ?>
                                   </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ตำบล/แขวง</label>
                                                                       <select name="subdistrict" id="subdistrict" class="form-control" required readonly>
                                       <?php if (!empty($user['subdistrict'])): ?>
                                           <option value="<?= htmlspecialchars($user['subdistrict']) ?>" selected>
                                               <?= htmlspecialchars($user['subdistrict']) ?>
                                           </option>
                                       <?php else: ?>
                                           <option value="">ไม่มีข้อมูล</option>
                                       <?php endif; ?>
                                   </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">รหัสไปรษณีย์</label>
                                    <input type="text" name="zipcode" id="zipcode" class="form-control" 
                                           value="<?= htmlspecialchars($user['zipcode'] ?? '') ?>" 
                                           pattern="[0-9]{5}" maxlength="5" readonly required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">เบอร์โทรศัพท์</label>
                                <input type="tel" name="cus_tel" class="form-control" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" readonly required>
                            </div>



                            <a href="payment.php" class="btn btn-success w-100 mt-3">
                                <i class="fas fa-credit-card me-2"></i>ไปหน้าชำระเงิน
                            </a>
                            


                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>


    // ฟังก์ชันอัปเดตจำนวนสินค้า
    function updateQuantity(key, newQuantity) {
        const quantity = parseInt(newQuantity);
        const inputElement = document.querySelector(`input[onchange="updateQuantity(${key}, this.value)"]`);
        const productId = inputElement.getAttribute('data-product-id');
        const size = inputElement.getAttribute('data-size');
        
        if (isNaN(quantity) || quantity < 1) {
            alert('กรุณากรอกจำนวนที่ถูกต้อง (ต้องมากกว่า 0)');
            location.reload();
            return;
        }
        
        // ตรวจสอบสต็อกสินค้า
        if (size) {
            // ส่ง AJAX request เพื่อตรวจสอบสต็อก
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('size', size);
            formData.append('quantity', quantity);
            
            fetch('check_stock.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // อัปเดตจำนวนใน session
                    window.location.href = `?updateQty=${key}&qty=${quantity}`;
                } else {
                    alert(`ไม่สามารถอัปเดตจำนวนได้: ${data.message}`);
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการอัปเดตจำนวน');
                location.reload();
            });
        } else {
            // ถ้าไม่มีไซส์ ให้อัปเดตเลย
            window.location.href = `?updateQty=${key}&qty=${quantity}`;
        }
    }



    // ฟังก์ชันสำหรับโหลดข้อมูลจังหวัด
    function loadProvinces() {
        const provinceSelect = document.getElementById('province');
        
        fetch('thailand_address_data.php?action=get_provinces')
            .then(response => response.json())
            .then(data => {
                // เพิ่มตัวเลือกแรก
                provinceSelect.innerHTML = '<option value="">เลือกจังหวัด</option>';
                
                // เพิ่มจังหวัดทั้งหมด
                data.forEach(province => {
                    const option = document.createElement('option');
                    option.value = province.name;
                    option.textContent = province.name;
                    provinceSelect.appendChild(option);
                });
                
                // ถ้ามีจังหวัดที่เลือกไว้แล้ว ให้เลือกไว้
                const currentProvince = '<?= htmlspecialchars($user['province'] ?? '') ?>';
                if (currentProvince) {
                    provinceSelect.value = currentProvince;
                    // โหลดเขต/อำเภอของจังหวัดที่เลือกไว้
                    loadDistricts();
                }
            })
            .catch(error => {
                console.error('Error loading provinces:', error);
            });
    }
    
    // ฟังก์ชันสำหรับดึงข้อมูลเขต/อำเภอ
    function loadDistricts() {
        const province = document.getElementById('province').value;
        const districtSelect = document.getElementById('district');
        const subdistrictSelect = document.getElementById('subdistrict');
        const zipcodeInput = document.getElementById('zipcode');
        
        // รีเซ็ต dropdown และ input
        districtSelect.innerHTML = '<option value="">เลือกเขต/อำเภอ</option>';
        subdistrictSelect.innerHTML = '<option value="">เลือกแขวง/ตำบล</option>';
        zipcodeInput.value = '';
        
        if (province) {
            fetch(`thailand_address_data.php?action=get_districts&province=${encodeURIComponent(province)}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.name;
                        option.textContent = district.name;
                        districtSelect.appendChild(option);
                    });
                    
                    // ถ้ามีเขต/อำเภอที่เลือกไว้แล้ว ให้เลือกไว้
                    const currentDistrict = '<?= htmlspecialchars($user['district'] ?? '') ?>';
                    if (currentDistrict) {
                        districtSelect.value = currentDistrict;
                        // โหลดแขวง/ตำบลของเขต/อำเภอที่เลือกไว้
                        loadSubdistricts();
                    }
                })
                .catch(error => {
                    console.error('Error loading districts:', error);
                });
        }
    }
    
    // ฟังก์ชันสำหรับดึงข้อมูลแขวง/ตำบล
    function loadSubdistricts() {
        const province = document.getElementById('province').value;
        const district = document.getElementById('district').value;
        const subdistrictSelect = document.getElementById('subdistrict');
        const zipcodeInput = document.getElementById('zipcode');
        
        // รีเซ็ต dropdown และ input
        subdistrictSelect.innerHTML = '<option value="">เลือกแขวง/ตำบล</option>';
        zipcodeInput.value = '';
        
        if (province && district) {
            fetch(`thailand_address_data.php?action=get_subdistricts&province=${encodeURIComponent(province)}&district=${encodeURIComponent(district)}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(subdistrict => {
                        const option = document.createElement('option');
                        option.value = subdistrict.name;
                        option.textContent = subdistrict.name;
                        subdistrictSelect.appendChild(option);
                    });
                    
                    // ถ้ามีแขวง/ตำบลที่เลือกไว้แล้ว ให้เลือกไว้
                    const currentSubdistrict = '<?= htmlspecialchars($user['subdistrict'] ?? '') ?>';
                    if (currentSubdistrict) {
                        subdistrictSelect.value = currentSubdistrict;
                        // โหลดรหัสไปรษณีย์
                        loadZipcode();
                    }
                })
                .catch(error => {
                    console.error('Error loading subdistricts:', error);
                });
        }
    }
    
    // ฟังก์ชันสำหรับดึงรหัสไปรษณีย์
    function loadZipcode() {
        const province = document.getElementById('province').value;
        const district = document.getElementById('district').value;
        const subdistrict = document.getElementById('subdistrict').value;
        const zipcodeInput = document.getElementById('zipcode');
        
        if (province && district && subdistrict) {
            fetch(`thailand_address_data.php?action=get_zipcode&province=${encodeURIComponent(province)}&district=${encodeURIComponent(district)}&subdistrict=${encodeURIComponent(subdistrict)}`)
                .then(response => response.json())
                .then(data => {
                    zipcodeInput.value = data;
                })
                .catch(error => {
                    console.error('Error loading zipcode:', error);
                });
        }
    }
    
    // เพิ่ม event listeners สำหรับ dropdown (เฉพาะเมื่อไม่ใช่ readonly)
    document.addEventListener('DOMContentLoaded', function() {
        // ตรวจสอบว่า dropdown เป็น readonly หรือไม่
        const provinceSelect = document.getElementById('province');
        const districtSelect = document.getElementById('district');
        const subdistrictSelect = document.getElementById('subdistrict');
        
        // ถ้าไม่ใช่ readonly ให้โหลดข้อมูลและเพิ่ม event listeners
        if (!provinceSelect.hasAttribute('readonly')) {
            // โหลดข้อมูลจังหวัดเมื่อหน้าโหลดเสร็จ
            loadProvinces();
            
            // เมื่อเลือกจังหวัด
            provinceSelect.addEventListener('change', loadDistricts);
            
            // เมื่อเลือกเขต/อำเภอ
            districtSelect.addEventListener('change', loadSubdistricts);
            
            // เมื่อเลือกแขวง/ตำบล
            subdistrictSelect.addEventListener('change', loadZipcode);
        } else {
            // ป้องกันการคลิก dropdown เมื่อเป็น readonly
            [provinceSelect, districtSelect, subdistrictSelect].forEach(select => {
                if (select) {
                    select.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });
                    
                    select.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });
                    
                    select.addEventListener('keydown', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });
                }
            });
        }
    });
    </script>
</body>
</html>

