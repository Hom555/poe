<?php
session_start();
include 'condb.php';

// เปิดการแสดงข้อผิดพลาดสำหรับการดีบั๊ก
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบการล็อกอินและสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่าเป็นการ POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit();
}

try {
    // รับข้อมูลจากฟอร์ม
    $email = trim($_POST['email']);
    $password = $_POST['password']; // เก็บรหัสผ่านแบบไม่เข้ารหัส
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $subdistrict = trim($_POST['subdistrict']);
    $district = trim($_POST['district']);
    $province = trim($_POST['province']);
    $zipcode = trim($_POST['zipcode']);
    $status = intval($_POST['status']);

    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
        header("Location: users.php");
        exit();
    }

    // ตรวจสอบรูปแบบอีเมล
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "รูปแบบอีเมลไม่ถูกต้อง";
        header("Location: users.php");
        exit();
    }

    // ตรวจสอบความยาวรหัสผ่าน
    if (strlen($password) < 6) {
        $_SESSION['error'] = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
        header("Location: users.php");
        exit();
    }

    // ตรวจสอบว่ามี email ซ้ำหรือไม่
    $check_sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception("Error preparing check statement: " . $conn->error);
    }
    
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();

    if ($check_row['count'] > 0) {
        $_SESSION['error'] = "อีเมลนี้มีอยู่ในระบบแล้ว";
        header("Location: users.php");
        exit();
    }

    // เตรียม SQL สำหรับเพิ่มผู้ใช้
    $sql = "INSERT INTO users (email, password, name, phone, 
            address, subdistrict, district, province, zipcode, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing insert statement: " . $conn->error);
    }

    // รวมชื่อและนามสกุล
    $full_name = trim($first_name . ' ' . $last_name);

    $stmt->bind_param("sssssssssi", 
        $email, 
        $password, // เก็บรหัสผ่านแบบไม่เข้ารหัส
        $full_name,
        $phone,
        $address, 
        $subdistrict, 
        $district, 
        $province, 
        $zipcode, 
        $status
    );

    // ดำเนินการเพิ่มผู้ใช้
    if ($stmt->execute()) {
        $_SESSION['success'] = "เพิ่มผู้ใช้งานเรียบร้อยแล้ว";
    } else {
        throw new Exception("Error executing insert: " . $stmt->error);
    }

} catch (Exception $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มผู้ใช้งาน: " . $e->getMessage();
    error_log("User registration error: " . $e->getMessage());
} finally {
    // ปิดการเชื่อมต่อ
    if (isset($check_stmt) && $check_stmt instanceof mysqli_stmt) {
        $check_stmt->close();
    }
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

// กลับไปยังหน้า users.php
header("Location: users.php");
exit(); 