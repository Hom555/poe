<?php
session_start();
include 'condb.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// คำนวณจำนวนสินค้าในตะกร้า (เหมือนใน sh_product.php)
$cart_count = isset($_SESSION["strProductID"]) ? count($_SESSION["strProductID"]) : 0;

// คำนวณราคารวมในตะกร้า (เหมือนใน sh_product.php)
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
    <title>ติดต่อเรา - Yaz Shop</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .contact-icon {
            font-size: 2rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .contact-card {
            transition: transform 0.2s;
        }
        .contact-card:hover {
            transform: translateY(-5px);
        }
        .map-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
        }
        .map-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
    </style>
</head>
<body>
    <!-- Navbar (เหมือนใน sh_product.php) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">Yaz Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="sh_product.php">สินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">ติดต่อเรา</a>
                    </li>
                </ul>
                
                <!-- Cart Dropdown (เหมือนใน sh_product.php) -->
                <div class="d-flex align-items-center gap-3">
                    <!-- ... โค้ดตะกร้าสินค้า ... -->
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="text-center mb-4">ติดต่อเรา</h2>
        
        <!-- ข้อมูลการติดต่อ -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card h-100 contact-card">
                    <div class="card-body text-center">
                        <i class="fas fa-map-marker-alt contact-icon"></i>
                        <h5 class="card-title">ที่อยู่</h5>
                        <p class="card-text">
                        ห้วยหมากแดง  <br>
                        ตำบลท่าหินโงม อำเภอเมืองชัยภูมิ<br>
                            ชัยภูมิ 36000
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 contact-card">
                    <div class="card-body text-center">
                        <i class="fas fa-phone contact-icon"></i>
                        <h5 class="card-title">เบอร์โทรศัพท์</h5>
                        <p class="card-text">
                            <a href="tel:0987654321" class="text-decoration-none">09x-xxx-xxxx</a><br>
                            <small class="text-muted">จันทร์ - อาทิตย์ 9:00 - 18:00 น.</small>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 contact-card">
                    <div class="card-body text-center">
                        <i class="fas fa-envelope contact-icon"></i>
                        <h5 class="card-title">อีเมล</h5>
                        <p class="card-text">
                            <a href="mailto:contact@yazshop.com" class="text-decoration-none">
                                contact@yazshop.com
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media -->
        <div class="text-center mb-5">
            <h4 class="mb-3">ติดตามเราได้ที่</h4>
            <div class="d-flex justify-content-center gap-3">
                <a href="#" class="btn btn-outline-primary btn-lg rounded-circle">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="btn btn-outline-info btn-lg rounded-circle">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="btn btn-outline-danger btn-lg rounded-circle">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="btn btn-outline-success btn-lg rounded-circle">
                    <i class="fab fa-line"></i>
                </a>
            </div>
        </div>

        <!-- แผนที่ -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">แผนที่ร้าน</h4>
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3827.8647989750784!2d102.02079871478567!3d16.027049088895325!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31183d47bc39a3c5%3A0x7f3eea67ac2bacb0!2z4Lih4Lir4Liy4Lin4Li04LiX4Lii4Liy4Lil4Lix4Lii4LiC4Lit4LiZ4LmB4LiB4LmI4LiZ!5e0!3m2!1sth!2sth!4v1677123456789"
                        width="100%"
                        height="450"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?> 