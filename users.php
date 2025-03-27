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
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

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
                            <th>ชื่อ-นามสกุล</th>
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
                                <td><?= htmlspecialchars($row['name'] ?: '-') ?></td>
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
                                    <?php elseif ($row['status'] == 0): ?>
                                        <span class="badge bg-danger">ระงับ</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ผู้ใช้งานทั่วไป</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_user.php?id=<?= $row['user_id'] ?>" 
                                           class="btn btn-warning btn-sm action-btn"
                                           data-bs-toggle="tooltip" 
                                           title="แก้ไขข้อมูล">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm action-btn"
                                                    onclick="deleteUser(<?= $row['user_id'] ?>)"
                                                    data-bs-toggle="tooltip" 
                                                    title="ลบผู้ใช้งาน">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <select class="form-select form-select-sm status-select" 
                                                    onchange="changeStatus(<?= $row['user_id'] ?>, this.value)"
                                                    style="min-width: 160px;"
                                                    data-bs-toggle="tooltip" 
                                                    title="เปลี่ยนสถานะผู้ใช้">
                                                <option value="2" <?= $row['status'] == 2 ? 'selected' : '' ?>>ผู้ใช้งานทั่วไป</option>
                                                <option value="1" <?= $row['status'] == 1 ? 'selected' : '' ?>>ผู้ดูแลระบบ</option>
                                                <option value="0" <?= $row['status'] == 0 ? 'selected' : '' ?>>ระงับการใช้งาน</option>
                                            </select>
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
                        <label class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" name="name" class="form-control">
                    </div>
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
                        <label class="form-label">ที่อยู่</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ตำบล/แขวง</label>
                        <input type="text" name="subdistrict" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อำเภอ/เขต</label>
                        <input type="text" name="district" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">จังหวัด</label>
                        <input type="text" name="province" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รหัสไปรษณีย์</label>
                        <input type="text" name="zipcode" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-select">
                            <option value="2">ผู้ใช้งานทั่วไป</option>
                            <option value="1">ผู้ดูแลระบบ</option>
                            <option value="0">ระงับ</option>
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

function changeStatus(userId, newStatus) {
    let statusText = {
        '0': 'ระงับ',
        '1': 'ผู้ดูแลระบบ',
        '2': 'ผู้ใช้งานทั่วไป'
    }[newStatus];
    
    if (confirm(`ต้องการเปลี่ยนสถานะเป็น${statusText}หรือไม่?`)) {
        window.location.href = `change_user_status.php?id=${userId}&status=${newStatus}`;
    } else {
        // ถ้าไม่ยืนยัน ให้รีโหลดหน้าเพื่อกลับไปสถานะเดิม
        window.location.reload();
    }
}

// เพิ่ม Tooltip
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>

<style>
:root {
    --primary-color: #4361ee;
    --primary-hover: #3a56d4;
    --secondary-color: #2d3748;
    --warning-color: #f59e0b;
    --warning-hover: #d97706;
    --danger-color: #ef4444;
    --danger-hover: #dc2626;
    --success-color: #10b981;
    --success-hover: #059669;
    --background-color: #f8fafc;
    --card-background: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
}

body {
    background-color: var(--background-color);
}

/* ปรับแต่งการ์ด */
.card {
    background-color: var(--card-background);
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
    border: none;
}

.card-header {
    background-color: var(--card-background);
    border-bottom: 1px solid var(--border-color);
    padding: 1.25rem;
}

.card-header h4 {
    color: var(--text-primary);
    font-weight: 600;
}

/* ปรับแต่งปุ่มจัดการ */
.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
}

.action-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s ease;
    border: none;
}

.action-btn.btn-warning {
    background-color: var(--warning-color);
    color: white;
}

