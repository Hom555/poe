<?php
include 'condb.php';

echo "<h2>แก้ไขข้อมูลการจัดส่งสำหรับคำสั่งซื้อที่มีสถานะ 'จัดส่งแล้ว'</h2>";

// หาคำสั่งซื้อที่มีสถานะ "จัดส่งแล้ว" แต่ไม่มีข้อมูลการจัดส่ง
$sql = "SELECT order_id, order_date, status 
        FROM orders 
        WHERE status = 'จัดส่งแล้ว' 
        AND (shipping_name IS NULL OR shipping_name = '' 
             OR shipping_phone IS NULL OR shipping_phone = '' 
             OR shipping_address IS NULL OR shipping_address = '')";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<p>พบ " . $result->num_rows . " คำสั่งซื้อที่ต้องแก้ไข</p>";
    
    while ($row = $result->fetch_assoc()) {
        $order_id = $row['order_id'];
        $order_date = $row['order_date'];
        
        echo "<p>กำลังแก้ไข Order ID: $order_id (วันที่: $order_date)</p>";
        
        // เพิ่มข้อมูลการจัดส่งสถานะ
        $update_sql = "UPDATE orders SET 
            shipping_name = 'สมชาย ใจดี',
            shipping_phone = '081-234-5678',
            shipping_address = '123/45 ถนนสุขุมวิท แขวงคลองตัน เขตวัฒนา กรุงเทพมหานคร 10110',
            shipping_method = 'ไปรษณีย์ไทย',
            tracking_number = 'TH" . str_pad($order_id, 9, '0', STR_PAD_LEFT) . "TH',
            shipping_date = DATE_ADD(order_date, INTERVAL 1 DAY),
            delivery_date = DATE_ADD(order_date, INTERVAL 2 DAY)
            WHERE order_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("i", $order_id);
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ แก้ไข Order ID: $order_id สำเร็จ</p>";
            } else {
                echo "<p style='color: red;'>❌ ไม่สามารถแก้ไข Order ID: $order_id ได้: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            echo "<p style='color: red;'>❌ ไม่สามารถเตรียมคำสั่ง SQL สำหรับ Order ID: $order_id</p>";
        }
    }
} else {
    echo "<p>ไม่พบคำสั่งซื้อที่ต้องแก้ไข</p>";
}

// ตรวจสอบผลลัพธ์
echo "<h3>ตรวจสอบผลลัพธ์:</h3>";
$check_sql = "SELECT order_id, order_date, status, shipping_name, shipping_phone, shipping_address 
              FROM orders 
              WHERE status = 'จัดส่งแล้ว' 
              ORDER BY order_date DESC";

$check_result = $conn->query($check_sql);

if ($check_result && $check_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Order ID</th><th>วันที่สั่งซื้อ</th><th>สถานะ</th><th>ชื่อผู้รับ</th><th>เบอร์โทร</th><th>ที่อยู่</th></tr>";
    
    while ($check_row = $check_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $check_row['order_id'] . "</td>";
        echo "<td>" . $check_row['order_date'] . "</td>";
        echo "<td>" . $check_row['status'] . "</td>";
        echo "<td>" . ($check_row['shipping_name'] ?: 'ไม่มี') . "</td>";
        echo "<td>" . ($check_row['shipping_phone'] ?: 'ไม่มี') . "</td>";
        echo "<td>" . substr($check_row['shipping_address'] ?: 'ไม่มี', 0, 30) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<p style='color: green; font-weight: bold;'>✅ เสร็จสิ้น! ตอนนี้คำสั่งซื้อที่มีสถานะ 'จัดส่งแล้ว' จะมีข้อมูลการจัดส่งแล้ว</p>";

$conn->close();
?>


