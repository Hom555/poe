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
    $description = $_POST['description'] ?? '';
    $detail = $_POST['detail'] ?? '';
    $old_image = $_POST['old_image'];
    
    try {
        // เริ่ม transaction
        mysqli_begin_transaction($conn);
        
        // ตรวจสอบการอัพโหลดรูปภาพใหม่
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allow_types = ['image/jpeg', 'image/jpg', 'image/png'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            // ตรวจสอบประเภทไฟล์
            if (!in_array($file['type'], $allow_types)) {
                throw new Exception('กรุณาอัพโหลดไฟล์รูปภาพ (jpg, jpeg, png) เท่านั้น');
            }

            // ตรวจสอบขนาดไฟล์
            if ($file['size'] > $max_size) {
                throw new Exception('ไฟล์มีขนาดใหญ่เกินไป (ไม่เกิน 2MB)');
            }
            
            // สร้างชื่อไฟล์ใหม่
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $new_filename = time() . '_' . uniqid() . '.' . $extension;
            $upload_path = 'img/' . $new_filename;
            
            // อัพโหลดไฟล์
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                throw new Exception('เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
            }
            
            // ลบรูปภาพเก่า
            if (file_exists('img/' . $old_image)) {
                unlink('img/' . $old_image);
            }
            
            // อัพเดทข้อมูลพร้อมรูปภาพใหม่
            $sql = "UPDATE product SET 
                    po_name = ?, 
                    type_id = ?, 
                    price = ?, 
                    amount = ?,
                    description = ?,
                    detail = ?,
                    image = ? 
                    WHERE po_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siidsssi", 
                $po_name, $type_id, $price, $amount, 
                $description, $detail, $new_filename, $po_id
            );
            
        } else {
            // อัพเดทข้อมูลโดยไม่เปลี่ยนรูปภาพ
            $sql = "UPDATE product SET 
                    po_name = ?, 
                    type_id = ?, 
                    price = ?, 
                    amount = ?,
                    description = ?,
                    detail = ?
                    WHERE po_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siidssi", 
                $po_name, $type_id, $price, $amount, 
                $description, $detail, $po_id
            );
        }
        
        if (!$stmt->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error);
        }
        
        // ยืนยัน transaction
        mysqli_commit($conn);
        
        echo "<script>
            alert('แก้ไขข้อมูลเรียบร้อยแล้ว');
            window.location.href = 'sh_product_ad.php';
        </script>";
        
    } catch (Exception $e) {
        // ยกเลิก transaction
        mysqli_rollback($conn);
        echo "<script>
            alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "');
            window.history.back();
        </script>";
    }
}

mysqli_close($conn);
?>
