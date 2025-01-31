<?php
$servername = "localhost";
$dbusername = "root";
$dbpassword = "12345678";
$dbname = "dro";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($servername, $dbusername, $dbpassword, $dbname);
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// ปิดการเชื่อมต่ออัตโนมัติในตอนจบ
register_shutdown_function(function () use ($conn) {
    if ($conn) {
        mysqli_close($conn);
    }
});
?>
