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
    // ดึงข้อมูลไซส์ สี และรายละเอียดสินค้าจากคำสั่งซื้อ
    $sql = "SELECT p.name, 
            COALESCE(od.size, 'ไม่ระบุ') as size, 
            COALESCE(od.color, 'ไม่ระบุ') as color, 
            od.quantity, od.price, od.total
            FROM order_details od 
            JOIN products p ON od.product_id = p.id 
            WHERE od.order_id = ?
            ORDER BY p.name, od.size";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("ไม่สามารถเตรียมคำสั่ง SQL ได้: " . $conn->error);
    }
    
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sizes = [];
    while ($row = $result->fetch_assoc()) {
        $sizes[] = [
            'name' => $row['name'],
            'size' => $row['size'],
            'color' => $row['color'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'total' => $row['total']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'sizes' => $sizes
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


