<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

$title = "รายงานการขาย";
include 'header.php';

// ดึงข้อมูลสถิติการสั่งซื้อ
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'จัดส่งแล้ว' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN status = 'รอตรวจสอบการชำระเงิน' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'ยกเลิก' THEN 1 ELSE 0 END) as cancelled_orders,
    SUM(CASE WHEN status = 'จัดส่งแล้ว' THEN total_amount ELSE 0 END) as total_revenue,
    AVG(CASE WHEN status = 'จัดส่งแล้ว' THEN total_amount ELSE NULL END) as avg_order_value
FROM orders";

$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// ดึงข้อมูลการขายรายวัน (30 วันล่าสุด)
$daily_sql = "SELECT 
    DATE(order_date) as day,
    COUNT(*) as orders_count,
    SUM(CASE WHEN status = 'จัดส่งแล้ว' THEN total_amount ELSE 0 END) as revenue
FROM orders 
WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(order_date)
ORDER BY day DESC";

$daily_result = mysqli_query($conn, $daily_sql);
$daily_data = array();
while($row = mysqli_fetch_assoc($daily_result)) {
    $daily_data[] = $row;
}

// ดึงข้อมูลการขายรายเดือน (12 เดือนล่าสุด)
$monthly_sql = "SELECT 
    DATE_FORMAT(order_date, '%Y-%m') as month,
    COUNT(*) as orders_count,
    SUM(CASE WHEN status = 'จัดส่งแล้ว' THEN total_amount ELSE 0 END) as revenue
FROM orders 
WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(order_date, '%Y-%m')
ORDER BY month DESC";

$monthly_result = mysqli_query($conn, $monthly_sql);
$monthly_data = array();
while($row = mysqli_fetch_assoc($monthly_result)) {
    $monthly_data[] = $row;
}

// ดึงข้อมูลการขายรายปี (5 ปีล่าสุด)
$yearly_sql = "SELECT 
    YEAR(order_date) as year,
    COUNT(*) as orders_count,
    SUM(CASE WHEN status = 'จัดส่งแล้ว' THEN total_amount ELSE 0 END) as revenue
FROM orders 
WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
GROUP BY YEAR(order_date)
ORDER BY year DESC";

$yearly_result = mysqli_query($conn, $yearly_sql);
$yearly_data = array();
while($row = mysqli_fetch_assoc($yearly_result)) {
    $yearly_data[] = $row;
}

// ดึงข้อมูลสินค้าขายดี (Top 10)
$top_products_sql = "SELECT 
    p.name as product_name,
    t.type_name,
    SUM(od.quantity) as total_sold,
    SUM(od.quantity * od.price) as total_revenue
FROM order_details od
JOIN products p ON od.product_id = p.id
JOIN type t ON p.type_id = t.type_id
JOIN orders o ON od.order_id = o.order_id
WHERE o.status = 'จัดส่งแล้ว'
GROUP BY p.id, p.name, t.type_name
ORDER BY total_sold DESC
LIMIT 10";

$top_products_result = mysqli_query($conn, $top_products_sql);

// เพิ่มการจัดการค้นหาสำหรับคำสั่งซื้อล่าสุด
$order_search = $_GET['order_search'] ?? '';
$order_status_filter = $_GET['order_status'] ?? '';

// สร้าง WHERE clause สำหรับการค้นหา
$order_where_conditions = array();
$order_params = array();
$order_types = "";

if (!empty($order_search)) {
    $order_where_conditions[] = "(o.order_id LIKE ? OR 
                                 COALESCE(o.shipping_name, u.name) LIKE ? OR 
                                 COALESCE(o.shipping_phone, u.phone) LIKE ?)";
    $search_param = "%$order_search%";
    $order_params[] = $search_param;
    $order_params[] = $search_param;
    $order_params[] = $search_param;
    $order_types .= "sss";
}

if (!empty($order_status_filter)) {
    $order_where_conditions[] = "o.status = ?";
    $order_params[] = $order_status_filter;
    $order_types .= "s";
}

