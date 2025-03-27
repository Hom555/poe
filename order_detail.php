<?php
session_start();
include 'condb.php';

// ตรวจสอบว่าเป็น admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: login.php');
    exit();
}

// ตรวจสอบ order_id
if (!isset($_GET['order_id'])) {
    header('Location: admin_orders.php');
    exit();
}

$order_id = intval($_GET['order_id']);

// ดึงข้อมูลคำสั่งซื้อและรายละเอียด
$sql = "SELECT o.*, od.quantity, od.price as item_price, od.total as item_total,
        p.po_id, p.po_name, p.image,
        t.type_name,
        u.username, u.email
        FROM orders o
        LEFT JOIN order_details od ON o.order_id = od.order_id
        LEFT JOIN product p ON od.product_id = p.po_id
        LEFT JOIN type t ON p.type_id = t.type_id
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: admin_orders.php');
    exit();
}

$order_info = $result->fetch_assoc(); // เก็บข้อมูลหลักของ order
$result->data_seek(0); // reset pointer สำหรับการวนลูปรายการสินค้า

// เพิ่มโค้ดนี้หลังจากดึงข้อมูล แต่ก่อน include header.php
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $order_id = $_POST['order_id'];
    
    $update_sql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        echo "<script>
            alert('อัพเดทสถานะเรียบร้อย');
            window.location.href = 'order_detail.php?order_id=" . $order_id . "';
        </script>";
        exit();
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัพเดทสถานะ');</script>";
    }
}

$title = "รายละเอียดคำสั่งซื้อ #" . str_pad($order_id, 8, '0', STR_PAD_LEFT);
include 'header.php';
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    รายละเอียดคำสั่งซื้อ #<?= str_pad($order_id, 8, '0', STR_PAD_LEFT) ?>
                </h5>
                <a href="admin_orders.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>กลับ
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- ข้อมูลลูกค้า -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="border-bottom pb-2">ข้อมูลลูกค้า</h6>
                    <p class="mb-1">ชื่อ: <?= htmlspecialchars($order_info['name']) ?></p>
                    <p class="mb-1">อีเมล: <?= htmlspecialchars($order_info['email']) ?></p>
                    <p class="mb-1">เบอร์โทร: <?= htmlspecialchars($order_info['phone']) ?></p>
                    <p class="mb-1">
                        ที่อยู่: <?= htmlspecialchars($order_info['address']) ?><br>
                        ตำบล/แขวง: <?= htmlspecialchars($order_info['subdistrict']) ?><br>
                        อำเภอ/เขต: <?= htmlspecialchars($order_info['district']) ?><br>
                        จังหวัด: <?= htmlspecialchars($order_info['province']) ?><br>
                        รหัสไปรษณีย์: <?= htmlspecialchars($order_info['zipcode']) ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="border-bottom pb-2">ข้อมูลการสั่งซื้อ</h6>
                    <p class="mb-1">วันที่สั่งซื้อ: <?= date('d/m/Y H:i', strtotime($order_info['order_date'])) ?></p>
                    <p class="mb-1">ยอดรวม: <?= number_format($order_info['total_amount'], 2) ?> บาท</p>
                    <p class="mb-1">สถานะ: 
                        <span class="badge bg-<?= getStatusColor($order_info['status']) ?>">
                            <?= $order_info['status'] ?>
                        </span>
                    </p>
                    <?php if ($order_info['payment_date']): ?>
                    <p class="mb-1">วันที่ชำระเงิน: <?= date('d/m/Y H:i', strtotime($order_info['payment_date'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- รายการสินค้า -->
            <h6 class="border-bottom pb-2 mb-3">รายการสินค้า</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>รูปภาพ</th>
                            <th>รหัสสินค้า</th>
                            <th>สินค้า</th>
                            <th>ประเภท</th>
                            <th class="text-end">ราคา/ชิ้น</th>
                            <th class="text-center">จำนวน</th>
                            <th class="text-end">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $total = 0;
                    while ($item = $result->fetch_assoc()):
                        if ($item['po_id']): // ตรวจสอบว่ามีข้อมูลสินค้า
                        $subtotal = $item['item_price'] * $item['quantity'];
                        $total += $subtotal;
                    ?>
                        <tr>
                            <td width="80">
                                <img src="image/<?= $item['image'] ?>" 
                                     alt="<?= htmlspecialchars($item['po_name']) ?>"
                                     class="img-thumbnail" 
                                     style="width: 50px; height: 50px; object-fit: cover;">
                            </td>
                            <td><?= str_pad($item['po_id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($item['po_name']) ?></td>
                            <td><?= htmlspecialchars($item['type_name']) ?></td>
                            <td class="text-end"><?= number_format($item['item_price'], 2) ?></td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-end"><?= number_format($subtotal, 2) ?></td>
                        </tr>
                    <?php 
                        endif;
                    endwhile; 
                    ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="6" class="text-end"><strong>รวมทั้งหมด</strong></td>
                            <td class="text-end"><strong><?= number_format($order_info['total_amount'], 2) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- หลักฐานการโอนเงิน -->
            <?php if (!empty($order_info['payment_slip'])): ?>
            <div class="text-center mt-4">
                <h6 class="border-bottom pb-2 mb-3">หลักฐานการโอนเงิน</h6>
                <img src="slips/<?= $order_info['payment_slip'] ?>" 
                     alt="สลิปการโอนเงิน" 
                     class="img-fluid" 
                     style="max-height: 400px;">
                <p class="mt-2 text-muted">
                    วันที่โอน: <?= date('d/m/Y H:i', strtotime($order_info['payment_date'])) ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- อัพเดทสถานะ -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">อัพเดทสถานะคำสั่งซื้อ</h6>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <div class="row align-items-end">
                            <div class="col-md-8">
                                <label class="form-label">สถานะคำสั่งซื้อ</label>
                                <select name="status" class="form-select">
                                    <option value="รอการชำระเงิน" <?= $order_info['status'] == 'รอการชำระเงิน' ? 'selected' : '' ?>>
                                        รอการชำระเงิน
                                    </option>
                                    <option value="รอตรวจสอบการชำระเงิน" <?= $order_info['status'] == 'รอตรวจสอบการชำระเงิน' ? 'selected' : '' ?>>
                                        รอตรวจสอบการชำระเงิน
                                    </option>
                                    <option value="ชำระเงินแล้ว" <?= $order_info['status'] == 'ชำระเงินแล้ว' ? 'selected' : '' ?>>
                                        ชำระเงินแล้ว
                                    </option>
                                    <option value="กำลังจัดส่ง" <?= $order_info['status'] == 'กำลังจัดส่ง' ? 'selected' : '' ?>>
                                        กำลังจัดส่ง
                                    </option>
                                    <option value="จัดส่งแล้ว" <?= $order_info['status'] == 'จัดส่งแล้ว' ? 'selected' : '' ?>>
                                        จัดส่งแล้ว
                                    </option>
                                    <option value="ยกเลิก" <?= $order_info['status'] == 'ยกเลิก' ? 'selected' : '' ?>>
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
</div>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'รอตรวจสอบการชำระเงิน': return 'warning';
        case 'ชำระเงินแล้ว': return 'info';
        case 'กำลังจัดส่ง': return 'primary';
        case 'จัดส่งแล้ว': return 'success';
        case 'ยกเลิก': return 'danger';
        default: return 'secondary';
    }
}

include 'footer.php';
?>
