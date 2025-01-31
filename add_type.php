<?php
session_start();
include 'condb.php';

$type_name = $_POST['type_name'];

$sql = "INSERT INTO type (type_name) VALUES ('$type_name')";
$result = mysqli_query($conn, $sql);

if($result) {
    echo "<script>
        alert('เพิ่มประเภทสินค้าเรียบร้อย');
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