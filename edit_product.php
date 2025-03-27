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
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0">
            <i class="fas fa-edit me-2"></i>แก้ไขข้อมูลสินค้า
        </h4>
    </div>
    <div class="card-body">
        <form action="update_product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="po_id" value="<?= $row['po_id'] ?>">
            
            <div class="row g-3">
                <!-- ชื่อสินค้า -->
                <div class="col-md-6">
                    <label class="form-label">ชื่อสินค้า</label>
                    <input type="text" name="po_name" class="form-control" 
                           value="<?= htmlspecialchars($row['po_name']) ?>" required>
                </div>

                <!-- ประเภทสินค้า -->
                <div class="col-md-6">
                    <label class="form-label">ประเภทสินค้า</label>
                    <select name="type_id" class="form-select" required>
                        <?php while($type = mysqli_fetch_assoc($type_result)): ?>
                            <option value="<?= $type['type_id'] ?>" 
                                    <?= ($type['type_id'] == $row['type_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['type_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- ราคา -->
                <div class="col-md-6">
                    <label class="form-label">ราคา</label>
                    <div class="input-group">
                        <input type="number" name="price" class="form-control" 
                               value="<?= $row['price'] ?>" min="0" step="0.01" required>
                        <span class="input-group-text">บาท</span>
                    </div>
                </div>

                <!-- จำนวน -->
                <div class="col-md-6">
                    <label class="form-label">จำนวน</label>
                    <input type="number" name="amount" class="form-control" 
                           value="<?= $row['amount'] ?>" min="0" required>
                </div>

                <!-- คำอธิบายสั้น -->
                <div class="col-12">
                    <label class="form-label">คำอธิบายสั้น</label>
                    <textarea name="description" class="form-control" rows="2" maxlength="255"
                              placeholder="คำอธิบายสั้นๆ ที่จะแสดงในหน้ารายการสินค้า"><?= htmlspecialchars($row['description'] ?? '') ?></textarea>
                    <div class="form-text">ไม่เกิน 255 ตัวอักษร</div>
                </div>

                <!-- รายละเอียดเพิ่มเติม -->
                <div class="col-12">
                    <label class="form-label">รายละเอียดเพิ่มเติม</label>
                    <textarea name="detail" class="form-control" rows="4"
                              placeholder="รายละเอียดเพิ่มเติมของสินค้า"><?= htmlspecialchars($row['detail'] ?? '') ?></textarea>
                </div>

                <!-- รูปภาพ -->
                <div class="col-12">
                    <label class="form-label">รูปภาพปัจจุบัน</label><br>
                    <img src="img/<?= $row['image'] ?>" class="img-thumbnail mb-2" style="max-height: 200px;">
                    
                    <div class="mt-2">
                        <label class="form-label">เปลี่ยนรูปภาพ</label>
                        <input type="file" name="image" class="form-control" accept="image/*"
                               onchange="previewImage(this)">
                        <input type="hidden" name="old_image" value="<?= $row['image'] ?>">
                        <div class="form-text">รองรับไฟล์ภาพ jpg, jpeg, png ขนาดไม่เกิน 2MB</div>
                    </div>

                    <!-- แสดงตัวอย่างรูปภาพใหม่ -->
                    <div id="image-preview" class="mt-2" style="display: none;">
                        <label class="form-label">ตัวอย่างรูปภาพใหม่</label><br>
                        <img src="" alt="ตัวอย่างรูปภาพ" class="img-thumbnail" style="max-height: 200px;">
                    </div>
                </div>

                <!-- ปุ่มบันทึก -->
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>บันทึก
                    </button>
                    <a href="sh_product_ad.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>ยกเลิก
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewImg = preview.querySelector('img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>