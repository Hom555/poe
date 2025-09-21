<?php
// แสดง error ทั้งหมด
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'condb.php';


// ตรวจสอบ order_id
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo "<script>
        alert('กรุณาระบุเลขที่คำสั่งซื้อ');
        window.location.href = 'admin_orders.php';
    </script>";
    exit();
}

$order_id = intval($_GET['order_id']); // แปลงเป็นตัวเลข

try {
    // ดึงข้อมูลคำสั่งซื้อและข้อมูลลูกค้า - แก้ไขให้ตรงกับโครงสร้างฐานข้อมูลใหม่
    $sql = "SELECT o.*, u.name, u.address, u.province, u.district, u.subdistrict, u.zipcode, u.phone
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.user_id 
            WHERE o.order_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
    }
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        throw new Exception("ไม่พบข้อมูลคำสั่งซื้อ");
    }

    // ดึงรายการสินค้า - แก้ไขให้ตรงกับโครงสร้างฐานข้อมูลใหม่
    // ตรวจสอบว่าตารางมีคอลัมน์ size และ color หรือไม่
    $check_columns_sql = "SHOW COLUMNS FROM order_details LIKE 'size'";
    $check_result = $conn->query($check_columns_sql);
    $has_size_column = $check_result && $check_result->num_rows > 0;
    
    if ($has_size_column) {
        // ใช้คอลัมน์ size และ color จาก order_details
        $sql = "SELECT od.quantity, od.price, p.name as product_name, p.id as product_id, 
                COALESCE(od.size, 'ไม่ระบุ') as size,
                COALESCE(od.color, 'ไม่ระบุ') as color
                FROM order_details od 
                JOIN products p ON od.product_id = p.id 
                WHERE od.order_id = ?";
    } else {
        // Fallback สำหรับโครงสร้างเก่า
        $sql = "SELECT od.quantity, od.price, p.name as product_name, p.id as product_id, 
                COALESCE(ps.size, 'ไม่ระบุ') as size,
                COALESCE(ps.color, 'ไม่ระบุ') as color
                FROM order_details od 
                JOIN products p ON od.product_id = p.id 
                LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                    AND od.price = ps.price
                WHERE od.order_id = ?";
    }
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
    }
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items = $stmt->get_result();

    if (!$items) {
        throw new Exception("เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า");
    }

} catch (Exception $e) {
    echo "<script>
        alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "');
        window.location.href = 'admin_orders.php';
    </script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบสั่งซื้อ</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            .print-only { display: block; }
            @page { 
                margin: 1cm; 
                size: A4;
            }
            body { 
                padding: 0; 
                font-size: 12px;
                line-height: 1.4;
            }
            .container {
                max-width: 100%;
            }
        }
        .company-info {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-info h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .order-info, .customer-info {
            margin-bottom: 25px;
        }
        .order-info h4, .customer-info h4 {
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .table {
            margin-bottom: 30px;
        }
        .table th, .table td {
            padding: 8px;
            border: 1px solid #333;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .table tfoot td {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .payment-slip {
            margin-top: 20px;
            text-align: center;
        }
        .payment-slip img {
            border: 1px solid #333;
            max-width: 100%;
        }
        .signature-section {
            margin-top: 50px;
        }
        .signature-section p {
            margin-bottom: 5px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            width: 200px;
            margin: 0 auto 20px auto;
        }
        
    </style>
</head>
<body class="p-4">
    <div class="container">
        <!-- ปุ่มพิมพ์และย้อนกลับ -->
        <div class="text-end mb-3 no-print">
            <a href="admin_orders.php" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> ย้อนกลับ
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> พิมพ์
            </button>
        </div>

        <!-- ข้อมูลร้าน -->
        <div class="company-info">
            <h2>Yaz Shop</h2>
            <p>ห้วยหมากแดง ตำบลท่าหินโงม อำเภอเมืองชัยภูมิ ชัยภูมิ 36000</p>
            <p>โทร: 09x-xxx-xxxx</p>
        </div>

        <!-- ข้อมูลคำสั่งซื้อ -->
        <div class="row order-info">
            <div class="col-6">
                <h4>ใบสั่งซื้อ</h4>
                <p><strong>เลขที่คำสั่งซื้อ:</strong> #<?= $order['order_id'] ?></p>
                <p><strong>วันที่สั่งซื้อ:</strong> <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
                <p><strong>สถานะ:</strong> <?= htmlspecialchars($order['status'] ?? 'ไม่ระบุ') ?></p>
                <?php if (!empty($order['payment_date'])): ?>
                    <p><strong>วันที่ชำระเงิน:</strong> <?= date('d/m/Y H:i', strtotime($order['payment_date'])) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-6 customer-info">
                <h4>ข้อมูลลูกค้า</h4>
                <p>ชื่อ: <?= htmlspecialchars($order['name'] ?? 'ไม่ระบุ') ?></p>
                <p>ที่อยู่: <?= htmlspecialchars($order['address'] ?? 'ไม่ระบุ') ?><br>
                   <?= htmlspecialchars($order['subdistrict'] ?? '') ?> 
                   <?= htmlspecialchars($order['district'] ?? '') ?><br>
                   <?= htmlspecialchars($order['province'] ?? '') ?> 
                   <?= htmlspecialchars($order['zipcode'] ?? '') ?>
                </p>
                <p>เบอร์โทร: <?= htmlspecialchars($order['phone'] ?? 'ไม่ระบุ') ?></p>
            </div>
        </div>

        <!-- รายการสินค้า -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th width="5%">ลำดับ</th>
                    <th width="35%">รายการ</th>
                    <th width="10%">ไซส์</th>
                    <th width="10%">สี</th>
                    <th width="15%" class="text-end">ราคา/หน่วย</th>
                    <th width="10%" class="text-center">จำนวน</th>
                    <th width="15%" class="text-end">รวม</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                $total = 0;
                while ($item = $items->fetch_assoc()):
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td>
                        <?php if (!empty($item['size']) && $item['size'] !== 'ไม่ระบุ'): ?>
                            <?= $item['size'] ?>
                        <?php else: ?>
                            <span style="color: #6c757d;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($item['color']) && $item['color'] !== 'ไม่ระบุ'): ?>
                            <?= htmlspecialchars($item['color']) ?>
                        <?php else: ?>
                            <span style="color: #6c757d;">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">฿<?= number_format($item['price'], 2) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-end">฿<?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end"><strong>รวมทั้งสิ้น</strong></td>
                    <td class="text-end"><strong>฿<?= number_format($order['total_amount'], 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <!-- หลักฐานการโอนเงิน -->
        <?php if (!empty($order['payment_slip'])): ?>
        <div class="row mt-4">
            <div class="col-12">
                <h5>หลักฐานการโอนเงิน</h5>
                <div class="text-center">
                    <img src="slips/<?= htmlspecialchars($order['payment_slip']) ?>" 
                         alt="สลิปการโอนเงิน" 
                         class="img-fluid border" 
                         style="max-height: 300px;">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ลายเซ็น -->
        <div class="row signature-section">
            <div class="col-6 text-center">
                <div class="signature-line"></div>
                <p><strong>ผู้รับสินค้า</strong></p>
                <p>วันที่ ____/____/____</p>
            </div>
            <div class="col-6 text-center">
                <div class="signature-line"></div>
                <p><strong>ผู้ส่งสินค้า</strong></p>
                <p>วันที่ ____/____/____</p>
            </div>
        </div>
    </div>
</body>
</html>
<?php
if (isset($conn)) {
    mysqli_close($conn);
}
?> 