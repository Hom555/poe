<?php
session_start();
include 'condb.php';

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

// ฟังก์ชันดึงรูปภาพสีสำหรับสินค้า
function getColorImages($product_id, $conn) {
    $sql = "SELECT DISTINCT color FROM product_sizes WHERE product_base_id = ? AND color IS NOT NULL AND color != ''";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $colors = [];
    while ($row = $result->fetch_assoc()) {
        $colors[] = $row['color'];
    }
    return $colors;
}

// ฟังก์ชันสร้าง URL รูปภาพสี (ดึงจาก database)
function getColorImageUrl($product_id, $color, $main_image, $conn) {
    // ดึงรูปภาพสีจาก database
    $sql = "SELECT image FROM product_sizes WHERE product_base_id = ? AND color = ? AND image IS NOT NULL AND image != '' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $product_id, $color);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $color_image = $row['image'];
        if (strpos($color_image, 'http') === 0) {
            return $color_image;
        } else {
            return 'img/' . $color_image;
        }
    }
    
    // ถ้าไม่มีรูปภาพสี ให้ใช้รูปภาพหลัก
    if (!empty($main_image)) {
        if (strpos($main_image, 'http') === 0) {
            return $main_image;
        } else {
            return 'img/' . $main_image;
        }
    }
    
    // ถ้าไม่มีรูปภาพใดเลย ให้ใช้รูปภาพ default
    return 'img/no-image.svg';
}

// สถานะการล็อกอิน (อนุญาตให้เข้าชมหน้าได้แม้ยังไม่ล็อกอิน)
$isAuthenticated = isset($_SESSION['user_id']);

// เพิ่มสินค้าลงตะกร้า
if (isset($_GET['id'])) {
    if (!$isAuthenticated) {
        header("Location: login.php");
        exit();
    }
    $product_id = $_GET['id'];
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1; // รับค่าจำนวนสินค้า
    $search = $_GET['search'] ?? '';
    $color = isset($_GET['color']) ? trim($_GET['color']) : ''; // รับค่าสี
    // รองรับรับค่าไซส์ทั้งแบบ name="size" และแบบ name="size_{product_id}"
    $size = isset($_GET['size']) ? strtoupper(trim($_GET['size'])) : '';
    if ($size === '' && isset($_GET['id'])) {
        $sizeParam = 'size_' . $_GET['id'];
        if (isset($_GET[$sizeParam])) {
            $size = strtoupper(trim($_GET[$sizeParam]));
        }
    }
    $allowed_sizes = ['XS','S','M','L','XL','XXL','3XL'];
    if ($size === '' || !in_array($size, $allowed_sizes, true)) {
        echo "<script>\n            alert('กรุณาเลือกไซส์สินค้า');\n            window.location.href = 'sh_product.php" . ($search ? "?search=" . urlencode($search) : "") . "';\n        </script>";
        exit();
    }
    
    // ตรวจสอบจำนวนสินค้าในสต็อก
    $stock_check = $conn->prepare("SELECT amount FROM product_sizes WHERE product_base_id = ? AND size = ?");
    $stock_check->bind_param("is", $product_id, $size);
    $stock_check->execute();
    $stock_result = $stock_check->get_result();
    $stock_data = $stock_result->fetch_assoc();
    
    if (!$stock_data || $quantity > $stock_data['amount']) {
        $available = $stock_data ? $stock_data['amount'] : 0;
        echo "<script>
            alert('จำนวนสินค้าไม่เพียงพอ มีสินค้าในสต็อก " . $available . " ชิ้น');
            window.location.href = 'sh_product.php" . ($search ? "?search=" . urlencode($search) : "") . "';
        </script>";
        exit();
    }
    
    // ตรวจสอบว่ามีตะกร้าสินค้าหรือยัง
    if (!isset($_SESSION["strProductID"])) {
        $_SESSION["strProductID"] = array();
        $_SESSION["strQty"] = array();
        $_SESSION["strSize"] = array();
        $_SESSION["strColor"] = array();
    }
    if (!isset($_SESSION["strSize"])) {
        $_SESSION["strSize"] = array();
    }
    if (!isset($_SESSION["strColor"])) {
        $_SESSION["strColor"] = array();
    }
    
    // เพิ่มสินค้าลงตะกร้า
    array_push($_SESSION["strProductID"], $product_id);
    array_push($_SESSION["strQty"], $quantity);
    array_push($_SESSION["strSize"], $size);
    array_push($_SESSION["strColor"], $color);
    
    echo "<script>
        alert('เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว');
        window.location.href = 'sh_product.php" . ($search ? "?search=" . urlencode($search) : "") . "';
    </script>";
    exit();
}

// คำนวณจำนวนสินค้าในตะกร้า
$cart_count = isset($_SESSION["strProductID"]) ? count($_SESSION["strProductID"]) : 0;

// คำนวณราคารวมในตะกร้า
$cart_total = 0;
if (isset($_SESSION["strProductID"])) {
    foreach ($_SESSION["strProductID"] as $key => $product_id) {
        $size = $_SESSION["strSize"][$key];
        $sql = "SELECT ps.price FROM product_sizes ps WHERE ps.product_base_id = ? AND ps.size = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $product_id, $size);
        $stmt->execute();
        $result_price = $stmt->get_result();
        $row_price = $result_price->fetch_assoc();
        if ($row_price) {
        $cart_total += $row_price['price'] * $_SESSION["strQty"][$key];
        }
    }
}

