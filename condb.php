<?php
$servername = "localhost";
$username = "root";
$password = "12345678";
$dbname = "dro"; // แก้ไขตามชื่อฐานข้อมูลที่มีอยู่จริง

// สร้างการเชื่อมต่อ
$conn = mysqli_connect($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตั้งค่า charset เป็น utf8
mysqli_set_charset($conn, "utf8");
?>
