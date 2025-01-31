<?php
session_start();
include 'condb.php';

// ตรวจสอบว่าตะกร้าว่างเปล่าหรือไม่
if (!isset($_SESSION["strProductID"]) || count($_SESSION["strProductID"]) == 0) {
    echo "<script>
        alert('ตะกร้าสินค้าว่างเปล่า! กลับไปเลือกสินค้า');
        window.location.href = 'sh_product.php';
    </script>";
    exit();
}

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
    <title>คำสั่งซื้อ</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>คำสั่งซื้อ</h2>
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
    <a href="sh_product.php" class="btn btn-primary">กลับไปเลือกสินค้า</a>
</div>
</body>
</html>
<?php
include 'condb.php';

// รับค่าจากฟอร์ม
$po_id = $_POST['po_id'];
$po_name = $_POST['po_name'];
$type_id = $_POST['type_id'];
$price = $_POST['price'];
$amount = $_POST['amount'];
$old_image = $_POST['old_image'];

// ตรวจสอบว่ามีการอัพโหลดรูปภาพใหม่หรือไม่
if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $image = $_FILES['image']['name'];
    $temp_name = $_FILES['image']['tmp_name'];
    
    // ลบรูปเก่า (ถ้ามี)
    if($old_image != "" && file_exists("img/".$old_image)) {
        unlink("img/".$old_image);
    }
    
    // อัพโหลดรูปใหม่
    move_uploaded_file($temp_name, "img/".$image);
} else {
    $image = $old_image;
}

// อัพเดทข้อมูลในฐานข้อมูล
$sql = "UPDATE product SET 
        po_name = '$po_name',
        type_id = '$type_id',
        price = '$price',
        amount = '$amount',
        image = '$image'
        WHERE po_id = '$po_id'";

$result = mysqli_query($conn, $sql);

if($result) {
    echo "<script>
        alert('อัพเดทข้อมูลเรียบร้อย');
        window.location.href = 'sh_product_ad.php';
    </script>";
} else {
    echo "<script>
        alert('เกิดข้อผิดพลาด');
        window.history.back();
    </script>";
}

mysqli_close($conn);
?>
