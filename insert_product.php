<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $po_name = $_POST['po_name'];
    $type_id = $_POST['type_id'];
    $price = $_POST['price'];
    $amount = $_POST['amount'];
    $description = $_POST['description'] ?? '';
    $detail = $_POST['detail'] ?? '';
    
    // ตรวจสอบการอัพโหลดรูปภาพ
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $allow_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // ตรวจสอบประเภทไฟล์
        if (!in_array($file['type'], $allow_types)) {
            echo "<script>
                alert('กรุณาอัพโหลดไฟล์รูปภาพ (jpg, jpeg, png) เท่านั้น');
                window.history.back();
            </script>";
            exit();
        }

        // ตรวจสอบขนาดไฟล์
        if ($file['size'] > $max_size) {
            echo "<script>
                alert('ไฟล์มีขนาดใหญ่เกินไป (ไม่เกิน 2MB)');
                window.history.back();
            </script>";
            exit();
        }
        
        // สร้างชื่อไฟล์ใหม่
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // ตรวจสอบนามสกุลไฟล์อีกครั้ง
        if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
            echo "<script>
                alert('กรุณาอัพโหลดไฟล์รูปภาพ (jpg, jpeg, png) เท่านั้น');
                window.history.back();
            </script>";
            exit();
        }
        
        // สร้างชื่อไฟล์แบบง่าย
        $new_filename = uniqid() . '.' . $extension;
        $upload_path = 'img/' . $new_filename;
        
        // ลบไฟล์เก่าถ้ามีการอัพโหลดซ้ำ
        if (file_exists($upload_path)) {
            unlink($upload_path);
        }
        
        // อัพโหลดไฟล์
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            echo "<script>
                alert('เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
                window.history.back();
            </script>";
            exit();
        }
        
        try {
            // เริ่ม transaction
            mysqli_begin_transaction($conn);
            
            // เพิ่มข้อมูลลงฐานข้อมูล
            $sql = "INSERT INTO product (po_name, type_id, price, amount, description, detail, image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siissss", 
                $po_name, $type_id, $price, $amount, 
                $description, $detail, $new_filename
            );
            
            if ($stmt->execute()) {
                // ยืนยัน transaction
                mysqli_commit($conn);
                echo "<script>
                    alert('เพิ่มสินค้าเรียบร้อยแล้ว');
                    window.location.href = 'sh_product_ad.php';
                </script>";
            } else {
                throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
            }
            
        } catch (Exception $e) {
            // ยกเลิก transaction
            mysqli_rollback($conn);
            echo "<script>
                alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "');
                window.history.back();
            </script>";
        }
        
    } else {
        echo "<script>
            alert('กรุณาเลือกรูปภาพสินค้า');
            window.history.back();
        </script>";
    }
}

mysqli_close($conn);
?>
