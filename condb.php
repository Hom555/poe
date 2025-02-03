<?php
$servername = "localhost";
$dbusername = "root";
$dbpassword = "12345678";
$dbname = "dro";

// สร้างการเชื่อมต่อ
$conn = mysqli_connect($servername, $dbusername, $dbpassword, $dbname);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ปิดการเชื่อมต่ออัตโนมัติในตอนจบ
register_shutdown_function(function () use ($conn) {
    if ($conn) {
        mysqli_close($conn);
    }
});
?>