// เพิ่มการจัดการค้นหาและกรอง
$where_clause = "ps.amount > 0"; // แสดงเฉพาะสินค้าที่มีในสต็อก
$params = array();
$types = "";

// ค้นหาตามคำค้น
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $where_clause .= " AND (p.name LIKE ? OR t.type_name LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

// กรองตามประเภท
if (isset($_GET['type_id']) && !empty($_GET['type_id'])) {
    $where_clause .= " AND p.type_id = ?";
    $params[] = $_GET['type_id'];
    $types .= "i";
}

// ดึงข้อมูลประเภทสินค้าสำหรับ dropdown
$type_sql = "SELECT * FROM type ORDER BY type_name";
$type_result = $conn->query($type_sql);

// แก้ไข SQL query หลักเพื่อดึงข้อมูลสินค้าแบบรวม
$sql = "SELECT p.*, t.type_name,
               GROUP_CONCAT(ps.size ORDER BY 
                   CASE ps.size
                       WHEN 'XS' THEN 1
                       WHEN 'S' THEN 2
                       WHEN 'M' THEN 3
                       WHEN 'L' THEN 4
                       WHEN 'XL' THEN 5
                       WHEN 'XXL' THEN 6
                       WHEN '3XL' THEN 7
                       ELSE 8
                   END SEPARATOR ', ') as sizes,
               GROUP_CONCAT(CONCAT(ps.size, ':', ps.price, ':', ps.amount) ORDER BY 
                   CASE ps.size
                       WHEN 'XS' THEN 1
                       WHEN 'S' THEN 2
                       WHEN 'M' THEN 3
                       WHEN 'L' THEN 4
                       WHEN 'XL' THEN 5
                       WHEN 'XXL' THEN 6
                       WHEN '3XL' THEN 7
                       ELSE 8
                   END SEPARATOR '|') as size_details,
               MIN(ps.price) as min_price,
               MAX(ps.price) as max_price,
               SUM(ps.amount) as total_amount
        FROM products p 
        LEFT JOIN type t ON p.type_id = t.type_id 
        LEFT JOIN product_sizes ps ON p.id = ps.product_base_id
        WHERE $where_clause 
        GROUP BY p.id, p.name, p.description, p.type_id, p.image, t.type_name
        ORDER BY p.name";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้าทั้งหมด - Yaz Shop</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="sh_product.php">
            
                <span class="fw-bold fs-4">Yaz Shop</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="sh_product.php">
                            <i class="fas fa-store me-1"></i>สินค้า
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="contact.php">
                            <i class="fas fa-phone me-1"></i>ติดต่อเรา
                        </a>
                    </li>
                </ul>
                

                
                
                <!-- Modern Cart Icon -->
                <div class="dropdown me-3">
                    <div class="cart-icon position-relative" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                        <i class="fas fa-shopping-cart fa-lg text-primary"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">
                                <?= $cart_count ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-menu dropdown-menu-end cart-dropdown shadow-lg border-0" style="min-width: 350px;">
                        <div class="d-flex align-items-center mb-3">
                            <h6 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-shopping-cart me-2"></i>ตะกร้าสินค้า
                            </h6>
                        </div>
                        <?php if ($cart_count > 0): ?>
                            <div class="cart-items" style="max-height: 300px; overflow-y: auto;">
                            <?php
                            foreach ($_SESSION["strProductID"] as $key => $product_id):
                                    $size = $_SESSION["strSize"][$key];
                                    $color = $_SESSION["strColor"][$key] ?? '';
                                    $sql = "SELECT p.*, ps.price FROM products p 
                                            LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                                            WHERE p.id = ? AND ps.size = ?";
                                $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("is", $product_id, $size);
                                $stmt->execute();
                                $result_cart = $stmt->get_result();
                                $item = $result_cart->fetch_assoc();
                                    if ($item) {
                                $subtotal = $item['price'] * $_SESSION["strQty"][$key];
                                    
                                    // ดึงรูปภาพที่ตรงกับสีที่เลือก
                                    $cart_image_src = '';
                                    if (!empty($color)) {
                                        $color_image_sql = "SELECT image FROM product_sizes WHERE product_base_id = ? AND color = ? AND image IS NOT NULL AND image != '' LIMIT 1";
                                        $color_image_stmt = $conn->prepare($color_image_sql);
                                        $color_image_stmt->bind_param("is", $product_id, $color);
                                        $color_image_stmt->execute();
                                        $color_image_result = $color_image_stmt->get_result();
                                        
                                        if ($color_image_row = $color_image_result->fetch_assoc()) {
                                            $color_image = $color_image_row['image'];
                                            if (strpos($color_image, 'http') === 0) {
                                                $cart_image_src = $color_image;
                                            } else {
                                                $cart_image_src = 'img/' . $color_image;
                                            }
                                        }
                                    }
                                    
                                    // ถ้าไม่มีรูปภาพสำหรับสีนี้ ให้ใช้รูปภาพหลัก
                                    if (empty($cart_image_src)) {
                                        if (!empty($item['image'])) {
                                            if (strpos($item['image'], 'http') === 0) {
                                                $cart_image_src = $item['image'];
                                            } else {
                                                $cart_image_src = 'img/' . $item['image'];
                                            }
                                        } else {
                                            $cart_image_src = 'img/no-image.svg';
                                        }
                                    }
                                ?>
                                    <div class="cart-item d-flex align-items-center p-2 rounded mb-2" style="background: #f8f9fa;">
                                        <img src="<?= htmlspecialchars($cart_image_src) ?>" alt="<?= $item['name'] ?>" 
                                             class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;"
                                             onerror="this.src='img/no-image.svg'; this.alt='ไม่มีรูปภาพ';">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold text-dark"><?= $item['name'] ?></div>
                                            <div class="text-muted small">
                                                ไซส์: <?= htmlspecialchars($size) ?>
                                                <?php if (!empty($color)): ?>
                                                    | สี: <?= htmlspecialchars($color) ?>
                                        <?php endif; ?>
                                        </div>
                                            <div class="text-primary fw-semibold">
                                                <?= $_SESSION["strQty"][$key] ?> x ฿<?= number_format($item['price'], 2) ?>
                                    </div>
                                </div>
                                </div>
                                    <?php 
                                    }
                                endforeach; 
                                ?>
                            </div>
                            <div class="cart-total p-3 rounded mt-3" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-bold text-dark">รวมทั้งหมด:</div>
                                    <div class="fw-bold text-primary fs-5">฿<?= number_format($cart_total, 2) ?></div>
                                </div>
                                <a href="cart.php" class="btn btn-primary w-100 mt-2 fw-semibold">
                                    <i class="fas fa-shopping-cart me-2"></i>ดูตะกร้า
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0 fw-semibold">ไม่มีสินค้าในตะกร้า</p>
                                <small class="text-muted">เริ่มช้อปปิ้งกันเลย!</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Modern User Menu -->
                <?php if ($isAuthenticated): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle fw-semibold" type="button" id="userMenu" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="userMenu">
                        <li>
                            <a class="dropdown-item fw-semibold" href="profile.php?return_to=sh_product">
                                <i class="fas fa-user-circle me-2 text-primary"></i> บัญชีของฉัน
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fw-semibold" href="order_history.php">
                                <i class="fas fa-shopping-bag me-2 text-success"></i> การซื้อของฉัน
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger fw-semibold" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
                <?php else: ?>
                <div class="d-flex gap-2 auth-buttons">
                    <a class="btn btn-primary fw-semibold" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i> เข้าสู่ระบบ
                    </a>
                    <a class="btn btn-outline-primary fw-semibold" href="register.php">
                        <i class="fas fa-user-plus me-1"></i> สมัครสมาชิก
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Modern Search Section -->
    <div class="search-section">
        <div class="container">
            <div class="search-container">
                <h2 class="search-title">
                    <i class="fas fa-search me-2"></i>ค้นหาสินค้าที่คุณต้องการ
                </h2>
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-6">
                    <div class="input-group">
                            <input type="text" name="search" class="form-control search-input" 
                               placeholder="ค้นหาสินค้า..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <button type="submit" class="btn search-btn">
                                <i class="fas fa-search me-1"></i>ค้นหา
                        </button>
                    </div>
                    </div>
                    <div class="col-md-4">
                        <select name="type_id" class="form-select filter-select" 
                            onchange="this.form.submit()">
                            <option value="">ทุกประเภทสินค้า</option>
                        <?php
                        // ดึงข้อมูลประเภทสินค้า
                        $type_sql = "SELECT * FROM type ORDER BY type_name";
                        $type_result = $conn->query($type_sql);
                        while ($type = $type_result->fetch_assoc()):
                        ?>
                            <option value="<?= $type['type_id'] ?>" 
                                    <?= (isset($_GET['type_id']) && $_GET['type_id'] == $type['type_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['type_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    </div>
                    <div class="col-md-2">
                    <?php if(isset($_GET['search']) || isset($_GET['type_id'])): ?>
                            <a href="sh_product.php" class="btn clear-btn w-100">
                                <i class="fas fa-times me-1"></i>ล้างตัวกรอง
                        </a>
                    <?php endif; ?>
                    </div>
                </form>
            </div>
            </div>
        </div>

    <!-- Alert Section -->
    <div class="container mt-4">
        <?php if ($result->num_rows == 0): ?>
            <div class="alert alert-info border-0 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x text-info me-3"></i>
                    <div>
                        <h6 class="mb-1 fw-bold">ไม่พบสินค้า</h6>
                        <p class="mb-0">
                <?php if(isset($_GET['search']) || isset($_GET['type_id'])): ?>
                                ไม่พบสินค้าที่ตรงกับเงื่อนไขที่เลือก ลองปรับคำค้นหาหรือตัวกรอง
                <?php else: ?>
                                ยังไม่มีสินค้าในระบบ
                <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modern Product Grid -->
    <div class="container mt-4">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card product-card h-100 border-0 shadow-sm">
                        <div class="position-relative overflow-hidden">
                            <?php
                            // ตรวจสอบว่ารูปภาพเป็น URL หรือชื่อไฟล์
                            $image_src = '';
                            if (!empty($row['image'])) {
                                if (strpos($row['image'], 'http') === 0) {
                                    $image_src = $row['image'];
                                } else {
                                    $image_src = 'img/' . $row['image'];
                                }
                            } else {
                                $image_src = 'img/no-image.svg';
                            }
                            ?>
                            <img id="product-image-<?= $row['id'] ?>" 
                                 src="<?= htmlspecialchars($image_src) ?>" 
                                 class="card-img-top product-image" 
                                 alt="<?= htmlspecialchars($row['name']) ?>"
                                 style="height: 250px; object-fit: cover; cursor: pointer;"
                                 onclick="viewProductDetail(<?= $row['id'] ?>)"
                                 onerror="this.src='img/no-image.svg'; this.alt='ไม่มีรูปภาพ';">
                            <!-- Product Badge -->
                            <?php if ($row['total_amount'] > 0): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-success rounded-pill">
                                        <i class="fas fa-check me-1"></i>มีสินค้า
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-danger rounded-pill">
                                        <i class="fas fa-times me-1"></i>สินค้าหมด
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body product-info p-3" style="cursor: pointer;" onclick="viewProductDetail(<?= $row['id'] ?>)">
                            <h5 class="product-title mb-2"><?= htmlspecialchars($row['name']) ?></h5>
                            
                            <!-- Modern Color Picker -->
                            <?php 
                            $available_colors = getColorImages($row['id'], $conn);
                            if (!empty($available_colors)): 
                            ?>
                                <div class="color-section mb-3">
                                    <div class="color-label">
                                        <i class="fas fa-palette text-primary"></i>
                                        <span>สีที่มี</span>
                                    </div>
                                    <div class="color-chips">
                                        <?php foreach ($available_colors as $index => $color): ?>
                                            <div class="color-chip-wrapper" data-color="<?= htmlspecialchars($color) ?>" onclick="preventColorClick(event)">
                                                <input type="radio" 
                                                       value="<?= htmlspecialchars($color) ?>" 
                                                       name="product-color-picker-<?= $row['id'] ?>" 
                                                       id="product-color-picker-<?= $row['id'] ?>-<?= $index ?>"
                                                       class="color-radio"
                                                       <?= $index === 0 ? 'checked' : '' ?>
                                                       onchange="updateProductImageByColor(<?= $row['id'] ?>, '<?= htmlspecialchars($color) ?>')"
                                                       onclick="preventColorClick(event)">
                                                <label class="color-chip-label" 
                                                       for="product-color-picker-<?= $row['id'] ?>-<?= $index ?>"
                                                       style="background-color: <?= getColorCode($color) ?>; color: <?= getTextColor($color) ?>;"
                                                       title="<?= htmlspecialchars($color) ?>"
                                                       onclick="preventColorClick(event)">
                                                    <span class="color-name" role="img" aria-label="<?= htmlspecialchars($color) ?>">
                                                        <?= htmlspecialchars($color) ?>
                                                    </span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Product Description -->
                            <?php if (!empty($row['description'])): ?>
                                <p class="product-description">
                                    <?= nl2br(htmlspecialchars($row['description'])) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Price Range -->
                            <?php if ($row['total_amount'] > 0): ?>
                                <div class="mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted small">
                                            <i class="fas fa-tag me-1"></i>ราคา
                                        </span>
                                        <span class="fw-bold text-primary">
                                            <?php if ($row['min_price'] == $row['max_price']): ?>
                                                ฿<?= number_format($row['min_price'], 0) ?>
                                            <?php else: ?>
                                                ฿<?= number_format($row['min_price'], 0) ?> - ฿<?= number_format($row['max_price'], 0) ?>
                                            <?php endif; ?>
                                </span>
                            </div>
                                </div>
                            <?php endif; ?>

                        </div>
                        
                        <!-- Modern Add to Cart Section -->
                        <div class="card-footer bg-light border-0 p-3">
                            <?php if($row['total_amount'] > 0): ?>
                                <form action="" method="GET" class="d-flex flex-column gap-3">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="color" id="selected-color-<?= $row['id'] ?>" value="">
                                    <?php if(isset($_GET['search'])): ?>
                                        <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
                                    <?php endif; ?>
                                    <?php if(isset($_GET['type_id'])): ?>
                                        <input type="hidden" name="type_id" value="<?= htmlspecialchars($_GET['type_id']) ?>">
                                    <?php endif; ?>
                                    
                                    <!-- Size Picker -->
                                    <div class="size-picker" id="size-picker-container-<?= $row['id'] ?>">
                                        <div class="text-center text-muted py-2">
                                            <i class="fas fa-info-circle me-1"></i>กรุณาเลือกสีก่อน
                                    </div>
                                        </div>
                                    
                                    <!-- Quantity and Add to Cart -->
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="quantity-control d-flex align-items-center border rounded">
                                            <button type="button" class="btn quantity-btn border-0" 
                                                    onclick="decrementQty(this)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" name="quantity" class="quantity-input border-0" 
                                                   value="1" min="1" max="1" 
                                                   onchange="validateQtyBySize(this, '<?= $row['id'] ?>')"
                                                   data-product-id="<?= $row['id'] ?>">
                                            <button type="button" class="btn quantity-btn border-0" 
                                                    onclick="incrementQtyBySize(this, '<?= $row['id'] ?>')">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <button type="submit" class="btn add-to-cart-btn flex-grow-1" aria-label="เพิ่มลงตะกร้า">
                                            <i class="fas fa-shopping-cart me-2"></i>เพิ่มลงตะกร้า
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-outline-danger w-100 py-2" disabled>
                                    <i class="fas fa-times me-2"></i>สินค้าหมด
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>


            <?php endwhile; ?>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    // ข้อมูลไซส์และจำนวนสินค้า
    const productSizes = {
        <?php 
        // Reset result pointer
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()): 
            $size_details_array = [];
            if (!empty($row['size_details'])):
                $size_details = explode('|', $row['size_details']);
                foreach ($size_details as $detail):
                    list($size, $price, $amount) = explode(':', $detail);
                    $size_details_array[$size] = $amount;
                endforeach;
            endif;
        ?>
        '<?= $row['id'] ?>': {
            <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'] as $size): ?>
                '<?= $size ?>': <?= isset($size_details_array[$size]) ? $size_details_array[$size] : 0 ?>,
            <?php endforeach; ?>
        },
        <?php endwhile; ?>
    };

    function decrementQty(btn) {
        const input = btn.parentNode.querySelector('input');
        const currentValue = parseInt(input.value);
        if (currentValue > 1) {
            input.value = currentValue - 1;
        }
    }

    function incrementQty(btn, max) {
        const input = btn.parentNode.querySelector('input');
        const currentValue = parseInt(input.value);
        if (currentValue < max) {
            input.value = currentValue + 1;
        }
    }

    function validateQty(input, max) {
        let value = parseInt(input.value);
        if (isNaN(value) || value < 1) {
            input.value = 1;
        } else if (value > max) {
            input.value = max;
        }
    }

    function viewProductDetail(productId) {
        // ตรวจสอบว่ามีสีที่เลือกอยู่หรือไม่
        const selectedColor = document.querySelector(`input[name="product-color-picker-${productId}"]:checked`);
        let url = 'product_detail.php?id=' + productId;
        
        if (selectedColor) {
            url += '&color=' + encodeURIComponent(selectedColor.value);
        }
        
        // ไปหน้ารายละเอียดสินค้า
        window.location.href = url;
    }
    
    // ฟังก์ชันป้องกันการไปหน้า product_detail.php เมื่อคลิกสี
    function preventColorClick(event) {
        // หยุดการแพร่กระจายของ event
        event.stopPropagation();
        
        // ไม่ preventDefault เพื่อให้สีทำงานตามปกติ
        return false;
    }

    // ฟังก์ชันใหม่สำหรับจัดการจำนวนตามไซส์
    function updateQuantityBySize(productId) {
        const selectedSize = document.querySelector(`input[name="size_${productId}"]:checked`);
        const quantityInput = document.querySelector(`input[data-product-id="${productId}"]`);
        const incrementBtn = quantityInput.parentNode.querySelector('button:last-child');
        
        if (selectedSize) {
            const size = selectedSize.value;
            const maxQty = productSizes[productId][size];
            
            // อัปเดต max attribute
            quantityInput.max = maxQty;
            
            // ตรวจสอบว่าจำนวนปัจจุบันเกินจำนวนที่มีหรือไม่
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > maxQty) {
                quantityInput.value = maxQty;
            }
            
            // อัปเดต onclick ของปุ่ม increment
            incrementBtn.onclick = function() {
                incrementQtyBySize(this, productId);
            };
        } else {
            // ถ้าไม่ได้เลือกไซส์ ให้ตั้งค่าเป็น 1
            quantityInput.value = 1;
            quantityInput.max = 1;
        }
    }

    function incrementQtyBySize(btn, productId) {
        const selectedSize = document.querySelector(`input[name="size_${productId}"]:checked`);
        if (!selectedSize) {
            alert('กรุณาเลือกไซส์ก่อน');
            return;
        }
        
        const size = selectedSize.value;
        const maxQty = productSizes[productId][size];
        const input = btn.parentNode.querySelector('input');
        const currentValue = parseInt(input.value);
        
        if (currentValue < maxQty) {
            input.value = currentValue + 1;
        } else {
            alert('จำนวนสินค้าไม่เพียงพอ มีสินค้าในสต็อก ' + maxQty + ' ชิ้น');
        }
    }

    function validateQtyBySize(input, productId) {
        const selectedSize = document.querySelector(`input[name="size_${productId}"]:checked`);
        if (!selectedSize) {
            input.value = 1;
            return;
        }
        
        const size = selectedSize.value;
        const maxQty = productSizes[productId][size];
        let value = parseInt(input.value);
        
        if (isNaN(value) || value < 1) {
            input.value = 1;
        } else if (value > maxQty) {
            // แสดงเตือน
            alert('จำนวนสินค้าไม่เพียงพอ มีสินค้าในสต็อก ' + maxQty + ' ชิ้น');
            input.value = maxQty;
        }
    }



    // เพิ่ม event listener สำหรับการเลือกไซส์
    document.addEventListener('DOMContentLoaded', function() {
        const sizeInputs = document.querySelectorAll('input[type="radio"]');
        sizeInputs.forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.name.replace('size_', '');
                updateQuantityBySize(productId);
            });
        });
        
        // เพิ่ม event listener สำหรับการเลือกสี
        const colorInputs = document.querySelectorAll('input[name*="product-color-picker"]');
        colorInputs.forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.name.replace('product-color-picker-', '');
                updateProductImageByColor(productId, this.value);
            });
        });
        
        // เริ่มต้นด้วยสีแรกที่ถูกเลือก
        const firstColorInputs = document.querySelectorAll('input[name*="product-color-picker"]:checked');
        firstColorInputs.forEach(input => {
            const productId = input.name.replace('product-color-picker-', '');
            updateProductImageByColor(productId, input.value);
        });
    });
    
    // ข้อมูลสินค้าสำหรับ JavaScript (รวมสีและไซส์)
    const productData = {
        <?php 
        // สร้างข้อมูลสินค้าสำหรับ JavaScript
        if (isset($result)) {
            mysqli_data_seek($result, 0);
            $first = true;
            while ($product_row = $result->fetch_assoc()) {
                if (!$first) echo ',';
                $first = false;
                
                echo "'{$product_row['id']}': {\n";
                echo "    colors: {\n";
                
                // ดึงข้อมูลสีและไซส์สำหรับสินค้านี้
                $color_sql = "SELECT DISTINCT color FROM product_sizes WHERE product_base_id = ? AND color IS NOT NULL AND color != ''";
                $color_stmt = $conn->prepare($color_sql);
                $color_stmt->bind_param("i", $product_row['id']);
                $color_stmt->execute();
                $color_result = $color_stmt->get_result();
                
                $color_first = true;
                while ($color_row = $color_result->fetch_assoc()) {
                    if (!$color_first) echo ',';
                    $color_first = false;
                    
                    $color = $color_row['color'];
                    echo "        '{$color}': {\n";
                    echo "            sizes: {\n";
                    
                    // ดึงข้อมูลไซส์สำหรับสีนี้
                    $size_sql = "SELECT size, price, amount, image FROM product_sizes WHERE product_base_id = ? AND color = ?";
                    $size_stmt = $conn->prepare($size_sql);
                    $size_stmt->bind_param("is", $product_row['id'], $color);
                    $size_stmt->execute();
                    $size_result = $size_stmt->get_result();
                    
                    $size_first = true;
                    while ($size_row = $size_result->fetch_assoc()) {
                        if (!$size_first) echo ',';
                        $size_first = false;
                        
                        $size = $size_row['size'];
                        $price = $size_row['price'];
                        $amount = $size_row['amount'];
                        $image = $size_row['image'];
                        
                        echo "                '{$size}': { price: {$price}, amount: {$amount} }";
                    }
                    
                    echo "\n            },\n";
                    
                    // หารูปภาพสำหรับสีนี้
                    $image_sql = "SELECT image FROM product_sizes WHERE product_base_id = ? AND color = ? AND image IS NOT NULL AND image != '' LIMIT 1";
                    $image_stmt = $conn->prepare($image_sql);
                    $image_stmt->bind_param("is", $product_row['id'], $color);
                    $image_stmt->execute();
                    $image_result = $image_stmt->get_result();
                    $image_row = $image_result->fetch_assoc();
                    
                    if ($image_row) {
                        $image_url = strpos($image_row['image'], 'http') === 0 ? $image_row['image'] : 'img/' . $image_row['image'];
                        echo "            image: '{$image_url}'\n";
                    } else {
                        // ใช้รูปภาพหลัก
                        $main_image = strpos($product_row['image'], 'http') === 0 ? $product_row['image'] : 'img/' . $product_row['image'];
                        echo "            image: '{$main_image}'\n";
                    }
                    
                    echo "        }";
                }
                
                echo "\n    }\n";
                echo "}";
            }
        }
        ?>
    };

    // ฟังก์ชันอัปเดตรูปภาพและไซส์ตามสีที่เลือก
    function updateProductImageByColor(productId, selectedColor) {
        const productImage = document.querySelector(`#product-image-${productId}`);
        const sizePickerContainer = document.querySelector(`#size-picker-container-${productId}`);
        const selectedColorInput = document.querySelector(`#selected-color-${productId}`);
        
        if (!productImage) return;
        
        // อัปเดตค่า color ในฟอร์ม
        if (selectedColorInput) {
            selectedColorInput.value = selectedColor;
        }
        
        // อัปเดตรูปภาพ
        const product = productData[productId];
        if (product && product.colors[selectedColor] && product.colors[selectedColor].image) {
            productImage.src = product.colors[selectedColor].image;
            productImage.alt = `${productImage.alt.split(' - ')[0]} - สี ${selectedColor}`;
        } else {
            // ถ้าไม่มีรูปภาพสำหรับสีนี้ ให้ใช้รูปภาพหลัก
            const mainImage = getMainProductImage(productId);
            if (mainImage) {
                productImage.src = mainImage;
                productImage.alt = `${productImage.alt.split(' - ')[0]}`;
            }
        }
        
        // อัปเดตไซส์ตามสีที่เลือก
        updateSizesByColor(productId, selectedColor);
        
        // เพิ่มเอฟเฟกต์การเปลี่ยนรูปภาพ
        productImage.style.opacity = '0.7';
        setTimeout(() => {
            productImage.style.opacity = '1';
        }, 200);
    }

    // ฟังก์ชันอัปเดตไซส์ตามสีที่เลือก
    function updateSizesByColor(productId, selectedColor) {
        const sizePickerContainer = document.querySelector(`#size-picker-container-${productId}`);
        if (!sizePickerContainer) return;
        
        const product = productData[productId];
        if (!product || !product.colors[selectedColor]) {
            sizePickerContainer.innerHTML = '<div class="text-muted"><i class="fas fa-info-circle me-1"></i>ไม่มีไซส์สำหรับสีนี้</div>';
            return;
        }
        
        const colorData = product.colors[selectedColor];
        const sizes = colorData.sizes;
        
        // สร้าง HTML สำหรับไซส์ - แสดงทุกไซส์
        let sizeHtml = '';
        const allSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'];
        
        allSizes.forEach(size => {
            if (sizes[size]) {
                const sizeData = sizes[size];
                const isAvailable = sizeData.amount > 0;
                
                sizeHtml += `
                    <input type="radio" class="btn-check" name="size_${productId}" 
                           id="size_${productId}_${size}" value="${size}" 
                           autocomplete="off" ${isAvailable ? 'required' : 'disabled'}>
                    <label class="btn btn-outline-dark btn-sm size-option ${!isAvailable ? 'disabled' : ''}" 
                           for="size_${productId}_${size}" 
                           title="${isAvailable ? `฿${sizeData.price.toLocaleString()} (${sizeData.amount} ชิ้น)` : 'ไม่มีในคลัง'}">
                        ${size}
                    </label>
                `;
            } else {
                // แสดงไซส์ที่ไม่มีในสีนี้เป็น disabled
                sizeHtml += `
                    <input type="radio" class="btn-check" name="size_${productId}" 
                           id="size_${productId}_${size}" value="${size}" 
                           autocomplete="off" disabled>
                    <label class="btn btn-outline-dark btn-sm size-option disabled" 
                           for="size_${productId}_${size}" 
                           title="ไม่มีในคลัง">
                        ${size}
                    </label>
                `;
            }
        });
        
        sizePickerContainer.innerHTML = sizeHtml;
        
        // เพิ่ม event listener สำหรับไซส์ที่เพิ่งสร้าง
        const newSizeInputs = sizePickerContainer.querySelectorAll('input[type="radio"]');
        newSizeInputs.forEach(input => {
            input.addEventListener('change', function() {
                updateQuantityBySize(productId);
            });
        });
        
        // อัปเดตจำนวนสินค้าตามไซส์ที่เลือก
        updateQuantityBySize(productId);
    }

    // ฟังก์ชันดึงรูปภาพหลักของสินค้า
    function getMainProductImage(productId) {
        const productImage = document.querySelector(`#product-image-${productId}`);
        return productImage ? productImage.src : null;
    }
    </script>

    <!-- Modern CSS Styles -->
