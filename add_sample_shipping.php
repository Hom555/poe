<?php
include 'condb.php';

echo "<h2>เพิ่มข้อมูลการจัดส่งตัวอย่าง</h2>";

try {
    // ตรวจสอบคำสั่งซื้อล่าสุด
    $sql = "SELECT order_id FROM orders ORDER BY order_date DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $order_id = $order['order_id'];
        
        echo "<p>พบคำสั่งซื้อล่าสุด: Order ID = $order_id</p>";
        
        // เพิ่มข้อมูลการจัดส่งตัวอย่าง
        $update_sql = "UPDATE orders SET 
                       shipping_name = 'นายสมชาย ใจดี',
                       shipping_phone = '081-234-5678',
                       shipping_address = '123/45 ถนนสุขุมวิท แขวงคลองตัน เขตวัฒนา กรุงเทพมหานคร 10110',
                       shipping_method = 'EMS',
                       tracking_number = 'TH123456789TH',
                       shipping_date = '2025-01-15 10:30:00',
                       delivery_date = '2025-01-16 14:20:00'
                       WHERE order_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ เพิ่มข้อมูลการจัดส่งสำเร็จ</p>";
            
            // แสดงข้อมูลที่เพิ่ม
            $check_sql = "SELECT shipping_name, shipping_phone, shipping_address, 
                                 shipping_method, tracking_number, shipping_date, delivery_date
                          FROM orders WHERE order_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $order_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $shipping_data = $check_result->fetch_assoc();
            
            echo "<h3>ข้อมูลการจัดส่งที่เพิ่ม:</h3>";
            echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0;'>";
            echo "<p><strong>ชื่อผู้รับ:</strong> " . $shipping_data['shipping_name'] . "</p>";
            echo "<p><strong>เบอร์โทร:</strong> " . $shipping_data['shipping_phone'] . "</p>";
            echo "<p><strong>ที่อยู่:</strong> " . nl2br($shipping_data['shipping_address']) . "</p>";
            echo "<p><strong>วิธีการจัดส่ง:</strong> " . $shipping_data['shipping_method'] . "</p>";
            echo "<p><strong>เลขติดตาม:</strong> " . $shipping_data['tracking_number'] . "</p>";
            echo "<p><strong>วันที่จัดส่ง:</strong> " . $shipping_data['shipping_date'] . "</p>";
            echo "<p><strong>วันที่ส่งมอบ:</strong> " . $shipping_data['delivery_date'] . "</p>";
            echo "</div>";
            
            echo "<p><a href='order_history.php'>ไปดูใน order_history.php</a></p>";
            
        } else {
            echo "<p style='color: red;'>❌ ไม่สามารถเพิ่มข้อมูลการจัดส่งได้: " . $stmt->error . "</p>";
        }
        
        $stmt->close();
        
    } else {
        echo "<p style='color: red;'>❌ ไม่พบคำสั่งซื้อ</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

$conn->close();
?>


