<?php
session_start();
include 'condb.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
if (!isset($_SESSION["strProductID"]) || empty($_SESSION["strProductID"])) {
    header("Location: cart.php");
    exit();
}

// ลบสินค้าที่หมดจากตะกร้า
$items_to_remove = [];
foreach ($_SESSION["strProductID"] as $key => $productId) {
    $size = $_SESSION["strSize"][$key] ?? '';
    
    if (!empty($size)) {
        $stock_sql = "SELECT ps.amount, p.name 
                      FROM product_sizes ps 
                      JOIN products p ON ps.product_base_id = p.id 
                      WHERE ps.product_base_id = ? AND ps.size = ?";
        $stock_stmt = $conn->prepare($stock_sql);
        $stock_stmt->bind_param("is", $productId, $size);
        $stock_stmt->execute();
        $stock_result = $stock_stmt->get_result();
        
        if ($stock_row = $stock_result->fetch_assoc()) {
            if ($stock_row['amount'] == 0) {
                $items_to_remove[] = $key;
            }
        }
    }
}

// ลบสินค้าที่หมดจาก session
if (!empty($items_to_remove)) {
    // เรียงลำดับจากมากไปน้อยเพื่อลบจากท้ายไปหน้า
    rsort($items_to_remove);
    
    foreach ($items_to_remove as $key) {
        unset($_SESSION["strProductID"][$key]);
        unset($_SESSION["strSize"][$key]);
        unset($_SESSION["strQty"][$key]);
    }
    
    // จัดเรียง array ใหม่
    $_SESSION["strProductID"] = array_values($_SESSION["strProductID"]);
    $_SESSION["strSize"] = array_values($_SESSION["strSize"]);
    $_SESSION["strQty"] = array_values($_SESSION["strQty"]);
}

// ถ้าไม่มีสินค้าเหลือในตะกร้า
if (empty($_SESSION["strProductID"])) {
    echo "<script>
        alert('สินค้าทั้งหมดในตะกร้าของคุณหมดจากคลังแล้ว กรุณาเลือกสินค้าใหม่');
        window.location.href = 'sh_product.php';
    </script>";
    exit();
}

// กลับไปหน้า cart.php
header("Location: cart.php");
exit();
?>




