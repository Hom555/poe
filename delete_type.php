<?php
session_start();
include 'condb.php';

$type_id = $_GET['id'];

// ตรวจสอบว่ามีสินค้าในประเภทนี้หรือไม่
$check_sql = "SELECT COUNT(*) as count FROM product WHERE type_id = '$type_id'";
$check_result = mysqli_query($conn, $check_sql);
$row = mysqli_fetch_assoc($check_result);

if($row['count'] > 0) {
    echo "<script>
        alert('ไม่สามารถลบได้เนื่องจากมีสินค้าในประเภทนี้');
        window.history.back();
    </script>";
} else {
    $sql = "DELETE FROM type WHERE type_id = '$type_id'";
    $result = mysqli_query($conn, $sql);

    if($result) {
        echo "<script>
            alert('ลบประเภทสินค้าเรียบร้อย');
            window.location.href = 'type_product.php';
        </script>";
    } else {
        echo "<script>
            alert('เกิดข้อผิดพลาด');
            window.history.back();
        </script>";
    }
}

mysqli_close($conn);
?> 