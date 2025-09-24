<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Debug: ดูข้อมูลที่ส่งมา
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
        
        // รับข้อมูลจากฟอร์ม
        $type_id = $_POST['type_id'];
        $description = $_POST['description'] ?? '';
        
        // จัดการรูปภาพ - ใช้รูปภาพแรกที่อัพโหลดเป็นรูปภาพหลัก
        $image = '';
        $first_image_found = false;
        
        // หารูปภาพแรกจากสีที่เลือก
        foreach ($_POST['sizes'] as $size) {
            $size_lower = strtolower($size);
            $colors = $_POST["colors_$size_lower"] ?? [];
            
            foreach ($colors as $color) {
                // ตรวจสอบไฟล์รูปภาพ
                $file_key = "color_images_{$size_lower}_{$color}";
                if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0 && !$first_image_found) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
                    $filename = $_FILES[$file_key]['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowed)) {
                        $newname = time() . '_' . uniqid() . '.' . $ext;
                        $upload_path = 'img/' . $newname;
                        
                        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $upload_path)) {
                            $image = $newname;
                            $first_image_found = true;
                            break 2; // ออกจาก loop ทั้งสอง
                        }
                    }
                }
                
                // ตรวจสอบ URL รูปภาพ
                $url_key = "color_images_url_{$size_lower}_{$color}";
                if (isset($_POST[$url_key]) && !empty($_POST[$url_key]) && !$first_image_found) {
                    $image = $_POST[$url_key];
                    $first_image_found = true;
                    break 2; // ออกจาก loop ทั้งสอง
                }
            }
        }
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($type_id)) {
            throw new Exception('กรุณาเลือกประเภทสินค้า');
        }
        
        if (!isset($_POST['sizes']) || empty($_POST['sizes'])) {
            throw new Exception('กรุณาเลือกไซส์อย่างน้อย 1 ไซส์');
        }
        
        // ตรวจสอบว่ามีการเลือกสีและกรอกราคาหรือไม่
        $has_valid_data = false;
        foreach ($_POST['sizes'] as $size) {
            $size_lower = strtolower($size);
            $price = $_POST["price_$size_lower"] ?? 0;
            $colors = $_POST["colors_$size_lower"] ?? [];
            
            if ($price > 0 && !empty($colors)) {
                // ตรวจสอบว่ามีสีที่มีจำนวนมากกว่า 0 หรือไม่
                $has_color_amount = false;
                foreach ($colors as $color) {
                    $color_amount_key = "color_amount_{$size_lower}_{$color}";
                    $color_amount = $_POST[$color_amount_key] ?? 0;
                    if ($color_amount > 0) {
                        $has_color_amount = true;
                        break;
                    }
                }
                
                if ($has_color_amount) {
                    $has_valid_data = true;
                    break;
                }
            }
        }
        
        if (!$has_valid_data) {
            throw new Exception('กรุณาเลือกสีและกรอกราคา/จำนวนสำหรับไซส์ที่เลือก');
        }
        
        // สร้างชื่อสินค้าจากประเภท
        $type_sql = "SELECT type_name FROM type WHERE type_id = ?";
        $type_stmt = $conn->prepare($type_sql);
        $type_stmt->bind_param("i", $type_id);
        $type_stmt->execute();
        $type_result = $type_stmt->get_result();
        $type_row = $type_result->fetch_assoc();
        $product_name = $type_row['type_name'];
        
        // เริ่ม transaction
        $conn->begin_transaction();
        
        // เพิ่มสินค้าหลัก
        $insert_product_sql = "INSERT INTO products (name, description, type_id, image) VALUES (?, ?, ?, ?)";
        $insert_product_stmt = $conn->prepare($insert_product_sql);
        $insert_product_stmt->bind_param("ssis", $product_name, $description, $type_id, $image);
        $insert_product_stmt->execute();
        
        $product_id = $conn->insert_id;
        
        // เพิ่มข้อมูลไซส์และสี
        $sizes = $_POST['sizes'];
        $insert_size_sql = "INSERT INTO product_sizes (product_base_id, size, color, price, amount, image) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_size_stmt = $conn->prepare($insert_size_sql);
        
        foreach ($sizes as $size) {
            $size_lower = strtolower($size);
            $price = $_POST["price_$size_lower"] ?? 0;
            $colors = $_POST["colors_$size_lower"] ?? [];
            
            if ($price > 0 && !empty($colors)) {
                // เพิ่มข้อมูลสำหรับแต่ละสีที่เลือก
                foreach ($colors as $color) {
                    // ตรวจสอบว่ามีการใส่ชื่อสีอื่นหรือไม่
                    $actual_color = $color;
                    if ($color === 'อื่นๆ') {
                        $custom_color_key = "custom_color_{$size_lower}";
                        if (isset($_POST[$custom_color_key]) && !empty(trim($_POST[$custom_color_key]))) {
                            $actual_color = trim($_POST[$custom_color_key]);
                        }
                    }
                    
                    // ดึงจำนวนสำหรับสีนี้
                    $color_amount_key = "color_amount_{$size_lower}_{$color}";
                    $color_amount = $_POST[$color_amount_key] ?? 0;
                    
                    // ตรวจสอบว่ามีจำนวนสีหรือไม่
                    if ($color_amount <= 0) {
                        continue; // ข้ามสีที่ไม่มีจำนวน
                    }
                    
                    // จัดการรูปภาพสำหรับสีนี้
                    $color_image = '';
                    
                    // ตรวจสอบไฟล์รูปภาพ
                    $file_key = "color_images_{$size_lower}_{$color}";
                    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
                        $filename = $_FILES[$file_key]['name'];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowed)) {
                            $newname = time() . '_' . uniqid() . '_' . $size_lower . '_' . $color . '.' . $ext;
                            $upload_path = 'img/' . $newname;
                            
                            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $upload_path)) {
                                $color_image = $newname;
                            }
                        }
                    }
                    
                    // ตรวจสอบ URL รูปภาพ
                    $url_key = "color_images_url_{$size_lower}_{$color}";
                    if (empty($color_image) && isset($_POST[$url_key]) && !empty($_POST[$url_key])) {
                        $color_image = $_POST[$url_key];
                    }
                    
                    // เพิ่มข้อมูลในฐานข้อมูล (ใช้จำนวนสีแทนจำนวนรวม)
                    $insert_size_stmt->bind_param("issdis", $product_id, $size, $actual_color, $price, $color_amount, $color_image);
                    $insert_size_stmt->execute();
                    
                    // TODO: บันทึกรูปภาพแยกตามสีในตารางแยก (ถ้าต้องการ)
                    // ตอนนี้ใช้รูปภาพหลักในตาราง products
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "เพิ่มสินค้าสำเร็จ";
        header("Location: sh_product_ad.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        if ($conn->connect_errno == 0) {
            $conn->rollback();
        }
        
        $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        error_log("Error in insert_product.php: " . $error_message);
        
        $_SESSION['error'] = $error_message;
        header("Location: add_product.php");
        exit();
    }
} else {
    header("Location: add_product.php");
    exit();
}
?>
