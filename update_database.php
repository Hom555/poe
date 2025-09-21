<?php
// ไฟล์สำหรับอัพเดทฐานข้อมูลโดยอัตโนมัติ
// รันไฟล์นี้เพื่อเพิ่มคอลัมน์ size และ color ในตาราง order_details

include 'condb.php';

echo "<h2>กำลังอัพเดทฐานข้อมูล...</h2>";

try {
    // ตรวจสอบว่าคอลัมน์ size มีอยู่แล้วหรือไม่
    $check_sql = "SHOW COLUMNS FROM order_details LIKE 'size'";
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ คอลัมน์ size และ color มีอยู่แล้วในตาราง order_details</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ กำลังเพิ่มคอลัมน์ size และ color...</p>";
        
        // เพิ่มคอลัมน์ size และ color
        $alter_sql = "ALTER TABLE order_details 
                      ADD COLUMN size VARCHAR(10) DEFAULT NULL COMMENT 'ไซส์สินค้าที่ซื้อ',
                      ADD COLUMN color VARCHAR(50) DEFAULT NULL COMMENT 'สีสินค้าที่ซื้อ'";
        
        if ($conn->query($alter_sql)) {
            echo "<p style='color: green;'>✅ เพิ่มคอลัมน์ size และ color สำเร็จ</p>";
            
            // เพิ่ม index
            $index_sql = "CREATE INDEX idx_order_details_size_color ON order_details(size, color)";
            if ($conn->query($index_sql)) {
                echo "<p style='color: green;'>✅ เพิ่ม index สำเร็จ</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ ไม่สามารถเพิ่ม index ได้: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ ไม่สามารถเพิ่มคอลัมน์ได้: " . $conn->error . "</p>";
        }
    }
    
    // แสดงโครงสร้างตาราง
    echo "<h3>โครงสร้างตาราง order_details:</h3>";
    $describe_sql = "DESCRIBE order_details";
    $result = $conn->query($describe_sql);
    
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<p style='color: green; font-weight: bold;'>🎉 อัพเดทฐานข้อมูลเสร็จสิ้น!</p>";
    echo "<p><a href='sh_product.php'>กลับไปหน้าหลัก</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

$conn->close();
?>



