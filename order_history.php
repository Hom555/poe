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
            <a class="navbar-brand fw-bold" href="#">ร้านค้า</a>
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-history me-2 text-primary"></i>ประวัติการสั่งซื้อ
            </h2>
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
                                        <strong class="fs-5">คำสั่งซื้อ #<?= str_pad($order['order_id'], 8, '0', STR_PAD_LEFT) ?></strong>
                                        <div class="text-muted mt-1">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            <?= formatThaiDate($order['order_date']) ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="order-status status-<?= getStatusColor($order['status']) ?>">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i><?= $order['status'] ?>
                                        </div>
                                        <div class="mt-2">
                                            <span class="order-total">฿<?= number_format($order['total_amount'], 2) ?></span>
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
                                                <th>สินค้า</th>
                                                <th class="text-center">ราคา</th>
                                                <th class="text-center">จำนวน</th>
                                                <th class="text-end">รวม</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $detail_sql = "SELECT od.*, p.po_name, p.image 
                                                         FROM order_details od 
                                                         LEFT JOIN product p ON od.product_id = p.po_id 
                                                         WHERE od.order_id = ?";
                                            $detail_stmt = $conn->prepare($detail_sql);
                                            $detail_stmt->bind_param("i", $order['order_id']);
                                            $detail_stmt->execute();
                                            $details = $detail_stmt->get_result();
                                            
                                            while ($item = $details->fetch_assoc()):
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="img/<?= $item['image'] ?>" 
                                                                 class="product-image me-3"
                                                                 alt="<?= $item['po_name'] ?>">
                                                            <div>
                                                                <div class="fw-bold"><?= $item['po_name'] ?></div>
                                                                <small class="text-muted">รหัสสินค้า: <?= $item['product_id'] ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        ฿<?= number_format($item['price'], 2) ?>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge bg-light text-dark">
                                                            <?= $item['quantity'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end align-middle fw-bold">
                                                        ฿<?= number_format($item['total'], 2) ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end">
                                                    <strong>รวมทั้งหมด</strong>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="text-primary fs-5">฿<?= number_format($order['total_amount'], 2) ?></strong>
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
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="fas fa-user me-2 text-muted"></i>
                                                    <strong>ชื่อผู้รับ:</strong> <?= $order['name'] ?>
                                                </p>
                                                <p class="mb-2">
                                                    <i class="fas fa-phone me-2 text-muted"></i>
                                                    <strong>เบอร์โทร:</strong> <?= $order['phone'] ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                                    <strong>ที่อยู่:</strong>
                                                </p>
                                                <p class="mb-0 ps-4">
                                                    <?= $order['address'] ?><br>
                                                    <?= $order['subdistrict'] ?> 
                                                    <?= $order['district'] ?><br>
                                                    <?= $order['province'] ?> 
                                                    <?= $order['zipcode'] ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- หลักฐานการโอนเงิน -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <i class="fas fa-receipt me-2 text-primary"></i>หลักฐานการโอนเงิน
                                    </div>
                                    <div class="card-body text-center">
                                        <?php if (!empty($order['payment_slip'])): ?>
                                            <img src="slips/<?= $order['payment_slip'] ?>" 
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