$order_where_clause = !empty($order_where_conditions) ? "WHERE " . implode(" AND ", $order_where_conditions) : "";

// ดึงข้อมูลการสั่งซื้อล่าสุด
$recent_orders_sql = "SELECT 
    o.order_id,
    o.user_id,
    o.order_date,
    o.total_amount,
    o.status,
    o.shipping_name,
    o.shipping_phone,
    o.shipping_address,
    u.name as user_name,
    u.phone as user_phone
FROM orders o
LEFT JOIN users u ON o.user_id = u.user_id
$order_where_clause
ORDER BY o.order_date DESC
LIMIT 50";

// Execute query with prepared statement
$recent_orders_stmt = $conn->prepare($recent_orders_sql);
if ($recent_orders_stmt) {
    if (!empty($order_params)) {
        $recent_orders_stmt->bind_param($order_types, ...$order_params);
    }
    $recent_orders_stmt->execute();
    $recent_orders_result = $recent_orders_stmt->get_result();
    
    // ตรวจสอบ error
    if (!$recent_orders_result) {
        error_log("Error in recent orders query: " . $recent_orders_stmt->error);
        $recent_orders_result = false;
    } else {
        // Debug: ดูจำนวนแถวที่ได้
        $row_count = $recent_orders_result->num_rows;
        error_log("Recent orders query returned $row_count rows");
    }
} else {
    error_log("Error preparing recent orders query: " . $conn->error);
    $recent_orders_result = false;
}

// ฟังก์ชันแปลงสถานะเป็นภาษาไทย
function getStatusText($status) {
    switch($status) {
        case 'รอตรวจสอบการชำระเงิน': return 'รอตรวจสอบการชำระเงิน';
        case 'กำลังดำเนินการ': return 'กำลังดำเนินการ';
        case 'จัดส่งแล้ว': return 'จัดส่งแล้ว';
        case 'ยกเลิก': return 'ยกเลิก';
        default: return $status;
    }
}

// ฟังก์ชันแปลงสถานะเป็นสี
function getStatusColor($status) {
    switch($status) {
        case 'รอตรวจสอบการชำระเงิน': return 'warning';
        case 'กำลังดำเนินการ': return 'info';
        case 'จัดส่งแล้ว': return 'success';
        case 'ยกเลิก': return 'danger';
        default: return 'secondary';
    }
}

// ฟังก์ชันแปลงวันที่เป็นภาษาไทย
function formatThaiDate($date) {
    $months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    
    $date_parts = explode('-', $date);
    $year = $date_parts[0] + 543; // แปลงเป็น พ.ศ.
    $month = $months[$date_parts[1]];
    $day = $date_parts[2];
    
    return "$day $month $year";
}

// ฟังก์ชันแปลงเดือนเป็นภาษาไทย
function formatThaiMonth($month) {
    $months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    
    $date_parts = explode('-', $month);
    $year = $date_parts[0] + 543; // แปลงเป็น พ.ศ.
    $month_name = $months[$date_parts[1]];
    
    return "$month_name $year";
}

