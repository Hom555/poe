<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // ป้องกันการลบตัวเอง
    if ($user_id == $_SESSION['user_id']) {
        echo "<script>
            alert('ไม่สามารถลบบัญชีตัวเองได้');
            window.location.href = 'users.php';
        </script>";
        exit();
    }

    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo "<script>
            alert('ลบผู้ใช้งานเรียบร้อยแล้ว');
            window.location.href = 'users.php';
        </script>";
    } else {
        echo "<script>
            alert('เกิดข้อผิดพลาดในการลบผู้ใช้งาน');
            window.location.href = 'users.php';
        </script>";
    }
}
?>