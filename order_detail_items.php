<?php 
$title = "รายละเอียดคำสั่งซื้อ";
include 'header.php';

$order_id = $_GET['order_id'];

// ดึงข้อมูลคำสั่งซื้อ
$sql = "SELECT tb_order.*, member.name 
        FROM tb_order 
        LEFT JOIN member ON tb_order.mem_id = member.id 
        WHERE order_id = '$order_id'";
$result = mysqli_query($conn, $sql);
$order = mysqli_fetch_assoc($result);

// ดึงรายการสินค้าในคำสั่งซื้อ
$items_sql = "SELECT order_detail.*, product.po_name 
              FROM order_detail 
              LEFT JOIN product ON order_detail.pro_id = product.po_id 
              WHERE order_id = '$order_id'";
$items_result = mysqli_query($conn, $items_sql);
?>

<div class="card mb-4">
    <div class="card-header">
        <h4 class="mb-0">รายละเอียดคำสั่งซื้อ #<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?></h4>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>ข้อมูลลูกค้า</h5>
                <p>ชื่อ: <?= $order['name'] ?></p>
                <p>ที่อยู่: <?= $order['address'] ?></p>
                <p>เบอร์โทร: <?= $order['phone'] ?></p>
            </div>
            <div class="col-md-6">
                <h5>ข้อมูลคำสั่งซื้อ</h5>
                <p>วันที่สั่งซื้อ: <?= date('d/m/Y H:i', strtotime($order['reg_date'])) ?></p>
                <p>สถานะ: 
                    <?php
                    switch($order['order_status']) {
                        case 1: echo "<span class='text-warning'>รอชำระเงิน</span>"; break;
                        case 2: echo "<span class='text-info'>ชำระเงินแล้ว</span>"; break;
                        case 3: echo "<span class='text-primary'>จัดส่งแล้ว</span>"; break;
                        case 4: echo "<span class='text-success'>เสร็จสมบูรณ์</span>"; break;
                        case 0: echo "<span class='text-danger'>ยกเลิก</span>"; break;
                    }
                    ?>
                </p>
            </div>
        </div>

        <h5>รายการสินค้า</h5>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>รหัสสินค้า</th>
                    <th>ชื่อสินค้า</th>
                    <th>ราคา/หน่วย</th>
                    <th>จำนวน</th>
                    <th>ราคารวม</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = mysqli_fetch_assoc($items_result)) { ?>
                    <tr>
                        <td><?= str_pad($item['pro_id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td><?= $item['po_name'] ?></td>
                        <td><?= number_format($item['orderPrice'], 2) ?> บาท</td>
                        <td><?= $item['orderQty'] ?> ชิ้น</td>
                        <td><?= number_format($item['Total'], 2) ?> บาท</td>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="4" class="text-end"><strong>ราคารวมทั้งหมด</strong></td>
                    <td><strong><?= number_format($order['total_price'], 2) ?> บาท</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="mt-3">
            <a href="order_detail.php?order_id=<?= $order_id ?>" class="btn btn-secondary">กลับ</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?> 