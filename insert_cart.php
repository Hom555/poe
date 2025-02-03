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

// เพิ่มการตรวจสอบไฟล์
if (!isset($_FILES['payment_slip']) || $_FILES['payment_slip']['error'] !== UPLOAD_ERR_OK) {
    echo "<script>
        alert('กรุณาแนบสลิปการโอนเงิน');
        window.history.back();
    </script>";
    exit();
}

// ตรวจสอบประเภทไฟล์
$allowed = ['jpg', 'jpeg', 'png'];
$file_ext = strtolower(pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION));
if (!in_array($file_ext, $allowed)) {
    echo "<script>
        alert('รองรับเฉพาะไฟล์ภาพ jpg, jpeg, png เท่านั้น');
        window.history.back();
    </script>";
    exit();
}

// ตรวจสอบขนาดไฟล์ (2MB)
if ($_FILES['payment_slip']['size'] > 2 * 1024 * 1024) {
    echo "<script>
        alert('ขนาดไฟล์ต้องไม่เกิน 2MB');
        window.history.back();
    </script>";
    exit();
}

// สร้างชื่อไฟล์ใหม่
$new_filename = uniqid() . '.' . $file_ext;
$upload_path = 'uploads/slips/' . $new_filename;

// อัพโหลดไฟล์
if (!move_uploaded_file($_FILES['payment_slip']['tmp_name'], $upload_path)) {
    echo "<script>
        alert('เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
        window.history.back();
    </script>";
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
        $new_filename,
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
