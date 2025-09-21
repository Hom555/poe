<?php
// ตรวจสอบก่อนเริ่ม session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'condb.php';

// ดึงข้อมูลผู้ใช้
$user_name = 'ผู้ใช้งาน';
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT name FROM users WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $user_name = $row['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yaz Shop - <?= $title ?? 'หน้าหลัก' ?></title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background-color: #f8f9fa;
            padding: 20px;
            height: 100vh;
            border-right: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
        }
        .sidebar .brand {
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 2px solid #dee2e6;
        }
        .sidebar .nav-item {
            margin-bottom: 5px;
        }
        .sidebar a {
            display: block;
            padding: 12px 15px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background-color: #e9ecef;
            transform: translateX(10px);
        }
        .sidebar a i {
            width: 25px;
            margin-right: 10px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .card {
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            border: none;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        .table {
            margin-bottom: 0;
        }
        .btn {
            border-radius: 5px;
        }
        .img-thumbnail {
            border-radius: 10px;
        }
        .user-menu {
            display: none;
        }
        .nav-items {
            flex: 1;
            margin-bottom: 20px;
        }
        .sidebar .dropdown {
            margin-top: auto;
        }
        .sidebar .dropdown-toggle {
            width: 100%;
            text-align: left;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            border-radius: 5px;
            color: #333;
        }
        .sidebar .dropdown-toggle:hover,
        .sidebar .dropdown-toggle:focus {
            background-color: #e9ecef;
        }
        .sidebar .dropdown-menu {
            width: 100%;
            margin-top: 5px;
            position: absolute;
            inset: auto auto 0 0;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 8px;
        }
        .sidebar .dropdown-item {
            padding: 8px 15px;
            border-radius: 4px;
        }
        .sidebar .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .sidebar .dropdown-item i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        .sidebar .dropdown-divider {
            margin: 8px 0;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar">
                <div class="brand">
                    <h4 class="text-center mb-0">Yaz Shop</h4>
                </div>
                <div class="nav-items">
                    <div class="nav-item">
                        <a href="yaz.php"><i class="fas fa-home"></i>หน้าหลัก</a>
                    </div>
                    <div class="nav-item">
                        <a href="users.php"><i class="fas fa-users"></i>จัดการผู้ใช้งาน</a>
                    </div>
                    <div class="nav-item">
                        <a href="sh_product_ad.php"><i class="fas fa-box"></i>สินค้า</a>
                    </div>
                    <div class="nav-item">
                        <a href="type_product.php"><i class="fas fa-tags"></i>ประเภทสินค้า</a>
                    </div>
                    <div class="nav-item">
                        <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i>คำสั่งซื้อ</a>
                    </div>
                    <div class="nav-item">
                        <a href="sales_report.php"><i class="fas fa-chart-line"></i>รายงานการขาย</a>
                    </div>
                    <div class="nav-item">
                        <a href="add_product.php"><i class="fas fa-plus-circle"></i>เพิ่มสินค้า</a>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i> <?php echo $user_name; ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <?php
                            // กำหนด return_to parameter ตามหน้าที่เรียกใช้
                            $current_page = basename($_SERVER['PHP_SELF']);
                            $return_to = '';
                            
                            switch($current_page) {
                                case 'yaz.php':
                                    $return_to = 'yaz';
                                    break;
                                case 'order_history.php':
                                    $return_to = 'order_history';
                                    break;
                                case 'sh_product.php':
                                default:
                                    $return_to = 'sh_product';
                                    break;
                            }
                            ?>
                            <a class="dropdown-item" href="profile.php?return_to=<?= $return_to ?>">
                                <i class="fas fa-user-circle"></i> ข้อมูลส่วนตัว
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 content"> 