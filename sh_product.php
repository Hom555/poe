<?php
session_start();
include 'condb.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลสินค้า
$sql = "SELECT p.*, t.type_name 
        FROM product p 
        LEFT JOIN type t ON p.type_id = t.type_id 
        ORDER BY p.po_id DESC";
$result = mysqli_query($conn, $sql);

// เพิ่มสินค้าลงตะกร้า
if (isset($_GET['id'])) {
    $po_id = $_GET['id'];
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1; // รับค่าจำนวนสินค้า
    $search = $_GET['search'] ?? '';
    
    // ตรวจสอบจำนวนสินค้าในสต็อก
    $stock_check = $conn->prepare("SELECT amount FROM product WHERE po_id = ?");
    $stock_check->bind_param("i", $po_id);
    $stock_check->execute();
    $stock_result = $stock_check->get_result();
    $stock_data = $stock_result->fetch_assoc();
    
    if ($quantity > $stock_data['amount']) {
        echo "<script>
            alert('จำนวนสินค้าไม่เพียงพอ มีสินค้าในสต็อก " . $stock_data['amount'] . " ชิ้น');
            window.location.href = 'sh_product.php" . ($search ? "?search=" . urlencode($search) : "") . "';
        </script>";
        exit();
    }
    
    // ตรวจสอบว่ามีตะกร้าสินค้าหรือยัง
    if (!isset($_SESSION["strProductID"])) {
        $_SESSION["strProductID"] = array();
        $_SESSION["strQty"] = array();
    }
    
    // เพิ่มสินค้าลงตะกร้า
    array_push($_SESSION["strProductID"], $po_id);
    array_push($_SESSION["strQty"], $quantity);
    
    echo "<script>
        alert('เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว');
        window.location.href = 'sh_product.php" . ($search ? "?search=" . urlencode($search) : "") . "';
    </script>";
    exit();
}

// คำนวณจำนวนสินค้าในตะกร้า
$cart_count = isset($_SESSION["strProductID"]) ? count($_SESSION["strProductID"]) : 0;

// คำนวณราคารวมในตะกร้า
$cart_total = 0;
if (isset($_SESSION["strProductID"])) {
    foreach ($_SESSION["strProductID"] as $key => $product_id) {
        $sql = "SELECT price FROM product WHERE po_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result_price = $stmt->get_result();
        $row_price = $result_price->fetch_assoc();
        $cart_total += $row_price['price'] * $_SESSION["strQty"][$key];
    }
}

// เพิ่มฟอร์มค้นหาด้านบน
$search = $_GET['search'] ?? '';
$sql = "SELECT p.*, t.type_name 
        FROM product p 
        LEFT JOIN type t ON p.type_id = t.type_id 
        WHERE p.po_name LIKE ? OR t.type_name LIKE ?
        ORDER BY p.po_id DESC";
$stmt = $conn->prepare($sql);
$searchTerm = "%$search%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

// เพิ่มการจัดการค้นหาและกรอง
$where_clause = "p.amount > 0"; // แสดงเฉพาะสินค้าที่มีในสต็อก
$params = array();
$types = "";

// ค้นหาตามคำค้น
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $where_clause .= " AND (p.po_name LIKE ? OR t.type_name LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

// กรองตามประเภท
if (isset($_GET['type_id']) && !empty($_GET['type_id'])) {
    $where_clause .= " AND p.type_id = ?";
    $params[] = $_GET['type_id'];
    $types .= "i";
}

// ดึงข้อมูลประเภทสินค้าสำหรับ dropdown
$type_sql = "SELECT * FROM type ORDER BY type_name";
$type_result = $conn->query($type_sql);

