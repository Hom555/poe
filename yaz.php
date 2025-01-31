<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "12345678";
$database = "dro";

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Change status logic
if (isset($_GET['change_status_id'])) {
    $user_id = intval($_GET['change_status_id']);

    // Fetch the current status
    $status_query = "SELECT status FROM users WHERE user_id = $user_id";
    $status_result = $conn->query($status_query);
    if ($status_result && $status_result->num_rows > 0) {
        $current_status = $status_result->fetch_assoc()['status'];

        // Toggle the status
        $new_status = $current_status == 1 ? 0 : 1;

        // Update the status in the database
        $update_status_query = "UPDATE users SET status = $new_status WHERE user_id = $user_id";
        $conn->query($update_status_query);
    }

    // Redirect back to the page
    header("Location: user_list.php");
    exit;
}

// Fetch data from users table
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด</title>
    <!-- Bootstrap CSS -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .sidebar {
            height: 100vh;
            background-color: #f8f9fa;
            padding: 20px 10px;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            text-align: center;
            padding: 12px;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #007bff;
            color: white;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        .status-active {
            color: green;
            font-weight: bold;
        }

        .status-inactive {
            color: red;
            font-weight: bold;
        }

        .btn {
            padding: 8px 12px;
            text-decoration: none;
            color: white;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-edit {
            background-color: #ffc107;
        }

        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-delete {
            background-color: #dc3545;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .btn-status {
            background-color: #17a2b8;
        }

        .btn-status:hover {
            background-color: #138496;
        }

        .no-data {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-top: 20px;
        }
        .sidebar a {
            color: #333;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: background-color 0.2s ease-in-out;
        }
        .sidebar a:hover {
            background-color: #007bff;
            color: white;
        }
        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>
<body>
<?php 
$title = "รายการสินค้า";
include 'header.php';
?>
            
            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2">ยินดีต้อนรับ</h1>
                </div>



    <h1>ข้อมูลผู้ใช้งาน</h1>
    <?php if ($result->num_rows > 0): ?>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ชื่อผู้ใช้งาน</th>
                    <th>อีเมล</th>
                    <th>เบอร์โทรศัพท์</th>
                    <th>สถานะ</th>
                    <th>วันที่สมัคร</th>
                    <th>การดำเนินการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone'] ?: 'N/A'); ?></td>
                        <td>
                            <?php
                            if ($row['status'] == 1) {
                                echo "<span class='status-active'>ผู้ดูแลระบบ</span>";
                            } else {
                                echo "<span class='status-inactive'>ผู้ใช้งานทั่วไป</span>";
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <a href="edit_user.php?user_id=<?php echo $row['user_id']; ?>" class="btn btn-edit">แก้ไข</a>
                            <a href="delete_user.php?user_id=<?php echo $row['user_id']; ?>" class="btn btn-delete" onclick="return confirm('ยืนยันการลบผู้ใช้งาน?');">ลบ</a>
                            <a href="user_list.php?change_status_id=<?php echo $row['user_id']; ?>" class="btn btn-status">
                                <?php echo $row['status'] == 1 ? "เปลี่ยนเป็นผู้ใช้งานทั่วไป" : "เปลี่ยนเป็นผู้ดูแลระบบ"; ?>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">ไม่มีข้อมูลผู้ใช้งาน</p>
    <?php endif; ?>

   


    <!-- Bootstrap JS -->
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>