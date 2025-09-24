<?php 
$title = "รายการสินค้า";
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

// เพิ่มการจัดการค้นหา
$where_clause = "1=1";
$params = array();
$types = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $where_clause .= " AND (p.name LIKE ? OR t.type_name LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

// แก้ไข SQL query เพื่อดึงข้อมูลสินค้าแบบรวม (รวมสีด้วย)
$sql = "SELECT p.*, t.type_name,
               GROUP_CONCAT(DISTINCT ps.size ORDER BY 
                   CASE ps.size
                       WHEN 'XS' THEN 1
                       WHEN 'S' THEN 2
                       WHEN 'M' THEN 3
                       WHEN 'L' THEN 4
                       WHEN 'XL' THEN 5
                       WHEN 'XXL' THEN 6
                       WHEN '3XL' THEN 7
                       ELSE 8
                   END SEPARATOR ', ') as sizes,
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
                   END, ps.color SEPARATOR '|') as size_details,
               GROUP_CONCAT(DISTINCT ps.color ORDER BY ps.color SEPARATOR ', ') as colors,
               MIN(ps.price) as min_price,
               MAX(ps.price) as max_price,
               SUM(ps.amount) as total_amount
        FROM products p 
        LEFT JOIN type t ON p.type_id = t.type_id 
        LEFT JOIN product_sizes ps ON p.id = ps.product_base_id
        WHERE $where_clause 
        GROUP BY p.id, p.name, p.description, p.type_id, p.image, t.type_name
        ORDER BY p.name";

