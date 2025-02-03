<?php 
$title = "แก้ไขข้อมูลสินค้า";
include 'header.php';

// รับค่า id จาก URL
$po_id = $_GET['id'];

// ดึงข้อมูลสินค้า
$sql = "SELECT * FROM product WHERE po_id = '$po_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// ดึงข้อมูลประเภทสินค้า
$type_sql = "SELECT * FROM type ORDER BY type_name";
$type_result = mysqli_query($conn, $type_sql);
?>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">แก้ไขข้อมูลสินค้า</h4>
    </div>
    <div class="card-body">
        <form action="update_product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="po_id" value="<?= $row['po_id'] ?>">
            
            <div class="mb-3">
                <label class="form-label">ชื่อสินค้า:</label>
                <input type="text" name="po_name" class="form-control" value="<?= $row['po_name'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">ประเภทสินค้า:</label>
                <select name="type_id" class="form-select" required>
                    <?php while($type = mysqli_fetch_assoc($type_result)) { ?>
                        <option value="<?= $type['type_id'] ?>" <?= ($type['type_id'] == $row['type_id']) ? 'selected' : '' ?>>
                            <?= $type['type_name'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">ราคา:</label>
                <input type="number" name="price" class="form-control" value="<?= $row['price'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">จำนวน:</label>
                <input type="number" name="amount" class="form-control" value="<?= $row['amount'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">รูปภาพปัจจุบัน:</label><br>
                <img src="img/<?= $row['image'] ?>" width="200" class="img-thumbnail mb-2"><br>
                <label class="form-label">เปลี่ยนรูปภาพ:</label>
                <input type="file" name="image" class="form-control">
                <input type="hidden" name="old_image" value="<?= $row['image'] ?>">
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> บันทึก
                </button>
                <a href="sh_product_ad.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>