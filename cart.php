<?php
session_start();

// ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
if (!isset($_SESSION["strProductID"]) || count($_SESSION["strProductID"]) == 0) {
    echo "<script>
        alert('ตะกร้าสินค้าว่างเปล่า! กลับไปเลือกสินค้า');
        window.location.href = 'sh_product.php';
    </script>";
    exit();
}

include 'condb.php';

// ตรวจสอบการลบสินค้า
if (isset($_GET['remove']) && isset($_SESSION["strProductID"][$_GET['remove']])) {
    $key = intval($_GET['remove']); // แปลง key ให้เป็น integer เพื่อความปลอดภัย
    unset($_SESSION["strProductID"][$key]);
    unset($_SESSION["strQty"][$key]);

    // จัดเรียง index ใหม่หลังลบ
    $_SESSION["strProductID"] = array_values($_SESSION["strProductID"]);
    $_SESSION["strQty"] = array_values($_SESSION["strQty"]);
    
    echo "<script>
        alert('ลบสินค้าสำเร็จ');
        window.location.href = 'cart.php';
    </script>";
    exit();
}

// ตรวจสอบการเพิ่ม/ลดจำนวนสินค้า
if (isset($_GET['changeQty']) && isset($_GET['key']) && isset($_SESSION["strQty"][$_GET['key']])) {
    $key = intval($_GET['key']); // แปลง key ให้เป็น integer เพื่อความปลอดภัย
    $change = $_GET['changeQty'];

    // ตรวจสอบการเพิ่มหรือลด
    if ($change == 'add') {
        $_SESSION["strQty"][$key]++;
    } elseif ($change == 'sub' && $_SESSION["strQty"][$key] > 1) {
        $_SESSION["strQty"][$key]--;
    }
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
<form id="form1" method="POST" action="insert_cart.php">
    <h2>ตะกร้าสินค้า</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>รหัสสินค้า</th>
                <th>ชื่อสินค้า</th>
                <th>จำนวน</th>
                <th>ราคา</th>
                <th>รวม</th>
                <th>เพิ่ม - ลด</th>
                <th>การจัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // ตรวจสอบข้อมูลที่เก็บในตะกร้า
            if (isset($_SESSION["strProductID"])) {
                $total = 0;
                foreach ($_SESSION["strProductID"] as $key => $productId) {
                    $productId = mysqli_real_escape_string($conn, $productId);

                    // ดึงข้อมูลสินค้าจากฐานข้อมูล
                    $sql = "SELECT * FROM product WHERE po_id = '$productId'";
                    $result = mysqli_query($conn, $sql);
                    if ($result && $row = mysqli_fetch_assoc($result)) {
                        $qty = $_SESSION["strQty"][$key];
                        $subtotal = $row['price'] * $qty;
                        $total += $subtotal;

                        echo "<tr>";
                        echo "<td>{$row['po_id']}</td>";
                        echo "<td>{$row['po_name']}</td>";
                        echo "<td>{$qty}</td>";
                        echo "<td>" . number_format($row['price'], 2) . "</td>";
                        echo "<td>" . number_format($subtotal, 2) . "</td>";
                        echo "<td>
                            <a href='cart.php?changeQty=add&key={$key}' class='btn btn-success btn-sm'>+</a>
                            <a href='cart.php?changeQty=sub&key={$key}' class='btn btn-warning btn-sm'>-</a>
                        </td>";
                        echo "<td><a href='cart.php?remove={$key}' class='btn btn-danger btn-sm'>ลบ</a></td>";
                        echo "</tr>";
                    } else {
                        echo "<tr><td colspan='7'>ไม่พบข้อมูลสินค้านี้</td></tr>";
                    }
                }
            } else {
                echo "<tr><td colspan='7'>ไม่มีสินค้าที่ถูกเพิ่มลงในตะกร้า</td></tr>";
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-end"><b>ยอดรวมทั้งหมด</b></td>
                <td><?= isset($total) ? number_format($total, 2) : '0.00'; ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <a href="sh_product.php" class="btn btn-primary">กลับไปเลือกสินค้า</a>
    
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="alert alert-success text-center" role="alert">
            <h4>ข้อมูลสำหรับจัดส่งสินค้า</h4>
        </div>
        <form>
            <!-- ชื่อ-นามสกุล -->
            <div class="mb-3">
                <label for="cusName" class="form-label">ชื่อ-นามสกุล:</label>
                <input type="text" id="cusName" name="cus_name" class="form-control" required placeholder="ชื่อ-นามสกุล ...">
            </div>
            <!-- ที่อยู่ -->
            <div class="mb-3">
                <label for="cusAdd" class="form-label">ที่อยู่จัดส่งสินค้า:</label>
                <textarea id="cusAdd" name="cus_add" class="form-control" required placeholder="ที่อยู่ ..." rows="3"></textarea>
            </div>
            <!-- เบอร์โทรศัพท์ -->
            <div class="mb-3">
                <label for="cusTel" class="form-label">เบอร์โทรศัพท์:</label>
                <input type="number" id="cusTel" name="cus_tel" class="form-control" required placeholder="เบอร์โทรศัพท์ ...">
            </div>
            <!-- ปุ่มยืนยัน -->
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success px-4">ยืนยันการสั่งซื้อ</button>
            </div>
        </form>
    </div>
</div>
</form>
</body>
</html>

<?php
// ปิดการเชื่อมต่อฐานข้อมูล
mysqli_close($conn);
?>
