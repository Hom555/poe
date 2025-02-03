<?php
session_start();
include 'condb.php';

// ตรวจสอบการล็อกอินและสิทธิ์แอดมิน
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit();
}

$title = "แดชบอร์ดผู้ดูแลระบบ";
include 'header.php';
?>


<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">แดชบอร์ดผู้ดูแลระบบ</h1>
    </div>

    <!-- สถิติภาพรวม -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                จำนวนสินค้าทั้งหมด
                            </div>
                            <?php
                            $sql = "SELECT COUNT(*) as total FROM product";
                            $result = mysqli_query($conn, $sql);
                            $row = mysqli_fetch_assoc($result);
                            ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $row['total'] ?> รายการ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                จำนวนผู้ใช้งาน
                            </div>
                            <?php
                            $sql = "SELECT COUNT(*) as total FROM users WHERE status = 2";
                            $result = mysqli_query($conn, $sql);
                            $row = mysqli_fetch_assoc($result);
                            ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $row['total'] ?> คน</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                สินค้าใกล้หมด
                            </div>
                            <?php
                            $sql = "SELECT COUNT(*) as total FROM product WHERE amount <= 5";
                            $result = mysqli_query($conn, $sql);
                            $row = mysqli_fetch_assoc($result);
                            ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $row['total'] ?> รายการ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- รายการสินค้าใกล้หมด -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">รายการสินค้าใกล้หมด</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>รหัสสินค้า</th>
                            <th>ชื่อสินค้า</th>
                            <th>ประเภท</th>
                            <th>ราคา</th>
                            <th>จำนวนคงเหลือ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT p.*, t.type_name 
                                FROM product p 
                                LEFT JOIN type t ON p.type_id = t.type_id 
                                WHERE p.amount <= 5 
                                ORDER BY p.amount ASC 
                                LIMIT 5";
                        $result = mysqli_query($conn, $sql);
                        while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <tr>
                                <td><?= str_pad($row['po_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td><?= $row['po_name'] ?></td>
                                <td><?= $row['type_name'] ?></td>
                                <td><?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <span class="badge bg-danger"><?= $row['amount'] ?> ชิ้น</span>
                                </td>
                                <td>
                                    <a href="edit_product.php?id=<?= $row['po_id'] ?>" 
                                       class="btn btn-warning btn-sm">แก้ไข</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>