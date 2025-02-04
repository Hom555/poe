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
    // ดึงข้อมูลคำสั่งซื้อ
    $sql = "SELECT o.*, u.username 
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

    // ถ้าไม่พบข้อมูล
    if (!$order) {
        echo "<script>
            alert('ไม่พบข้อมูลคำสั่งซื้อ');
            window.location.href = 'admin_orders.php';
        </script>";
        exit();
    }

    // ดึงรายการสินค้า
    $sql = "SELECT od.quantity, p.po_name as name, p.price 
            FROM order_details od 
            INNER JOIN product p ON od.product_id = p.po_id 
            WHERE od.order_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
    }
    
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items = $stmt->get_result();

    if (!$items) {
        throw new Exception("เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า: " . $conn->error);
    }

} catch (Exception $e) {
    // แสดงข้อผิดพลาด
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบสั่งซื้อ #<?= str_pad($order_id, 8, '0', STR_PAD_LEFT) ?></title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            .print-only { display: block; }
            @page { margin: 0.5cm; }
            body { padding: 0.5cm; }
        }
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .order-info, .customer-info {
            margin-bottom: 20px;
        }
        .table th, .table td {
            padding: 8px;
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
            <p>123/45 ถนนสุขุมวิท แขวงบางนา เขตบางนา กรุงเทพฯ 10260</p>
            <p>โทร: 098-765-4321</p>
        </div>

        <!-- ข้อมูลคำสั่งซื้อ -->
        <div class="row order-info">
            <div class="col-6">
                <h4>ใบสั่งซื้อ</h4>
                <p>เลขที่: <?= str_pad($order_id, 8, '0', STR_PAD_LEFT) ?></p>
                <p>วันที่: <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
            </div>
            <div class="col-6 customer-info">
                <h4>ข้อมูลลูกค้า</h4>
                <p>ชื่อ: <?= htmlspecialchars($order['name']) ?></p>
                <p>ที่อยู่: <?= htmlspecialchars($order['address']) ?><br>
                   <?= htmlspecialchars($order['subdistrict']) ?> 
                   <?= htmlspecialchars($order['district']) ?><br>
                   <?= htmlspecialchars($order['province']) ?> 
                   <?= htmlspecialchars($order['zipcode']) ?>
                </p>
                <p>เบอร์โทร: <?= htmlspecialchars($order['phone']) ?></p>
            </div>
        </div>

        <!-- รายการสินค้า -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th width="5%">ลำดับ</th>
                    <th width="45%">รายการ</th>
                    <th width="15%" class="text-end">ราคา/หน่วย</th>
                    <th width="15%" class="text-center">จำนวน</th>
                    <th width="20%" class="text-end">รวม</th>
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
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td class="text-end"><?= number_format($item['price'], 2) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-end"><?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end"><strong>รวมทั้งสิ้น</strong></td>
                    <td class="text-end"><strong><?= number_format($total, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <!-- ลายเซ็น -->
        <div class="row mt-5">
            <div class="col-6 text-center">
                <p>____________________</p>
                <p>ผู้รับสินค้า</p>
                <p>วันที่ ____/____/____</p>
            </div>
            <div class="col-6 text-center">
                <p>____________________</p>
                <p>ผู้ส่งสินค้า</p>
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