$stmt = $conn->prepare($sql);
if ($types && $params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// จัดกลุ่มสินค้าตามประเภท
$grouped_products = array();
while($row = $result->fetch_assoc()) {
    $type_name = $row['type_name'] ?? 'ไม่ระบุ';
    
    if (!isset($grouped_products[$type_name])) {
        $grouped_products[$type_name] = array();
    }
    
    $grouped_products[$type_name][] = $row;
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-box me-2"></i>จัดการสินค้า
                        </h4>
                        <a href="add_product.php" class="btn btn-light">
                            <i class="fas fa-plus"></i> เพิ่มสินค้า
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- เพิ่มฟอร์มค้นหาแบบอัตโนมัติ -->
                    <form method="GET" class="row g-3 mb-4" id="searchForm">
                        <div class="col-md-8">
                            <div class="input-group position-relative">
                                <span class="input-group-text bg-primary text-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" 
                                       name="search" 
                                       id="searchInput"
                                       class="form-control" 
                                       placeholder="ค้นหาด้วยชื่อสินค้าหรือประเภทสินค้า"
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                       style="border-radius: 0 12px 12px 0;">
                                <!-- Search indicator -->
                                <div id="searchIndicator" class="position-absolute top-50 end-0 translate-middle-y me-3" style="display: none;">
                                    <i class="fas fa-magic text-primary fa-spin"></i>
                                </div>
                            </div>
                            <!-- Search status -->
                            <div id="searchStatus" class="mt-2" style="display: none;">
                                <small class="text-primary">
                                    <i class="fas fa-spinner fa-spin me-1"></i>กำลังค้นหา...
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2 align-items-center justify-content-between">
                                <a href="sh_product_ad.php" class="btn btn-outline-secondary" style="border-radius: 12px;">
                                    <i class="fas fa-redo me-2"></i>รีเซ็ต
                                </a>
                                <div class="text-end">
                                    <div class="auto-search-text">
                                        <i class="fas fa-magic"></i>
                                        <span>ค้นหาแบบอัตโนมัติ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- แสดงสินค้าจัดกลุ่มตามประเภท -->
                    <?php if (empty($grouped_products)): ?>
                        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <!-- No search results -->
                            <div class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">ไม่พบสินค้าที่ค้นหา</h5>
                                    <p class="text-muted">ลองใช้คำค้นหาอื่น หรือ <a href="sh_product_ad.php" class="text-primary">ดูสินค้าทั้งหมด</a></p>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            เคล็ดลับ: ค้นหาด้วยชื่อสินค้าหรือประเภทสินค้า
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- No products at all -->
                            <div class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">ยังไม่มีสินค้า</h5>
                                    <p class="text-muted">เริ่มต้นด้วยการเพิ่มสินค้าใหม่</p>
                                    <a href="add_product.php" class="btn btn-primary mt-3">
                                        <i class="fas fa-plus me-2"></i>เพิ่มสินค้าใหม่
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Search Results Info -->
                        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <?php 
                            $total_products = 0;
                            foreach($grouped_products as $products) {
                                $total_products += count($products);
                            }
                            ?>
                            <div class="alert alert-info mb-4" style="border-radius: 12px; border: none; background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-search me-3 text-primary"></i>
                                    <div>
                                        <strong>ผลการค้นหา:</strong> พบ <?= $total_products ?> สินค้า สำหรับคำค้นหา "<strong><?= htmlspecialchars($_GET['search']) ?></strong>"
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-magic me-1"></i>ค้นหาแบบอัตโนมัติ - เปลี่ยนคำค้นหาเพื่อดูผลลัพธ์ใหม่
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach($grouped_products as $type_name => $products): ?>
                            <div class="mb-4">
                                <h5 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-tags me-2"></i><?= htmlspecialchars($type_name) ?>
                                    <span class="badge bg-primary ms-2"><?= count($products) ?></span>
                                </h5>
                                <div class="row g-3">
                                    <?php foreach($products as $product): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card h-100 product-card">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-4">
                                        <?php
                                                            // หารูปภาพสีแรกที่พบ
                                                            $color_image_sql = "SELECT image FROM product_sizes WHERE product_base_id = ? AND image IS NOT NULL AND image != '' LIMIT 1";
                                                            $color_image_stmt = $conn->prepare($color_image_sql);
                                                            $color_image_stmt->bind_param("i", $product['id']);
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
                                                                $image_src = strpos($product['image'], 'http') === 0 ? 
                                                                    $product['image'] : 
                                                                    'img/' . $product['image'];
                                                            }
                                        ?>
                                        <img src="<?= htmlspecialchars($image_src) ?>" 
                                                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                                                 class="img-fluid rounded"
                                                                 style="width: 100%; height: 80px; object-fit: cover;"
                                                                 onerror="this.src='img/no-image.svg'; this.alt='ไม่มีรูปภาพ';">
                                                        </div>
                                                        <div class="col-8">
                                                            <div class="mb-2">
                                                                <h6 class="card-title mb-0">
                                                                    <?= htmlspecialchars($product['name']) ?>
                                                                </h6>
                                                            </div>
                                                            
                                                            <!-- แสดงสีที่มี -->
                                                            <?php if (!empty($product['colors'])): ?>
                                                                <div class="mb-2">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-palette me-1"></i>สีที่มี:
                                                                    </small>
                                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                                        <?php 
                                                                        $colors = explode(',', $product['colors']);
                                                                        foreach ($colors as $color): 
                                                                            $color = trim($color);
                                                                            if (!empty($color)):
                                                                        ?>
                                                                            <span class="badge" style="background-color: <?= getColorCode($color) ?>; color: <?= getTextColor($color) ?>; font-size: 0.7rem; padding: 3px 8px;">
                                                                                <?= htmlspecialchars($color) ?>
                                                                            </span>
                                                                        <?php 
                                                                            endif;
                                                                        endforeach; 
                                                                        ?>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <!-- แสดงไซส์และจำนวน -->
                                                            <?php if (!empty($product['size_details'])): ?>
                                                                <div class="mb-3">
                                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-tshirt me-1"></i>ไซส์และราคา:
                                                                        </small>
                                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                                type="button" 
                                                                                data-bs-toggle="collapse" 
                                                                                data-bs-target="#sizeDetails<?= $product['id'] ?>" 
                                                                                aria-expanded="false"
                                                                                title="ดูรายละเอียดไซส์และสี">
                                                                            <i class="fas fa-chevron-down"></i>
                                                                        </button>
                                                                    </div>
                                                                    
                                                                    <!-- แสดงข้อมูลย่อ -->
                                                                    <div class="size-summary mb-2">
                                                                        <?php 
                                                                        $size_details = explode('|', $product['size_details']);
                                                                        $size_count = count(array_unique(array_map(function($detail) {
                                                                            return explode(':', $detail)[0];
                                                                        }, $size_details)));
                                                                        $color_count = count(array_unique(array_map(function($detail) {
                                                                            return explode(':', $detail)[1];
                                                                        }, $size_details)));
                                                                        $total_amount = array_sum(array_map(function($detail) {
                                                                            return explode(':', $detail)[3];
                                                                        }, $size_details));
                                                                        ?>
                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <span class="badge bg-info">
                                                                                <i class="fas fa-tshirt me-1"></i><?= $size_count ?> ไซส์
                                                                            </span>
                                                                            <span class="badge bg-secondary">
                                                                                <i class="fas fa-palette me-1"></i><?= $color_count ?> สี
                                                                            </span>
                                                                            <span class="badge bg-success">
                                                                                <i class="fas fa-boxes me-1"></i><?= $total_amount ?> ชิ้น
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <!-- รายละเอียดแบบ Collapse -->
                                                                    <div class="collapse" id="sizeDetails<?= $product['id'] ?>">
                                                                        <div class="size-details-container">
                                                                            <?php 
                                                                            $grouped_sizes = array();
                                                                            
                                                                            // จัดกลุ่มตามไซส์
                                                                            foreach ($size_details as $detail):
                                                                                list($size, $color, $price, $amount) = explode(':', $detail);
                                                                                if (!isset($grouped_sizes[$size])) {
                                                                                    $grouped_sizes[$size] = array(
                                                                                        'price' => $price,
                                                                                        'colors' => array()
                                                                                    );
                                                                                }
                                                                                $grouped_sizes[$size]['colors'][] = array(
                                                                                    'color' => $color,
                                                                                    'amount' => $amount
                                                                                );
                                                                            endforeach;
                                                                            
                                                                            // แสดงผลตามไซส์
                                                                            foreach ($grouped_sizes as $size => $size_data):
                                                                            ?>
                                                                                <div class="mb-2 p-2 bg-light rounded size-color-item">
                                                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                        <span class="badge bg-primary size-badge"><?= $size ?></span>
                                                                                        <span class="fw-bold text-success price-display">฿<?= number_format($size_data['price'], 2) ?></span>
                                                                                    </div>
                                                                                    <div class="d-flex flex-wrap gap-1">
                                                                                        <?php foreach ($size_data['colors'] as $color_data): ?>
                                                                                            <span class="badge color-badge" style="background-color: <?= getColorCode($color_data['color']) ?>; color: <?= getTextColor($color_data['color']) ?>;">
                                                                                                <?= htmlspecialchars($color_data['color']) ?> (<?= $color_data['amount'] ?>)
                                                                                            </span>
                                                                                        <?php endforeach; ?>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            

                                                            
                                                            <div class="d-flex gap-1">
                                                                <a href="edit_product.php?id=<?= $product['id'] ?>" 
                                                                   class="btn btn-warning btn-sm flex-fill">
                                                                    <i class="fas fa-edit"></i> แก้ไข
                                                                </a>
                                                                <a href="delete_product.php?id=<?= $product['id'] ?>" 
                                                                   class="btn btn-danger btn-sm"
                                                                   onclick="return confirm('ต้องการลบสินค้านี้หรือไม่?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                    </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.product-card {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: #0d6efd;
}

