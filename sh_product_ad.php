<?php 
$title = "รายการสินค้า";
include 'header.php';

// เพิ่มการจัดการค้นหา
$where_clause = "1=1";
$params = array();
$types = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $where_clause .= " AND (p.po_id LIKE ? OR p.po_name LIKE ? OR t.type_name LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

// แก้ไข SQL query
$sql = "SELECT p.*, t.type_name 
        FROM product p 
        LEFT JOIN type t ON p.type_id = t.type_id 
        WHERE $where_clause 
        ORDER BY p.po_id DESC";

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
                    <!-- เพิ่มฟอร์มค้นหา -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="ค้นหาด้วยรหัสสินค้า, ชื่อสินค้า หรือประเภทสินค้า"
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i> ค้นหา
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <a href="sh_product_ad.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> รีเซ็ต
                            </a>
                        </div>
                    </form>

                    <!-- ตารางแสดงสินค้า -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>รหัสสินค้า</th>
                                    <th>รูปภาพ</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>ประเภท</th>
                                    <th class="text-end">ราคา</th>
                                    <th class="text-center">จำนวน</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= str_pad($row['po_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td>
                                        <img src="img/<?= $row['image'] ?>" 
                                             alt="<?= htmlspecialchars($row['po_name']) ?>"
                                             class="img-thumbnail"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td><?= htmlspecialchars($row['po_name']) ?></td>
                                    <td><?= htmlspecialchars($row['type_name'] ?? 'ไม่ระบุ') ?></td>
                                    <td class="text-end"><?= number_format($row['price'], 2) ?></td>
                                    <td class="text-center"><?= $row['amount'] ?></td>
                                    <td class="text-center">
                                        <a href="edit_product.php?id=<?= $row['po_id'] ?>" class="btn btn-warning btn-sm">แก้ไข</a>
                                        <a href="delete_product.php?id=<?= $row['po_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ต้องการลบสินค้านี้หรือไม่?')">ลบ</a>
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
</div>

<?php include 'footer.php'; ?>
