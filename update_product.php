<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $po_id = $_POST['po_id'];
    $po_name = $_POST['po_name'];
    $type_id = $_POST['type_id'];
    $price = $_POST['price'];
    $amount = $_POST['amount'];
    $old_image = $_POST['old_image'];

    // ตรวจสอบการอัพโหลดรูปภาพใหม่
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $temp_name = $_FILES['image']['tmp_name'];
        
        // อัพโหลดรูปภาพ
        move_uploaded_file($temp_name, "img/$image");
    } else {
        $image = $old_image;
    }

    // อัพเดทข้อมูล
    $sql = "UPDATE product SET 
            po_name = ?, 
            type_id = ?,
            price = ?,
            amount = ?,
            image = ?
            WHERE po_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siissi", $po_name, $type_id, $price, $amount, $image, $po_id);

    if ($stmt->execute()) {
        echo "<script>
            alert('อัพเดทข้อมูลสำเร็จ');
            window.location.href = 'sh_product_ad.php';
        </script>";
    } else {
        echo "<script>
            alert('เกิดข้อผิดพลาดในการอัพเดทข้อมูล');
            window.history.back();
        </script>";
    }
}
?>
