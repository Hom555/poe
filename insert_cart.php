<?php
session_start();
include 'condb.php';

// เปิดการแสดงข้อผิดพลาดเพื่อการ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || !isset($_SESSION["strProductID"])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($_POST['first_name'], $_POST['last_name'], $_POST['cus_add'], $_POST['province'], 
          $_POST['district'], $_POST['subdistrict'], $_POST['zipcode'], $_POST['cus_tel'])) {
    echo "<script>
        alert('กรุณากรอกข้อมูลให้ครบถ้วน');
        window.history.back();
    </script>";
    exit();
}

// รวมชื่อและนามสกุล
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$full_name = trim($first_name . ' ' . $last_name);

// ตรวจสอบว่าชื่อและนามสกุลไม่ว่างเปล่า
if (empty($first_name) || empty($last_name)) {
    echo "<script>
        alert('กรุณากรอกชื่อและนามสกุลให้ครบถ้วน');
        window.history.back();
    </script>";
    exit();
}

// จัดการอัพโหลดสลิป
$payment_slip = '';
if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] == 0) {
    $file = $_FILES['payment_slip'];
    
    // ตรวจสอบว่าเป็นไฟล์รูปภาพจริง
    $check = getimagesize($file['tmp_name']);
    if($check === false) {
        echo "<script>alert('ไฟล์ที่อัพโหลดไม่ใช่รูปภาพ'); window.history.back();</script>";
        exit();
    }
    
    // ตรวจสอบประเภทไฟล์
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        echo "<script>alert('รองรับเฉพาะไฟล์ .jpg, .png และ .webp เท่านั้น'); window.history.back();</script>";
        exit();
    }

    // สร้างชื่อไฟล์ใหม่
    $timestamp = time();
    $unique = uniqid();
    
    // กำหนดนามสกุลไฟล์ตาม MIME type
    $file_extension = '.jpg'; // default
    switch($file['type']) {
        case 'image/jpeg':
            $file_extension = '.jpg';
            break;
        case 'image/png':
            $file_extension = '.png';
            break;
        case 'image/webp':
            $file_extension = '.webp';
            break;
        default:
            $file_extension = '.jpg';
            break;
    }
    
    $new_filename = $timestamp . '_' . $unique . $file_extension;
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!file_exists('slips')) {
        mkdir('slips', 0777, true);
    }
    
    // ย้ายไฟล์
    $upload_path = 'slips/' . $new_filename;
    
    // อ่านไฟล์รูปภาพและบันทึกใหม่
    $image_data = file_get_contents($file['tmp_name']);
    if ($image_data === false) {
        echo "<script>alert('ไม่สามารถอ่านไฟล์รูปภาพได้'); window.history.back();</script>";
        exit();
    }
    
    if (file_put_contents($upload_path, $image_data) === false) {
        echo "<script>alert('ไม่สามารถบันทึกไฟล์รูปภาพได้'); window.history.back();</script>";
        exit();
    }
    
    chmod($upload_path, 0777);
    $payment_slip = $new_filename;
} else {
    echo "<script>alert('กรุณาแนบสลิปการโอนเงิน'); window.history.back();</script>";
    exit();
}