// ฟังก์ชันแปลงปีเป็นภาษาไทย
function formatThaiYear($year) {
    return ($year + 543) . ' (พ.ศ.)';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header Section -->
            <div class="text-center mb-4">
                <h2 class="display-6 fw-bold text-primary mb-3">
                    <i class="fas fa-chart-line me-3"></i>รายงานการขาย
                </h2>
                <p class="lead text-muted">สถิติการสั่งซื้อและรายได้ของร้าน</p>
            </div>
            
            <!-- สถิติหลัก -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?= number_format($stats['total_orders']) ?></h4>
                                    <p class="card-text">คำสั่งซื้อทั้งหมด</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?= number_format($stats['completed_orders']) ?></h4>
                                    <p class="card-text">คำสั่งซื้อที่เสร็จสิ้น</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?= number_format($stats['pending_orders']) ?></h4>
                                    <p class="card-text">รอดำเนินการ</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">฿<?= number_format($stats['total_revenue'], 2) ?></h4>
                                    <p class="card-text">รายได้รวม</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- รายได้รายวัน รายเดือน รายปี -->
            <div class="row g-4 mb-4">
                <!-- รายได้รายวัน -->
                <div class="col-lg-4">
                    <div class="card shadow h-100">
                        <div class="card-header bg-gradient-warning text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-day me-2"></i>รายได้รายวัน (30 วันล่าสุด)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>วันที่</th>
                                            <th class="text-end">คำสั่งซื้อ</th>
                                            <th class="text-end">รายได้</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($daily_data as $daily): ?>
                                        <tr>
                                            <td>
                                                <small><?= formatThaiDate($daily['day']) ?></small>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?= number_format($daily['orders_count']) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <small class="text-success fw-bold">฿<?= number_format($daily['revenue'], 2) ?></small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- รายได้รายเดือน -->
                <div class="col-lg-4">
                    <div class="card shadow h-100">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>รายได้รายเดือน (12 เดือนล่าสุด)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>เดือน</th>
                                            <th class="text-end">คำสั่งซื้อ</th>
                                            <th class="text-end">รายได้</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($monthly_data as $monthly): ?>
                                        <tr>
                                            <td>
                                                <small><?= formatThaiMonth($monthly['month']) ?></small>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?= number_format($monthly['orders_count']) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <small class="text-success fw-bold">฿<?= number_format($monthly['revenue'], 2) ?></small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- รายได้รายปี -->
                <div class="col-lg-4">
                    <div class="card shadow h-100">
                        <div class="card-header bg-gradient-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar me-2"></i>รายได้รายปี (5 ปีล่าสุด)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>ปี</th>
                                            <th class="text-end">คำสั่งซื้อ</th>
                                            <th class="text-end">รายได้</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($yearly_data as $yearly): ?>
                                        <tr>
                                            <td>
                                                <small><?= formatThaiYear($yearly['year']) ?></small>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?= number_format($yearly['orders_count']) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <small class="text-success fw-bold">฿<?= number_format($yearly['revenue'], 2) ?></small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- กราฟรายได้รายเดือน -->
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-header bg-gradient-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>กราฟรายได้รายเดือน (12 เดือนล่าสุด)
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- สินค้าขายดี -->
                <div class="col-lg-4">
                    <div class="card shadow h-100">
                        <div class="card-header bg-gradient-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-trophy me-2"></i>สินค้าขายดี (Top 10)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>สินค้า</th>
                                            <th class="text-end">ขาย</th>
                                            <th class="text-end">รายได้</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($product = mysqli_fetch_assoc($top_products_result)): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($product['type_name']) ?></small>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?= number_format($product['total_sold']) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <small class="text-success fw-bold">฿<?= number_format($product['total_revenue'], 2) ?></small>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- คำสั่งซื้อล่าสุด -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-gradient-info text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>คำสั่งซื้อล่าสุด
                                </h5>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#orderSearchForm" aria-expanded="false">
                                        <i class="fas fa-search me-1"></i>ค้นหา
                                    </button>
                                    <a href="sales_report.php" class="btn btn-outline-light btn-sm">
                                        <i class="fas fa-redo me-1"></i>รีเซ็ต
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- ฟอร์มค้นหา -->
                            <div class="collapse mb-4" id="orderSearchForm">
                                <div class="card border-info">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-info">
                                            <i class="fas fa-filter me-2"></i>ค้นหาคำสั่งซื้อ
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="GET" class="row g-3">
                                            <div class="col-md-6">
                                                <label for="order_search" class="form-label">
                                                    <i class="fas fa-search me-1"></i>ค้นหา
                                                </label>
                                                <div class="input-group position-relative">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           id="order_search" 
                                                           name="order_search" 
                                                           placeholder="หมายเลขคำสั่งซื้อ, ชื่อลูกค้า, เบอร์โทร"
                                                           value="<?= htmlspecialchars($order_search) ?>"
                                                           style="border-radius: 0 12px 12px 0;">
                                                    <!-- Search indicator -->
                                                    <div id="orderSearchIndicator" class="position-absolute top-50 end-0 translate-middle-y me-3" style="display: none;">
                                                        <i class="fas fa-magic text-primary fa-spin"></i>
                                                    </div>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-magic me-1"></i>
                                                    ค้นหาแบบอัตโนมัติ - พิมพ์เพื่อค้นหา
                                                </div>
                                                <!-- Search status -->
                                                <div id="orderSearchStatus" class="mt-2" style="display: none;">
                                                    <small class="text-primary">
                                                        <i class="fas fa-spinner fa-spin me-1"></i>กำลังค้นหา...
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="order_status" class="form-label">
                                                    <i class="fas fa-tags me-1"></i>สถานะ
                                                </label>
                                                <select class="form-select" id="order_status" name="order_status">
                                                    <option value="">ทุกสถานะ</option>
                                                    <option value="รอตรวจสอบการชำระเงิน" <?= $order_status_filter === 'รอตรวจสอบการชำระเงิน' ? 'selected' : '' ?>>
                                                        รอตรวจสอบการชำระเงิน
                                                    </option>
                                                    <option value="กำลังดำเนินการ" <?= $order_status_filter === 'กำลังดำเนินการ' ? 'selected' : '' ?>>
                                                        กำลังดำเนินการ
                                                    </option>
                                                    <option value="จัดส่งแล้ว" <?= $order_status_filter === 'จัดส่งแล้ว' ? 'selected' : '' ?>>
                                                        จัดส่งแล้ว
                                                    </option>
                                                    <option value="ยกเลิก" <?= $order_status_filter === 'ยกเลิก' ? 'selected' : '' ?>>
                                                        ยกเลิก
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">&nbsp;</label>
                                                <div class="d-grid gap-2">
                                                    <button type="submit" class="btn btn-primary" id="searchButton">
                                                        <i class="fas fa-search me-1"></i>ค้นหา
                                                    </button>
                                                    <a href="sales_report.php" class="btn btn-outline-secondary btn-sm">
                                                        <i class="fas fa-redo me-1"></i>รีเซ็ต
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- แสดงผลการค้นหา -->
                            <?php if (!empty($order_search) || !empty($order_status_filter)): ?>
                                <div class="alert alert-info mb-3" style="border-radius: 12px; border: none; background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-search me-3 text-primary"></i>
                                        <div>
                                            <strong>ผลการค้นหา:</strong>
                                            <?php if (!empty($order_search)): ?>
                                                ค้นหา "<strong><?= htmlspecialchars($order_search) ?></strong>"
                                            <?php endif; ?>
                                            <?php if (!empty($order_status_filter)): ?>
                                                <?= !empty($order_search) ? 'และ' : '' ?> สถานะ: <strong><?= htmlspecialchars($order_status_filter) ?></strong>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                พบ <?= $recent_orders_result ? $recent_orders_result->num_rows : 0 ?> คำสั่งซื้อ
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>หมายเลขคำสั่งซื้อ</th>
                                            <th>ลูกค้า</th>
                                            <th>เบอร์โทร</th>
                                            <th>วันที่สั่งซื้อ</th>
                                            <th>จำนวนเงิน</th>
                                            <th>สถานะ</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_orders_result && $recent_orders_result->num_rows > 0): ?>
                                            <?php while($order = $recent_orders_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong>#<?= $order['order_id'] ?></strong>
                                                </td>
                                                <td>
                                                    <?php 
                                                    // ใช้ข้อมูลจาก shipping หรือ user
                                                    if (!empty($order['shipping_name'])) {
                                                        echo htmlspecialchars($order['shipping_name']);
                                                    } else {
                                                        echo htmlspecialchars($order['user_name'] ?? 'ไม่ระบุ');
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    // ใช้ข้อมูลจาก shipping หรือ user
                                                    if (!empty($order['shipping_phone'])) {
                                                        echo htmlspecialchars($order['shipping_phone']);
                                                    } else {
                                                        echo htmlspecialchars($order['user_phone'] ?? 'ไม่ระบุ');
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?= formatThaiDate($order['order_date']) ?>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">฿<?= number_format($order['total_amount'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusColor($order['status']) ?>">
                                                        <?= getStatusText($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="order_detail.php?order_id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> ดูรายละเอียด
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                                    <p class="mb-0">ไม่มีข้อมูลคำสั่งซื้อ</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// กราฟรายได้รายเดือน (12 เดือนล่าสุด)
const monthlyData = <?= json_encode($monthly_data) ?>;
const months = monthlyData.map(item => {
    const [year, month] = item.month.split('-');
    const thaiMonths = [
        'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
        'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
    ];
    return `${thaiMonths[parseInt(month) - 1]} ${parseInt(year) + 543}`;
}).reverse();

const revenues = monthlyData.map(item => parseFloat(item.revenue)).reverse();
const orders = monthlyData.map(item => parseInt(item.orders_count)).reverse();

const ctx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'รายได้ (฿)',
            data: revenues,
            backgroundColor: 'rgba(13, 110, 253, 0.8)',
            borderColor: 'rgba(13, 110, 253, 1)',
            borderWidth: 1,
            yAxisID: 'y'
        }, {
            label: 'จำนวนคำสั่งซื้อ',
            data: orders,
            type: 'line',
            backgroundColor: 'rgba(220, 53, 69, 0.8)',
            borderColor: 'rgba(220, 53, 69, 1)',
            borderWidth: 2,
            fill: false,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'เดือน'
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'รายได้ (฿)'
                },
                ticks: {
                    callback: function(value) {
                        return '฿' + value.toLocaleString();
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'จำนวนคำสั่งซื้อ'
                },
                grid: {
                    drawOnChartArea: false,
                },
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.datasetIndex === 0) {
                            return 'รายได้: ฿' + context.parsed.y.toLocaleString();
                        } else {
                            return 'คำสั่งซื้อ: ' + context.parsed.y + ' รายการ';
                        }
                    }
                }
            }
        }
    }
});

