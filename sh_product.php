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
        window.location.href = 'sh_product.php';
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
            height: 200px;
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

    <!-- Product Grid -->
    <div class="container product-grid">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="img/<?= $row['image'] ?>" 
                             class="card-img-top" 
                             alt="<?= $row['po_name'] ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= $row['po_name'] ?></h5>
                            <p class="card-text">
                                <span class="badge bg-info"><?= $row['type_name'] ?></span>
                            </p>
                            <p class="card-text">
                                ราคา: <span class="text-primary fw-bold">
                                    <?= number_format($row['price'], 2) ?> บาท
                                </span>
                            </p>
                            <p class="card-text">
                                สถานะ: 
                                <?php if($row['amount'] > 0) { ?>
                                    <span class="text-success">มีสินค้า <?= $row['amount'] ?> ชิ้น</span>
                                <?php } else { ?>
                                    <span class="text-danger">สินค้าหมด</span>
                                <?php } ?>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <?php if($row['amount'] > 0) { ?>
                                <a href="?id=<?= $row['po_id'] ?>" 
                                   class="btn btn-primary w-100">
                                    <i class="fas fa-cart-plus"></i> หยิบใส่ตะกร้า
                                </a>
                            <?php } else { ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-times"></i> สินค้าหมด
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

