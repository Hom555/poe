<?php
session_start();
include 'condb.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

$title = "เพิ่มสินค้า";
include 'header.php';

// ดึงข้อมูลประเภทสินค้า
$sql = "SELECT * FROM type ORDER BY type_name";
$result = mysqli_query($conn, $sql);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>เพิ่มสินค้า
                    </h4>
                </div>
                <div class="card-body">
                    <form action="insert_product.php" method="post" enctype="multipart/form-data">
                        <div class="row g-3">
                            <!-- ชื่อสินค้า -->
                            <div class="col-md-6">
                                <label class="form-label">ชื่อสินค้า</label>
                                <input type="text" name="po_name" class="form-control" required>
                            </div>

                            <!-- ประเภทสินค้า -->
                            <div class="col-md-6">
                                <label class="form-label">ประเภทสินค้า</label>
                                <select name="type_id" class="form-select" required>
                                    <option value="">เลือกประเภทสินค้า</option>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <option value="<?= $row['type_id'] ?>">
                                            <?= htmlspecialchars($row['type_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- ราคา -->
                            <div class="col-md-6">
                                <label class="form-label">ราคา</label>
                                <div class="input-group">
                                    <input type="number" name="price" class="form-control" 
                                           min="0" step="0.01" required>
                                    <span class="input-group-text">บาท</span>
                                </div>
                            </div>

                            <!-- จำนวน -->
                            <div class="col-md-6">
                                <label class="form-label">จำนวน</label>
                                <input type="number" name="amount" class="form-control" 
                                       min="0" required>
                            </div>

                            <!-- คำอธิบายสั้น -->
                            <div class="col-12">
                                <label class="form-label">คำอธิบายสั้น</label>
                                <textarea name="description" class="form-control" 
                                          rows="2" maxlength="255"
                                          placeholder="คำอธิบายสั้นๆ ที่จะแสดงในหน้ารายการสินค้า"></textarea>
                                <div class="form-text">ไม่เกิน 255 ตัวอักษร</div>
                            </div>

                            <!-- รายละเอียดเพิ่มเติม -->
                            <div class="col-12">
                                <label class="form-label">รายละเอียดเพิ่มเติม</label>
                                <textarea name="detail" class="form-control" rows="4"
                                          placeholder="รายละเอียดเพิ่มเติมของสินค้า"></textarea>
                            </div>

                            <!-- รูปภาพ -->
                            <div class="col-12">
                                <label class="form-label">รูปภาพสินค้า</label>
                                <input type="file" name="image" class="form-control" 
                                       accept="image/*" required
                                       onchange="previewImage(this)">
                                <div class="form-text">รองรับไฟล์ภาพ jpg, jpeg, png ขนาดไม่เกิน 2MB</div>
                            </div>

                            <!-- แสดงตัวอย่างรูปภาพ -->
                            <div class="col-12">
                                <div id="image-preview" class="mt-2 text-center" style="display: none;">
                                    <img src="" alt="ตัวอย่างรูปภาพ" 
                                         class="img-thumbnail" 
                                         style="max-height: 200px;">
                                </div>
                            </div>

                            <!-- ปุ่มบันทึก -->
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-save me-2"></i>บันทึกข้อมูล
                                </button>
                                <a href="sh_product_ad.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>ยกเลิก
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
