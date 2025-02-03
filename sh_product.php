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
    $search = $_GET['search'] ?? '';  // เก็บค่าการค้นหา
    
    // ตรวจสอบว่ามีตะกร้าสินค้าหรือยัง
    if (!isset($_SESSION["strProductID"])) {
        $_SESSION["strProductID"] = array();
        $_SESSION["strQty"] = array();
    }
    
    // เพิ่มสินค้าลงตะกร้า
    array_push($_SESSION["strProductID"], $po_id);
    array_push($_SESSION["strQty"], 1);
    
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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้าทั้งหมด</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">ร้านค้า</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="sh_product.php">สินค้า</a>
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

    <!-- แก้ไขส่วนฟอร์มค้นหา -->
    <div class="container mt-3">
        <div class="row mb-3">
            <div class="col-md-4">
                <form action="" method="GET" class="input-group">
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="ค้นหาสินค้า..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if(isset($_GET['search'])): ?>
                        <a href="sh_product.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($result->num_rows == 0): ?>
            <div class="alert alert-info py-2 px-3" style="font-size: 0.9rem;">
                <i class="fas fa-info-circle me-1"></i>
                <?php if($search): ?>
                    ไม่พบสินค้าที่ตรงกับ "<?= htmlspecialchars($search) ?>"
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
                        <img src="img/<?= $row['image'] ?>" class="card-img-top" 
                             alt="<?= htmlspecialchars($row['po_name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title text-truncate" title="<?= htmlspecialchars($row['po_name']) ?>">
                                <?= $row['po_name'] ?>
                            </h5>
                            <p class="card-text mb-1">
                                <span class="badge bg-info"><?= $row['type_name'] ?></span>
                            </p>
                            <p class="card-text mb-1">
                                ฿<?= number_format($row['price'], 2) ?>
                            </p>
                            <p class="card-text">
                                <?php if($row['amount'] > 0): ?>
                                    <small class="text-success">มีสินค้า <?= $row['amount'] ?></small>
                                <?php else: ?>
                                    <small class="text-danger">สินค้าหมด</small>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 p-2">
                            <?php if($row['amount'] > 0): ?>
                                <a href="?id=<?= $row['po_id'] ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" 
                                   class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-cart-plus"></i> เพิ่มลงตะกร้า
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm w-100" disabled>
                                    <i class="fas fa-times"></i> สินค้าหมด
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

