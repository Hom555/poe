<?php
session_start();
include 'condb.php';

// รับค่า id สินค้าที่ต้องการลบ
$po_id = $_GET['id'];

// ดึงข้อมูลรูปภาพของสินค้าที่จะลบ
$sql = "SELECT image FROM product WHERE po_id = '$po_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$image = $row['image'];

// ลบรูปภาพออกจากโฟลเดอร์ (ถ้ามี)
if($image != "" && file_exists("img/".$image)) {
    unlink("img/".$image);
}

// ลบข้อมูลสินค้าออกจากฐานข้อมูล
$delete_sql = "DELETE FROM product WHERE po_id = '$po_id'";
$delete_result = mysqli_query($conn, $delete_sql);

if($delete_result) {
    echo "<script>
        alert('ลบสินค้าเรียบร้อยแล้ว');
        window.location.href = 'sh_product_ad.php';
    </script>";
} else {
    echo "<script>
        alert('เกิดข้อผิดพลาดในการลบสินค้า');
        window.history.back();
    </script>";
}

mysqli_close($conn);
?> 