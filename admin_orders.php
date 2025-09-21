<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'condb.php';
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบว่าเป็น admin หรือไม่
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    echo "<script>
        alert('กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบ');
        window.location.href = 'login.php';
    </script>";
    exit();
}

// อัพเดทสถานะคำสั่งซื้อ
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
}

$title = "จัดการคำสั่งซื้อ";
include 'header.php';

// เพิ่มการจัดการ filter
$where_clause = "1=1"; // เริ่มต้นด้วยเงื่อนไขที่เป็นจริงเสมอ
$params = array();
$types = "";

// ค้นหาตามคำค้น
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $where_clause .= " AND (o.order_id LIKE ? OR u.name LIKE ? OR u.phone LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

// กรองตามสถานะ
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_clause .= " AND o.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// แก้ไข SQL query ให้ดึงข้อมูลลูกค้าจากตาราง users และเพิ่มข้อมูลสินค้า
$sql = "SELECT o.*, u.name, u.phone, u.address, u.province, u.district, u.subdistrict, u.zipcode,
        COUNT(od.id) as total_items,
        (SELECT GROUP_CONCAT(CONCAT(p.name, ' (', od2.quantity, ')'))
         FROM order_details od2 
         JOIN products p ON od2.product_id = p.id 
         WHERE od2.order_id = o.order_id) as product_list,
        (SELECT GROUP_CONCAT(CONCAT(COALESCE(od2.size, 'ไม่ระบุ'), ':', COALESCE(od2.color, 'ไม่ระบุ'), ':', od2.quantity) SEPARATOR ', ')
         FROM order_details od2 
         JOIN products p ON od2.product_id = p.id 
         WHERE od2.order_id = o.order_id) as size_details,
        (SELECT GROUP_CONCAT(
            CASE 
                WHEN od2.color IS NOT NULL AND od2.color != '' AND ps.image IS NOT NULL AND ps.image != '' 
                THEN ps.image
                WHEN p.image IS NOT NULL AND p.image != '' 
                THEN p.image
                ELSE 'no-image.svg'
            END SEPARATOR ', ')
         FROM order_details od2 
         JOIN products p ON od2.product_id = p.id 
         LEFT JOIN product_sizes ps ON p.id = ps.product_base_id 
            AND od2.size = ps.size 
            AND od2.color = ps.color
         WHERE od2.order_id = o.order_id) as product_images
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        LEFT JOIN order_details od ON o.order_id = od.order_id 
        WHERE $where_clause
        GROUP BY o.order_id 
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
if ($types && $params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>คำสั่งซื้อทั้งหมด
                    </h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="ค้นหาด้วยเลขที่สั่งซื้อ, ชื่อ หรือเบอร์โทร"
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i> ค้นหา
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">- ทุกสถานะ -</option>
                                <option value="รอการชำระเงิน" <?= ($_GET['status'] ?? '') == 'รอการชำระเงิน' ? 'selected' : '' ?>>
                                    รอการชำระเงิน
                                </option>
                                <option value="รอตรวจสอบการชำระเงิน" <?= ($_GET['status'] ?? '') == 'รอตรวจสอบการชำระเงิน' ? 'selected' : '' ?>>
                                    รอตรวจสอบการชำระเงิน
                                </option>
                                <option value="ชำระเงินแล้ว" <?= ($_GET['status'] ?? '') == 'ชำระเงินแล้ว' ? 'selected' : '' ?>>
                                    ชำระเงินแล้ว
                                </option>
                                <option value="กำลังจัดส่ง" <?= ($_GET['status'] ?? '') == 'กำลังจัดส่ง' ? 'selected' : '' ?>>
                                    กำลังจัดส่ง
                                </option>
                                <option value="จัดส่งแล้ว" <?= ($_GET['status'] ?? '') == 'จัดส่งแล้ว' ? 'selected' : '' ?>>
                                    จัดส่งแล้ว
                                </option>
                                <option value="ยกเลิก" <?= ($_GET['status'] ?? '') == 'ยกเลิก' ? 'selected' : '' ?>>
                                    ยกเลิก
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="admin_orders.php" class="btn btn-secondary w-100">
                                <i class="fas fa-redo"></i> รีเซ็ต
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>วันที่สั่งซื้อ</th>
                                    <th>ลูกค้า</th>
                                    <th>ที่อยู่จัดส่ง</th>
                                    <th>รายการสินค้า</th>
                                    <th class="text-end">ยอดรวม</th>
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= formatThaiDate($row['order_date']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($row['name'] ?? 'ไม่ระบุ') ?><br>
                                            <small class="text-muted"><?= $row['phone'] ?? 'ไม่ระบุ' ?></small>
                                        </td>
                                        <td>
                                            <small>
                                                <?= htmlspecialchars($row['address'] ?? 'ไม่ระบุ') ?><br>
                                                <?= htmlspecialchars($row['subdistrict'] ?? '') ?>
                                                <?= htmlspecialchars($row['district'] ?? '') ?><br>
                                                <?= htmlspecialchars($row['province'] ?? '') ?>
                                                <?= htmlspecialchars($row['zipcode'] ?? '') ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($row['product_list'] ?? 'ไม่ระบุ') ?></small>
                                        </td>
                                        <td class="text-end">
                                            ฿<?= number_format($row['total_amount'], 2) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= getStatusColor($row['status']) ?>">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center manage-column">
                                            <div class="d-flex flex-column gap-2 align-items-center">
                                                <!-- ปุ่มรวมรูปภาพและไซส์ -->
                                                <?php 
                                                $has_images = !empty($row['product_images']);
                                                $has_sizes = !empty($row['size_details']);
                                                
                                                if ($has_images || $has_sizes) {
                                                    $images = $has_images ? explode(',', $row['product_images']) : [];
                                                    $image_count = count($images);
                                                    
                                                    $valid_sizes = [];
                                                    if ($has_sizes) {
                                                        $size_details = explode(',', $row['size_details']);
                                                        foreach ($size_details as $size_detail) {
                                                            $size_parts = explode(':', trim($size_detail));
                                                            if (count($size_parts) == 3) {
                                                                $size = $size_parts[0];
                                                                $color = $size_parts[1];
                                                                $qty = $size_parts[2];
                                                                if (!empty($size) && $size != 'NULL') {
                                                                    $valid_sizes[] = ['size' => $size, 'color' => $color, 'qty' => $qty];
                                                                }
                                                            }
                                                        }
                                                    }
                                                    $size_count = count($valid_sizes);
                                                    
                                                    // สร้างข้อความสำหรับปุ่ม
                                                    $button_text = '';
                                                    $button_icon = '';
                                                    
                                                    if ($has_images && $has_sizes) {
                                                        $button_text = "รูป ({$image_count}) + ไซส์/สี ({$size_count})";
                                                        $button_icon = 'fas fa-images';
                                                    } elseif ($has_images) {
                                                        $button_text = "รูป ({$image_count})";
                                                        $button_icon = 'fas fa-images';
                                                    } elseif ($has_sizes) {
                                                        $button_text = "ไซส์/สี ({$size_count})";
                                                        $button_icon = 'fas fa-list';
                                                    }
                                                ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary"
                                                            onclick="showProductDetailsModal(<?= $row['order_id'] ?>, '<?= htmlspecialchars($row['product_list'] ?? 'สินค้า') ?>')"
                                                            title="ดูรูปภาพและไซส์สินค้า">
                                                        <i class="<?= $button_icon ?> me-1"></i><?= $button_text ?>
                                                    </button>
                                                <?php 
                                                } else { 
                                                ?>
                                                    <div class="d-flex flex-column align-items-center">
                                                        <div class="bg-light d-flex align-items-center justify-content-center mb-1"
                                                             style="width: 40px; height: 40px; border-radius: 8px;">
                                                            <i class="fas fa-box text-muted"></i>
                                                        </div>
                                                        <small class="text-muted">ไม่มีข้อมูล</small>
                                                    </div>
                                                <?php } ?>
                                                
                                                <!-- ปุ่มจัดการ -->
                                            <div class="btn-group">
                                                <a href="order_detail.php?order_id=<?= $row['order_id'] ?>" 
                                                       class="btn btn-sm btn-info"
                                                       title="ดูรายละเอียด">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="print_order.php?order_id=<?= $row['order_id'] ?>" 
                                                   class="btn btn-sm btn-success"
                                                       target="_blank"
                                                       title="พิมพ์">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Modal แสดงรายละเอียด -->
                                    <div class="modal fade" id="orderModal<?= $row['order_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        รายละเอียดคำสั่งซื้อ
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- หลักฐานการโอนเงิน -->
                                                    <?php if (!empty($row['payment_slip'])): ?>
                                                        <div class="text-center mb-3">
                                                            <img src="slips/<?= htmlspecialchars($row['payment_slip']) ?>" 
                                                                 alt="สลิปการโอนเงิน" 
                                                                 class="img-fluid" 
                                                                 style="max-height: 300px;">
                                                            <p class="text-muted mt-2">
                                                                วันที่โอน: <?= formatThaiDate($row['payment_date']) ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- อัพเดทสถานะ -->
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                                        <div class="row align-items-end">
                                                            <div class="col-md-8">
                                                                <label class="form-label">สถานะคำสั่งซื้อ</label>
                                                                <select name="status" class="form-select">
                                                                    <option value="รอการชำระเงิน" <?= $row['status'] == 'รอการชำระเงิน' ? 'selected' : '' ?>>
                                                                        รอการชำระเงิน
                                                                    </option>
                                                                    <option value="รอตรวจสอบการชำระเงิน" <?= $row['status'] == 'รอตรวจสอบการชำระเงิน' ? 'selected' : '' ?>>
                                                                        รอตรวจสอบการชำระเงิน
                                                                    </option>
                                                                    <option value="ชำระเงินแล้ว" <?= $row['status'] == 'ชำระเงินแล้ว' ? 'selected' : '' ?>>
                                                                        ชำระเงินแล้ว
                                                                    </option>
                                                                    <option value="กำลังจัดส่ง" <?= $row['status'] == 'กำลังจัดส่ง' ? 'selected' : '' ?>>
                                                                        กำลังจัดส่ง
                                                                    </option>
                                                                    <option value="จัดส่งแล้ว" <?= $row['status'] == 'จัดส่งแล้ว' ? 'selected' : '' ?>>
                                                                        จัดส่งแล้ว
                                                                    </option>
                                                                    <option value="ยกเลิก" <?= $row['status'] == 'ยกเลิก' ? 'selected' : '' ?>>
                                                                        ยกเลิก
                                                                    </option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <button type="submit" 
                                                                        name="update_status" 
                                                                        class="btn btn-primary w-100">
                                                                    <i class="fas fa-save me-1"></i>
                                                                    บันทึก
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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

<!-- Modal สำหรับแสดงรูปภาพสินค้าหลายรูป -->
<div class="modal fade" id="productImagesModal" tabindex="-1" aria-labelledby="productImagesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productImagesModalLabel">รูปภาพสินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="productImagesContainer" class="row g-3">
                    <!-- รูปภาพจะถูกโหลดด้วย JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับแสดงข้อมูลไซส์ -->
<div class="modal fade" id="sizesModal" tabindex="-1" aria-labelledby="sizesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sizesModalLabel">ข้อมูลไซส์สินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="sizesContainer">
                    <!-- ข้อมูลไซส์จะถูกโหลดด้วย JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับแสดงรูปภาพและไซส์รวมกัน -->
<div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productDetailsModalLabel">รูปภาพและไซส์สินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- รูปภาพสินค้า -->
                    <div class="col-md-6">
                        <h6 class="mb-3">
                            <i class="fas fa-images me-2 text-primary"></i>รูปภาพสินค้า
                        </h6>
                        <div id="productDetailsImagesContainer" class="row g-3">
                            <!-- รูปภาพจะถูกโหลดด้วย JavaScript -->
                        </div>
                    </div>
                    
                    <!-- ข้อมูลไซส์ -->
                    <div class="col-md-6">
                        <h6 class="mb-3">
                            <i class="fas fa-list me-2 text-info"></i>ข้อมูลไซส์สินค้า
                        </h6>
                        <div id="productDetailsSizesContainer">
                            <!-- ข้อมูลไซส์จะถูกโหลดด้วย JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<style>
.product-image {
    transition: all 0.3s ease;
}

.product-image:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    border-color: #0d6efd;
}

.modal-body img {
    max-width: 100%;
    height: auto;
}

/* เพิ่มสไตล์สำหรับปุ่มและ badge */
.btn-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
}