.product-card .card-body {
    padding: 1rem;
}

.badge {
    font-size: 0.75rem;
}

.btn-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.size-details {
    margin-top: 0.5rem;
}

.size-details .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* CSS สำหรับการแสดงผลไซส์และสี */
.size-color-item {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.size-color-item:hover {
    background-color: #f8f9fa !important;
    border-color: #0d6efd;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.color-badge {
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 12px;
    font-weight: 500;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.color-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

.size-badge {
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 8px;
    font-weight: 600;
}

.price-display {
    font-size: 0.9rem;
    font-weight: 600;
}

.amount-display {
    font-size: 0.75rem;
    color: #6c757d;
}

/* Auto Search Styles */
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

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

/* Search Input Enhancements */
.input-group .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.input-group-text {
    border-radius: 12px 0 0 12px;
    border: 1px solid #0d6efd;
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
}

/* Search Indicator */
#searchIndicator {
    z-index: 10;
}

#searchStatus {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Empty State Styles */
.empty-state {
    padding: 2rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 20px;
    border: 2px dashed #e9ecef;
    transition: all 0.3s ease;
}

.empty-state:hover {
    border-color: #0d6efd;
    background: linear-gradient(135deg, #f0f8ff 0%, #ffffff 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(13, 110, 253, 0.1);
}

.empty-state i {
    opacity: 0.6;
    transition: all 0.3s ease;
}

.empty-state:hover i {
    opacity: 1;
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .size-color-item {
        padding: 0.5rem !important;
    }
    
    .color-badge {
        font-size: 0.65rem;
        padding: 2px 6px;
    }
    
    .size-badge {
        font-size: 0.7rem;
        padding: 3px 6px;
    }
    
    .auto-search-text {
        font-size: 0.75rem;
        padding: 0.4rem 0.8rem;
    }
}

/* Size Details Styles */
.size-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
}

.size-details-container {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px;
    background: #ffffff;
}

.size-details-container::-webkit-scrollbar {
    width: 6px;
}

.size-details-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.size-details-container::-webkit-scrollbar-thumb {
    background: #0d6efd;
    border-radius: 3px;
}

.size-details-container::-webkit-scrollbar-thumb:hover {
    background: #0a58ca;
}

.collapse-btn {
    transition: all 0.3s ease;
}

.collapse-btn[aria-expanded="true"] i {
    transform: rotate(180deg);
}

.collapse-btn i {
    transition: transform 0.3s ease;
}

/* Badge Improvements */
.badge {
    font-size: 0.7rem;
    padding: 4px 8px;
    border-radius: 10px;
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.badge i {
    font-size: 0.6rem;
}
</style>

<script>
// Auto Search Functionality
let searchTimeout;
let isAutoSearching = false;

// Perform auto search with debounce
function performAutoSearch() {
    if (isAutoSearching) return;
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        isAutoSearching = true;
        const form = document.getElementById('searchForm');
        if (form) {
            showSearchStatus();
            form.submit();
        }
    }, 800); // Wait 800ms after user stops typing
}

