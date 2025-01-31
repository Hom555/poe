<?php
session_start();
include 'condb.php';

if (!isset($_SESSION["strProductID"]) || count($_SESSION["strProductID"]) == 0) {
    echo "<script>
        alert('ตะกร้าสินค้าว่างเปล่า! กลับไปเลือกสินค้า');
        window.location.href = 'sh_product.php';
    </script>";
    exit();
}

if (!isset($_POST['cus_name'], $_POST['cus_add'], $_POST['cus_tel'])) {
    echo "<script>alert('ข้อมูลการสั่งซื้อไม่ครบถ้วน กรุณาลองใหม่');</script>";
    exit();
}

$cusName = mysqli_real_escape_string($conn, $_POST['cus_name']);
$cusAddress = mysqli_real_escape_string($conn, $_POST['cus_add']);
$cusTel = mysqli_real_escape_string($conn, $_POST['cus_tel']);

if (!isset($_SESSION["sum_price"]) || $_SESSION["sum_price"] <= 0) {
    $total = 0;
    foreach ($_SESSION["strProductID"] as $key => $productID) {
        $productID = mysqli_real_escape_string($conn, $productID);
        $sql = "SELECT price FROM product WHERE po_id = '$productID'";
        $result = mysqli_query($conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $qty = (int)$_SESSION["strQty"][$key];
            $total += $row['price'] * $qty;
        }
    }
    $_SESSION["sum_price"] = $total;

    if ($_SESSION["sum_price"] <= 0) {
        echo "<script>alert('ยอดรวมคำสั่งซื้อไม่ถูกต้อง กรุณาลองใหม่');</script>";
        exit();
    }
}

mysqli_query($conn, "START TRANSACTION");

try {
    $sql = "INSERT INTO tb_order (cus_name, address, telephone, total_price, order_status)
            VALUES ('$cusName', '$cusAddress', '$cusTel', '" . $_SESSION["sum_price"] . "', '1')";
    if (!mysqli_query($conn, $sql)) {
        throw new Exception("เกิดข้อผิดพลาดในการบันทึกคำสั่งซื้อ");
    }

    $orderID = mysqli_insert_id($conn);

    foreach ($_SESSION["strProductID"] as $key => $productID) {
        $productID = mysqli_real_escape_string($conn, $productID);
        $quantity = (int)$_SESSION["strQty"][$key];

        $sql1 = "SELECT price, amount FROM product WHERE po_id = '$productID'";
        $result1 = mysqli_query($conn, $sql1);
        $row1 = mysqli_fetch_assoc($result1);

        if ($row1['amount'] >= $quantity) {
            $price = $row1['price'];
            $total = $quantity * $price;

            $sql2 = "INSERT INTO order_detail (orderID, pro_id, orderPrice, orderQty, Total)
                     VALUES ('$orderID', '$productID', '$price', '$quantity', '$total')";
            mysqli_query($conn, $sql2);

            $sql3 = "UPDATE product SET amount = amount - '$quantity' WHERE po_id = '$productID'";
            mysqli_query($conn, $sql3);
        } else {
            throw new Exception("สินค้าในสต็อกไม่เพียงพอสำหรับสินค้า ID: $productID");
        }
    }

    mysqli_query($conn, "COMMIT");

    echo "<script>
            alert('บันทึกคำสั่งซื้อสำเร็จ');
            window.location.href = 'sh_product.php';
          </script>";
} catch (Exception $e) {
    mysqli_query($conn, "ROLLBACK");
    echo "<script>alert('" . $e->getMessage() . "');</script>";
}

mysqli_close($conn);

unset($_SESSION["strProductID"]);
unset($_SESSION["strQty"]);
unset($_SESSION["sum_price"]);
session_destroy();
?>