/* สไตล์สำหรับ Modal */
.modal-xl {
    max-width: 1200px;
}

.card-img-top {
    transition: transform 0.2s ease;
}

.card-img-top:hover {
    transform: scale(1.05);
}

/* สไตล์สำหรับตารางใน Modal */
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: none;
}

/* สไตล์สำหรับคอลัมน์จัดการ */
.manage-column {
    min-width: 200px;
}

.manage-column .d-flex {
    gap: 0.5rem;
}

.manage-column .product-image {
    border-radius: 6px;
    transition: all 0.2s ease;
}

.manage-column .product-image:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.manage-column .btn-sm {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    min-width: 60px;
}

.manage-column .badge {
    font-size: 0.65rem;
    padding: 0.2rem 0.4rem;
    white-space: nowrap;
}

.manage-column small {
    font-size: 0.65rem;
    color: #6c757d;
}

/* สไตล์สำหรับ responsive */
@media (max-width: 768px) {
    .btn-sm {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    .modal-xl {
        max-width: 95%;
    }
    
    .manage-column {
        min-width: 150px;
    }
    
    .manage-column .btn-sm {
        font-size: 0.6rem;
        padding: 0.2rem 0.3rem;
        min-width: 50px;
    }
    
    .manage-column .product-image {
        width: 30px !important;
        height: 30px !important;
    }
}

/* Color Badge Styles */
.badge {
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 600;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.badge:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
</style>

<script>
function showImageModal(imageSrc, productName) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('modalImage').alt = productName;
    document.getElementById('imageModalLabel').textContent = productName;
    
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}

function showProductImagesModal(orderId, productName) {
    // ตั้งค่า title
    document.getElementById('productImagesModalLabel').textContent = 'รูปภาพสินค้า - ' + productName;
    
    // ดึงข้อมูลรูปภาพจากตาราง
    fetch(`get_order_images.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('productImagesContainer');
            container.innerHTML = '';
            
            if (data.success && data.images.length > 0) {
                data.images.forEach((image, index) => {
                    const imageSrc = image.image.startsWith('http') ? image.image : 'img/' + image.image;
                    const colDiv = document.createElement('div');
                    colDiv.className = 'col-md-4 col-sm-6';
                    colDiv.innerHTML = `
                        <div class="card h-100">
                            <img src="${imageSrc}" 
                                 class="card-img-top" 
                                 alt="${image.name}"
                                 style="height: 200px; object-fit: cover; cursor: pointer;"
                                 onclick="showImageModal('${imageSrc}', '${image.name}')"
                                 title="คลิกเพื่อดูรูปขนาดใหญ่">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1">${image.name}</h6>
                                <small class="text-muted">
                                    ${image.size || 'ไม่ระบุไซส์'} 
                                    ${image.color ? ' - ' + image.color : ''}
                                </small>
                            </div>
                        </div>
                    `;
                    container.appendChild(colDiv);
                });
            } else {
                container.innerHTML = '<div class="col-12 text-center text-muted"><i class="fas fa-image fa-3x mb-3"></i><p>ไม่พบรูปภาพสินค้า</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading images:', error);
            document.getElementById('productImagesContainer').innerHTML = '<div class="col-12 text-center text-danger"><p>เกิดข้อผิดพลาดในการโหลดรูปภาพ</p></div>';
        });
    
    const modal = new bootstrap.Modal(document.getElementById('productImagesModal'));
    modal.show();
}

function showSizesModal(orderId, productName) {
    // ตั้งค่า title
    document.getElementById('sizesModalLabel').textContent = 'ข้อมูลไซส์สินค้า - ' + productName;
    
    // ดึงข้อมูลไซส์จากตาราง
    fetch(`get_order_sizes.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('sizesContainer');
            container.innerHTML = '';
            
            if (data.success && data.sizes.length > 0) {
                const table = document.createElement('table');
                table.className = 'table table-striped';
                table.innerHTML = `
                    <thead class="table-dark">
                        <tr>
                            <th>สินค้า</th>
                            <th>ไซส์</th>
                            <th>สี</th>
                            <th class="text-center">จำนวน</th>
                            <th class="text-end">ราคา/ชิ้น</th>
                            <th class="text-end">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.sizes.map(item => `
                            <tr>
                                <td>${item.name}</td>
                                <td><span class="badge bg-info">${item.size || 'ไม่ระบุ'}</span></td>
                                <td><span class="badge" style="background-color: ${getColorCode(item.color)}; color: ${getTextColor(item.color)};">${item.color || 'ไม่ระบุ'}</span></td>
                                <td class="text-center">${item.quantity}</td>
                                <td class="text-end">฿${parseFloat(item.price).toLocaleString()}</td>
                                <td class="text-end fw-bold">฿${parseFloat(item.total).toLocaleString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                `;
                container.appendChild(table);
            } else {
                container.innerHTML = '<div class="text-center text-muted"><i class="fas fa-list fa-3x mb-3"></i><p>ไม่พบข้อมูลไซส์สินค้า</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading sizes:', error);
            document.getElementById('sizesContainer').innerHTML = '<div class="text-center text-danger"><p>เกิดข้อผิดพลาดในการโหลดข้อมูลไซส์</p></div>';
        });
    
    const modal = new bootstrap.Modal(document.getElementById('sizesModal'));
    modal.show();
}

function showProductDetailsModal(orderId, productName) {
    // ตั้งค่า title
    document.getElementById('productDetailsModalLabel').textContent = 'รูปภาพและไซส์สินค้า - ' + productName;
    
    // โหลดรูปภาพ
    fetch(`get_order_images.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            const imagesContainer = document.getElementById('productDetailsImagesContainer');
            imagesContainer.innerHTML = '';
            
            if (data.success && data.images.length > 0) {
                data.images.forEach((image, index) => {
                    const imageSrc = image.image.startsWith('http') ? image.image : 'img/' + image.image;
                    const colDiv = document.createElement('div');
                    colDiv.className = 'col-md-6 col-sm-12';
                    colDiv.innerHTML = `
                        <div class="card h-100">
                            <img src="${imageSrc}" 
                                 class="card-img-top" 
                                 alt="${image.name}"
                                 style="height: 200px; object-fit: cover; cursor: pointer;"
                                 onclick="showImageModal('${imageSrc}', '${image.name}')"
                                 title="คลิกเพื่อดูรูปขนาดใหญ่">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1">${image.name}</h6>
                                <small class="text-muted">
                                    ${image.size || 'ไม่ระบุไซส์'} 
                                    ${image.color ? ' - ' + image.color : ''}
                                </small>
                            </div>
                        </div>
                    `;
                    imagesContainer.appendChild(colDiv);
                });
            } else {
                imagesContainer.innerHTML = '<div class="col-12 text-center text-muted"><i class="fas fa-image fa-3x mb-3"></i><p>ไม่พบรูปภาพสินค้า</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading images:', error);
            document.getElementById('productDetailsImagesContainer').innerHTML = '<div class="col-12 text-center text-danger"><p>เกิดข้อผิดพลาดในการโหลดรูปภาพ</p></div>';
        });
    
    // โหลดข้อมูลไซส์
    fetch(`get_order_sizes.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            const sizesContainer = document.getElementById('productDetailsSizesContainer');
            sizesContainer.innerHTML = '';
            
            if (data.success && data.sizes.length > 0) {
                const table = document.createElement('table');
                table.className = 'table table-striped';
                table.innerHTML = `
                    <thead class="table-dark">
                        <tr>
                            <th>สินค้า</th>
                            <th>ไซส์</th>
                            <th>สี</th>
                            <th class="text-center">จำนวน</th>
                            <th class="text-end">ราคา/ชิ้น</th>
                            <th class="text-end">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.sizes.map(item => `
                            <tr>
                                <td>${item.name}</td>
                                <td><span class="badge bg-info">${item.size || 'ไม่ระบุ'}</span></td>
                                <td><span class="badge" style="background-color: ${getColorCode(item.color)}; color: ${getTextColor(item.color)};">${item.color || 'ไม่ระบุ'}</span></td>
                                <td class="text-center">${item.quantity}</td>
                                <td class="text-end">฿${parseFloat(item.price).toLocaleString()}</td>
                                <td class="text-end fw-bold">฿${parseFloat(item.total).toLocaleString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                `;
                sizesContainer.appendChild(table);
            } else {
                sizesContainer.innerHTML = '<div class="text-center text-muted"><i class="fas fa-list fa-3x mb-3"></i><p>ไม่พบข้อมูลไซส์สินค้า</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading sizes:', error);
            document.getElementById('productDetailsSizesContainer').innerHTML = '<div class="text-center text-danger"><p>เกิดข้อผิดพลาดในการโหลดข้อมูลไซส์</p></div>';
        });
    
    const modal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
    modal.show();
}

// ฟังก์ชันแปลงชื่อสีเป็นรหัสสี
function getColorCode(colorName) {
    const colors = {
        'ขาว': '#FFFFFF',
        'ดำ': '#000000',
        'แดง': '#FF0000',
        'น้ำเงิน': '#0000FF',
        'เขียว': '#008000',
        'เหลือง': '#FFFF00',
        'ส้ม': '#FFA500',
        'ม่วง': '#800080',
        'ชมพู': '#FFC0CB',
        'เทา': '#808080',
        'น้ำตาล': '#A52A2A',
        'ครีม': '#F5F5DC',
        'เบจ': '#F5F5DC',
        'ฟ้า': '#87CEEB',
        'เขียวอ่อน': '#90EE90',
        'อื่นๆ': '#E0E0E0'
    };
    return colors[colorName] || '#E0E0E0';
}

// ฟังก์ชันกำหนดสีข้อความให้เหมาะสมกับสีพื้นหลัง
function getTextColor(colorName) {
    const lightColors = ['ขาว', 'เหลือง', 'ครีม', 'เบจ', 'ฟ้า', 'เขียวอ่อน', 'ชมพู'];
    return lightColors.includes(colorName) ? '#000000' : '#FFFFFF';
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

<?php
// ฟังก์ชันกำหนดสีตามสถานะ
function getStatusColor($status) {
    switch ($status) {
        case 'รอการชำระเงิน':
            return 'warning';
        case 'รอตรวจสอบการชำระเงิน':
            return 'warning';
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

// ฟังก์ชันแปลงวันที่เป็น พ.ศ.
function formatThaiDate($date) {
    $timestamp = strtotime($date);
    $year = date('Y', $timestamp) + 543;
    return date('d/m/', $timestamp) . $year . date(' H:i', $timestamp);
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

// ปิดการเชื่อมต่อก่อน include footer
if (isset($conn)) {
    mysqli_close($conn);
}

include 'footer.php';
?> 