try {
    // เริ่ม transaction
    $conn->begin_transaction();

    // อัพเดทข้อมูลลูกค้าในตาราง users
    $update_user_sql = "UPDATE users SET 
        name = ?, 
        address = ?, 
        province = ?, 
        district = ?, 
        subdistrict = ?, 
        zipcode = ?, 
        phone = ? 
        WHERE user_id = ?";
    $update_user_stmt = $conn->prepare($update_user_sql);
    if (!$update_user_stmt) {
        throw new Exception("ไม่สามารถเตรียมคำสั่ง SQL สำหรับอัพเดทข้อมูลลูกค้าได้: " . $conn->error);
    }
    
    $update_user_stmt->bind_param("sssssssi", 
        $full_name,
        $_POST['cus_add'],
        $_POST['province'],
        $_POST['district'],
        $_POST['subdistrict'],
        $_POST['zipcode'],
        $_POST['cus_tel'],
        $_SESSION['user_id']
    );
    
    if (!$update_user_stmt->execute()) {
        throw new Exception("ไม่สามารถอัพเดทข้อมูลลูกค้าได้: " . $update_user_stmt->error);
    }
    $update_user_stmt->close();

    // คำนวณยอดรวม
    $total_amount = 0;
    foreach ($_SESSION["strProductID"] as $key => $product_id) {
        $size = $_SESSION["strSize"][$key];
        $color = $_SESSION["strColor"][$key] ?? 'ขาว'; // ใช้สีขาวเป็น default ถ้าไม่มี
        
        $sql = "SELECT ps.price FROM product_sizes ps WHERE ps.product_base_id = ? AND ps.size = ? AND ps.color = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("ไม่สามารถเตรียมคำสั่ง SQL ได้: " . $conn->error);
        }
        $stmt->bind_param("iss", $product_id, $size, $color);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row) {
            $total_amount += $row['price'] * $_SESSION["strQty"][$key];
        }
        $stmt->close();
    }

    // บันทึกข้อมูลการสั่งซื้อ - แก้ไขให้ตรงกับโครงสร้างฐานข้อมูลใหม่
    $sql = "INSERT INTO orders (user_id, total_amount, payment_slip, payment_date, status) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("ไม่สามารถเตรียมคำสั่ง SQL สำหรับ orders ได้: " . $conn->error);
    }
    
    $status = 'รอตรวจสอบการชำระเงิน';
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d H:i:s');
    
    $stmt->bind_param("idsss", 
        $_SESSION['user_id'],
        $total_amount,
        $payment_slip,
        $payment_date,
        $status
    );
    
    if (!$stmt->execute()) {
        throw new Exception("ไม่สามารถบันทึกข้อมูลการสั่งซื้อได้: " . $stmt->error);
    }
    
    $order_id = $conn->insert_id;
    $stmt->close();

    // บันทึกรายละเอียดสินค้า
    foreach ($_SESSION["strProductID"] as $key => $product_id) {
        $size = $_SESSION["strSize"][$key];
        $color = $_SESSION["strColor"][$key] ?? 'ขาว'; // ใช้สีขาวเป็น default ถ้าไม่มี
        $qty = $_SESSION["strQty"][$key];
        
        // ตรวจสอบสินค้าและจำนวนคงเหลือ
        $sql = "SELECT ps.price, ps.amount FROM product_sizes ps WHERE ps.product_base_id = ? AND ps.size = ? AND ps.color = ? FOR UPDATE";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("ไม่สามารถเตรียมคำสั่ง SQL สำหรับตรวจสอบสต็อกได้: " . $conn->error);
        }
        $stmt->bind_param("iss", $product_id, $size, $color);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if (!$product) {
            throw new Exception("ไม่พบสินค้ารหัส $product_id ไซส์ $size สี $color");
        }

        if ($product['amount'] < $qty) {
            throw new Exception("สินค้ารหัส $product_id ไซส์ $size สี $color มีไม่เพียงพอ (มีในคลัง: {$product['amount']} ชิ้น)");
        }

        // บันทึกรายละเอียดสินค้า
        $sql = "INSERT INTO order_details (order_id, product_id, quantity, price, total, size, color) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("ไม่สามารถเตรียมคำสั่ง SQL สำหรับ order_details ได้: " . $conn->error);
        }
        
        $total = $product['price'] * $qty;
        $stmt->bind_param("iiidiss", $order_id, $product_id, $qty, $product['price'], $total, $size, $color);
        
        if (!$stmt->execute()) {
            throw new Exception("ไม่สามารถบันทึกรายละเอียดสินค้าได้: " . $stmt->error);
        }
        $stmt->close();

        // อัพเดทจำนวนสินค้า
        $sql = "UPDATE product_sizes SET amount = amount - ? WHERE product_base_id = ? AND size = ? AND color = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("ไม่สามารถเตรียมคำสั่ง SQL สำหรับอัพเดทสต็อกได้: " . $conn->error);
        }
        $stmt->bind_param("iiss", $qty, $product_id, $size, $color);
        
        if (!$stmt->execute()) {
            throw new Exception("ไม่สามารถอัพเดทจำนวนสินค้าได้: " . $stmt->error);
        }
        $stmt->close();
    }

    // ยืนยัน transaction
    $conn->commit();
    
    // ล้างตะกร้า
    unset($_SESSION["strProductID"]);
    unset($_SESSION["strQty"]);
    unset($_SESSION["strSize"]);
    unset($_SESSION["strColor"]);
    
    echo "<script>
        alert('สั่งซื้อสำเร็จ เลขที่คำสั่งซื้อ: $order_id');
        window.location.href = 'order_history.php';
    </script>";

} catch (Exception $e) {
    // ยกเลิก transaction
    if ($conn->connect_errno == 0) {
        $conn->rollback();
    }
    
    echo "<script>
        alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "');
        window.history.back();
    </script>";
}

if (isset($conn)) {
    $conn->close();
}
?>
