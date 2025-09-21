<?php
include 'condb.php';

echo "<h2>เพิ่มคำสั่งซื้อที่จัดส่งแล้ว</h2>";

try {
    // ตรวจสอบคำสั่งซื้อที่มีอยู่
    $check_sql = "SELECT order_id, status, total_amount FROM orders ORDER BY order_date DESC LIMIT 3";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        echo "<h3>คำสั่งซื้อล่าสุด:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Order ID</th><th>Status</th><th>Total Amount</th><th>Action</th></tr>";
        
        while ($row = $check_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['order_id'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>฿" . number_format($row['total_amount'], 2) . "</td>";
            
            if ($row['status'] === 'รอตรวจสอบการชำระเงิน') {
                echo "<td><a href='?update_status=" . $row['order_id'] . "' style='color: green;'>เปลี่ยนเป็น จัดส่งแล้ว</a></td>";
            } else {
                echo "<td>-</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // ตรวจสอบว่ามีการส่ง parameter update_status หรือไม่
        if (isset($_GET['update_status'])) {
            $order_id = $_GET['update_status'];
            
            // อัพเดทสถานะเป็น "จัดส่งแล้ว"
            $update_sql = "UPDATE orders SET status = 'จัดส่งแล้ว' WHERE order_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $order_id);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ อัพเดทสถานะ Order ID $order_id เป็น 'จัดส่งแล้ว' สำเร็จ</p>";
                echo "<p><a href='sales_report.php'>ไปดูรายงานการขาย</a></p>";
            } else {
                echo "<p style='color: red;'>❌ ไม่สามารถอัพเดทสถานะได้: " . $stmt->error . "</p>";
            }
            
            $stmt->close();
        }
        
        // แสดงสถิติปัจจุบัน
        $stats_sql = "SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'จัดส่งแล้ว' THEN 1 ELSE 0 END) as shipped_orders,
            SUM(CASE WHEN status = 'รอตรวจสอบการชำระเงิน' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'จัดส่งแล้ว' THEN total_amount ELSE 0 END) as shipped_revenue
        FROM orders";
        
        $stats_result = $conn->query($stats_sql);
        $stats = $stats_result->fetch_assoc();
        
        echo "<h3>สถิติปัจจุบัน:</h3>";
        echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0;'>";
        echo "<p><strong>คำสั่งซื้อทั้งหมด:</strong> " . $stats['total_orders'] . " รายการ</p>";
        echo "<p><strong>จัดส่งแล้ว:</strong> " . $stats['shipped_orders'] . " รายการ</p>";
        echo "<p><strong>รอตรวจสอบการชำระเงิน:</strong> " . $stats['pending_orders'] . " รายการ</p>";
        echo "<p><strong>รายได้จากคำสั่งซื้อที่จัดส่งแล้ว:</strong> ฿" . number_format($stats['shipped_revenue'], 2) . "</p>";
        echo "</div>";
        
    } else {
        echo "<p>ไม่มีคำสั่งซื้อ</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

$conn->close();
?>


