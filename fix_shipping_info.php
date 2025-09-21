<?php
include 'condb.php';

echo "<h2>แก้ไขข้อมูลการจัดส่งใน order_history.php</h2>";

try {
    // 1. ตรวจสอบและเพิ่มคอลัมน์การจัดส่ง
    echo "<h3>1. ตรวจสอบและเพิ่มคอลัมน์การจัดส่ง:</h3>";
    
    $shipping_columns = [
        'shipping_name' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_name VARCHAR(100) DEFAULT NULL COMMENT 'ชื่อผู้รับ'",
        'shipping_phone' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_phone VARCHAR(20) DEFAULT NULL COMMENT 'เบอร์โทรผู้รับ'",
        'shipping_address' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_address TEXT DEFAULT NULL COMMENT 'ที่อยู่จัดส่ง'",
        'shipping_method' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_method VARCHAR(50) DEFAULT NULL COMMENT 'วิธีการจัดส่ง'",
        'tracking_number' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(100) DEFAULT NULL COMMENT 'เลขติดตามพัสดุ'",
        'shipping_date' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_date DATETIME DEFAULT NULL COMMENT 'วันที่จัดส่ง'",
        'delivery_date' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_date DATETIME DEFAULT NULL COMMENT 'วันที่ส่งมอบ'"
    ];
    
    foreach ($shipping_columns as $column => $sql) {
        echo "<p>กำลังเพิ่มคอลัมน์ $column...</p>";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ เพิ่มคอลัมน์ $column สำเร็จ</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ คอลัมน์ $column อาจมีอยู่แล้ว: " . $conn->error . "</p>";
        }
    }
    
    echo "<hr>";
    
    // 2. ตรวจสอบโครงสร้างตาราง orders
    echo "<h3>2. ตรวจสอบโครงสร้างตาราง orders:</h3>";
    $structure = $conn->query("DESCRIBE orders");
    
    if ($structure) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $shipping_found = [];
        while ($row = $structure->fetch_assoc()) {
            $is_shipping = (strpos($row['Field'], 'shipping') !== false || 
                           strpos($row['Field'], 'tracking') !== false || 
                           strpos($row['Field'], 'delivery') !== false);
            
            if ($is_shipping) {
                $shipping_found[] = $row['Field'];
                echo "<tr style='background-color: #d4edda;'>";
            } else {
                echo "<tr>";
            }
            
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h4>คอลัมน์การจัดส่งที่พบ:</h4>";
        if (!empty($shipping_found)) {
            echo "<ul>";
            foreach ($shipping_found as $column) {
                echo "<li><strong>" . $column . "</strong></li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ ไม่พบคอลัมน์การจัดส่ง</p>";
        }
    }
    
    echo "<hr>";
    
    // 3. เพิ่มข้อมูลการจัดส่งให้คำสั่งซื้อล่าสุด
    echo "<h3>3. เพิ่มข้อมูลการจัดส่งให้คำสั่งซื้อล่าสุด:</h3>";
    
    // หาคำสั่งซื้อล่าสุด
    $latest_order = $conn->query("SELECT order_id, user_id, order_date, status FROM orders ORDER BY order_date DESC LIMIT 1");
    
    if ($latest_order && $latest_order->num_rows > 0) {
        $order_row = $latest_order->fetch_assoc();
        $order_id = $order_row['order_id'];
        
        echo "<p>พบคำสั่งซื้อล่าสุด: <strong>Order ID #$order_id</strong></p>";
        echo "<p>วันที่สั่งซื้อ: " . $order_row['order_date'] . "</p>";
        echo "<p>สถานะ: " . $order_row['status'] . "</p>";
        
        // เพิ่มข้อมูลการจัดส่ง
        $update_sql = "UPDATE orders SET 
            shipping_name = 'สมชาย ใจดี',
            shipping_phone = '081-234-5678',
            shipping_address = '123 ถนนสุขุมวิท แขวงคลองตัน เขตวัฒนา กรุงเทพฯ 10110',
            shipping_method = 'ไปรษณีย์ไทย',
            tracking_number = 'TH123456789TH',
            shipping_date = '2024-12-15 10:30:00',
            delivery_date = '2024-12-16 14:20:00'
            WHERE order_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ เพิ่มข้อมูลการจัดส่งสำเร็จ</p>";
            
            // ตรวจสอบข้อมูลที่เพิ่ม
            $check_sql = "SELECT shipping_name, shipping_phone, shipping_address, shipping_method, tracking_number, shipping_date, delivery_date FROM orders WHERE order_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $order_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_row = $check_result->fetch_assoc();
            
            echo "<h4>ข้อมูลการจัดส่งที่เพิ่ม:</h4>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>shipping_name</td><td>" . htmlspecialchars($check_row['shipping_name']) . "</td></tr>";
            echo "<tr><td>shipping_phone</td><td>" . htmlspecialchars($check_row['shipping_phone']) . "</td></tr>";
            echo "<tr><td>shipping_address</td><td>" . htmlspecialchars($check_row['shipping_address']) . "</td></tr>";
            echo "<tr><td>shipping_method</td><td>" . htmlspecialchars($check_row['shipping_method']) . "</td></tr>";
            echo "<tr><td>tracking_number</td><td>" . htmlspecialchars($check_row['tracking_number']) . "</td></tr>";
            echo "<tr><td>shipping_date</td><td>" . $check_row['shipping_date'] . "</td></tr>";
            echo "<tr><td>delivery_date</td><td>" . $check_row['delivery_date'] . "</td></tr>";
            echo "</table>";
            
        } else {
            echo "<p style='color: red;'>❌ ไม่สามารถเพิ่มข้อมูลการจัดส่งได้: " . $stmt->error . "</p>";
        }
        
        $stmt->close();
        
    } else {
        echo "<p style='color: orange;'>⚠️ ไม่พบคำสั่งซื้อ</p>";
    }
    
    echo "<hr>";
    
    // 4. ทดสอบ SQL Query ที่ใช้ใน order_history.php
    echo "<h3>4. ทดสอบ SQL Query ที่ใช้ใน order_history.php:</h3>";
    
    $test_sql = "SELECT o.*, COUNT(od.id) as total_items 
                 FROM orders o 
                 LEFT JOIN order_details od ON o.order_id = od.order_id 
                 WHERE o.user_id = 1 
                 GROUP BY o.order_id 
                 ORDER BY o.order_date DESC 
                 LIMIT 1";
    
    $test_result = $conn->query($test_sql);
    
    if ($test_result && $test_result->num_rows > 0) {
        echo "<p style='color: green;'>✅ SQL Query ทำงานได้</p>";
        
        $row = $test_result->fetch_assoc();
        echo "<h4>ข้อมูลที่ได้จาก Query:</h4>";
        
        $shipping_fields = ['shipping_name', 'shipping_phone', 'shipping_address', 'shipping_method', 'tracking_number', 'shipping_date', 'delivery_date'];
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Value</th><th>Status</th></tr>";
        
        $has_any_shipping_data = false;
        foreach ($shipping_fields as $field) {
            $value = $row[$field] ?? null;
            $status = !empty($value) ? '✅ มีข้อมูล' : '❌ ไม่มีข้อมูล';
            
            if (!empty($value)) {
                $has_any_shipping_data = true;
            }
            
            echo "<tr>";
            echo "<td><strong>" . $field . "</strong></td>";
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            echo "<td>" . $status . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($has_any_shipping_data) {
            echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4 style='color: #155724;'>✅ ข้อมูลการจัดส่งจะแสดงใน order_history.php</h4>";
            echo "<p><a href='order_history.php' style='color: #155724; font-weight: bold;'>ไปดู order_history.php</a></p>";
            echo "</div>";
        } else {
            echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4 style='color: #721c24;'>❌ ข้อมูลการจัดส่งจะไม่แสดงใน order_history.php</h4>";
            echo "<p>กรุณาลองรันไฟล์นี้อีกครั้ง</p>";
            echo "</div>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ SQL Query ไม่ทำงาน: " . mysqli_error($conn) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

$conn->close();
?>


