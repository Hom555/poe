<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $user_id = $_GET['id'];
    $new_status = $_GET['status'];
    
    // ป้องกันการเปลี่ยนสถานะตัวเอง
    if ($user_id == $_SESSION['user_id']) {
        echo "<script>
            alert('ไม่สามารถเปลี่ยนสถานะตัวเองได้');
            window.location.href = 'users.php';
        </script>";
        exit();
    }

    $sql = "UPDATE users SET status = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $new_status, $user_id);
    
    if ($stmt->execute()) {
        echo "<script>
            alert('เปลี่ยนสถานะผู้ใช้งานเรียบร้อยแล้ว');
            window.location.href = 'users.php';
        </script>";
    } else {
        echo "<script>
            alert('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
            window.location.href = 'users.php';
        </script>";
    }
}
?> 