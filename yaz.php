<?php
session_start();
include 'condb.php';

// ตรวจสอบการล็อกอินและสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

$title = "แดชบอร์ดผู้ดูแลระบบ";
include 'header.php';

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
?>


<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-primary mb-3">
            <i class="fas fa-tachometer-alt me-3"></i>แดชบอร์ดผู้ดูแลระบบ
        </h1>
        <p class="lead text-muted">จัดการและติดตามสถานะสินค้าในระบบ</p>
    </div>

    <!-- ฟอร์มค้นหา -->
    <div class="card shadow-lg border-0 mb-5">
        <div class="card-header bg-gradient-primary text-white py-4">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="bg-white bg-opacity-20 rounded-circle p-3">
                        <i class="fas fa-search fa-2x"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h4 class="mb-1">ค้นหาและกรองสินค้า</h4>
                    <p class="mb-0 opacity-75">ค้นหาสินค้าตามประเภทและสถานะสต็อก</p>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form method="GET" class="row g-4">
                <div class="col-md-4">
                    <div class="form-floating">
                        <select name="type_filter" class="form-select" id="typeFilter">
                            <option value="">ทุกประเภท</option>
                            <?php
                            $type_sql = "SELECT DISTINCT t.type_name FROM type t 
                                         INNER JOIN products p ON t.type_id = p.type_id 
                                         INNER JOIN product_sizes ps ON p.id = ps.product_base_id 
                                         WHERE ps.amount <= 5 OR ps.amount = 0
                                         ORDER BY t.type_name";
                            $type_result = mysqli_query($conn, $type_sql);
                            while ($type_row = mysqli_fetch_assoc($type_result)) {
                                $selected = (isset($_GET['type_filter']) && $_GET['type_filter'] == $type_row['type_name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($type_row['type_name']) . "' $selected>" . htmlspecialchars($type_row['type_name']) . "</option>";
                            }
                            ?>
                        </select>
                        <label for="typeFilter">
                            <i class="fas fa-tags me-2"></i>ประเภทสินค้า
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select name="stock_status" class="form-select" id="stockStatus">
                            <option value="">ทั้งหมด</option>
                            <option value="low" <?= (isset($_GET['stock_status']) && $_GET['stock_status'] == 'low') ? 'selected' : '' ?>>ใกล้หมด (≤ 5 ชิ้น)</option>
                            <option value="out" <?= (isset($_GET['stock_status']) && $_GET['stock_status'] == 'out') ? 'selected' : '' ?>>หมด (0 ชิ้น)</option>
                        </select>
                        <label for="stockStatus">
                            <i class="fas fa-boxes me-2"></i>สถานะสต็อก
                        </label>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="d-flex gap-3 w-100">
                        <button type="submit" class="btn btn-primary btn-lg flex-fill">
                            <i class="fas fa-search me-2"></i>ค้นหา
                        </button>
                        <a href="yaz.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-redo me-2"></i>รีเซ็ต
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>



    <div class="row">
        <!-- เนื้อหาหลัก -->
        <div class="col-lg-9">
            <!-- รายการสินค้าใกล้หมด -->
            <div class="card shadow-lg border-0 mb-5">
                <div class="card-header bg-gradient-warning text-dark py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-white bg-opacity-20 rounded-circle p-3 me-3">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                            <div>
                                <h4 class="mb-1">รายการสินค้าใกล้หมด</h4>
                                <p class="mb-0 opacity-75">สินค้าที่มีจำนวน ≤ 5 ชิ้น</p>
                            </div>
                        </div>
                        <?php if ($low_stock_count > 0): ?>
                            <span class="badge bg-light text-dark fs-6 px-3 py-2"><?= $low_stock_count ?> รายการ</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-warning">
                                <tr>
                                    <th class="text-center">
                                        <i class="fas fa-image me-2"></i>รูปภาพ
                                    </th>
                                    <th>
                                        <i class="fas fa-tags me-2"></i>ประเภท
                                    </th>
                                    <th>
                                        <i class="fas fa-tshirt me-2"></i>ไซส์และสีที่ใกล้หมด
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-cogs me-2"></i>จัดการ
                                    </th>
                                </tr>
                            </thead>
                    <tbody>
                        <?php
                        // สร้างเงื่อนไขการค้นหา
                        $where_conditions = ["ps.amount <= 5 AND ps.amount > 0"];
                        $params = [];
                        $types = "";
                        
                        // เงื่อนไขประเภทสินค้า
                        if (isset($_GET['type_filter']) && !empty($_GET['type_filter'])) {
                            $where_conditions[] = "t.type_name = ?";
                            $params[] = $_GET['type_filter'];
                            $types .= "s";
                        }
                        
                        // เงื่อนไขสถานะสต็อก
                        if (isset($_GET['stock_status']) && !empty($_GET['stock_status'])) {
                            if ($_GET['stock_status'] == 'low') {
                                $where_conditions[] = "ps.amount <= 5 AND ps.amount > 0";
                            } elseif ($_GET['stock_status'] == 'out') {
                                $where_conditions[] = "ps.amount = 0";
                            }
                        }
                        
                        $where_clause = implode(" AND ", $where_conditions);
                        
                        $sql = "SELECT p.id, p.name, p.image, t.type_name,
                                       GROUP_CONCAT(ps.size ORDER BY 
                                           CASE ps.size
                                               WHEN 'XS' THEN 1
                                               WHEN 'S' THEN 2
                                               WHEN 'M' THEN 3
                                               WHEN 'L' THEN 4
                                               WHEN 'XL' THEN 5
                                               WHEN 'XXL' THEN 6
                                               WHEN '3XL' THEN 7
                                               ELSE 8
                                           END SEPARATOR ', ') as low_stock_sizes,
                                       GROUP_CONCAT(CONCAT(ps.size, ':', ps.color, ':', ps.price, ':', ps.amount) ORDER BY 
                                           CASE ps.size
                                               WHEN 'XS' THEN 1
                                               WHEN 'S' THEN 2
                                               WHEN 'M' THEN 3
                                               WHEN 'L' THEN 4
                                               WHEN 'XL' THEN 5
                                               WHEN 'XXL' THEN 6
                                               WHEN '3XL' THEN 7
                                               ELSE 8
                                           END SEPARATOR '|') as size_details,
                                       MIN(ps.price) as min_price,
                                       MAX(ps.price) as max_price,
                                       SUM(ps.amount) as total_amount
                                FROM products p 
                                LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                                LEFT JOIN type t ON p.type_id = t.type_id 
                                WHERE $where_clause
                                GROUP BY p.id, p.name, p.image, t.type_name
                                ORDER BY total_amount ASC 
                                LIMIT 10";
                        
                        $stmt = $conn->prepare($sql);
                        if ($types && $params) {
                            $stmt->bind_param($types, ...$params);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $low_stock_count = 0;
                        while ($row = mysqli_fetch_assoc($result)) {
                            $low_stock_count++;
                        ?>
                            <tr>
                                <td>
                                    <?php
                                    // หารูปภาพสีแรกที่พบ
                                    $color_image_sql = "SELECT image FROM product_sizes WHERE product_base_id = ? AND image IS NOT NULL AND image != '' LIMIT 1";
                                    $color_image_stmt = $conn->prepare($color_image_sql);
                                    $color_image_stmt->bind_param("i", $row['id']);
                                    $color_image_stmt->execute();
                                    $color_image_result = $color_image_stmt->get_result();
                                    $color_image_row = $color_image_result->fetch_assoc();
                                    
                                    if ($color_image_row && !empty($color_image_row['image'])) {
                                        // ใช้รูปภาพสี
                                        $image_src = strpos($color_image_row['image'], 'http') === 0 ? 
                                            $color_image_row['image'] : 
                                            'img/' . $color_image_row['image'];
                                    } else {
                                        // ใช้รูปภาพหลัก
                                        $image_src = strpos($row['image'], 'http') === 0 ? 
                                            $row['image'] : 
                                            'img/' . $row['image'];
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($image_src) ?>" 
                                         alt="<?= htmlspecialchars($row['name']) ?>"
                                         class="img-thumbnail product-image"
                                         style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;"
                                         onclick="showImageModal('<?= htmlspecialchars($image_src) ?>', '<?= htmlspecialchars($row['name']) ?>')"
                                         title="คลิกเพื่อดูรูปขนาดใหญ่">
                                </td>
                                <td><?= htmlspecialchars($row['type_name']) ?></td>
                                <td>
                            <?php
                                    $size_details = explode('|', $row['size_details']);
                                    foreach ($size_details as $detail):
                                        list($size, $color, $price, $amount) = explode(':', $detail);
                                    ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-warning text-dark me-2">
                                                <?= $size ?> (<?= $amount ?> ชิ้น)
                                            </span>
                                            <span class="badge" style="background-color: <?= getColorCode($color) ?>; color: <?= getTextColor($color) ?>; font-size: 0.7rem; padding: 4px 8px; border-radius: 12px;">
                                                <?= htmlspecialchars($color) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                                <td class="text-center">
                                    <a href="edit_product.php?id=<?= $row['id'] ?>&return_to=yaz" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-1"></i>แก้ไข
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if ($low_stock_count == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                    ไม่มีสินค้าใกล้หมด
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- รายการสินค้าหมด -->
    <div class="card shadow-lg border-0 mb-5">
        <div class="card-header bg-gradient-danger text-white py-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 me-3">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-1">รายการสินค้าหมด</h4>
                        <p class="mb-0 opacity-75">สินค้าที่มีจำนวน 0 ชิ้น</p>
                    </div>
                </div>
                <?php if ($out_of_stock_count > 0): ?>
                    <span class="badge bg-light text-dark fs-6 px-3 py-2"><?= $out_of_stock_count ?> รายการ</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-danger">
                        <tr>
                            <th class="text-center">
                                <i class="fas fa-image me-2"></i>รูปภาพ
                            </th>
                            <th>
                                <i class="fas fa-tags me-2"></i>ประเภท
                            </th>
                            <th>
                                <i class="fas fa-tshirt me-2"></i>ไซส์และสีที่หมด
                            </th>
                            <th class="text-center">
                                <i class="fas fa-cogs me-2"></i>จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // สร้างเงื่อนไขการค้นหาสำหรับสินค้าหมด
                        $where_conditions_out = ["ps.amount = 0"];
                        $params_out = [];
                        $types_out = "";
                        
                        // เงื่อนไขประเภทสินค้า
                        if (isset($_GET['type_filter']) && !empty($_GET['type_filter'])) {
                            $where_conditions_out[] = "t.type_name = ?";
                            $params_out[] = $_GET['type_filter'];
                            $types_out .= "s";
                        }
                        
                        // เงื่อนไขสถานะสต็อก
                        if (isset($_GET['stock_status']) && !empty($_GET['stock_status'])) {
                            if ($_GET['stock_status'] == 'low') {
                                $where_conditions_out[] = "ps.amount <= 5 AND ps.amount > 0";
                            } elseif ($_GET['stock_status'] == 'out') {
                                $where_conditions_out[] = "ps.amount = 0";
                            }
                        }
                        
                        $where_clause_out = implode(" AND ", $where_conditions_out);
                        
                        $sql = "SELECT p.id, p.name, p.image, t.type_name,
                                       GROUP_CONCAT(ps.size ORDER BY 
                                           CASE ps.size
                                               WHEN 'XS' THEN 1
                                               WHEN 'S' THEN 2
                                               WHEN 'M' THEN 3
                                               WHEN 'L' THEN 4
                                               WHEN 'XL' THEN 5
                                               WHEN 'XXL' THEN 6
                                               WHEN '3XL' THEN 7
                                               ELSE 8
                                           END SEPARATOR ', ') as out_of_stock_sizes,
                                       GROUP_CONCAT(CONCAT(ps.size, ':', ps.color, ':', ps.price) ORDER BY 
                                           CASE ps.size
                                               WHEN 'XS' THEN 1
                                               WHEN 'S' THEN 2
                                               WHEN 'M' THEN 3
                                               WHEN 'L' THEN 4
                                               WHEN 'XL' THEN 5
                                               WHEN 'XXL' THEN 6
                                               WHEN '3XL' THEN 7
                                               ELSE 8
                                           END SEPARATOR '|') as size_details
                                FROM products p 
                                LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
                                LEFT JOIN type t ON p.type_id = t.type_id 
                                WHERE $where_clause_out
                                GROUP BY p.id, p.name, p.image, t.type_name
                                ORDER BY p.name ASC 
                                LIMIT 10";
                        
                        $stmt_out = $conn->prepare($sql);
                        if ($types_out && $params_out) {
                            $stmt_out->bind_param($types_out, ...$params_out);
                        }
                        $stmt_out->execute();
                        $result = $stmt_out->get_result();
                        $out_of_stock_count = 0;
                        while ($row = mysqli_fetch_assoc($result)) {
                            $out_of_stock_count++;
                        ?>
                            <tr>
                                <td>
                                    <?php
                                    // หารูปภาพสีแรกที่พบ
                                    $color_image_sql = "SELECT image FROM product_sizes WHERE product_base_id = ? AND image IS NOT NULL AND image != '' LIMIT 1";
                                    $color_image_stmt = $conn->prepare($color_image_sql);
                                    $color_image_stmt->bind_param("i", $row['id']);
                                    $color_image_stmt->execute();
                                    $color_image_result = $color_image_stmt->get_result();
                                    $color_image_row = $color_image_result->fetch_assoc();
                                    
                                    if ($color_image_row && !empty($color_image_row['image'])) {
                                        // ใช้รูปภาพสี
                                        $image_src = strpos($color_image_row['image'], 'http') === 0 ? 
                                            $color_image_row['image'] : 
                                            'img/' . $color_image_row['image'];
                                    } else {
                                        // ใช้รูปภาพหลัก
                                        $image_src = strpos($row['image'], 'http') === 0 ? 
                                            $row['image'] : 
                                            'img/' . $row['image'];
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($image_src) ?>" 
                                         alt="<?= htmlspecialchars($row['name']) ?>"
                                         class="img-thumbnail product-image"
                                         style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;"
                                         onclick="showImageModal('<?= htmlspecialchars($image_src) ?>', '<?= htmlspecialchars($row['name']) ?>')"
                                         title="คลิกเพื่อดูรูปขนาดใหญ่">
                                </td>
                                <td><?= htmlspecialchars($row['type_name']) ?></td>
                                <td>
                                    <?php 
                                    $size_details = explode('|', $row['size_details']);
                                    foreach ($size_details as $detail):
                                        list($size, $color, $price) = explode(':', $detail);
                                    ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-danger me-2">
                                                <?= $size ?>
                                            </span>
                                            <span class="badge" style="background-color: <?= getColorCode($color) ?>; color: <?= getTextColor($color) ?>; font-size: 0.7rem; padding: 4px 8px; border-radius: 12px;">
                                                <?= htmlspecialchars($color) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </td>

                                <td class="text-center">
                                    <a href="edit_product.php?id=<?= $row['id'] ?>&return_to=yaz" 
                                       class="btn btn-danger btn-sm">
                                        <i class="fas fa-plus me-1"></i>เพิ่มสต็อก
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if ($out_of_stock_count == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                    ไม่มีสินค้าหมด
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

        </div>

    <!-- Sidebar -->
    <div class="col-lg-3">
        <!-- สถิติภาพรวม -->
        <div class="card shadow-lg border-0 mb-4">
            <div class="card-header bg-gradient-primary text-white py-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3">
                            <i class="fas fa-chart-bar fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-1">สถิติภาพรวม</h4>
                        <p class="mb-0 opacity-75">ข้อมูลสรุปของระบบ</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background: linear-gradient(135deg, #e3f2fd 0%, #f8f9ff 100%);">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                <i class="fas fa-box text-primary"></i>
                            </div>
                            <div>
                                <div class="text-muted small">จำนวนสินค้าทั้งหมด</div>
                                <div class="fw-bold text-primary fs-5">
                                    <?php
                                    $sql = "SELECT COUNT(DISTINCT p.id) as total FROM products p";
                                    $result = mysqli_query($conn, $sql);
                                    $row = mysqli_fetch_assoc($result);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>
                        <span class="text-muted small">รายการ</span>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 100%);">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                <i class="fas fa-users text-success"></i>
                            </div>
                            <div>
                                <div class="text-muted small">จำนวนผู้ใช้งาน</div>
                                <div class="fw-bold text-success fs-5">
                                    <?php
                                    $sql = "SELECT COUNT(*) as total FROM users WHERE status = 2";
                                    $result = mysqli_query($conn, $sql);
                                    $row = mysqli_fetch_assoc($result);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>
                        <span class="text-muted small">คน</span>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background: linear-gradient(135deg, #fff3cd 0%, #fef9e7 100%);">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                            </div>
                            <div>
                                <div class="text-muted small">สินค้าใกล้หมด</div>
                                <div class="fw-bold text-warning fs-5">
                                    <?php
                                    $sql = "SELECT COUNT(*) as total FROM product_sizes WHERE amount <= 5 AND amount > 0";
                                    $result = mysqli_query($conn, $sql);
                                    $row = mysqli_fetch_assoc($result);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>
                        <span class="text-muted small">รายการ</span>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background: linear-gradient(135deg, #f8d7da 0%, #fdf2f2 100%);">
                        <div class="d-flex align-items-center">
                            <div class="bg-danger bg-opacity-10 rounded-circle p-2 me-3">
                                <i class="fas fa-times-circle text-danger"></i>
                            </div>
                            <div>
                                <div class="text-muted small">สินค้าหมด</div>
                                <div class="fw-bold text-danger fs-5">
                                    <?php
                                    $sql = "SELECT COUNT(*) as total FROM product_sizes WHERE amount = 0";
                                    $result = mysqli_query($conn, $sql);
                                    $row = mysqli_fetch_assoc($result);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>
                        <span class="text-muted small">รายการ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับแสดงรูปภาพขนาดใหญ่ -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">รูปภาพสินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="" class="img-fluid rounded" style="max-height: 500px; object-fit: contain;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
function showImageModal(imageSrc, productName) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('modalImage').alt = productName;
    document.getElementById('imageModalLabel').textContent = productName;
    
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}

// เพิ่ม hover effect สำหรับรูปภาพ
document.addEventListener('DOMContentLoaded', function() {
    const productImages = document.querySelectorAll('.product-image');
    productImages.forEach(img => {
        img.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        img.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});
</script>

<style>
/* Background and Layout */
body {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

/* Header Styles */
.display-4 {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Card Styles */
.card {
    border-radius: 20px;
    overflow: hidden;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.95);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.card-header {
    border-radius: 20px 20px 0 0 !important;
    border: none;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

/* Form Floating */
.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label {
    color: #0d6efd;
}

.form-floating > .form-select ~ label {
    color: #0d6efd;
}

/* Button Styles */
.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
}

.btn-outline-secondary {
    border: 2px solid #6c757d;
    color: #6c757d;
    background: transparent;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    border-color: #6c757d;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

/* Table Styles */
.table-hover tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    padding: 1rem 0.75rem;
}

.table td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

/* Badge Styles */
.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 12px;
    font-weight: 600;
}

/* Color Badge Styles */
.color-badge {
    font-size: 0.7rem;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.color-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Product Image Styles */
.product-image:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    border-color: #0d6efd;
    transform: scale(1.05);
    transition: all 0.3s ease;
}

/* Modal Styles */
.modal-body img {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Statistics Cards */
.stat-card {
    transition: all 0.3s ease;
    border-radius: 15px;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

/* Animations */
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

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }
.card:nth-child(3) { animation-delay: 0.3s; }

/* Loading States */
.btn-primary:active {
    animation: pulse 0.3s ease-in-out;
}

/* Focus States */
.form-control:focus,
.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Responsive Improvements */
@media (max-width: 768px) {
    .display-4 {
        font-size: 2.5rem;
    }
    
    .btn-lg {
        padding: 0.6rem 1.5rem;
        font-size: 1rem;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
}
</style>

<?php include 'footer.php'; ?>