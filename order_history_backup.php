<?php
session_start();
include 'condb.php';
date_default_timezone_set('Asia/Bangkok');  // ตั้งค่า timezone เป็นประเทศไทย

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลรายการสั่งซื้อ
$sql = "SELECT o.*, COUNT(od.id) as total_items 
        FROM orders o 
        LEFT JOIN order_details od ON o.order_id = od.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.order_id 
        ORDER BY o.order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result();

// ฟังก์ชันกำหนดสีตามสถานะ
function getStatusColor($status) {
    switch ($status) {
        case 'รอการชำระเงิน':
            return 'warning';
        case 'รอตรวจสอบการชำระเงิน':
            return 'info';
        case 'ชำระเงินแล้ว':
            return 'info';
        case 'กำลังจัดส่ง':
            return 'primary';
        case 'จัดส่งแล้ว':
            return 'success';
        case 'ยกเลิก':
            return 'danger';
        default:
            return 'secondary';
    }
}

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

// เพิ่มฟังก์ชันแปลงวันที่
function formatThaiDate($date) {
    $timestamp = strtotime($date);
    $year = date('Y', $timestamp) + 543;
    return date('d/m/', $timestamp) . $year . date(' H:i', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการสั่งซื้อ</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
        }
        .order-status {
            font-size: 0.85rem;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-warning { 
            background-color: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeeba;
        }
        .status-info { 
            background-color: #cce5ff; 
            color: #004085; 
            border: 1px solid #b8daff;
        }
        .status-primary { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        .status-success { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        .status-danger { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }
        .status-secondary { 
            background-color: #e2e3e5; 
            color: #383d41; 
            border: 1px solid #d6d8db;
        }
        .accordion-item {
            border: none;
            background: white;
            border-radius: 10px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
            margin-bottom: 1rem;
        }
        .accordion-button {
            border-radius: 10px !important;
            background: white;
        }
        .accordion-button:not(.collapsed) {
            background-color: #f8f9fa;
            color: #0d6efd;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
            border-radius: 10px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            padding: 1rem;
            font-weight: 600;
        }
        .payment-slip {
            max-height: 300px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .badge {
            padding: 0.5em 0.8em;
            border-radius: 6px;
        }
        .order-total {
            font-size: 1.1rem;
            color: #0d6efd;
        }
        .shipping-info {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand fw-bold" href="sh_product.php">
                <i class="fas fa-shopping-bag me-2"></i>Yaz Shop
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="sh_product.php">
                            <i class="fas fa-store me-1"></i>หน้าร้านค้า
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>ตะกร้าสินค้า
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i>โปรไฟล์
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">
                    <i class="fas fa-history me-2 text-primary"></i>ประวัติการสั่งซื้อ
                </h2>
                <p class="text-muted mb-0 mt-1">
                    <i class="fas fa-info-circle me-1"></i>ดูรายละเอียดการสั่งซื้อ พร้อมรูปภาพและสีที่เลือก
                </p>
            </div>
        </div>

        <?php if ($orders->num_rows > 0): ?>
            <div class="accordion" id="orderAccordion">
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#order<?= $order['order_id'] ?>">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div>
                                        <div class="text-muted">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            <?= formatThaiDate($order['order_date']) ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="order-status status-<?= getStatusColor($order['status']) ?>">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i><?= $order['status'] ?>
                                        </div>
                                        <div class="mt-2">
                                            <span class="order-total">
                                                <i class="fas fa-baht-sign me-1"></i>฿<?= number_format($order['total_amount'], 2) ?>
                                            </span>
                                            <span class="badge bg-secondary ms-2">
                                                <i class="fas fa-box me-1"></i><?= $order['total_items'] ?> รายการ
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="order<?= $order['order_id'] ?>" class="accordion-collapse collapse" 
                             data-bs-parent="#orderAccordion">
                            <div class="accordion-body">
                                <!-- รายละเอียดสินค้า -->
                                <div class="table-responsive mb-3">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <i class="fas fa-box me-1"></i>สินค้า
                                                </th>
                                                <th class="text-center">
                                                    <i class="fas fa-tag me-1"></i>ราคา
                                                </th>
                                                <th class="text-center">
                                                    <i class="fas fa-hashtag me-1"></i>จำนวน
                                                </th>
                                                <th class="text-end">
                                                    <i class="fas fa-calculator me-1"></i>รวม
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // แก้ไข SQL query ให้ตรงกับโครงสร้างฐานข้อมูลใหม่
                                            // ตรวจสอบว่าตารางมีคอลัมน์ size และ color หรือไม่
                                            $check_columns_sql = "SHOW COLUMNS FROM order_details LIKE 'size'";
                                            $check_result = $conn->query($check_columns_sql);
                                            $has_size_column = $check_result && $check_result->num_rows > 0;
                                            
                                            // Debug: ดูว่ามีคอลัมน์ size หรือไม่
                                            echo "<!-- Debug: Has size column: " . ($has_size_column ? 'YES' : 'NO') . " -->";
                                            
                                            if ($has_size_column) {
                                                // ใช้คอลัมน์ size และ color จาก order_details
                                                $detail_sql = "SELECT od.*, p.name, p.image, 
                                                                    COALESCE(od.size, 'ไม่ระบุ') as size, 
                                                                    COALESCE(od.color, 'ไม่ระบุ') as color, 
                                                                    ps.image as color_image
                                                             FROM order_details od 
                                                             LEFT JOIN products p ON od.product_id = p.id 
                                                             LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                                                                AND od.size = ps.size 
                                                                AND od.color = ps.color
                                                             WHERE od.order_id = ?
                                                             ORDER BY od.id";
                                                echo "<!-- Debug: Using NEW structure with size/color columns -->";
                                            } else {
                                                // ใช้โครงสร้างเดิม (ไม่มีคอลัมน์ size และ color)
                                                // ลองหา size และ color จาก product_sizes โดยใช้ราคา
                                                $detail_sql = "SELECT od.*, p.name, p.image, 
                                                                    COALESCE(ps.size, 'ไม่ระบุ') as size, 
                                                                    COALESCE(ps.color, 'ไม่ระบุ') as color, 
                                                                    COALESCE(ps.image, p.image) as color_image
                                                             FROM order_details od 
                                                             LEFT JOIN products p ON od.product_id = p.id 
                                                             LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                                                                AND od.price = ps.price
                                                             WHERE od.order_id = ?
                                                             GROUP BY od.id
                                                             ORDER BY od.id";
                                                echo "<!-- Debug: Using OLD structure with price-based JOIN -->";
                                            }
                                            $detail_stmt = $conn->prepare($detail_sql);
                                            $detail_stmt->bind_param("i", $order['order_id']);
                                            $detail_stmt->execute();
                                            $details = $detail_stmt->get_result();
                                            
                                            while ($item = $details->fetch_assoc()):
                                                // Debug: ดูข้อมูลที่ได้จากฐานข้อมูล
                                                echo "<!-- Debug: Item data: " . json_encode($item) . " -->";
                                                
                                                // ดึงรูปภาพที่ตรงกับสีที่เลือก
                                                $image_src = '';
                                                
                                                // ลำดับความสำคัญ: รูปภาพสำหรับสี > รูปภาพหลัก > รูปภาพ default
                                                if (!empty($item['color_image']) && $item['color_image'] !== $item['image']) {
                                                    // ใช้รูปภาพสำหรับสีที่เลือก (ถ้าไม่ใช่รูปภาพหลัก)
                                                    if (strpos($item['color_image'], 'http') === 0) {
                                                        $image_src = $item['color_image'];
                                                    } else {
                                                        $image_src = 'img/' . $item['color_image'];
                                                    }
                                                } elseif (!empty($item['image'])) {
                                                    // ใช้รูปภาพหลัก
                                                    if (strpos($item['image'], 'http') === 0) {
                                                        $image_src = $item['image'];
                                                    } else {
                                                        $image_src = 'img/' . $item['image'];
                                                    }
                                                } else {
                                                    // ใช้รูปภาพ default
                                                    $image_src = 'img/no-image.svg';
                                                }
                                                
                                                // ตรวจสอบว่าเป็นคำสั่งซื้อเก่าหรือไม่
                                                $is_old_order = ($item['size'] === 'ไม่ระบุ' || $item['color'] === 'ไม่ระบุ' || 
                                                               empty($item['size']) || empty($item['color']));
                                                
                                                // Debug: ดูรูปภาพที่เลือก
                                                echo "<!-- Debug: Selected image: " . $image_src . " -->";
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?= htmlspecialchars($image_src) ?>" 
                                                                 class="product-image me-3"
                                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                                 onerror="this.src='img/no-image.svg'; this.alt='ไม่มีรูปภาพ';">
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($item['name']) ?></div>
                                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                                    <?php if (!$is_old_order && !empty($item['size']) && $item['size'] !== 'ไม่ระบุ'): ?>
                                                                        <span class="badge bg-primary" style="font-size: 0.7rem;">
                                                                            <i class="fas fa-tshirt me-1"></i><?= htmlspecialchars($item['size']) ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <?php if (!$is_old_order && !empty($item['color']) && $item['color'] !== 'ไม่ระบุ'): ?>
                                                                        <span class="badge" style="font-size: 0.7rem; background-color: <?= getColorCode($item['color']) ?>; color: <?= getTextColor($item['color']) ?>;">
                                                                            <i class="fas fa-palette me-1"></i><?= htmlspecialchars($item['color']) ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <?php if ($is_old_order): ?>
                                                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                                                            <i class="fas fa-exclamation-triangle me-1"></i>ข้อมูลเก่า
                                                                        </span>
                                                                        <br><small class="text-muted">ไม่มีข้อมูลสี/ไซส์</small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge bg-success text-white">
                                                            ฿<?= number_format($item['price'], 2) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge bg-light text-dark">
                                                            <?= $item['quantity'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end align-middle fw-bold">
                                                        <span class="badge bg-primary text-white">
                                                            ฿<?= number_format($item['total'], 2) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <td colspan="3" class="text-end">
                                                    <strong>
                                                        <i class="fas fa-calculator me-1"></i>รวมทั้งหมด
                                                    </strong>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="text-primary fs-5">
                                                        <i class="fas fa-baht-sign me-1"></i>฿<?= number_format($order['total_amount'], 2) ?>
                                                    </strong>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- ข้อมูลการจัดส่ง -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <i class="fas fa-shipping-fast me-2 text-primary"></i>ข้อมูลการจัดส่ง
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($order['shipping_name']) || !empty($order['shipping_address'])): ?>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-2">
                                                        <i class="fas fa-user me-2 text-muted"></i>
                                                        <strong>ชื่อผู้รับ:</strong> 
                                                        <?= !empty($order['shipping_name']) ? htmlspecialchars($order['shipping_name']) : '<span class="text-muted">ไม่ระบุ</span>' ?>
                                                    </p>
                                                    <p class="mb-2">
                                                        <i class="fas fa-phone me-2 text-muted"></i>
                                                        <strong>เบอร์โทร:</strong> 
                                                        <?= !empty($order['shipping_phone']) ? htmlspecialchars($order['shipping_phone']) : '<span class="text-muted">ไม่ระบุ</span>' ?>
                                                    </p>
                                                    <p class="mb-2">
                                                        <i class="fas fa-truck me-2 text-muted"></i>
                                                        <strong>วิธีการจัดส่ง:</strong> 
                                                        <?= !empty($order['shipping_method']) ? htmlspecialchars($order['shipping_method']) : '<span class="text-muted">ไม่ระบุ</span>' ?>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-2">
                                                        <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                                        <strong>ที่อยู่จัดส่ง:</strong>
                                                    </p>
                                                    <p class="mb-0 ps-4">
                                                        <?= !empty($order['shipping_address']) ? nl2br(htmlspecialchars($order['shipping_address'])) : '<span class="text-muted">ไม่ระบุ</span>' ?>
                                                    </p>
                                                    
                                                    <?php if (!empty($order['tracking_number'])): ?>
                                                        <p class="mb-2 mt-3">
                                                            <i class="fas fa-barcode me-2 text-muted"></i>
                                                            <strong>เลขติดตาม:</strong> 
                                                            <span class="badge bg-info text-white"><?= htmlspecialchars($order['tracking_number']) ?></span>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($order['shipping_date'])): ?>
                                                        <p class="mb-2">
                                                            <i class="fas fa-shipping-fast me-2 text-muted"></i>
                                                            <strong>วันที่จัดส่ง:</strong> 
                                                            <?= formatThaiDate($order['shipping_date']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($order['delivery_date'])): ?>
                                                        <p class="mb-2">
                                                            <i class="fas fa-check-circle me-2 text-muted"></i>
                                                            <strong>วันที่ส่งมอบ:</strong> 
                                                            <?= formatThaiDate($order['delivery_date']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center text-muted py-4">
                                                <i class="fas fa-shipping-fast fa-3x mb-3"></i>
                                                <p class="mb-0">ยังไม่มีข้อมูลการจัดส่ง</p>
                                                <small>ข้อมูลจะแสดงเมื่อมีการจัดส่งสินค้า</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- หลักฐานการโอนเงิน -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <i class="fas fa-receipt me-2 text-primary"></i>หลักฐานการโอนเงิน
                                    </div>
                                    <div class="card-body text-center">
                                        <?php if (!empty($order['payment_slip'])): ?>
                                            <img src="slips/<?= htmlspecialchars($order['payment_slip']) ?>" 
                                                 alt="สลิปการโอนเงิน" 
                                                 class="payment-slip">
                                            <p class="mt-3 text-muted">
                                                <i class="far fa-clock me-1"></i>
                                                วันที่โอน: <?= formatThaiDate($order['payment_date']) ?>
                                            </p>
                                        <?php else: ?>
                                            <div class="text-muted py-4">
                                                <i class="fas fa-receipt fa-3x mb-3"></i>
                                                <p class="mb-0">ไม่พบหลักฐานการโอนเงิน</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-info-circle fa-lg me-3"></i>
                <div>
                    ยังไม่มีประวัติการสั่งซื้อ
                    <a href="sh_product.php" class="alert-link ms-2">เริ่มช้อปปิ้งเลย!</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html> 