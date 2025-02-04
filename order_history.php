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
        .order-status {
            font-size: 0.9rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        .status-warning { background-color: #ffeeba; color: #856404; }
        .status-info { background-color: #b8daff; color: #004085; }
        .status-primary { background-color: #c3e6cb; color: #155724; }
        .status-success { background-color: #d4edda; color: #155724; }
        .status-danger { background-color: #f5c6cb; color: #721c24; }
        .status-secondary { background-color: #e2e3e5; color: #383d41; }
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
                        <a class="nav-link" href="sh_product.php">หน้าร้านค้า</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">ประวัติการสั่งซื้อ</h2>

        <?php if ($orders->num_rows > 0): ?>
            <div class="accordion" id="orderAccordion">
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#order<?= $order['order_id'] ?>">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div>
                                        <strong>คำสั่งซื้อ #<?= str_pad($order['order_id'], 8, '0', STR_PAD_LEFT) ?></strong>
                                        <span class="ms-3 text-muted">
                                            <?= formatThaiDate($order['order_date']) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="order-status status-<?= getStatusColor($order['status']) ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                        <span class="ms-3">
                                            ฿<?= number_format($order['total_amount'], 2) ?>
                                        </span>
                                        <span class="badge bg-secondary ms-2">
                                            <?= $order['total_items'] ?> รายการ
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="order<?= $order['order_id'] ?>" class="accordion-collapse collapse" 
                             data-bs-parent="#orderAccordion">
                            <div class="accordion-body">
                                <!-- รายละเอียดสินค้า -->
                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered table-striped">
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
                                                                 class="img-thumbnail me-2" 
                                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                                            <?= $item['po_name'] ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        ฿<?= number_format($item['price'], 2) ?>
                                                    </td>
                                                    <td class="text-center"><?= $item['quantity'] ?></td>
                                                    <td class="text-end">
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
                                                    <strong>฿<?= number_format($order['total_amount'], 2) ?></strong>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- ข้อมูลการจัดส่ง -->
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-shipping-fast me-2"></i>ข้อมูลการจัดส่ง
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>ชื่อผู้รับ:</strong> <?= $order['name'] ?></p>
                                        <p class="mb-1"><strong>ที่อยู่:</strong> <?= $order['address'] ?></p>
                                        <p class="mb-1">
                                            <?= $order['subdistrict'] ?> 
                                            <?= $order['district'] ?> 
                                            <?= $order['province'] ?> 
                                            <?= $order['zipcode'] ?>
                                        </p>
                                        <p class="mb-0"><strong>เบอร์โทร:</strong> <?= $order['phone'] ?></p>
                                    </div>
                                </div>

                                <!-- แก้ไขส่วนแสดงวันที่โอน -->
                                <div class="mt-4">
                                    <h6 class="border-bottom pb-2">หลักฐานการโอนเงิน</h6>
                                    <div class="text-center">
                                        <?php if (!empty($order['payment_slip'])): ?>
                                            <img src="slips/<?= $order['payment_slip'] ?>" 
                                                 alt="สลิปการโอนเงิน" 
                                                 class="img-fluid" style="max-height: 300px;">
                                            <p class="mt-2 text-muted">
                                                วันที่โอน: <?= formatThaiDate($order['payment_date']) ?>
                                            </p>
                                        <?php else: ?>
                                            <p class="text-muted">ไม่พบหลักฐานการโอนเงิน</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>ยังไม่มีประวัติการสั่งซื้อ
            </div>
        <?php endif; ?>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html> 