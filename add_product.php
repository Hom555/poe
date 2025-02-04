<?php
session_start();
include 'condb.php';

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $po_name = $_POST['po_name'];
    $type_id = $_POST['type_id'];
    $price = $_POST['price'];
    $amount = $_POST['amount'];
    
    // ตรวจสอบการอัพโหลดรูปภาพ
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $allow_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // ตรวจสอบประเภทไฟล์
        if (!in_array($file['type'], $allow_types)) {
            echo "<script>
                alert('กรุณาอัพโหลดไฟล์รูปภาพ (jpg, jpeg, png) เท่านั้น');
                window.history.back();
            </script>";
            exit();
        }
        
        // ตรวจสอบขนาดไฟล์
        if ($file['size'] > $max_size) {
            echo "<script>
                alert('ไฟล์มีขนาดใหญ่เกินไป (ไม่เกิน 2MB)');
                window.history.back();
            </script>";
            exit();
        }
        
        // สร้างชื่อไฟล์ใหม่
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $extension;
        $upload_path = 'img/' . $new_filename;
        
        // อัพโหลดไฟล์
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            $error = error_get_last();
            echo "<script>
                alert('เกิดข้อผิดพลาดในการอัพโหลดไฟล์: " . 
                ($error ? $error['message'] : 'Unknown error') . "');
                window.history.back();
            </script>";
            exit();
        }
        
        // เพิ่มข้อมูลลงฐานข้อมูล
        $sql = "INSERT INTO product (po_name, type_id, price, amount, image) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siids", $po_name, $type_id, $price, $amount, $new_filename);
        
        if ($stmt->execute()) {
            echo "<script>
                alert('เพิ่มสินค้าเรียบร้อยแล้ว');
                window.location.href = 'sh_product_ad.php';
            </script>";
        } else {
            echo "<script>
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                window.history.back();
            </script>";
        }
    } else {
        echo "<script>
            alert('กรุณาเลือกรูปภาพสินค้า');
            window.history.back();
        </script>";
    }
}

// ดึงข้อมูลประเภทสินค้า
$type_sql = "SELECT * FROM type ORDER BY type_name";
$type_result = mysqli_query($conn, $type_sql);

$title = "เพิ่มสินค้าใหม่";
include 'header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">เพิ่มสินค้าใหม่</h4>
        </div>
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">ชื่อสินค้า</label>
                    <input type="text" name="po_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">ประเภทสินค้า</label>
                    <select name="type_id" class="form-select" required>
                        <option value="">เลือกประเภทสินค้า</option>
                        <?php while($type = mysqli_fetch_assoc($type_result)) { ?>
                            <option value="<?= $type['type_id'] ?>">
                                <?= $type['type_name'] ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">ราคา</label>
                    <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">จำนวน</label>
                    <input type="number" name="amount" class="form-control" min="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">รูปภาพสินค้า</label>
                    <input type="file" name="image" class="form-control" accept="image/*" required 
                           onchange="previewImage(this)">
                    <div class="form-text">รองรับไฟล์ภาพ jpg, jpeg, png ขนาดไม่เกิน 2MB</div>
                    <div id="image-preview" class="mt-2 text-center" style="display: none;">
                        <img src="" alt="ตัวอย่างรูปภาพ" class="img-fluid" style="max-height: 200px;">
                    </div>
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
