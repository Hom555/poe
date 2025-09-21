<?php
include 'condb.php';

echo "กำลังอัพเดทฐานข้อมูล...\n";

try {
    // ตรวจสอบว่าคอลัมน์ size มีอยู่แล้วหรือไม่
    $check_sql = "SHOW COLUMNS FROM order_details LIKE 'size'";
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "คอลัมน์ size และ color มีอยู่แล้ว\n";
    } else {
        echo "กำลังเพิ่มคอลัมน์ size และ color...\n";
        
        // เพิ่มคอลัมน์ size และ color
        $alter_sql = "ALTER TABLE order_details 
                      ADD COLUMN size VARCHAR(10) DEFAULT NULL,
                      ADD COLUMN color VARCHAR(50) DEFAULT NULL";
        
        if ($conn->query($alter_sql)) {
            echo "เพิ่มคอลัมน์สำเร็จ\n";
        } else {
            echo "ไม่สามารถเพิ่มคอลัมน์ได้: " . $conn->error . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
}

$conn->close();
echo "เสร็จสิ้น\n";
?>



