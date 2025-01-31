<?php
session_start();
include 'condb.php';

$type_id = $_POST['type_id'];
$type_name = $_POST['type_name'];

$sql = "UPDATE type SET type_name = '$type_name' WHERE type_id = '$type_id'";
$result = mysqli_query($conn, $sql);

if($result) {
    echo "<script>
        alert('แก้ไขประเภทสินค้าเรียบร้อย');
        window.location.href = 'type_product.php';
    </script>";
} else {
    echo "<script>
        alert('เกิดข้อผิดพลาด');
        window.history.back();
    </script>";
}

mysqli_close($conn);
?> 