// Auto Search Functionality
let orderSearchTimeout;
let isOrderAutoSearching = false;

// Perform auto search with debounce
function performOrderAutoSearch() {
    if (isOrderAutoSearching) return;
    
    clearTimeout(orderSearchTimeout);
    orderSearchTimeout = setTimeout(() => {
        isOrderAutoSearching = true;
        const form = document.querySelector('form[method="GET"]');
        if (form) {
            showOrderSearchStatus();
            form.submit();
        }
    }, 800); // Wait 800ms after user stops typing
}

// Show search status
function showOrderSearchStatus() {
    const statusDiv = document.getElementById('orderSearchStatus');
    if (statusDiv) {
        statusDiv.style.display = 'block';
    }
}

// Show search indicator
function showOrderSearchIndicator() {
    const indicator = document.getElementById('orderSearchIndicator');
    if (indicator) {
        indicator.style.display = 'block';
    }
}

// Hide search indicator
function hideOrderSearchIndicator() {
    const indicator = document.getElementById('orderSearchIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
}

// Enhanced auto search with visual feedback
function enhancedOrderAutoSearch() {
    showOrderSearchIndicator();
    clearTimeout(orderSearchTimeout);
    orderSearchTimeout = setTimeout(() => {
        hideOrderSearchIndicator();
        const form = document.querySelector('form[method="GET"]');
        if (form) {
            showOrderSearchStatus();
            form.submit();
        }
    }, 800);
}

// Search Form Enhancements
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('order_search');
    const statusSelect = document.getElementById('order_status');
    
    // Auto-expand search form if there are search parameters
    const urlParams = new URLSearchParams(window.location.search);
    const hasSearch = urlParams.get('order_search') || urlParams.get('order_status');
    
    if (hasSearch) {
        const searchForm = document.getElementById('orderSearchForm');
        if (searchForm) {
            searchForm.classList.add('show');
        }
    }
    
    // Add auto search for search input
    if (searchInput) {
        searchInput.addEventListener('input', enhancedOrderAutoSearch);
        
        // Add focus event for better UX
        searchInput.addEventListener('focus', function() {
            this.style.borderColor = '#0dcaf0';
            this.style.boxShadow = '0 0 0 0.2rem rgba(13, 202, 240, 0.25)';
        });
        
        // Add blur event
        searchInput.addEventListener('blur', function() {
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
    }
    
    // Add auto search for status select
    if (statusSelect) {
        statusSelect.addEventListener('change', enhancedOrderAutoSearch);
    }
    
    // Reset auto searching flag when page loads
    isOrderAutoSearching = false;
    
    // Hide search status if it's visible
    const statusDiv = document.getElementById('orderSearchStatus');
    if (statusDiv) {
        statusDiv.style.display = 'none';
    }
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            if (searchInput) {
                // Show search form first
                const searchForm = document.getElementById('orderSearchForm');
                if (searchForm && !searchForm.classList.contains('show')) {
                    const collapse = new bootstrap.Collapse(searchForm);
                    collapse.show();
                }
                // Focus search input
                setTimeout(() => {
                    searchInput.focus();
                    searchInput.select();
                }, 300);
            }
        }
        
        // Escape to clear search
        if (e.key === 'Escape') {
            if (searchInput && searchInput.value) {
                searchInput.value = '';
                searchInput.focus();
                // Trigger auto search to clear results
                enhancedOrderAutoSearch();
            }
        }
    });
    
    // Add search form validation
    const searchForm = document.querySelector('form[method="GET"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            // If both fields are empty, prevent submission
            if (!searchInput.value.trim() && !statusSelect.value) {
                e.preventDefault();
                alert('กรุณากรอกคำค้นหาหรือเลือกสถานะ');
                return false;
            }
        });
    }
});
</script>

