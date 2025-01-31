<?php
session_start();
include 'condb.php';

// ดึงข้อมูลคำสั่งซื้อ
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT id, orderID, pro_id, orderPrice, orderQty, Total FROM order_detail";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดูคำสั่งซื้อ</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>ดูคำสั่งซื้อ</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ลำดับที่</th>
                <th>เลขที่ใบสั่งซื้อ</th>
                <th>รหัสสินค้า</th>
                <th>ราคาสินค้า</th>
                <th>จำนวนสินค้า</th>
                <th>ราคารวม</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($orderDetails) > 0): ?>
                <?php foreach ($orderDetails as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars(str_pad($row['orderID'], 10, '0', STR_PAD_LEFT)); ?></td>
                        <td><?php echo htmlspecialchars(str_pad($row['pro_id'], 6, '0', STR_PAD_LEFT)); ?></td>
                        <td><?php echo htmlspecialchars(number_format($row['orderPrice'], 2)); ?></td>
                        <td><?php echo htmlspecialchars($row['orderQty']); ?></td>
                        <td><?php echo htmlspecialchars(number_format($row['Total'], 2)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">ไม่มีข้อมูล</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="yaz.php" class="btn btn-primary">กลับไปเลือกสินค้า</a>
</div>
</body>
</html>
