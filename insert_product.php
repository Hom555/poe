<?php
include 'condb.php';

// รับค่าจากฟอร์ม
$pname = $_POST['pname'];
$typeID = $_POST['typeID'];
$price = $_POST['price'];
$num = $_POST['num'];

// ตรวจสอบและอัปโหลดไฟล์
if (isset($_FILES['file1']) && $_FILES['file1']['error'] == 0) {
    $target_dir = "img/"; // โฟลเดอร์เก็บรูปภาพ
    $target_file = $target_dir . basename($_FILES["file1"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // ตรวจสอบว่าไฟล์เป็นรูปภาพ
    $check = getimagesize($_FILES["file1"]["tmp_name"]);
    if ($check === false) {
        die("ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ");
    }

    // ตรวจสอบประเภทของไฟล์ที่อนุญาต
    $allowed_types = ["jpg", "png", "jpeg", "gif"];
    if (!in_array($imageFileType, $allowed_types)) {
        die("อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG, GIF เท่านั้น");
    }

    // ย้ายไฟล์ไปยังโฟลเดอร์
    if (move_uploaded_file($_FILES["file1"]["tmp_name"], $target_file)) {
        $image_name = basename($_FILES["file1"]["name"]);
    } else {
        die("เกิดข้อผิดพลาดในการอัปโหลดไฟล์");
    }
} else {
    die("กรุณาอัปโหลดไฟล์รูปภาพ");
}

// เพิ่มข้อมูลลงฐานข้อมูล
$sql = "INSERT INTO product (po_name, type_id, price, amount, image) 
        VALUES ('$pname', '$typeID', '$price', '$num', '$image_name')";

if ($conn->query($sql) === TRUE) {
    echo "เพิ่มข้อมูลสำเร็จ";
    header("Location: show_product.php"); // เปลี่ยนเส้นทางไปหน้าแสดงสินค้า
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
