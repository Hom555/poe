<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit();
}

// ตรวจสอบ order_id
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ไม่พบ order_id']);
    exit();
}

$order_id = intval($_GET['order_id']);

try {
    // ดึงข้อมูลรูปภาพสินค้าจากคำสั่งซื้อ (รูปภาพตามสีที่เลือก)
    $sql = "SELECT DISTINCT p.name, 
            CASE 
                WHEN od.color IS NOT NULL AND od.color != '' AND ps.image IS NOT NULL AND ps.image != '' 
                THEN ps.image
                WHEN p.image IS NOT NULL AND p.image != '' 
                THEN p.image
                ELSE 'no-image.svg'
            END as image,
            od.size, od.color
            FROM order_details od 
            JOIN products p ON od.product_id = p.id 
            LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                AND od.size = ps.size 
                AND od.color = ps.color
            WHERE od.order_id = ?
            ORDER BY p.name, od.size";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("ไม่สามารถเตรียมคำสั่ง SQL ได้: " . $conn->error);
    }
    
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = [
            'name' => $row['name'],
            'image' => $row['image'],
            'size' => $row['size'],
            'color' => $row['color']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'images' => $images
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>