// แก้ไข SQL query หลัก
$sql = "SELECT p.*, t.type_name 
        FROM product p 
        LEFT JOIN type t ON p.type_id = t.type_id 
        WHERE $where_clause 
        ORDER BY p.po_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้าทั้งหมด</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="img/logo.png" alt="" 
                     style="height: 40px; margin-right: 10px;">
                <span class="fw-bold">Yaz Shop</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="sh_product.php">สินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">ติดต่อเรา</a>
                    </li>
                </ul>
                


                
                <!-- ตะกร้าสินค้า -->
                <div class="dropdown me-3">
                    <div class="cart-icon" data-bs-toggle="dropdown">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-menu dropdown-menu-end cart-dropdown">
                        <?php if ($cart_count > 0): ?>
                            <?php
                            foreach ($_SESSION["strProductID"] as $key => $product_id):
                                $sql = "SELECT * FROM product WHERE po_id = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $product_id);
                                $stmt->execute();
                                $result_cart = $stmt->get_result();
                                $item = $result_cart->fetch_assoc();
                                $subtotal = $item['price'] * $_SESSION["strQty"][$key];
                            ?>
                                <div class="cart-item">
                                    <img src="img/<?= $item['image'] ?>" alt="<?= $item['po_name'] ?>">
                                    <div class="cart-item-details">
                                        <div><?= $item['po_name'] ?></div>
                                        <div class="text-muted">
                                            <?= $_SESSION["strQty"][$key] ?> x 
                                            <span class="cart-item-price">
                                                ฿<?= number_format($item['price'], 2) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <strong>รวมทั้งหมด:</strong>
                                    <span class="cart-item-price">฿<?= number_format($cart_total, 2) ?></span>
                                </div>
                                <a href="cart.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-shopping-cart"></i> ดูตะกร้า
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">ไม่มีสินค้าในตะกร้า</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user-circle"></i> บัญชีของฉัน
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="order_history.php">
                                <i class="fas fa-shopping-bag"></i> การซื้อของฉัน
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
            </div>
        </div>
    </nav>

    <!-- แก้ไขส่วนฟอร์มค้นหาและตัวกรอง -->
    <div class="container mt-3">
        <div class="row mb-3">
            <div class="col-md-8">
                <form action="" method="GET" class="d-flex gap-2">
                    <!-- ช่องค้นหา -->
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="ค้นหาสินค้า..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <!-- เลือกประเภทสินค้า -->
                    <select name="type_id" class="form-select" style="width: auto;" 
                            onchange="this.form.submit()">
                        <option value="">ทุกประเภท</option>
                        <?php
                        // ดึงข้อมูลประเภทสินค้า
                        $type_sql = "SELECT * FROM type ORDER BY type_name";
                        $type_result = $conn->query($type_sql);
                        while ($type = $type_result->fetch_assoc()):
                        ?>
                            <option value="<?= $type['type_id'] ?>" 
                                    <?= (isset($_GET['type_id']) && $_GET['type_id'] == $type['type_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['type_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <?php if(isset($_GET['search']) || isset($_GET['type_id'])): ?>
                        <a href="sh_product.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> ล้างตัวกรอง
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($result->num_rows == 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <?php if(isset($_GET['search']) || isset($_GET['type_id'])): ?>
                    ไม่พบสินค้าที่ตรงกับเงื่อนไขที่เลือก
                <?php else: ?>
                    ไม่มีสินค้าในระบบ
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- แสดงผลการค้นหา -->
    <div class="container mt-4">
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-2">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="position-relative">
                            <img src="img/<?= $row['image'] ?>" class="card-img-top" 
                                 alt="<?= htmlspecialchars($row['po_name']) ?>"
                                 style="height: 200px; object-fit: cover;">
                            <?php if ($row['amount'] <= 0): ?>
                                <div class="sold-out-badge">สินค้าหมด</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($row['po_name']) ?></h5>
                            
                            <!-- เพิ่มรายละเอียดสั้น -->
                            <?php if (!empty($row['description'])): ?>
                                <p class="card-text description">
                                    <?= nl2br(htmlspecialchars($row['description'])) ?>
                                </p>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary"><?= htmlspecialchars($row['type_name']) ?></span>
                                <span class="text-danger fw-bold">฿<?= number_format($row['price'], 2) ?></span>
                            </div>

                            <!-- เพิ่มการแสดงจำนวนสินค้า -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge <?= $row['amount'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $row['amount'] > 0 ? 'มีสินค้า ' . $row['amount'] . ' ชิ้น' : 'สินค้าหมด' ?>
                                </span>
                            </div>

                            <!-- ปุ่มดูรายละเอียด -->
                            <?php if (!empty($row['detail'])): ?>
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm w-100 mb-2"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#productModal<?= $row['po_id'] ?>">
                                    <i class="fas fa-info-circle"></i> ดูรายละเอียด
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- ส่วนเพิ่มลงตะกร้า -->
                        <div class="card-footer bg-transparent border-top-0 p-2">
                            <?php if($row['amount'] > 0): ?>
                                <form action="" method="GET" class="d-flex gap-1">
                                    <input type="hidden" name="id" value="<?= $row['po_id'] ?>">
                                    <?php if(isset($_GET['search'])): ?>
                                        <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
                                    <?php endif; ?>
                                    <div class="input-group input-group-sm quantity-control">
                                        <button type="button" class="btn btn-outline-secondary" 
                                                onclick="decrementQty(this)">-</button>
                                        <input type="number" name="quantity" class="form-control text-center px-0" 
                                               value="1" min="1" max="<?= min($row['amount'], 10) ?>" 
                                               onchange="validateQty(this, <?= min($row['amount'], 10) ?>)">
                                        <button type="button" class="btn btn-outline-secondary" 
                                                onclick="incrementQty(this, <?= min($row['amount'], 10) ?>)">+</button>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm add-to-cart">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm w-100" disabled>
                                    <i class="fas fa-times"></i> สินค้าหมด
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Modal แสดงรายละเอียด -->
                <?php if (!empty($row['detail'])): ?>
                <div class="modal fade" id="productModal<?= $row['po_id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= htmlspecialchars($row['po_name']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <img src="img/<?= $row['image'] ?>" 
                                             class="img-fluid rounded" 
                                             alt="<?= htmlspecialchars($row['po_name']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <h6>รายละเอียดสินค้า</h6>
                                        <p><?= nl2br(htmlspecialchars($row['detail'])) ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-primary"><?= htmlspecialchars($row['type_name']) ?></span>
                                            <span class="text-danger fw-bold">฿<?= number_format($row['price'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    function decrementQty(btn) {
        const input = btn.parentNode.querySelector('input');
        const currentValue = parseInt(input.value);
        if (currentValue > 1) {
            input.value = currentValue - 1;
        }
    }

    function incrementQty(btn, max) {
        const input = btn.parentNode.querySelector('input');
        const currentValue = parseInt(input.value);
        if (currentValue < max) {
            input.value = currentValue + 1;
        }
    }

    function validateQty(input, max) {
        let value = parseInt(input.value);
        if (isNaN(value) || value < 1) {
            input.value = 1;
        } else if (value > max) {
            input.value = max;
        }
    }
    </script>
</body>
</html>
<style>
        .dropdown-menu {
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .dropdown-item {
            padding: 8px 20px;
        }
        .dropdown-item i {
            width: 20px;
            margin-right: 10px;
        }
        .card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-img-top {
            height: 150px;
            object-fit: cover;
        }
        .product-grid {
            padding: 20px;
        }
        .cart-icon {
            position: relative;
            cursor: pointer;
        }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }
        .cart-dropdown {
            min-width: 300px;
            padding: 15px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .cart-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            margin-right: 10px;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-price {
            color: #dc3545;
            font-weight: bold;
        }
        .card-title {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .card-body {
            padding: 0.75rem;
        }
        .card-text {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .btn {
            font-size: 0.9rem;
            padding: 0.375rem 0.75rem;
        }
        input[type="number"] {
            -webkit-appearance: textfield;
            -moz-appearance: textfield;
            appearance: textfield;
        }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
        }
        .input-group .form-control {
            border-left: 0;
            border-right: 0;
        }
        .input-group .btn {
            z-index: 0;
        }
        .quantity-control {
            width: 100px !important;
            margin-right: 5px;
        }
        .quantity-control .btn {
            padding: 2px 8px;
            font-size: 14px;
            line-height: 1.5;
        }
        .quantity-control .form-control {
            padding: 2px;
            font-size: 14px;
            width: 40px;
            text-align: center;
        }
        .add-to-cart {
            padding: 4px 12px;
            font-size: 14px;
        }
        .input-group .btn:hover {
            background-color: #e9ecef;
        }
        .card-footer form {
            display: flex;
            align-items: center;
        }
        .navbar-brand img {
            transition: transform 0.2s;
        }
        .navbar-brand:hover img {
            transform: scale(1.05);
        }
        .navbar-brand span {
            color: #0d6efd;
            font-size: 1.4rem;
        }
        .description {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 0.9rem;
            color: #666;
        }
        .sold-out-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        .modal-body img {
            max-height: 300px;
            object-fit: cover;
        }
        .form-select {
            min-width: 150px;
        }
        .alert {
            margin-top: 1rem;
            padding: 0.75rem 1.25rem;
        }
        select.form-select {
            background-color: #fff;
            border: 1px solid #ced4da;
            cursor: pointer;
            transition: all 0.2s;
        }
        select.form-select:hover {
            border-color: #86b7fe;
        }
        .btn-outline-secondary {
            border-color: #ced4da;
        }
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            border-color: #86b7fe;
        }
    </style>
<?php mysqli_close($conn); ?>

