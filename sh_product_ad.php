<?php 
$title = "รายการสินค้า";
include 'header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">รายการสินค้า</h4>
        <a href="add_product.php" class="btn btn-success">เพิ่มสินค้าใหม่</a>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>รหัสสินค้า</th>
                    <th>ไซส์สินค้าสินค้า</th>
                    <th>ประเภทสินค้า</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>รูป</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT product.*, type.type_name 
                    FROM product 
                    LEFT JOIN type ON product.type_id = type.type_id
                    ORDER BY product.po_id DESC";
            $result = mysqli_query($conn, $sql);

            while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?= str_pad($row['po_id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td><?= $row['po_name'] ?></td>
                    <td><?= $row['type_name'] ?></td>
                    <td><?= number_format($row['price'], 2) ?> บาท</td>
                    <td><?= $row['amount'] ?> ชิ้น</td>
                    <td>
                        <img src="img/<?= $row['image'] ?>" alt="<?= $row['po_name'] ?>" width="100" class="img-thumbnail">
                    </td>
                    <td>
                        <a href="edit_product.php?id=<?= $row['po_id'] ?>" class="btn btn-warning btn-sm">แก้ไข</a>
                        <a href="delete_product.php?id=<?= $row['po_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ต้องการลบสินค้านี้หรือไม่?')">ลบ</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