.action-btn.btn-warning:hover {
    background-color: var(--warning-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}

.action-btn.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.action-btn.btn-danger:hover {
    background-color: var(--danger-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
}

.action-btn i {
    margin: 0;
    font-size: 14px;
}

/* ปรับแต่ง dropdown status */
.status-select {
    min-width: 160px !important;
    height: 32px;
    padding: 4px 28px 4px 12px;
    font-size: 13px;
    border-radius: 6px;
    background-position: right 8px center;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    transition: all 0.2s ease;
    background-color: white;
    cursor: pointer;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
}

.status-select:hover {
    border-color: var(--primary-color);
    background-color: var(--background-color);
}

.status-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
    outline: none;
}

.status-select option {
    padding: 8px;
    font-size: 13px;
    background-color: white;
    color: var(--text-primary);
}

/* ปรับแต่งตาราง */
.table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
    background-color: var(--card-background);
}

.table th {
    background-color: var(--background-color);
    color: var(--text-primary);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    padding: 1rem;
}

.table td {
    vertical-align: middle;
    font-size: 14px;
    color: #212529;(--text-secondary);
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.table tr:hover {
    background-color: rgba(67, 97, 238, 0.05);
}

/* ปรับแต่ง badge สถานะ */
.badge {
    padding: 6px 12px;
    font-weight: 500;
    letter-spacing: 0.3px;
    border-radius: 6px;
}

.bg-primary {
    background-color: var(--primary-color) !important;
}

.bg-danger {
    background-color: var(--danger-color) !important;
}

.bg-secondary {
    background-color: var(--secondary-color) !important;
}

/* ปรับแต่งปุ่มเพิ่มผู้ใช้งาน */
.btn-primary {
    background-color: var(--primary-color);
    border: none;
    padding: 8px 16px;
    font-weight: 500;
    letter-spacing: 0.3px;
    transition: all 0.2s ease;
    border-radius: 6px;
}

.btn-primary:hover {
    background-color: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
}

/* ปรับแต่งฟอร์มค้นหา */
.input-group {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border-radius: 8px;
    overflow: hidden;
}

.input-group .form-control {
    border: 1px solid var(--border-color);
    padding: 8px 16px;
    font-size: 14px;
    color: var(--text-primary);
}

.input-group .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
}

.input-group .btn {
    padding: 8px 16px;
    font-weight: 500;
}

/* ปรับแต่ง Alert */
.alert {
    border-radius: 8px;
    border: none;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.alert-success {
    background-color: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
}

.alert-danger {
    background-color: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
}

.alert-info {
    background-color: rgba(67, 97, 238, 0.1);
    color: var(--primary-color);
}

.alert-dismissible .btn-close {
    padding: 1.25rem;
}

/* Modal styling */
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.modal-header {
    background-color: var(--background-color);
    border-bottom: 1px solid var(--border-color);
    border-radius: 12px 12px 0 0;
    padding: 1.25rem;
}

.modal-title {
    color: var(--text-primary);
    font-weight: 600;
}

.modal-body {
    padding: 1.25rem;
}

.form-label {
    color: var(--text-primary);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 2px 12px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
}

.modal-footer {
    border-top: 1px solid var(--border-color);
    padding: 1.25rem;
}

.btn-secondary {
    background-color: var(--secondary-color);
    border: none;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background-color: #1a202c;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(45, 55, 72, 0.2);
}

/* Tooltip custom styling */
.tooltip .tooltip-inner {
    background-color: var(--secondary-color);
    color: white;
    border-radius: 4px;
    font-size: 12px;
    padding: 6px 10px;
}

.bs-tooltip-top .tooltip-arrow::before {
    border-top-color: var(--secondary-color);
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: var(--text-secondary);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--text-primary);
}

/* ปรับแต่งความกว้างคอลัมน์จัดการ */
.table th:last-child,
.table td:last-child {
    min-width: 280px;
    width: auto;
}

/* ปรับแต่งการจัดวางปุ่มในคอลัมน์จัดการ */
.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: nowrap;
}
</style>

<?php include 'footer.php'; ?> 