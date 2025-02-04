<?php
session_start();
include 'condb.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION["strProductID"])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($_POST['cus_name'], $_POST['cus_add'], $_POST['province'], 
          $_POST['district'], $_POST['subdistrict'], $_POST['zipcode'], $_POST['cus_tel'])) {
    echo "<script>
        alert('กรุณากรอกข้อมูลให้ครบถ้วน');
        window.history.back();
    </script>";
    exit();
}

// จัดการอัพโหลดสลิป
if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] == 0) {
    $file = $_FILES['payment_slip'];
    
    // ตรวจสอบว่าเป็นไฟล์รูปภาพจริง
    $check = getimagesize($file['tmp_name']);
    if($check === false) {
        echo "<script>alert('ไฟล์ที่อัพโหลดไม่ใช่รูปภาพ'); window.history.back();</script>";
        exit();
    }
    
    // ตรวจสอบประเภทไฟล์
    $allowed_types = ['image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        echo "<script>alert('รองรับเฉพาะไฟล์ .jpg และ .png เท่านั้น'); window.history.back();</script>";
        exit();
    }

    // สร้างชื่อไฟล์ใหม่
    $timestamp = time();
    $unique = uniqid();
    $file_extension = ($file['type'] == 'image/jpeg') ? '.jpg' : '.png';
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
    mysqli_query($conn, "START TRANSACTION");

    // คำนวณยอดรวม
    $total_amount = 0;
    foreach ($_SESSION["strProductID"] as $key => $product_id) {
        $sql = "SELECT price FROM product WHERE po_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_amount += $row['price'] * $_SESSION["strQty"][$key];
    }

    // บันทึกข้อมูลการสั่งซื้อ
    $sql = "INSERT INTO orders (user_id, total_amount, name, address, province, district, 
            subdistrict, zipcode, phone, payment_slip, payment_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $status = 'รอตรวจสอบการชำระเงิน';
    $stmt->bind_param("idssssssssss", 
        $_SESSION['user_id'],
        $total_amount,
        $_POST['cus_name'],
        $_POST['cus_add'],
        $_POST['province'],
        $_POST['district'],
        $_POST['subdistrict'],
        $_POST['zipcode'],
        $_POST['cus_tel'],
        $payment_slip,
        $_POST['payment_date'],
        $status
    );
    $stmt->execute();
    $order_id = mysqli_insert_id($conn);

    // บันทึกรายละเอียดสินค้า
    foreach ($_SESSION["strProductID"] as $key => $product_id) {
        // ตรวจสอบสินค้าและจำนวนคงเหลือ
        $sql = "SELECT price, amount FROM product WHERE po_id = ? FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        $qty = $_SESSION["strQty"][$key];
        if ($product['amount'] < $qty) {
            throw new Exception("สินค้ารหัส $product_id มีไม่เพียงพอ");
        }

        // บันทึกรายละเอียดสินค้า
        $sql = "INSERT INTO order_details (order_id, product_id, quantity, price, total) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $total = $product['price'] * $qty;
        $stmt->bind_param("iiidi", $order_id, $product_id, $qty, $product['price'], $total);
        $stmt->execute();

        // อัพเดทจำนวนสินค้า
        $sql = "UPDATE product SET amount = amount - ? WHERE po_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $qty, $product_id);
        $stmt->execute();
    }

    // ยืนยัน transaction
    mysqli_query($conn, "COMMIT");
    
    // ล้างตะกร้า
    unset($_SESSION["strProductID"]);
    unset($_SESSION["strQty"]);
    
    echo "<script>
        alert('สั่งซื้อสำเร็จ เลขที่คำสั่งซื้อ: $order_id');
        window.location.href = 'order_history.php';
    </script>";

} catch (Exception $e) {
    // ยกเลิก transaction
    mysqli_query($conn, "ROLLBACK");
    
    echo "<script>
        alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "');
        window.history.back();
    </script>";
}

mysqli_close($conn);
?>
