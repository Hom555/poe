<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    
    try {
        // เริ่ม transaction
        $conn->begin_transaction();
        
        // ดึงข้อมูลรูปภาพก่อนลบ
        $image_sql = "SELECT image FROM products WHERE id = ?";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("i", $product_id);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $image_row = $image_result->fetch_assoc();
        
        // ลบข้อมูลไซส์ก่อน
        $delete_sizes_sql = "DELETE FROM product_sizes WHERE product_base_id = ?";
        $delete_sizes_stmt = $conn->prepare($delete_sizes_sql);
        $delete_sizes_stmt->bind_param("i", $product_id);
        $delete_sizes_stmt->execute();
        
        // ลบข้อมูลสินค้าหลัก
        $delete_product_sql = "DELETE FROM products WHERE id = ?";
        $delete_product_stmt = $conn->prepare($delete_product_sql);
        $delete_product_stmt->bind_param("i", $product_id);
        $delete_product_stmt->execute();
        
        // ลบรูปภาพจากเซิร์ฟเวอร์
        if ($image_row && $image_row['image'] && !strpos($image_row['image'], 'http')) {
            $image_path = 'img/' . $image_row['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "ลบสินค้าสำเร็จ";
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "ไม่พบรหัสสินค้า";
}

header("Location: sh_product_ad.php");
exit();
?> 