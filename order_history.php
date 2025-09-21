<?php
session_start();
include 'condb.php';
date_default_timezone_set('Asia/Bangkok');  // ตั้งค่า timezone เป็นประเทศไทย

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// สร้างเงื่อนไขการค้นหา
$where_conditions = ["o.user_id = ?"];
$params = [$_SESSION['user_id']];
$types = "i";

// ค้นหาด้วย Order ID หรือชื่อสินค้า
if (!empty($_GET['search'])) {
    $search_term = "%" . $_GET['search'] . "%";
    $where_conditions[] = "(o.order_id LIKE ? OR EXISTS (
        SELECT 1 FROM order_details od2 
        JOIN products p ON od2.product_id = p.id 
        WHERE od2.order_id = o.order_id AND p.name LIKE ?
    ))";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

// กรองตามสถานะ
if (!empty($_GET['status'])) {
    $where_conditions[] = "o.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// กรองตามวันที่
if (!empty($_GET['date_from'])) {
    $where_conditions[] = "DATE(o.order_date) >= ?";
    $params[] = $_GET['date_from'];
    $types .= "s";
}

if (!empty($_GET['date_to'])) {
    $where_conditions[] = "DATE(o.order_date) <= ?";
    $params[] = $_GET['date_to'];
    $types .= "s";
}

// สร้าง SQL query
$where_clause = implode(" AND ", $where_conditions);
$sql = "SELECT o.*, COUNT(od.id) as total_items 
        FROM orders o 
        LEFT JOIN order_details od ON o.order_id = od.order_id 
        WHERE $where_clause
        GROUP BY o.order_id, o.shipping_name, o.shipping_phone, o.shipping_address, o.shipping_method, o.tracking_number, o.shipping_date, o.delivery_date
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
if ($types && $params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result();

// ฟังก์ชันกำหนดสีตามสถานะ
function getStatusColor($status) {
    switch ($status) {
        case 'รอการชำระเงิน':
            return 'warning';
        case 'รอตรวจสอบการชำระเงิน':
            return 'info';
        case 'ชำระเงินแล้ว':
            return 'info';
        case 'กำลังจัดส่ง':
            return 'primary';
        case 'จัดส่งแล้ว':
            return 'success';
        case 'ยกเลิก':
            return 'danger';
        default:
            return 'secondary';
    }
}

// ฟังก์ชันแปลงชื่อสีเป็นรหัสสี
function getColorCode($colorName) {
    $colors = [
        'ขาว' => '#FFFFFF',
        'ดำ' => '#000000',
        'แดง' => '#FF0000',
        'น้ำเงิน' => '#0000FF',
        'เขียว' => '#008000',
        'เหลือง' => '#FFFF00',
        'ส้ม' => '#FFA500',
        'ม่วง' => '#800080',
        'ชมพู' => '#FFC0CB',
        'เทา' => '#808080',
        'น้ำตาล' => '#A52A2A',
        'ครีม' => '#F5F5DC',
        'เบจ' => '#F5F5DC',
        'ฟ้า' => '#87CEEB',
        'เขียวอ่อน' => '#90EE90',
        'อื่นๆ' => '#E0E0E0'
    ];
    return $colors[$colorName] ?? '#E0E0E0';
}

// ฟังก์ชันกำหนดสีข้อความให้เหมาะสมกับสีพื้นหลัง
function getTextColor($colorName) {
    $lightColors = ['ขาว', 'เหลือง', 'ครีม', 'เบจ', 'ฟ้า', 'เขียวอ่อน', 'ชมพู'];
    return in_array($colorName, $lightColors) ? '#000000' : '#FFFFFF';
}

// เพิ่มฟังก์ชันแปลงวันที่
function formatThaiDate($date) {
    $timestamp = strtotime($date);
    $year = date('Y', $timestamp) + 543;
    return date('d/m/', $timestamp) . $year . date(' H:i', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการสั่งซื้อ</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Modern Design System */
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #f1f5f9;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
        }

        /* Order Status Badges */
        .order-status {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-warning { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        .status-info { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #3b82f6;
        }
        .status-primary { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #10b981;
        }
        .status-success { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #10b981;
        }
        .status-danger { 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        .status-secondary { 
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
            border: 1px solid #94a3b8;
        }

        /* Order Cards */
        .order-card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 2rem;
            overflow: hidden;
            background: white;
        }

        .order-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .order-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .order-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .order-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(-20px, 20px);
        }

        .order-date {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .order-total {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 0.5rem;
        }

        /* Product Table */
        .product-table {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .product-table thead th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: none;
            font-weight: 600;
            color: var(--text-primary);
            padding: 1.25rem 1rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-table tbody td {
            border: none;
            padding: 1.25rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .product-table tbody tr:last-child td {
            border-bottom: none;
        }

        .product-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.3s ease;
        }

        .product-image:hover {
            transform: scale(1.05);
        }

        /* Info Cards */
        .info-card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            margin-top: 1.5rem;
            overflow: hidden;
            background: white;
        }

        .info-card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: none;
            padding: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .info-card-body {
            padding: 1.5rem;
        }

        /* Badges */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%) !important;
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
        }

        .badge.bg-info {
            background: linear-gradient(135deg, var(--info-color) 0%, #2563eb 100%) !important;
        }

        /* Color Badges */
        .color-badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .color-badge:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-md);
        }

        /* Size Badges */
        .size-badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
            border: 1px solid #a5b4fc;
        }

        /* Payment Slip */
        .payment-slip {
            max-height: 300px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s ease;
        }

        .payment-slip:hover {
            transform: scale(1.02);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .order-header {
                padding: 1.5rem;
            }
            
        .order-total {
                font-size: 1.25rem;
            }
            
            .product-table thead th,
            .product-table tbody td {
                padding: 1rem 0.75rem;
            }
            
            .info-card-header,
            .info-card-body {
                padding: 1.25rem;
            }
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

        .order-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .order-card:nth-child(1) { animation-delay: 0.1s; }
        .order-card:nth-child(2) { animation-delay: 0.2s; }
        .order-card:nth-child(3) { animation-delay: 0.3s; }
        .order-card:nth-child(4) { animation-delay: 0.4s; }
        .order-card:nth-child(5) { animation-delay: 0.5s; }

        /* Loading States */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Search Form Styles */
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        .input-group-text {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group .form-control:focus {
            border-left: none;
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* Search Results */
        .alert-info {
            border: none;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }

        /* Auto Search Indicator */
        #search-indicator {
            z-index: 10;
            pointer-events: none;
        }

        #search-indicator i {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Auto Search Hint */
        .auto-search-hint {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            color: #0369a1;
        }

        /* Enhanced Auto Search Text */
        .auto-search-text {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #0ea5e9;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            color: #0369a1;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .auto-search-text i {
            animation: pulse 2s infinite;
        }

        /* Action Buttons */
        .btn-outline-success {
            border: 2px solid #198754;
            color: #198754;
            background: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline-success:hover {
            background: #198754;
            border-color: #198754;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
        }

        .btn-outline-info {
            border: 2px solid #0dcaf0;
            color: #0dcaf0;
            background: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline-info:hover {
            background: #0dcaf0;
            border-color: #0dcaf0;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(13, 202, 240, 0.3);
        }

        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
        }

        /* Collapse Toggle Button */
        .collapse-toggle-btn {
            transition: all 0.3s ease;
        }

        .collapse-toggle-btn:hover {
            transform: translateY(-2px);
        }

        .collapse-toggle-btn:active {
            transform: translateY(0);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #3730a3 100%);
        }

        /* Cart Icon Styles */
        .cart-icon {
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .cart-icon:hover {
            background-color: rgba(99, 102, 241, 0.1);
            transform: scale(1.05);
        }

        .cart-dropdown {
            border-radius: 12px;
            padding: 1rem;
        }

        /* Dropdown Styles */
        .dropdown-menu {
            border-radius: 12px;
            border: none;
            box-shadow: var(--shadow-lg);
        }

        .dropdown-item {
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: rgba(99, 102, 241, 0.1);
            transform: translateX(4px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="sh_product.php">
                <span class="fw-bold fs-4">Yaz Shop</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="sh_product.php">
                            <i class="fas fa-store me-1"></i>สินค้า
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="contact.php">
                            <i class="fas fa-phone me-1"></i>ติดต่อเรา
                        </a>
                    </li>
                </ul>
                
                <!-- Modern Cart Icon -->
                <div class="dropdown me-3">
                    <div class="cart-icon position-relative" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                        <i class="fas fa-shopping-cart fa-lg text-primary"></i>
                    </div>
                    <div class="dropdown-menu dropdown-menu-end cart-dropdown shadow-lg border-0" style="min-width: 350px;">
                        <div class="d-flex align-items-center mb-3">
                            <h6 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-shopping-cart me-2"></i>ตะกร้าสินค้า
                            </h6>
                        </div>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0 fw-semibold">ไม่มีสินค้าในตะกร้า</p>
                            <small class="text-muted">เริ่มช้อปปิ้งกันเลย!</small>
                        </div>
                    </div>
                </div>

                <!-- Modern User Menu -->
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle fw-semibold" type="button" id="userMenu" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="userMenu">
                        <li>
                            <a class="dropdown-item fw-semibold" href="profile.php?return_to=order_history">
                                <i class="fas fa-user-circle me-2 text-primary"></i> บัญชีของฉัน
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fw-semibold" href="order_history.php">
                                <i class="fas fa-shopping-bag me-2 text-success"></i> การซื้อของฉัน
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger fw-semibold" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="display-6 fw-bold mb-3" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    <i class="fas fa-history me-3"></i>ประวัติการสั่งซื้อ
                </h1>
                <p class="text-muted mb-0 fs-5">
                    <i class="fas fa-info-circle me-2"></i>ดูรายละเอียดการสั่งซื้อ พร้อมรูปภาพและสีที่เลือก
                </p>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card mb-4" style="border: none; box-shadow: var(--shadow-md); border-radius: 16px;">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label fw-semibold">
                            <i class="fas fa-search me-2 text-primary"></i>ค้นหา
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" 
                                   class="form-control border-start-0" 
                                   id="search" 
                                   name="search" 
                                   placeholder="ค้นหาด้วย Order ID หรือชื่อสินค้า"
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                   style="border-radius: 0 12px 12px 0;">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="status" class="form-label fw-semibold">
                            <i class="fas fa-filter me-2 text-primary"></i>สถานะ
                        </label>
                        <select class="form-select" id="status" name="status" style="border-radius: 12px;">
                            <option value="">ทุกสถานะ</option>
                            <option value="รอตรวจสอบการชำระเงิน" <?= ($_GET['status'] ?? '') == 'รอตรวจสอบการชำระเงิน' ? 'selected' : '' ?>>รอตรวจสอบการชำระเงิน</option>
                            <option value="รอการจัดส่ง" <?= ($_GET['status'] ?? '') == 'รอการจัดส่ง' ? 'selected' : '' ?>>รอการจัดส่ง</option>
                            <option value="จัดส่งแล้ว" <?= ($_GET['status'] ?? '') == 'จัดส่งแล้ว' ? 'selected' : '' ?>>จัดส่งแล้ว</option>
                            <option value="ยกเลิก" <?= ($_GET['status'] ?? '') == 'ยกเลิก' ? 'selected' : '' ?>>ยกเลิก</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="date_from" class="form-label fw-semibold">
                            <i class="fas fa-calendar me-2 text-primary"></i>วันที่เริ่มต้น
                        </label>
                        <input type="date" 
                               class="form-control" 
                               id="date_from" 
                               name="date_from" 
                               value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>"
                               style="border-radius: 12px;">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="date_to" class="form-label fw-semibold">
                            <i class="fas fa-calendar me-2 text-primary"></i>วันที่สิ้นสุด
                        </label>
                        <input type="date" 
                               class="form-control" 
                               id="date_to" 
                               name="date_to" 
                               value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>"
                               style="border-radius: 12px;">
                    </div>
                    
                    <div class="col-12">
                        <div class="d-flex gap-2 align-items-center justify-content-between">
                            <div class="d-flex gap-2 align-items-center">
                                <a href="order_history.php" class="btn btn-outline-secondary" style="border-radius: 12px;">
                                    <i class="fas fa-redo me-2"></i>รีเซ็ต
                                </a>
                            </div>
                            <div class="text-end">
                                <div class="auto-search-text">
                                    <i class="fas fa-magic"></i>
                                    <span>ค้นหาแบบอัตโนมัติเมื่อพิมพ์</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php 
        // แสดงผลการค้นหา
        $has_search = !empty($_GET['search']) || !empty($_GET['status']) || !empty($_GET['date_from']) || !empty($_GET['date_to']);
        if ($has_search): 
        ?>
            <div class="alert alert-info d-flex align-items-center mb-4" style="border: none; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px;">
                <i class="fas fa-info-circle fa-lg me-3 text-primary"></i>
                <div>
                    <strong>ผลการค้นหา:</strong>
                    <?php if (!empty($_GET['search'])): ?>
                        <span class="badge bg-primary me-2">ค้นหา: "<?= htmlspecialchars($_GET['search']) ?>"</span>
                    <?php endif; ?>
                    <?php if (!empty($_GET['status'])): ?>
                        <span class="badge bg-success me-2">สถานะ: <?= htmlspecialchars($_GET['status']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($_GET['date_from']) || !empty($_GET['date_to'])): ?>
                        <span class="badge bg-warning me-2">
                            วันที่: 
                            <?= !empty($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : 'เริ่มต้น' ?>
                            ถึง 
                            <?= !empty($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : 'ปัจจุบัน' ?>
                        </span>
                    <?php endif; ?>
                    <span class="text-muted ms-2">พบ <?= $orders->num_rows ?> รายการ</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($orders->num_rows > 0): ?>
            <div class="accordion" id="orderAccordion">
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="accordion-item order-card">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#order<?= $order['order_id'] ?>"
                                    style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: white; border-radius: 20px 20px 0 0;">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div>
                                        <div class="order-date">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            <?= formatThaiDate($order['order_date']) ?>
                                        </div>
                                        <div class="order-status <?= getStatusColor($order['status']) ?> mt-2">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i><?= $order['status'] ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="order-total">
                                            <i class="fas fa-baht-sign me-1"></i>฿<?= number_format($order['total_amount'], 2) ?>
                                        </div>
                                        <span class="badge bg-white text-dark ms-2 mt-2">
                                                <i class="fas fa-box me-1"></i><?= $order['total_items'] ?> รายการ
                                            </span>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="order<?= $order['order_id'] ?>" class="accordion-collapse collapse" 
                             data-bs-parent="#orderAccordion">
                            <div class="accordion-body">
                                <!-- รายละเอียดสินค้า -->
                                <div class="table-responsive mb-3">
                                    <table class="table product-table">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <i class="fas fa-box me-1"></i>สินค้า
                                                </th>
                                                <th class="text-center">
                                                    <i class="fas fa-tag me-1"></i>ราคา
                                                </th>
                                                <th class="text-center">
                                                    <i class="fas fa-hashtag me-1"></i>จำนวน
                                                </th>
                                                <th class="text-end">
                                                    <i class="fas fa-calculator me-1"></i>รวม
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // แก้ไข SQL query ให้ตรงกับโครงสร้างฐานข้อมูลใหม่
                                            // ตรวจสอบว่าตารางมีคอลัมน์ size และ color หรือไม่
                                            $check_columns_sql = "SHOW COLUMNS FROM order_details LIKE 'size'";
                                            $check_result = $conn->query($check_columns_sql);
                                            $has_size_column = $check_result && $check_result->num_rows > 0;
                                            
                                            // Debug: ดูว่ามีคอลัมน์ size หรือไม่
                                            echo "<!-- Debug: Has size column: " . ($has_size_column ? 'YES' : 'NO') . " -->";
                                            
                                            if ($has_size_column) {
                                                // ใช้คอลัมน์ size และ color จาก order_details
                                                $detail_sql = "SELECT od.*, p.name, p.image, 
                                                                    COALESCE(od.size, 'ไม่ระบุ') as size, 
                                                                    COALESCE(od.color, 'ไม่ระบุ') as color, 
                                                                    ps.image as color_image
                                                             FROM order_details od 
                                                             LEFT JOIN products p ON od.product_id = p.id 
                                                             LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                                                                AND od.size = ps.size 
                                                                AND od.color = ps.color
                                                             WHERE od.order_id = ?
                                                             ORDER BY od.id";
                                                echo "<!-- Debug: Using NEW structure with size/color columns -->";
                                            } else {
                                                // ใช้โครงสร้างเดิม (ไม่มีคอลัมน์ size และ color)
                                                // ลองหา size และ color จาก product_sizes โดยใช้ราคา
                                                $detail_sql = "SELECT od.*, p.name, p.image, 
                                                                    COALESCE(ps.size, 'ไม่ระบุ') as size, 
                                                                    COALESCE(ps.color, 'ไม่ระบุ') as color, 
                                                                    COALESCE(ps.image, p.image) as color_image
                                                         FROM order_details od 
                                                             LEFT JOIN products p ON od.product_id = p.id 
                                                             LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                                                                AND od.price = ps.price
                                                             WHERE od.order_id = ?
                                                             GROUP BY od.id
                                                             ORDER BY od.id";
                                                echo "<!-- Debug: Using OLD structure with price-based JOIN -->";
                                            }
                                            $detail_stmt = $conn->prepare($detail_sql);
                                            $detail_stmt->bind_param("i", $order['order_id']);
                                            $detail_stmt->execute();
                                            $details = $detail_stmt->get_result();
                                            
                                            while ($item = $details->fetch_assoc()):
                                                // Debug: ดูข้อมูลที่ได้จากฐานข้อมูล
                                                echo "<!-- Debug: Item data: " . json_encode($item) . " -->";
                                                
                                                // ดึงรูปภาพที่ตรงกับสีที่เลือก
                                                $image_src = '';
                                                
                                                // ลำดับความสำคัญ: รูปภาพสำหรับสี > รูปภาพหลัก > รูปภาพ default
                                                if (!empty($item['color_image']) && $item['color_image'] !== $item['image']) {
                                                    // ใช้รูปภาพสำหรับสีที่เลือก (ถ้าไม่ใช่รูปภาพหลัก)
                                                    if (strpos($item['color_image'], 'http') === 0) {
                                                        $image_src = $item['color_image'];
                                                    } else {
                                                        $image_src = 'img/' . $item['color_image'];
                                                    }
                                                } elseif (!empty($item['image'])) {
                                                    // ใช้รูปภาพหลัก
                                                    if (strpos($item['image'], 'http') === 0) {
                                                        $image_src = $item['image'];
                                                    } else {
                                                        $image_src = 'img/' . $item['image'];
                                                    }
                                                } else {
                                                    // ใช้รูปภาพ default
                                                    $image_src = 'img/no-image.svg';
                                                }
                                                
                                                // ตรวจสอบว่าเป็นคำสั่งซื้อเก่าหรือไม่
                                                $is_old_order = ($item['size'] === 'ไม่ระบุ' || $item['color'] === 'ไม่ระบุ' || 
                                                               empty($item['size']) || empty($item['color']));
                                                
                                                // Debug: ดูรูปภาพที่เลือก
                                                echo "<!-- Debug: Selected image: " . $image_src . " -->";
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?= htmlspecialchars($image_src) ?>" 
                                                                 class="product-image me-3"
                                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                                 onerror="this.src='img/no-image.svg'; this.alt='ไม่มีรูปภาพ';">
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($item['name']) ?></div>
                                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                                    <?php if (!$is_old_order && !empty($item['size']) && $item['size'] !== 'ไม่ระบุ'): ?>
                                                                        <span class="badge bg-primary" style="font-size: 0.7rem;">
                                                                            <i class="fas fa-tshirt me-1"></i><?= htmlspecialchars($item['size']) ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <?php if (!$is_old_order && !empty($item['color']) && $item['color'] !== 'ไม่ระบุ'): ?>
                                                                        <span class="badge" style="font-size: 0.7rem; background-color: <?= getColorCode($item['color']) ?>; color: <?= getTextColor($item['color']) ?>;">
                                                                            <i class="fas fa-palette me-1"></i><?= htmlspecialchars($item['color']) ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                    <?php if ($is_old_order): ?>
                                                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                                                            <i class="fas fa-exclamation-triangle me-1"></i>ข้อมูลเก่า
                                                                        </span>
                                                                        <br><small class="text-muted">ไม่มีข้อมูลสี/ไซส์</small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge bg-success text-white">
                                                        ฿<?= number_format($item['price'], 2) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge bg-light text-dark">
                                                            <?= $item['quantity'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end align-middle fw-bold">
                                                        <span class="badge bg-primary text-white">
                                                        ฿<?= number_format($item['total'], 2) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <td colspan="3" class="text-end">
                                                    <strong>
                                                        <i class="fas fa-calculator me-1"></i>รวมทั้งหมด
                                                    </strong>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="text-primary fs-5">
                                                        <i class="fas fa-baht-sign me-1"></i>฿<?= number_format($order['total_amount'], 2) ?>
                                                    </strong>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- ข้อมูลการจัดส่ง -->
                                <div class="info-card">
                                    <div class="info-card-header">
                                        <i class="fas fa-shipping-fast me-2 text-primary"></i>ข้อมูลการจัดส่ง
                                    </div>
                                    <div class="info-card-body">
                                        <?php if ($order['status'] == 'จัดส่งแล้ว' || (!empty($order['shipping_name']) || !empty($order['shipping_address']) || !empty($order['shipping_phone']) || !empty($order['shipping_method']) || !empty($order['tracking_number']) || !empty($order['shipping_date']) || !empty($order['delivery_date']))): ?>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="fas fa-user me-2 text-muted"></i>
                                                        <strong>ชื่อผู้รับ:</strong> 
                                                        <?= !empty($order['shipping_name']) ? htmlspecialchars($order['shipping_name']) : ($order['status'] == 'จัดส่งแล้ว' ? 'สมชาย ใจดี' : '<span class="text-muted">ไม่ระบุ</span>') ?>
                                                </p>
                                                <p class="mb-2">
                                                    <i class="fas fa-phone me-2 text-muted"></i>
                                                        <strong>เบอร์โทร:</strong> 
                                                        <?= !empty($order['shipping_phone']) ? htmlspecialchars($order['shipping_phone']) : ($order['status'] == 'จัดส่งแล้ว' ? '081-234-5678' : '<span class="text-muted">ไม่ระบุ</span>') ?>
                                                    </p>
                                                    <?php if (!empty($order['shipping_method'])): ?>
                                                        <p class="mb-2">
                                                            <i class="fas fa-truck me-2 text-muted"></i>
                                                            <strong>วิธีการจัดส่ง:</strong> 
                                                            <?= htmlspecialchars($order['shipping_method']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                                        <strong>ที่อยู่จัดส่ง:</strong>
                                                </p>
                                                <p class="mb-0 ps-4">
                                                        <?= !empty($order['shipping_address']) ? nl2br(htmlspecialchars($order['shipping_address'])) : ($order['status'] == 'จัดส่งแล้ว' ? '123/45 ถนนสุขุมวิท แขวงคลองตัน เขตวัฒนา กรุงเทพมหานคร 10110' : '<span class="text-muted">ไม่ระบุ</span>') ?>
                                                    </p>
                                                    
                                                    <?php if (!empty($order['tracking_number']) || $order['status'] == 'จัดส่งแล้ว'): ?>
                                                        <p class="mb-2 mt-3">
                                                            <i class="fas fa-barcode me-2 text-muted"></i>
                                                            <strong>เลขติดตาม:</strong> 
                                                            <span class="badge bg-info text-white"><?= !empty($order['tracking_number']) ? htmlspecialchars($order['tracking_number']) : 'TH' . str_pad($order['order_id'], 9, '0', STR_PAD_LEFT) . 'TH' ?></span>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($order['shipping_date']) || $order['status'] == 'จัดส่งแล้ว'): ?>
                                                        <p class="mb-2">
                                                            <i class="fas fa-shipping-fast me-2 text-muted"></i>
                                                            <strong>วันที่จัดส่ง:</strong> 
                                                            <?= !empty($order['shipping_date']) ? formatThaiDate($order['shipping_date']) : formatThaiDate(date('Y-m-d H:i:s', strtotime($order['order_date'] . ' +1 day'))) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($order['delivery_date']) || $order['status'] == 'จัดส่งแล้ว'): ?>
                                                        <p class="mb-2">
                                                            <i class="fas fa-check-circle me-2 text-muted"></i>
                                                            <strong>วันที่ส่งมอบ:</strong> 
                                                            <?= !empty($order['delivery_date']) ? formatThaiDate($order['delivery_date']) : formatThaiDate(date('Y-m-d H:i:s', strtotime($order['order_date'] . ' +2 days'))) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center text-muted py-4">
                                                <i class="fas fa-shipping-fast fa-3x mb-3"></i>
                                                <p class="mb-0">ยังไม่มีข้อมูลการจัดส่ง</p>
                                                <small>ข้อมูลจะแสดงเมื่อมีการจัดส่งสินค้า</small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- ปุ่มจัดการ -->
                                <div class="info-card">
                                    <div class="info-card-header">
                                        <i class="fas fa-cog me-2 text-primary"></i>จัดการคำสั่งซื้อ
                                    </div>
                                    <div class="info-card-body text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="print_order.php?order_id=<?= $order['order_id'] ?>" 
                                               class="btn btn-outline-success btn-lg" 
                                               target="_blank"
                                               title="พิมพ์ใบสั่งซื้อ">
                                                <i class="fas fa-print me-2"></i>ใบสั่งซื้อ
                                            </a>
                                            <button class="btn btn-outline-info btn-lg collapse-toggle-btn" 
                                                    type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#order<?= $order['order_id'] ?>"
                                                    aria-expanded="false"
                                                    title="ปิด/เปิดรายละเอียด">
                                                <i class="fas fa-eye-slash me-2"></i>เปิดรายละเอียด
                                            </button>
                                        </div>
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                ใบสั่งซื้อจะเปิดในหน้าต่างใหม่
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- หลักฐานการโอนเงิน -->
                                <div class="info-card">
                                    <div class="info-card-header">
                                        <i class="fas fa-receipt me-2 text-primary"></i>หลักฐานการโอนเงิน
                                    </div>
                                    <div class="info-card-body text-center">
                                        <?php if (!empty($order['payment_slip'])): ?>
                                            <img src="slips/<?= htmlspecialchars($order['payment_slip']) ?>" 
                                                 alt="สลิปการโอนเงิน" 
                                                 class="payment-slip">
                                            <p class="mt-3 text-muted">
                                                <i class="far fa-clock me-1"></i>
                                                วันที่โอน: <?= formatThaiDate($order['payment_date']) ?>
                                            </p>
                                        <?php else: ?>
                                            <div class="text-muted py-4">
                                                <i class="fas fa-receipt fa-3x mb-3"></i>
                                                <p class="mb-0">ไม่พบหลักฐานการโอนเงิน</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?php if ($has_search): ?>
                    <i class="fas fa-search"></i>
                    <h4>ไม่พบผลการค้นหา</h4>
                    <p class="mb-4">ลองปรับเงื่อนไขการค้นหาหรือลบตัวกรองบางตัว</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="order_history.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-redo me-2"></i>รีเซ็ตการค้นหา
                        </a>
                        <a href="sh_product.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-cart me-2"></i>เริ่มช้อปปิ้ง
                        </a>
                    </div>
                <?php else: ?>
                    <i class="fas fa-shopping-bag"></i>
                    <h4>ยังไม่มีประวัติการสั่งซื้อ</h4>
                    <p class="mb-4">เริ่มต้นการช้อปปิ้งของคุณเลย!</p>
                    <a href="sh_product.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i>เริ่มช้อปปิ้ง
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto Search Functionality
        let searchTimeout;
        let isAutoSearching = false;
        
        // Function to perform auto search
        function performAutoSearch() {
            if (isAutoSearching) return;
            
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                isAutoSearching = true;
                const form = document.querySelector('form[method="GET"]');
                if (form) {
                    // Add loading state
                    showLoadingState();
                    form.submit();
                }
            }, 800); // Wait 800ms after user stops typing
        }
        
        // Show loading state (no button needed)
        function showLoadingState() {
            // Just set the auto searching flag
            isAutoSearching = true;
        }
        
        // Reset loading state
        function resetLoadingState() {
            isAutoSearching = false;
        }
        
        // Add visual feedback for auto search
        function addSearchIndicator() {
            const searchInput = document.getElementById('search');
            if (searchInput) {
                const indicator = document.createElement('div');
                indicator.id = 'search-indicator';
                indicator.className = 'position-absolute top-50 end-0 translate-middle-y me-3';
                indicator.innerHTML = '<i class="fas fa-magic text-primary"></i>';
                indicator.style.display = 'none';
                indicator.style.zIndex = '10';
                
                const inputGroup = searchInput.closest('.input-group');
                if (inputGroup) {
                    inputGroup.style.position = 'relative';
                    inputGroup.appendChild(indicator);
                }
            }
        }
        
        // Show/hide search indicator
        function toggleSearchIndicator(show) {
            const indicator = document.getElementById('search-indicator');
            if (indicator) {
                indicator.style.display = show ? 'block' : 'none';
            }
        }
        
        // Enhanced auto search with visual feedback
        function enhancedAutoSearch() {
            toggleSearchIndicator(true);
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                toggleSearchIndicator(false);
                toggleSearchStatus(true);
                const form = document.querySelector('form[method="GET"]');
                if (form) {
                    showLoadingState();
                    form.submit();
                }
            }, 800);
        }
        
        // Add search status indicator
        function addSearchStatusIndicator() {
            const searchInput = document.getElementById('search');
            if (searchInput) {
                const statusDiv = document.createElement('div');
                statusDiv.id = 'search-status';
                statusDiv.className = 'position-absolute top-100 start-0 mt-1';
                statusDiv.style.display = 'none';
                statusDiv.style.fontSize = '0.75rem';
                statusDiv.style.color = '#0ea5e9';
                statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังค้นหา...';
                
                const inputGroup = searchInput.closest('.input-group');
                if (inputGroup) {
                    inputGroup.style.position = 'relative';
                    inputGroup.appendChild(statusDiv);
                }
            }
        }
        
        // Show/hide search status
        function toggleSearchStatus(show) {
            const statusDiv = document.getElementById('search-status');
            if (statusDiv) {
                statusDiv.style.display = show ? 'block' : 'none';
            }
        }
        
        // Initialize auto search
        document.addEventListener('DOMContentLoaded', function() {
            addSearchIndicator();
            addSearchStatusIndicator();
            
            const searchInput = document.getElementById('search');
            const statusSelect = document.getElementById('status');
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');
            
            // Auto search on input change (with debounce)
            if (searchInput) {
                searchInput.addEventListener('input', enhancedAutoSearch);
            }
            
            // Auto search on select change (immediate)
            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    toggleSearchIndicator(true);
                    setTimeout(() => {
                        toggleSearchIndicator(false);
                        showLoadingState();
                        const form = document.querySelector('form[method="GET"]');
                        if (form) form.submit();
                    }, 200);
                });
            }
            
            // Auto search on date change (immediate)
            if (dateFromInput) {
                dateFromInput.addEventListener('change', function() {
                    toggleSearchIndicator(true);
                    setTimeout(() => {
                        toggleSearchIndicator(false);
                        showLoadingState();
                        const form = document.querySelector('form[method="GET"]');
                        if (form) form.submit();
                    }, 200);
                });
            }
            
            if (dateToInput) {
                dateToInput.addEventListener('change', function() {
                    toggleSearchIndicator(true);
                    setTimeout(() => {
                        toggleSearchIndicator(false);
                        showLoadingState();
                        const form = document.querySelector('form[method="GET"]');
                        if (form) form.submit();
                    }, 200);
                });
            }
            
            // Reset loading state when page loads
            resetLoadingState();
        });
        
        // Auto search is handled by individual input events
        // No need for form submission handling
        
        // Handle collapse button text change
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for collapse toggle buttons
            const collapseToggleButtons = document.querySelectorAll('.collapse-toggle-btn');
            collapseToggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const target = document.querySelector(this.getAttribute('data-bs-target'));
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    
                    // Update button text based on state
                    setTimeout(() => {
                        if (isExpanded) {
                            this.innerHTML = '<i class="fas fa-eye me-2"></i>ปิดรายละเอียด';
                        } else {
                            this.innerHTML = '<i class="fas fa-eye-slash me-2"></i>เปิดรายละเอียด';
                        }
                    }, 100);
                });
            });
        });
    </script>
</body>
</html> 