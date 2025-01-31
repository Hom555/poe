<?php
ob_start();
session_start();
include 'condb.php';

// ตรวจสอบว่ามีการส่งค่า id มาหรือไม่
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    die("Invalid product ID.");
}

$productId = $_GET["id"];

// ถ้าไม่มีข้อมูลใน session ให้เริ่มต้น
if (!isset($_SESSION["intLine"])) {
    $_SESSION["intLine"] = 0;
    $_SESSION["strProductID"][0] = $productId; // รหัสสินค้า
    $_SESSION["strQty"][0] = 1;                // จำนวนสินค้า
    header("location:cart.php");
    exit(); // ป้องกันการทำงานต่อหลัง redirect
}

// ตรวจสอบว่ารหัสสินค้านี้มีอยู่ใน session หรือไม่
$key = array_search($productId, $_SESSION["strProductID"]);
if ($key !== false) { // ถ้ามีสินค้าใน session แล้ว
    $_SESSION["strQty"][$key] += 1; // เพิ่มจำนวนสินค้า
} else { // ถ้าไม่มีสินค้าใน session
    $_SESSION["intLine"]++;
    $intNewLine = $_SESSION["intLine"];
    $_SESSION["strProductID"][$intNewLine] = $productId;
    $_SESSION["strQty"][$intNewLine] = 1;
}





// เปลี่ยนเส้นทางไปยัง cart.php เพื่อป้องกันปัญหาลูป
header("location:cart.php");
exit();
?>
