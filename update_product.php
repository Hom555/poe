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
        
        // Debug: ตรวจสอบ form enctype
        error_log("Content-Type: " . $_SERVER['CONTENT_TYPE']);
        
        // Debug: ตรวจสอบ sizes ที่ส่งมา
        if (isset($_POST['sizes'])) {
            error_log("Sizes: " . print_r($_POST['sizes'], true));
        } else {
            error_log("No sizes found in POST data");
        }
        
        // รับข้อมูลจากฟอร์ม
        if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
            throw new Exception('ไม่พบ product_id');
        }
        if (!isset($_POST['type_id']) || empty($_POST['type_id'])) {
            throw new Exception('ไม่พบ type_id');
        }
        if (!isset($_POST['old_image'])) {
            throw new Exception('ไม่พบ old_image');
        }
        
        $product_id = intval($_POST['product_id']);
        $type_id = intval($_POST['type_id']);
        $description = trim($_POST['description'] ?? '');
        $old_image = $_POST['old_image'];
        $return_to = $_POST['return_to'] ?? 'sh_product_ad';
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($product_id) || empty($type_id)) {
            throw new Exception('ข้อมูลไม่ครบถ้วน');
        }
        

        

        
        // ใช้รูปภาพเดิมเป็นรูปภาพหลัก (ไม่เปลี่ยนรูปภาพหลัก)
        $image = $old_image;
        
        // จัดการรูปภาพแยกตามสี (เก็บในตัวแปร array)
        $color_images = array();
        
        // ตรวจสอบว่ามีการเลือกไซส์หรือไม่
        if (!isset($_POST['sizes']) || empty($_POST['sizes'])) {
            throw new Exception('กรุณาเลือกไซส์อย่างน้อย 1 ไซส์');
        }
        
        // ตรวจสอบว่าสินค้ามีอยู่จริง
        $product_sql = "SELECT id FROM products WHERE id = ?";
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        
        if (!$product_result->fetch_assoc()) {
            throw new Exception("ไม่พบสินค้านี้");
        }
        
        // ตรวจสอบว่าประเภทสินค้ามีอยู่จริง
        $type_sql = "SELECT type_id FROM type WHERE type_id = ?";
        $type_stmt = $conn->prepare($type_sql);
        $type_stmt->bind_param("s", strval($type_id));
        $type_stmt->execute();
        $type_result = $type_stmt->get_result();
        
        if (!$type_result->fetch_assoc()) {
            throw new Exception("ไม่พบประเภทสินค้าที่เลือก");
        }
        
        // เริ่ม transaction
        $conn->begin_transaction();
        
        // อัพเดทข้อมูลสินค้าหลัก
        $update_product_sql = "UPDATE products SET description = ?, type_id = ?, image = ? WHERE id = ?";
        $update_product_stmt = $conn->prepare($update_product_sql);
        
        // แปลง type_id เป็น string เพื่อให้ตรงกับ ZEROFILL
        $type_id_str = strval($type_id);
        
        // ตรวจสอบข้อมูลก่อน bind
        if ($description === null) $description = '';
        if ($type_id_str === null) $type_id_str = '';
        if ($image === null) $image = '';
        if ($product_id === null) $product_id = 0;
        
        $update_product_stmt->bind_param("sssi", $description, $type_id_str, $image, $product_id);
        
        $result = $update_product_stmt->execute();
        
        if (!$result) {
            throw new Exception("ไม่สามารถอัปเดตข้อมูลสินค้าได้: " . $update_product_stmt->error);
        }
        
        // ดึงข้อมูลไซส์เก่าที่มีอยู่
        $existing_sizes_sql = "SELECT size, color, price, amount, image FROM product_sizes WHERE product_base_id = ?";
        $existing_sizes_stmt = $conn->prepare($existing_sizes_sql);
        $existing_sizes_stmt->bind_param("i", $product_id);
        $existing_sizes_stmt->execute();
        $existing_sizes_result = $existing_sizes_stmt->get_result();
        
        $existing_data = array();
        while ($row = $existing_sizes_result->fetch_assoc()) {
            $key = $row['size'] . '_' . $row['color'];
            $existing_data[$key] = $row;
        }
        
        // เตรียม SQL สำหรับ INSERT และ UPDATE
        $insert_size_sql = "INSERT INTO product_sizes (product_base_id, size, color, image, price, amount) VALUES (?, ?, ?, ?, ?, ?)";
        $update_size_sql = "UPDATE product_sizes SET image = ?, price = ?, amount = ? WHERE product_base_id = ? AND size = ? AND color = ?";
        $delete_size_sql = "DELETE FROM product_sizes WHERE product_base_id = ? AND size = ? AND color = ?";
        
        $insert_stmt = $conn->prepare($insert_size_sql);
        $update_stmt = $conn->prepare($update_size_sql);
        $delete_stmt = $conn->prepare($delete_size_sql);
        
        $sizes = $_POST['sizes'];
        $processed_keys = array();
        $color_images = array(); // ประกาศตัวแปรสำหรับเก็บรูปภาพสี
        
        foreach ($sizes as $size) {
            $size_lower = strtolower($size);
            $price = $_POST["price_$size_lower"] ?? 0;
            $colors = $_POST["colors_$size_lower"] ?? [];
            
            error_log("Processing size: $size, price: $price, colors: " . print_r($colors, true));
            
            if ($price > 0 && !empty($colors)) {
                // เพิ่มข้อมูลสำหรับแต่ละสีที่เลือก
                foreach ($colors as $color) {
                    error_log("Processing color: $color for size: $size");
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
                    error_log("Checking file key: " . $file_key);
                    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
                        error_log("File found: " . $file_key . " - " . $_FILES[$file_key]['name']);
                        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
                        $filename = $_FILES[$file_key]['name'];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowed)) {
                            $newname = time() . '_' . uniqid() . '_' . $size_lower . '_' . $color . '.' . $ext;
                            $upload_path = 'img/' . $newname;
                            
                            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $upload_path)) {
                                $color_image = $newname;
                                $color_images[$size . '_' . $color] = $color_image;
                                error_log("File uploaded successfully: " . $color_image);
                            } else {
                                error_log("Failed to move uploaded file: " . $upload_path);
                            }
                        } else {
                            error_log("Invalid file extension: " . $ext);
                        }
                    } else {
                        error_log("No file uploaded for key: " . $file_key);
                    }
                    
                    // ตรวจสอบ URL รูปภาพ
                    $url_key = "color_images_url_{$size_lower}_{$color}";
                    if (empty($color_image) && isset($_POST[$url_key]) && !empty($_POST[$url_key])) {
                        $color_image = $_POST[$url_key];
                        $color_images[$size . '_' . $color] = $color_image;
                    }
                    
                    // ถ้าไม่มีรูปภาพใหม่ ให้ใช้รูปภาพเดิม
                    if (empty($color_image)) {
                        $key = $size . '_' . $actual_color;
                        if (isset($existing_data[$key]) && !empty($existing_data[$key]['image'])) {
                            $color_image = $existing_data[$key]['image'];
                            error_log("Using existing image: " . $color_image);
                        }
                    }
                    
                    $key = $size . '_' . $actual_color;
                    $processed_keys[] = $key;
                    
                    if (isset($existing_data[$key])) {
                        // อัพเดทข้อมูลที่มีอยู่
                        $update_stmt->bind_param("sdiiss", $color_image, $price, $color_amount, $product_id, $size, $actual_color);
                        $update_stmt->execute();
                    } else {
                        // เพิ่มข้อมูลใหม่
                        $insert_stmt->bind_param("isssdi", $product_id, $size, $actual_color, $color_image, $price, $color_amount);
                        $insert_stmt->execute();
                    }
                }
            }
        }
        
        // ลบข้อมูลที่ไม่ได้เลือกไว้
        foreach ($existing_data as $key => $data) {
            if (!in_array($key, $processed_keys)) {
                $delete_stmt->bind_param("iss", $product_id, $data['size'], $data['color']);
                $delete_stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // เก็บข้อมูลรูปภาพแยกตามสีไว้ใน session (สำหรับการแสดงผล)
        if (!empty($color_images)) {
            $_SESSION['color_images_' . $product_id] = $color_images;
        }
        
        $_SESSION['success'] = "แก้ไขข้อมูลสินค้าสำเร็จ";
        
        // ตรวจสอบว่ามาจากหน้าไหน
        $return_to = $_POST['return_to'] ?? 'sh_product_ad';
        if ($return_to == 'yaz') {
            header("Location: yaz.php");
        } else {
            header("Location: sh_product_ad.php");
        }
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        
        // ตรวจสอบว่ามาจากหน้าไหน
        $return_to = $_POST['return_to'] ?? 'sh_product_ad';
        if ($return_to == 'yaz') {
            header("Location: edit_product.php?id=" . $product_id . "&return_to=yaz");
        } else {
            header("Location: edit_product.php?id=" . $product_id);
        }
        exit();
    }
} else {
    // ตรวจสอบว่ามาจากหน้าไหน
    $return_to = $_POST['return_to'] ?? 'sh_product_ad';
    if ($return_to == 'yaz') {
        header("Location: yaz.php");
    } else {
        header("Location: sh_product_ad.php");
    }
    exit();
}
?>
