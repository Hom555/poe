<?php
session_start();
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
$sql = "SELECT o.*, u.username, u.name as customer_name, COUNT(od.id) as total_items 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        LEFT JOIN order_details od ON o.order_id = od.order_id 
        GROUP BY o.order_id 
        ORDER BY o.order_date DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shopping-cart me-2"></i>คำสั่งซื้อทั้งหมด
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>เลขที่คำสั่งซื้อ</th>
                                    <th>วันที่สั่งซื้อ</th>
                                    <th>ลูกค้า</th>
                                    <th>ที่อยู่จัดส่ง</th>
                                    <th>เบอร์โทร</th>
                                    <th>จำนวน</th>
                                    <th>ยอดรวม</th>
                                    <th>สถานะ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= str_pad($row['order_id'], 8, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= formatThaiDate($row['order_date']) ?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($row['address']) ?><br>
                                            <?= htmlspecialchars($row['subdistrict']) ?>
                                            <?= htmlspecialchars($row['district']) ?>
                                            <?= htmlspecialchars($row['province']) ?>
                                            <?= htmlspecialchars($row['zipcode']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['phone']) ?></td>
                                        <td><?= $row['total_items'] ?> รายการ</td>
                                        <td>฿<?= number_format($row['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusColor($row['status']) ?>">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#orderModal<?= $row['order_id'] ?>">
                                                <i class="fas fa-eye"></i> ดูรายละเอียด
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal แสดงรายละเอียดคำสั่งซื้อ -->
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
                                                    <!-- รายการสินค้า -->
                                                    <h6 class="border-bottom pb-2">รายการสินค้า</h6>
                                                    <div class="table-responsive">
                                                        <table class="table">
                                                            <thead>
                                                                <tr>
                                                                    <th>รูปภาพ</th>
                                                                    <th>สินค้า</th>
                                                                    <th class="text-center">ราคา</th>
                                                                    <th class="text-center">จำนวน</th>
                                                                    <th class="text-end">รวม</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $detail_sql = "SELECT od.*, p.po_name, p.image 
                                                                             FROM order_details od 
                                                                             LEFT JOIN product p ON od.product_id = p.po_id 
                                                                             WHERE od.order_id = ?";
                                                                $stmt = $conn->prepare($detail_sql);
                                                                $stmt->bind_param("i", $row['order_id']);
                                                                $stmt->execute();
                                                                $details = $stmt->get_result();
                                                                while ($item = $details->fetch_assoc()):
                                                                ?>
                                                                    <tr>
                                                                        <td>
                                                                            <img src="img/<?= $item['image'] ?>" 
                                                                                 alt="<?= htmlspecialchars($item['po_name']) ?>"
                                                                                 class="img-thumbnail" style="width: 50px;">
                                                                        </td>
                                                                        <td><?= htmlspecialchars($item['po_name']) ?></td>
                                                                        <td class="text-center">฿<?= number_format($item['price'], 2) ?></td>
                                                                        <td class="text-center"><?= $item['quantity'] ?></td>
                                                                        <td class="text-end">฿<?= number_format($item['total'], 2) ?></td>
                                                                    </tr>
                                                                <?php endwhile; ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <td colspan="4" class="text-end">
                                                                        <strong>รวมทั้งหมด</strong>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <strong>฿<?= number_format($row['total_amount'], 2) ?></strong>
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>

                                                    <!-- หลักฐานการโอนเงิน -->
                                                    <div class="mt-4">
                                                        <h6 class="border-bottom pb-2">หลักฐานการโอนเงิน</h6>
                                                        <div class="text-center">
                                                            <?php if (!empty($row['payment_slip'])): ?>
                                                                <img src="slip/<?= $row['payment_slip'] ?>" 
                                                                     alt="สลิปการโอนเงิน" 
                                                                     class="img-fluid" style="max-height: 300px;">
                                                                <p class="mt-2 text-muted">
                                                                    วันที่โอน: <?= formatThaiDate($row['payment_date']) ?>
                                                                </p>
                                                            <?php else: ?>
                                                                <p class="text-muted">ไม่พบหลักฐานการโอนเงิน</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- อัพเดทสถานะ -->
                                                    <form action="" method="POST" class="mt-3">
                                                        <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                                        <div class="row align-items-end">
                                                            <div class="col-md-8">
                                                                <label class="form-label">สถานะคำสั่งซื้อ</label>
                                                                <select name="status" class="form-select">
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
                                                                <button type="submit" name="update_status" class="btn btn-primary w-100">
                                                                    <i class="fas fa-save me-2"></i>บันทึกสถานะ
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
    $year = date('Y', $timestamp) + 543;  // แปลงเป็น พ.ศ.
    return date('d/m/', $timestamp) . $year . date(' H:i', $timestamp);
}

include 'footer.php';
?> 