<?php 
$title = "จัดการประเภทสินค้า";
include 'header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">จัดการประเภทสินค้า</h4>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTypeModal">
            เพิ่มประเภทสินค้า
        </button>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th width="15%">รหัสประเภท</th>
                    <th>ชื่อประเภทสินค้า</th>
                    <th width="20%">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM type ORDER BY type_id DESC";
                $result = mysqli_query($conn, $sql);
                while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= $row['type_id'] ?></td>
                        <td><?= $row['type_name'] ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" 
                                    onclick="editType('<?= $row['type_id'] ?>', '<?= $row['type_name'] ?>')"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editTypeModal">
                                แก้ไข
                            </button>
                            <a href="delete_type.php?id=<?= $row['type_id'] ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('ต้องการลบประเภทสินค้านี้หรือไม่?')">
                                ลบ
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal เพิ่มประเภทสินค้า -->
<div class="modal fade" id="addTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มประเภทสินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add_type.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อประเภทสินค้า:</label>
                        <input type="text" name="type_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไขประเภทสินค้า -->
<div class="modal fade" id="editTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขประเภทสินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="update_type.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="edit_type_id" name="type_id">
                    <div class="mb-3">
                        <label class="form-label">ชื่อประเภทสินค้า:</label>
                        <input type="text" id="edit_type_name" name="type_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function editType(typeId, typeName) {
    document.getElementById('edit_type_id').value = typeId;
    document.getElementById('edit_type_name').value = typeName;
}
</script>

<?php include 'footer.php'; ?> 