<style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Modern Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            text-decoration: none;
        }

        .navbar-brand img {
            transition: var(--transition);
        }

        .navbar-brand:hover img {
            transform: scale(1.1) rotate(5deg);
        }

        .nav-link {
            font-weight: 500;
            color: var(--secondary-color) !important;
            transition: var(--transition);
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: var(--transition);
            transform: translateX(-50%);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        /* Modern Search Section */
        .search-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
        }

        .search-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .search-title {
            color: white;
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .search-input {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .search-input:focus {
            background: white;
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .search-btn {
            background: var(--accent-color);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .search-btn:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .filter-select {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .filter-select:focus {
            background: white;
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .clear-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            color: white;
            font-weight: 500;
            transition: var(--transition);
        }

        .clear-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }

        /* Modern Product Cards */
        .product-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid var(--border-color);
            overflow: hidden;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            opacity: 0;
            transition: var(--transition);
        }

        .product-card:hover::before {
            opacity: 1;
        }

        .product-image {
            height: 250px;
            object-fit: cover;
            transition: var(--transition);
            cursor: pointer;
        }

        .product-image:hover {
            transform: scale(1.05);
        }

        .product-info {
            padding: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .product-info:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .product-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark-color);
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }

        .product-description {
            color: var(--secondary-color);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Modern Color Picker */
        .color-section {
            margin-bottom: 1rem;
        }

        .color-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .color-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 0;
            padding: 0;
        }

        .color-chip-wrapper {
            position: relative;
        }

        .color-radio {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .color-chip-label {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid var(--border-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            font-size: 0.7rem;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .color-chip-label:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .color-radio:checked + .color-chip-label {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
            transform: scale(1.05);
        }

        .color-radio:checked + .color-chip-label::after {
            content: '✓';
            position: absolute;
            top: -3px;
            right: -3px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            z-index: 1;
        }

        /* Modern Size Picker */
        .size-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .size-option {
            min-width: 45px;
            text-align: center;
            transition: var(--transition);
            border-radius: var(--border-radius);
            font-weight: 500;
        }

        .size-option:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .size-option.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f1f5f9 !important;
            color: #94a3b8 !important;
            border-color: #e2e8f0 !important;
        }

        .btn-check:disabled + .size-option {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f1f5f9 !important;
            color: #94a3b8 !important;
            border-color: #e2e8f0 !important;
        }

        /* Modern Quantity Control */
        .quantity-control {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .quantity-btn {
            background: var(--light-color);
            border: none;
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            color: var(--secondary-color);
            transition: var(--transition);
        }

        .quantity-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .quantity-input {
            border: none;
            text-align: center;
            font-weight: 600;
            color: var(--dark-color);
            background: transparent;
        }

        .quantity-input:focus {
            outline: none;
            box-shadow: none;
        }

        /* Modern Add to Cart Button */
        .add-to-cart-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.2rem 1rem;
            color: white;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .add-to-cart-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .add-to-cart-btn:hover::before {
            left: 100%;
        }

        /* Modern Cart Icon */
        .cart-icon {
            position: relative;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .cart-icon:hover {
            background: var(--light-color);
            transform: scale(1.1);
        }

        .cart-count {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Modern Dropdown */
        .cart-dropdown {
            background: white;
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            padding: 1rem;
            min-width: 320px;
            backdrop-filter: blur(20px);
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .cart-item:hover {
            background: var(--light-color);
            border-radius: var(--border-radius);
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .cart-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .cart-item-size {
            font-size: 0.8rem;
            color: var(--secondary-color);
            margin-bottom: 0.25rem;
        }

        .cart-item-price {
            color: var(--primary-color);
            font-weight: 600;
        }

        .cart-total {
            background: var(--light-color);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 1rem;
        }

        .cart-total-text {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1.1rem;
        }

        .cart-total-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.2rem;
        }

        .view-cart-btn {
            background: var(--primary-color);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
        }

        .view-cart-btn:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Modern User Menu */
        .user-menu-btn {
            background: var(--light-color);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            color: var(--secondary-color);
            font-weight: 500;
            transition: var(--transition);
        }

        .user-menu-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .auth-buttons .btn {
            border-radius: var(--border-radius);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
        }

        .auth-buttons .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .auth-buttons .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .auth-buttons .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .auth-buttons .btn-outline-primary:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Modern Alert */
        .alert {
            border: none;
            border-radius: var(--border-radius-lg);
            padding: 1rem 1.5rem;
            margin: 1rem 0;
            box-shadow: var(--shadow-sm);
        }

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-left: 4px solid var(--info-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .search-section {
                padding: 1.5rem 0;
            }

            .search-container {
                padding: 1rem;
            }

            .search-title {
                font-size: 1.25rem;
            }

            .product-card {
                margin-bottom: 1.5rem;
            }

            .color-chip-label {
                width: 36px;
                height: 36px;
            }

            .cart-dropdown {
                min-width: 280px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Focus States */
        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--secondary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }

        /* Animation สำหรับการเลือกสี */
        @keyframes colorSelect {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1.05); }
        }
        
        .color-radio:checked + .color-chip-label {
            animation: colorSelect 0.3s ease;
        }
        
        /* เอฟเฟกต์ hover สำหรับสี */
        .color-chip-label::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.3) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }
        
        .color-chip-label:hover::before {
            transform: translateX(100%);
        }
        
        /* ป้องกันการคลิกที่สี */
        .color-chip-wrapper,
        .color-chip,
        .color-radio,
        .color-chip-label {
            pointer-events: auto !important;
        }
        
        .color-chip-wrapper:hover {
            cursor: pointer;
        }
        
        .color-chip-label:hover {
            cursor: pointer;
        }

        /* CSS สำหรับสีอ่อน */
        .color-chip-label[style*="#FFFFFF"],
        .color-chip-label[style*="#FFFF00"],
        .color-chip-label[style*="#F5F5DC"],
        .color-chip-label[style*="#87CEEB"],
        .color-chip-label[style*="#90EE90"],
        .color-chip-label[style*="#FFC0CB"] {
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            border-color: rgba(0,0,0,0.2);
        }
        
        /* CSS สำหรับสีเข้ม */
        .color-chip-label[style*="#000000"],
        .color-chip-label[style*="#FF0000"],
        .color-chip-label[style*="#0000FF"],
        .color-chip-label[style*="#008000"],
        .color-chip-label[style*="#800080"],
        .color-chip-label[style*="#A52A2A"] {
            text-shadow: 1px 1px 2px rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.3);
        }
        
        .color-name {
            display: none;
        }
        
        /* Responsive สำหรับสี */
        @media (max-width: 768px) {
            .color-chip-label {
                width: 32px;
                height: 32px;
                background-size: 40px !important;
            }
            
            .color-chips {
                gap: 6px;
            }
        }
    </style>
</body>
</html>
<!-- CSS เก่าถูกลบออกแล้ว -->
        /* CSS เก่าถูกลบออกแล้ว */
<?php mysqli_close($conn); ?>
