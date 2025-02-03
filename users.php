<?php
session_start();
include 'condb.php';

// ตรวจสอบการล็อกอินและสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

$title = "จัดการข้อมูลผู้ใช้งาน";
include 'header.php';

// เพิ่มการค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ดึงข้อมูลผู้ใช้งานทั้งหมด
$sql = "SELECT * FROM users WHERE 1=1";
if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR email LIKE ? OR phone LIKE ?)";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

// ถ้ามีการค้นหา bind parameters
if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">จัดการข้อมูลผู้ใช้งาน</h4>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus"></i> เพิ่มผู้ใช้งาน
            </button>
        </div>
        <div class="card-body">
            <!-- เพิ่มฟอร์มค้นหา -->
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="ค้นหาจากชื่อผู้ใช้, อีเมล หรือเบอร์โทร"
                                   value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">ค้นหา</button>
                            <?php if (!empty($search)): ?>
                                <a href="users.php" class="btn btn-secondary">ล้างการค้นหา</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

            <!-- แสดงผลการค้นหา ถ้ามี -->
            <?php if (!empty($search)): ?>
                <div class="alert alert-info">
                    ผลการค้นหาสำหรับ: "<?= htmlspecialchars($search) ?>"
                    (พบ <?= $result->num_rows ?> รายการ)
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>อีเมล</th>
                            <th>เบอร์โทร</th>
                            <th>ที่อยู่</th>
                            <th>สถานะ</th>
                            <th>วันที่สมัคร</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?= str_pad($row['user_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone'] ?: '-') ?></td>
                                <td>
                                    <?php
                                    $address_parts = array_filter([
                                        $row['address'],
                                        $row['subdistrict'],
                                        $row['district'],
                                        $row['province'],
                                        $row['zipcode']
                                    ]);
                                    echo htmlspecialchars(implode(' ', $address_parts)) ?: '-';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 1): ?>
                                        <span class="badge bg-primary">ผู้ดูแลระบบ</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ผู้ใช้งานทั่วไป</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_user.php?id=<?= $row['user_id'] ?>" 
                                           class="btn btn-warning">
                                            แก้ไข
                                        </a>
                                        <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                                            <button type="button" 
                                                    class="btn btn-danger"
                                                    onclick="deleteUser(<?= $row['user_id'] ?>)">
                                                ลบ
                                            </button>
                                            <button type="button" 
                                                    class="btn <?= $row['status'] == 1 ? 'btn-secondary' : 'btn-primary' ?>"
                                                    onclick="changeStatus(<?= $row['user_id'] ?>, <?= $row['status'] ?>)">
                                                <?= $row['status'] == 1 ? 'ลดสิทธิ์' : 'เพิ่มสิทธิ์' ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal เพิ่มผู้ใช้งาน -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มผู้ใช้งาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add_user.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อีเมล</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เบอร์โทร</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-select">
                            <option value="2">ผู้ใช้งานทั่วไป</option>
                            <option value="1">ผู้ดูแลระบบ</option>
                        </select>
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

<script>
function deleteUser(userId) {
    if (confirm('ต้องการลบผู้ใช้งานนี้หรือไม่?')) {
        window.location.href = 'delete_user.php?id=' + userId;
    }
}

function changeStatus(userId, currentStatus) {
    const newStatus = currentStatus == 1 ? 2 : 1;
    const statusText = newStatus == 1 ? 'ผู้ดูแลระบบ' : 'ผู้ใช้งานทั่วไป';
    
    if (confirm(`ต้องการเปลี่ยนสถานะเป็น${statusText}หรือไม่?`)) {
        window.location.href = `change_user_status.php?id=${userId}&status=${newStatus}`;
    }
}
</script>

<?php include 'footer.php'; ?> 