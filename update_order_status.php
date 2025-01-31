<?php
session_start();
include 'condb.php';

$order_id = $_POST['order_id'];
$order_status = $_POST['order_status'];

$sql = "UPDATE tb_order SET order_status = '$order_status' WHERE order_id = '$order_id'";
$result = mysqli_query($conn, $sql);

if($result) {
    echo "<script>
        alert('อัพเดทสถานะเรียบร้อย');
        window.location.href = 'order_detail.php';
    </script>";
} else {
    echo "<script>
        alert('เกิดข้อผิดพลาด');
        window.history.back();
    </script>";
}

mysqli_close($conn);
?> 