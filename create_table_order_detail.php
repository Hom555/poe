<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "12345678";
$dbname = "dro";

try {
    // สร้างการเชื่อมต่อ
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // สร้างคำสั่ง SQL
    $sql = "SELECT id, orderID, pro_id, orderPrice, orderQty, Total FROM order_detail";

    // เตรียมคำสั่ง SQL
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // ดึงผลลัพธ์ทั้งหมด
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
    <title>แสดงสินค้า</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>แสดงสินค้า</h1>
    <table>
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
</body>
</html>