// Show search status
function showSearchStatus() {
    const statusDiv = document.getElementById('searchStatus');
    if (statusDiv) {
        statusDiv.style.display = 'block';
    }
}

// Show search indicator
function showSearchIndicator() {
    const indicator = document.getElementById('searchIndicator');
    if (indicator) {
        indicator.style.display = 'block';
    }
}

// Hide search indicator
function hideSearchIndicator() {
    const indicator = document.getElementById('searchIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
}

// Enhanced auto search with visual feedback
function enhancedAutoSearch() {
    showSearchIndicator();
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        hideSearchIndicator();
        const form = document.getElementById('searchForm');
        if (form) {
            showSearchStatus();
            form.submit();
        }
    }, 800);
}

// Initialize auto search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    
    if (searchInput) {
        // Add input event listener for auto search
        searchInput.addEventListener('input', enhancedAutoSearch);
        
        // Add focus event for better UX
        searchInput.addEventListener('focus', function() {
            this.style.borderColor = '#0d6efd';
            this.style.boxShadow = '0 0 0 0.2rem rgba(13, 110, 253, 0.25)';
        });
        
        // Add blur event
        searchInput.addEventListener('blur', function() {
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
    }
    
    // Reset auto searching flag when page loads
    isAutoSearching = false;
    
    // Hide search status if it's visible
    const statusDiv = document.getElementById('searchStatus');
    if (statusDiv) {
        statusDiv.style.display = 'none';
    }
    
    // Handle collapse buttons for size details
    const collapseButtons = document.querySelectorAll('[data-bs-toggle="collapse"]');
    collapseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            // Update button icon
            const icon = this.querySelector('i');
            if (icon) {
                if (isExpanded) {
                    icon.className = 'fas fa-chevron-down';
                } else {
                    icon.className = 'fas fa-chevron-up';
                }
            }
        });
    });
});

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Escape to clear search
    if (e.key === 'Escape') {
        const searchInput = document.getElementById('searchInput');
        if (searchInput && searchInput.value) {
            searchInput.value = '';
            searchInput.focus();
            // Trigger auto search to clear results
            enhancedAutoSearch();
        }
    }
});
</script>

<?php include 'footer.php'; ?>
