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

// ดึงข้อมูลคำสั่งซื้อ
$sql = "SELECT o.*, u.username, COUNT(od.id) as total_items,
        (SELECT GROUP_CONCAT(CONCAT(p.po_name, ' (', od2.quantity, ')'))
         FROM order_details od2 
         JOIN product p ON od2.product_id = p.po_id 
         WHERE od2.order_id = o.order_id) as product_list
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        LEFT JOIN order_details od ON o.order_id = od.order_id 
        GROUP BY o.order_id 
        ORDER BY o.order_date DESC";
$result = mysqli_query($conn, $sql);
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
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>เลขที่สั่งซื้อ</th>
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
                                        <td>
                                            <span class="fw-bold"><?= str_pad($row['order_id'], 8, '0', STR_PAD_LEFT) ?></span>
                                        </td>
                                        <td><?= formatThaiDate($row['order_date']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($row['name']) ?><br>
                                            <small class="text-muted"><?= $row['phone'] ?></small>
                                        </td>
                                        <td>
                                            <small>
                                                <?= htmlspecialchars($row['address']) ?><br>
                                                <?= htmlspecialchars($row['subdistrict']) ?>
                                                <?= htmlspecialchars($row['district']) ?><br>
                                                <?= htmlspecialchars($row['province']) ?>
                                                <?= htmlspecialchars($row['zipcode']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($row['product_list']) ?></small>
                                        </td>
                                        <td class="text-end">
                                            ฿<?= number_format($row['total_amount'], 2) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= getStatusColor($row['status']) ?>">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="order_detail.php?order_id=<?= $row['order_id'] ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="print_order.php?order_id=<?= $row['order_id'] ?>" 
                                                   class="btn btn-sm btn-success"
                                                   target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Modal แสดงรายละเอียด -->
                                    <div class="modal fade" id="orderModal<?= $row['order_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        รายละเอียดคำสั่งซื้อ #<?= str_pad($row['order_id'], 8, '0', STR_PAD_LEFT) ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- หลักฐานการโอนเงิน -->
                                                    <?php if (!empty($row['payment_slip'])): ?>
                                                        <div class="text-center mb-3">
                                                            <img src="slips/<?= $row['payment_slip'] ?>" 
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

<?php
// ฟังก์ชันกำหนดสีตามสถานะ
function getStatusColor($status) {
    switch ($status) {
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

// ปิดการเชื่อมต่อก่อน include footer
if (isset($conn)) {
    mysqli_close($conn);
}

include 'footer.php';
?> 