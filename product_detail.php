
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

// เพิ่มสินค้าลงตะกร้า
if (isset($_GET['id']) && isset($_GET['size']) && isset($_GET['quantity'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    $product_id = $_GET['id'];
    $size = strtoupper(trim($_GET['size']));
    $quantity = intval($_GET['quantity']);
    $color = isset($_GET['color']) ? trim($_GET['color']) : '';
    
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
            window.location.href = 'product_detail.php?id=" . $product_id . "';
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
        window.location.href = 'product_detail.php?id=" . $product_id . "';
    </script>";
    exit();
}

// รับค่า id และ color จาก URL
$product_id = $_GET['id'] ?? 0;
$selected_color = $_GET['color'] ?? '';

if (!$product_id) {
    header("Location: sh_product.php");
    exit();
}

// ดึงข้อมูลสินค้า
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
               GROUP_CONCAT(CONCAT(ps.size, ':', ps.color, ':', ps.price, ':', ps.amount) ORDER BY 
                   CASE ps.size
                       WHEN 'XS' THEN 1
                       WHEN 'S' THEN 2
                       WHEN 'M' THEN 3
                       WHEN 'L' THEN 4
                       WHEN 'XL' THEN 5
                       WHEN 'XXL' THEN 6
                       WHEN '3XL' THEN 7
                       ELSE 8
                   END, ps.color SEPARATOR '|') as size_details,
               GROUP_CONCAT(DISTINCT ps.color ORDER BY ps.color SEPARATOR ', ') as colors,
               MIN(ps.price) as min_price,
               MAX(ps.price) as max_price,
               SUM(ps.amount) as total_amount
        FROM products p 
        LEFT JOIN type t ON p.type_id = t.type_id 
        LEFT JOIN product_sizes ps ON p.id = ps.product_base_id
        WHERE p.id = ?
        GROUP BY p.id, p.name, p.description, p.type_id, p.image, t.type_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: sh_product.php");
    exit();
}

// สถานะการล็อกอิน
$isAuthenticated = isset($_SESSION['user_id']);

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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - รายละเอียดสินค้า</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="sh_product.php">
    
                <span class="fw-bold text-primary" style="font-size: 1.4rem;">Yaz Shop</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
                    <div class="cart-icon position-relative text-primary" data-bs-toggle="dropdown" style="cursor: pointer; padding: 8px; border-radius: 8px; transition: all 0.3s ease;">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" style="font-size: 0.7rem; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;">
                                <?= $cart_count ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-menu dropdown-menu-end cart-dropdown shadow-lg border-0" style="min-width: 350px; border-radius: 16px; padding: 0;">
                        <?php if ($cart_count > 0): ?>
                            <div class="p-3 border-bottom">
                                <h6 class="mb-0 fw-bold text-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>ตะกร้าสินค้า
                                </h6>
                            </div>
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
                                    <div class="cart-item d-flex align-items-center p-3 border-bottom">
                                        <img src="<?= htmlspecialchars($cart_image_src) ?>" 
                                             alt="<?= $item['name'] ?>"
                                             class="rounded me-3"
                                             style="width: 60px; height: 60px; object-fit: cover;"
                                             onerror="this.src='img/no-image.svg'; this.alt='ไม่มีรูปภาพ';">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold text-dark mb-1"><?= $item['name'] ?></div>
                                            <div class="text-muted small mb-1">
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
                            <div class="p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold">รวมทั้งหมด:</span>
                                    <span class="text-primary fw-bold fs-5">฿<?= number_format($cart_total, 2) ?></span>
                                </div>
                                <a href="cart.php" class="btn btn-primary w-100 fw-semibold">
                                    <i class="fas fa-shopping-cart me-2"></i>ดูตะกร้า
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted mb-1">ไม่มีสินค้าในตะกร้า</h6>
                                <p class="text-muted small mb-0">เริ่มช้อปปิ้งกันเลย!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Modern User Menu -->
                <?php if ($isAuthenticated): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle fw-semibold" type="button" id="userMenu" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($_SESSION['username']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="userMenu" style="border-radius: 12px; min-width: 200px;">
                        <li>
                            <a class="dropdown-item fw-semibold" href="profile.php?return_to=sh_product">
                                <i class="fas fa-user-circle me-2 text-primary"></i>บัญชีของฉัน
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fw-semibold" href="order_history.php">
                                <i class="fas fa-shopping-bag me-2 text-primary"></i>การซื้อของฉัน
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger fw-semibold" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
                <?php else: ?>
                <div class="auth-buttons d-flex gap-2">
                    <a class="btn btn-primary fw-semibold" href="login.php">
                        <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                    </a>
                    <a class="btn btn-outline-primary fw-semibold" href="register.php">
                        <i class="fas fa-user-plus me-2"></i>สมัครสมาชิก
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Modern Product Detail Section -->
    <div class="container-fluid py-5" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh;">
        <div class="container">
            <div class="row g-5">
                <!-- Product Image Section -->
                <div class="col-lg-6">
                    <div class="product-image-container position-relative">
                        <?php
                        // ตรวจสอบว่ารูปภาพเป็น URL หรือชื่อไฟล์
                        $image_src = '';
                        if (!empty($product['image'])) {
                            if (strpos($product['image'], 'http') === 0) {
                                // เป็น URL
                                $image_src = $product['image'];
                            } else {
                                // เป็นชื่อไฟล์
                                $image_src = 'img/' . $product['image'];
                            }
                        } else {
                            // ไม่มีรูปภาพ ใช้รูปภาพเริ่มต้น
                            $image_src = 'img/no-image.svg';
                        }
                        ?>
                        <div class="product-image-wrapper position-relative overflow-hidden rounded-4 shadow-lg" style="background: white; padding: 2rem;">
                            <img id="product-main-image" 
                                 src="<?= htmlspecialchars($image_src) ?>" 
                                 class="img-fluid w-100" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 style="max-height: 600px; object-fit: contain; transition: all 0.3s ease;"
                                 onerror="this.src='img/no-image.svg'; this.alt='ไม่มีรูปภาพ';">
                            
                            <!-- Product Badge -->
                            <?php if ($product['total_amount'] > 0): ?>
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i>มีสินค้า
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-danger fs-6 px-3 py-2">
                                        <i class="fas fa-times-circle me-1"></i>หมด
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Product Info Section -->
                <div class="col-lg-6">
                    <!-- Modern Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb bg-transparent p-0">
                            <li class="breadcrumb-item">
                                <a href="sh_product.php" class="text-decoration-none text-primary fw-semibold">
                                    <i class="fas fa-home me-1"></i>สินค้า
                                </a>
                            </li>
                            <li class="breadcrumb-item active fw-semibold" aria-current="page">
                                <?= htmlspecialchars($product['name']) ?>
                            </li>
                        </ol>
                    </nav>
                    
                    <!-- Product Title -->
                    <div class="product-header mb-4">
                        <h1 class="product-title fw-bold text-dark mb-3" style="font-size: 2.5rem; line-height: 1.2;">
                            <?= htmlspecialchars($product['name']) ?>
                        </h1>
                        <div class="product-category mb-3">
                            <span class="badge bg-primary fs-6 px-3 py-2">
                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($product['type_name']) ?>
                            </span>
                        </div>
                    </div>
                
                
                    <!-- Product Description -->
                    <?php if (!empty($product['description'])): ?>
                        <div class="product-description mb-4">
                            <p class="text-muted fs-5 lh-lg"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Price Section -->
                    <?php if ($product['total_amount'] > 0): ?>
                        <div class="price-section mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <?php if ($product['min_price'] == $product['max_price']): ?>
                                    <h2 class="price-display text-primary fw-bold mb-0" style="font-size: 2.5rem;">
                                        ฿<?= number_format($product['min_price'], 2) ?>
                                    </h2>
                                <?php else: ?>
                                    <h2 class="price-display text-primary fw-bold mb-0" style="font-size: 2.5rem;">
                                        ฿<?= number_format($product['min_price'], 2) ?> - ฿<?= number_format($product['max_price'], 2) ?>
                                    </h2>
                                <?php endif; ?>
                                <div class="stock-info">
                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        <i class="fas fa-boxes me-1"></i>คงเหลือ <?= $product['total_amount'] ?> ชิ้น
                                    </span>
                                </div>
                            </div>
                        </div>
                     
                        <!-- Modern Add to Cart Section -->
                        <div class="add-to-cart-section">
                            <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                                <div class="card-header bg-gradient-primary text-white py-4" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                                    <h5 class="mb-0 fw-bold">
                                        <i class="fas fa-shopping-cart me-2"></i>เพิ่มลงตะกร้า
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    <form action="" method="GET" class="d-flex flex-column gap-4" onsubmit="return checkLogin()">
                                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="color" id="selected-color" value="">
                                 
                                        <!-- Modern Color Picker -->
                                        <?php if (!empty($product['colors'])): ?>
                                        <div class="color-section">
                                            <div class="d-flex align-items-center mb-3">
                                                <h6 class="mb-0 fw-bold text-dark">
                                                    <i class="fas fa-palette me-2 text-primary"></i>เลือกสี:
                                                </h6>
                                            </div>
                                            <div class="color-picker-container">
                                                <div class="color-chips d-flex flex-wrap gap-3">
                                                    <?php
                                                    $colors = explode(',', $product['colors']);
                                                    foreach ($colors as $index => $color): 
                                                        $color = trim($color);
                                                        if (!empty($color)):
                                                    ?>
                                                        <div class="color-chip-wrapper" data-color="<?= htmlspecialchars($color) ?>">
                                                            <input type="radio" 
                                                                   value="<?= htmlspecialchars($color) ?>" 
                                                                   name="color" 
                                                                   id="color_<?= $index ?>"
                                                                   class="color-radio"
                                                                   <?= ($index === 0 && empty($selected_color)) || $color === $selected_color ? 'checked' : '' ?>
                                                                   onchange="updateProductImageByColor('<?= $product['id'] ?>', '<?= htmlspecialchars($color) ?>')">
                                                            <label class="color-chip-label" 
                                                                   for="color_<?= $index ?>"
                                                                   style="background-color: <?= getColorCode($color) ?>; color: <?= getTextColor($color) ?>; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 3px solid #e2e8f0; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                                                   title="<?= htmlspecialchars($color) ?>">
                                                                <span class="color-name" role="img" aria-label="<?= htmlspecialchars($color) ?>" style="font-size: 0.7rem; font-weight: 600; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                                                                    <?= htmlspecialchars($color) ?>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    <?php
                                                        endif;
                                                    endforeach;
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                 
                                        <!-- Modern Size Picker -->
                                        <div class="size-section">
                                            <div class="d-flex align-items-center mb-3">
                                                <h6 class="mb-0 fw-bold text-dark">
                                                    <i class="fas fa-tshirt me-2 text-primary"></i>เลือกไซส์:
                                                </h6>
                                            </div>
                                            <div class="size-picker" id="size-picker-container">
                                                <!-- ไซส์จะถูกอัปเดตด้วย JavaScript ตามสีที่เลือก -->
                                                <div class="text-muted p-3 text-center bg-light rounded-3">
                                                    <i class="fas fa-info-circle me-2"></i>กรุณาเลือกสีก่อน
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Modern Quantity Control -->
                                        <div class="quantity-section">
                                            <div class="d-flex align-items-center mb-3">
                                                <h6 class="mb-0 fw-bold text-dark">
                                                    <i class="fas fa-hashtag me-2 text-primary"></i>จำนวน:
                                                </h6>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="quantity-control d-flex align-items-center border rounded-3" style="background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                    <button type="button" class="quantity-btn btn btn-outline-secondary border-0" 
                                                            onclick="decrementQty(this)" style="border-radius: 8px 0 0 8px; padding: 12px 16px;">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" name="quantity" class="quantity-input form-control border-0 text-center fw-bold" 
                                                           value="1" min="1" max="1" 
                                                           onchange="validateQtyBySize(this, '<?= $product['id'] ?>')"
                                                           data-product-id="<?= $product['id'] ?>"
                                                           style="width: 80px; font-size: 1.1rem;">
                                                    <button type="button" class="quantity-btn btn btn-outline-secondary border-0" 
                                                            onclick="incrementQtyBySize(this, '<?= $product['id'] ?>')" style="border-radius: 0 8px 8px 0; padding: 12px 16px;">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <button type="submit" class="add-to-cart-btn btn btn-primary btn-lg px-5 fw-bold" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none; border-radius: 12px; padding: 12px 24px; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);">
                                                    <i class="fas fa-shopping-cart me-2"></i>เพิ่มลงตะกร้า
                                                </button>
                                            </div>
                                        </div>
                             </form>
                         </div>
                     </div>
                    
                    <?php 
                    $all_sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'];
                    $size_details_array = [];
                    $has_any_stock = false;
                    
                    if (!empty($product['size_details'])):
                        $size_details = explode('|', $product['size_details']);
                        foreach ($size_details as $detail):
                            list($size, $color, $price, $amount) = explode(':', $detail);
                            if (!isset($size_details_array[$size])) {
                                $size_details_array[$size] = [];
                            }
                            $size_details_array[$size][$color] = ['price' => $price, 'amount' => $amount];
                            if ($amount > 0) $has_any_stock = true;
                        endforeach;
                    endif;
                    
                    if ($has_any_stock):
                    ?>
                        <div class="mb-4">
                            <h5>รายละเอียดไซส์และสี</h5>
                            <div class="row">
                                <?php 
                                foreach ($all_sizes as $size):
                                    $has_stock = isset($size_details_array[$size]) && !empty($size_details_array[$size]);
                                    if ($has_stock):
                                ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-primary">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="badge bg-primary size-badge"><?= $size ?></span>
                                                    <span class="text-muted small">
                                                        <i class="fas fa-palette me-1"></i><?= count($size_details_array[$size]) ?> สี
                                                    </span>
                                                </div>
                                                
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach ($size_details_array[$size] as $color => $color_data): ?>
                                                        <div class="color-detail-item" 
                                                             style="background-color: <?= getColorCode($color) ?>; color: <?= getTextColor($color) ?>; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                                                            <?= htmlspecialchars($color) ?> (<?= $color_data['amount'] ?>)
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                
                                                <div class="mt-2 text-center">
                                                    <span class="text-success fw-bold">
                                                        ฿<?= number_format(min(array_column($size_details_array[$size], 'price')), 2) ?>
                                                        <?php if (min(array_column($size_details_array[$size], 'price')) != max(array_column($size_details_array[$size], 'price'))): ?>
                                                            - ฿<?= number_format(max(array_column($size_details_array[$size], 'price')), 2) ?>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        สินค้าหมด
                    </div>
                <?php endif; ?>
                
                <div class="d-flex gap-2">
                    <a href="sh_product.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>กลับไปหน้ารายการสินค้า
                    </a>
                    <?php if ($cart_count > 0): ?>
                        <a href="cart.php" class="btn btn-primary">
                            <i class="fas fa-shopping-cart me-2"></i>ดูตะกร้า (<?= $cart_count ?>)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // ข้อมูลไซส์และจำนวนสินค้า
    const productSizes = {
        '<?= $product['id'] ?>': {
            <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'] as $size): ?>
                '<?= $size ?>': <?= isset($size_details_array[$size]) ? $size_details_array[$size] : 0 ?>,
            <?php endforeach; ?>
        }
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

    // ฟังก์ชันใหม่สำหรับจัดการจำนวนตามไซส์
    function updateQuantityBySize(productId) {
        const selectedSize = document.querySelector(`input[name="size"]:checked`);
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
        const selectedSize = document.querySelector(`input[name="size"]:checked`);
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
            // แสดงเตือนเมื่อพยายามเพิ่มเกินจำนวนที่มี
            alert('จำนวนสินค้าไม่เพียงพอ มีสินค้าในสต็อก ' + maxQty + ' ชิ้น');
        }
    }

    function validateQtyBySize(input, productId) {
        const selectedSize = document.querySelector(`input[name="size"]:checked`);
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
        const sizeInputs = document.querySelectorAll('input[name="size"]');
        sizeInputs.forEach(input => {
            input.addEventListener('change', function() {
                updateQuantityBySize('<?= $product['id'] ?>');
            });
        });
        
        // เพิ่ม event listener สำหรับการเลือกสี
        const colorInputs = document.querySelectorAll('input[name="color"]');
        colorInputs.forEach(input => {
            input.addEventListener('change', function() {
                updateProductImageByColor('<?= $product['id'] ?>', this.value);
            });
        });
        
        // เปลี่ยนรูปภาพตามสีที่ส่งมาจากหน้า sh_product.php
        <?php if (!empty($selected_color)): ?>
            updateProductImageByColor('<?= $product['id'] ?>', '<?= htmlspecialchars($selected_color) ?>');
        <?php endif; ?>
    });

    // ข้อมูลสินค้าสำหรับ JavaScript
    const productData = {
        '<?= $product['id'] ?>': {
            colors: {
                <?php 
                if (!empty($product['size_details'])):
                    $size_details = explode('|', $product['size_details']);
                    $color_data_map = [];
                    $color_image_map = [];
                    
                    foreach ($size_details as $detail):
                        list($size, $color, $price, $amount) = explode(':', $detail);
                        
                        // เก็บข้อมูลสีและไซส์
                        if (!isset($color_data_map[$color])) {
                            $color_data_map[$color] = [];
                        }
                        $color_data_map[$color][$size] = array(
                            'price' => floatval($price),
                            'amount' => intval($amount)
                        );
                        
                        // เก็บข้อมูลรูปภาพ
                        if (!isset($color_image_map[$color])) {
                            $color_image_map[$color] = '';
                        }
                        // ใช้รูปภาพแรกที่พบสำหรับสีนี้
                        if (empty($color_image_map[$color])) {
                            // ตรวจสอบว่ามีรูปภาพสำหรับสีนี้หรือไม่
                            $color_image_sql = "SELECT image FROM product_sizes WHERE product_base_id = ? AND color = ? AND image IS NOT NULL AND image != '' LIMIT 1";
                            $color_image_stmt = $conn->prepare($color_image_sql);
                            $color_image_stmt->bind_param("is", $product['id'], $color);
                            $color_image_stmt->execute();
                            $color_image_result = $color_image_stmt->get_result();
                            $color_image_row = $color_image_result->fetch_assoc();
                            
                            if ($color_image_row && !empty($color_image_row['image'])) {
                                $color_image = $color_image_row['image'];
                                if (strpos($color_image, 'http') === 0) {
                                    $color_image_map[$color] = $color_image;
                                } else {
                                    $color_image_map[$color] = 'img/' . $color_image;
                                }
                            }
                        }
                    endforeach;
                    
                    foreach ($color_data_map as $color => $sizes):
                ?>
                '<?= htmlspecialchars($color) ?>': {
                    sizes: {
                        <?php foreach ($sizes as $size => $data): ?>
                        '<?= $size ?>': {
                            price: <?= $data['price'] ?>,
                            amount: <?= $data['amount'] ?>
                        },
                        <?php endforeach; ?>
                    },
                    image: '<?= !empty($color_image_map[$color]) ? htmlspecialchars($color_image_map[$color]) : '' ?>'
                },
                <?php endforeach; ?>
                <?php endif; ?>
            }
        }
    };

    // ฟังก์ชันอัปเดตรูปภาพและไซส์ตามสีที่เลือก
    function updateProductImageByColor(productId, selectedColor) {
        const mainImage = document.getElementById('product-main-image');
        const sizePickerContainer = document.getElementById('size-picker-container');
        const selectedColorInput = document.getElementById('selected-color');
        
        if (!mainImage || !sizePickerContainer) return;
        
        // อัปเดตค่า color ในฟอร์ม
        if (selectedColorInput) {
            selectedColorInput.value = selectedColor;
        }
        
        // อัปเดตรูปภาพ
        const product = productData[productId];
        if (product && product.colors[selectedColor] && product.colors[selectedColor].image) {
            mainImage.src = product.colors[selectedColor].image;
            mainImage.alt = '<?= htmlspecialchars($product['name']) ?> - สี ' + selectedColor;
        } else {
            // ถ้าไม่มีรูปภาพสำหรับสีนี้ ให้ใช้รูปภาพหลัก
            <?php if (!empty($product['image'])): ?>
                <?php if (strpos($product['image'], 'http') === 0): ?>
                    mainImage.src = '<?= htmlspecialchars($product['image']) ?>';
                <?php else: ?>
                    mainImage.src = 'img/<?= htmlspecialchars($product['image']) ?>';
                <?php endif; ?>
            <?php else: ?>
                mainImage.src = 'img/no-image.svg';
            <?php endif; ?>
            mainImage.alt = '<?= htmlspecialchars($product['name']) ?>';
        }
        
        // อัปเดตไซส์ตามสีที่เลือก
        updateSizesByColor(productId, selectedColor);
        
        // เพิ่มเอฟเฟกต์การเปลี่ยนรูปภาพ
        mainImage.style.opacity = '0.7';
        setTimeout(() => {
            mainImage.style.opacity = '1';
        }, 200);
    }

    // ฟังก์ชันอัปเดตไซส์ตามสีที่เลือก
    function updateSizesByColor(productId, selectedColor) {
        const sizePickerContainer = document.getElementById('size-picker-container');
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
                    <input type="radio" class="btn-check" name="size" 
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
                    <input type="radio" class="btn-check" name="size" 
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
    }

    function checkLogin() {
        <?php if (!$isAuthenticated): ?>
            alert('กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าลงตะกร้า');
            return false;
        <?php endif; ?>
        
        const selectedSize = document.querySelector('input[name="size"]:checked');
        if (!selectedSize) {
            alert('กรุณาเลือกไซส์ก่อน');
            return false;
        }
        
        return true;
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

    /* Product Image */
    .product-image-wrapper {
        transition: var(--transition);
    }

    .product-image-wrapper:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-xl);
    }

    #product-main-image {
        transition: var(--transition);
    }

    #product-main-image:hover {
        transform: scale(1.05);
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
        width: 50px;
        height: 50px;
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
        padding: 0.75rem 1.5rem;
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
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .product-title {
            font-size: 2rem !important;
        }
        
        .price-display {
            font-size: 2rem !important;
        }
        
        .color-chip-label {
            width: 40px;
            height: 40px;
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
    </style>
</body>
</html>
