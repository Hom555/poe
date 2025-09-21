<?php
include 'condb.php';

echo "<h2>อัพเดทคอลัมน์ข้อมูลการจัดส่ง</h2>";

try {
    // ตรวจสอบว่าคอลัมน์มีอยู่แล้วหรือไม่
    $check_sql = "SHOW COLUMNS FROM orders LIKE 'shipping_name'";
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ คอลัมน์ข้อมูลการจัดส่งมีอยู่แล้ว</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ กำลังเพิ่มคอลัมน์ข้อมูลการจัดส่ง...</p>";
        
        // เพิ่มคอลัมน์ข้อมูลการจัดส่ง
        $alter_sql = "ALTER TABLE orders 
                      ADD COLUMN shipping_name VARCHAR(100) DEFAULT NULL COMMENT 'ชื่อผู้รับ',
                      ADD COLUMN shipping_phone VARCHAR(20) DEFAULT NULL COMMENT 'เบอร์โทรผู้รับ',
                      ADD COLUMN shipping_address TEXT DEFAULT NULL COMMENT 'ที่อยู่จัดส่ง',
                      ADD COLUMN shipping_method VARCHAR(50) DEFAULT NULL COMMENT 'วิธีการจัดส่ง',
                      ADD COLUMN tracking_number VARCHAR(100) DEFAULT NULL COMMENT 'เลขติดตามพัสดุ',
                      ADD COLUMN shipping_date DATETIME DEFAULT NULL COMMENT 'วันที่จัดส่ง',
                      ADD COLUMN delivery_date DATETIME DEFAULT NULL COMMENT 'วันที่ส่งมอบ'";
        
        if ($conn->query($alter_sql)) {
            echo "<p style='color: green;'>✅ เพิ่มคอลัมน์สำเร็จ</p>";
        } else {
            echo "<p style='color: red;'>❌ ไม่สามารถเพิ่มคอลัมน์ได้: " . $conn->error . "</p>";
        }
    }
    
    // แสดงโครงสร้างตารางใหม่
    echo "<h3>โครงสร้างตาราง orders ใหม่:</h3>";
    $structure_sql = "DESCRIBE orders";
    $structure_result = $conn->query($structure_sql);
    
    if ($structure_result && $structure_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $structure_result->fetch_assoc()) {
            $highlight = (strpos($row['Field'], 'shipping') !== false || strpos($row['Field'], 'delivery') !== false) ? 
                        "style='background-color: #e8f5e8;'" : "";
            echo "<tr $highlight>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

$conn->close();
echo "<p><strong>เสร็จสิ้น!</strong></p>";
?>