<style>
/* Background and Layout */
body {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

/* Header Styles */
.display-6 {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Card Styles */
.card {
    border-radius: 15px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
    border: none;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

/* Statistics Cards */
.card.bg-primary,
.card.bg-success,
.card.bg-warning,
.card.bg-info {
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.card.bg-primary:hover,
.card.bg-success:hover,
.card.bg-warning:hover,
.card.bg-info:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.card-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.card-text {
    font-size: 0.9rem;
    opacity: 0.9;
}

/* Table Styles */
.table {
    border-radius: 10px;
    overflow: hidden;
}

.table thead th {
    background-color: #f8f9fa;
    border: none;
    font-weight: 600;
    color: #495057;
}

.table tbody tr {
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
}

/* Badge Styles */
.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
}

/* Button Styles */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Chart Container */
#monthlyChart {
    max-height: 400px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .display-6 {
        font-size: 2rem;
    }
    
    .card-title {
        font-size: 1.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

/* Search Form Styles */
.collapse .card {
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.collapse .card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.form-control:focus,
.form-select:focus {
    border-color: #0dcaf0;
    box-shadow: 0 0 0 0.2rem rgba(13, 202, 240, 0.25);
}

/* Auto Search Styles */
.input-group .form-control:focus {
    border-color: #0dcaf0;
    box-shadow: 0 0 0 0.2rem rgba(13, 202, 240, 0.25);
}

/* Search Indicator */
#orderSearchIndicator {
    z-index: 10;
}

#orderSearchStatus {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.btn-primary {
    background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0aa2c0 0%, #087990 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 202, 240, 0.3);
}

.btn-outline-light:hover {
    background-color: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    color: #fff;
}

/* Search Results Alert */
.alert-info {
    border-left: 4px solid #0dcaf0;
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Table Enhancements */
.table-hover tbody tr:hover {
    background-color: rgba(13, 202, 240, 0.05);
    transform: scale(1.01);
    transition: all 0.3s ease;
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }
.card:nth-child(3) { animation-delay: 0.3s; }
.card:nth-child(4) { animation-delay: 0.4s; }
</style>

<?php include 'footer.php'; ?>
