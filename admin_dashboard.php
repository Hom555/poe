<?php
session_start();

if (!isset($_SESSION['admin_username'])) {
    header("Location: admin_login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "12345678";
$dbname = "dro";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// ดึงข้อมูลสมาชิก
$sql_users = "SELECT * FROM users";
$result_users = $conn->query($sql_users);

// ดึงข้อมูลสินค้า
$sql_products = "SELECT * FROM products";
$result_products = $conn->query($sql_products);

// ดึงข้อมูลช่างตัดผม
$sql_barbers = "SELECT * FROM barbers";
$result_barbers = $conn->query($sql_barbers);

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดผู้ดูแลระบบ</title>
    <link rel="stylesheet" href="styleadmin.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php">หน้าแรก</a>
            <a href="admin_dashboard.php">แดชบอร์ด</a>
            <a href="logout.php">ออกจากระบบ</a>
        </nav>
    </header>
    <main>
        <h2>แดชบอร์ดผู้ดูแลระบบ</h2>
        <p>สวัสดีแอดมิน <?php echo $_SESSION['admin_username']; ?>!</p>

        <div class="menu">
            <button onclick="window.location.href='time.php'">จัดการช่วงเวลา</button>
            <button onclick="window.location.href='manage_users.php'">จัดการผู้ใช้</button>
            <button onclick="window.location.href='manage_products.php'">จัดการสินค้า</button>
            <button onclick="window.location.href='manage_barbers.php'">จัดการช่างตัดผม</button>
            <button onclick="window.location.href='manage_appointments.php'">จัดการการจองคิว</button>
            <button onclick="window.location.href='#'">ประวัติการซื้อสินค้า</button>
            <button onclick="window.location.href='#'">จัดการซื้อสินค้า</button>
            <button onclick="window.location.href='#'">ประวัติการจอง</button>
        </div>

        <section>
            <h3>จัดการสมาชิก</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>อีเมล</th>
                    <th>การดำเนินการ</th>
                </tr>
                <?php
                if ($result_users->num_rows > 0) {
                    while ($row = $result_users->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['username']}</td>";
                        echo "<td>{$row['email']}</td>";
                        echo "<td>
                            <form action='server_admin.php' method='post'>
                                <input type='hidden' name='action' value='edit_user'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <input type='text' name='username' value='{$row['username']}'>
                                <input type='text' name='email' value='{$row['email']}'>
                                <button type='submit'>แก้ไข</button>
                            </form>
                            <form action='server_admin.php' method='post'>
                                <input type='hidden' name='action' value='delete_user'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <button type='submit'>ลบ</button>
                            </form>
                        </td>";
                        echo "</tr>";
                    }
                }
                ?>
            </table>
            <h4>เพิ่มสมาชิก</h4>
            <form action="server_admin.php" method="post" class="form-inline">
                <input type="hidden" name="action" value="add_user">
                <label for="username">ชื่อผู้ใช้:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required>
                <label for="email">อีเมล:</label>
                <input type="email" id="email" name="email" required>
                <button type="submit">เพิ่มสมาชิก</button>
            </form>
        </section>

        <section>
            <h3>จัดการสินค้า</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>ชื่อ</th>
                    <th>คำอธิบาย</th>
                    <th>ราคา</th>
                    <th>รูปภาพ</th>
                    <th>การดำเนินการ</th>
                </tr>
                <?php
                if ($result_products->num_rows > 0) {
                    while ($row = $result_products->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['name']}</td>";
                        echo "<td>{$row['description']}</td>";
                        echo "<td>{$row['price']}</td>";
                        echo "<td><img src='images/{$row['image']}' alt='{$row['name']}' width='50'></td>";
                        echo "<td>
                            <form action='server_admin.php' method='post' enctype='multipart/form-data'>
                                <input type='hidden' name='action' value='edit_product'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <input type='text' name='name' value='{$row['name']}'>
                                <textarea name='description'>{$row['description']}</textarea>
                                <input type='text' name='price' value='{$row['price']}'>
                                <input type='file' name='image'>
                                <button type='submit'>แก้ไข</button>
                            </form>
                            <form action='server_admin.php' method='post'>
                                <input type='hidden' name='action' value='delete_product'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <button type='submit'>ลบ</button>
                            </form>
                        </td>";
                        echo "</tr>";
                    }
                }
                ?>
            </table>
            <h4>เพิ่มสินค้า</h4>
            <form action="server_admin.php" method="post" class="form-inline" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                <label for="name">ชื่อสินค้า:</label>
                <input type="text" id="name" name="name" required>
                <label for="description">คำอธิบาย:</label>
                <textarea id="description" name="description" required></textarea>
                <label for="price">ราคา:</label>
                <input type="text" id="price" name="price" required>
                <label for="image">รูปภาพ:</label>
                <input type="file" id="image" name="image" required>
                <button type="submit">เพิ่มสินค้า</button>
            </form>
        </section>

        <section>
            <h3>จัดการช่างตัดผม</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>ชื่อ</th>
                    <th>รายละเอียด</th>
                    <th>ข้อมูลติดต่อ</th>
                    <th>รูปช่างตัดผม</th>
                    <th>การดำเนินการ</th>
                </tr>
                <?php
                if ($result_barbers->num_rows > 0) {
                    while ($row = $result_barbers->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['name']}</td>";
                        echo "<td>{$row['description']}</td>";
                        echo "<td>{$row['contact']}</td>";
                        echo "<td><img src='images/{$row['photo']}' alt='{$row['name']}' width='100'></td>";
                        echo "<td>
                            <form action='server_admin.php' method='post' enctype='multipart/form-data'>
                                <input type='hidden' name='action' value='edit_barber'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <label for='name_{$row['id']}'>ชื่อ:</label>
                                <input type='text' id='name_{$row['id']}' name='name' value='{$row['name']}' required>
                                <label for='description_{$row['id']}'>รายละเอียด:</label>
                                <input type='text' id='description_{$row['id']}' name='description' value='{$row['description']}' required>
                                <label for='contact_{$row['id']}'>ข้อมูลติดต่อ:</label>
                                <input type='text' id='contact_{$row['id']}' name='contact' value='{$row['contact']}' required>
                                <label for='photo_{$row['id']}'>รูปช่างตัดผม:</label>
                                <input type='file' id='photo_{$row['id']}' name='photo'>
                                <button type='submit'>แก้ไข</button>
                            </form>
                            <form action='server_admin.php' method='post'>
                                <input type='hidden' name='action' value='delete_barber'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <button type='submit'>ลบ</button>
                            </form>
                        </td>";
                        echo "</tr>";
                    }
                }
                ?>
            </table>
            <h4>เพิ่มช่างตัดผม</h4>
            <form action="server_admin.php" method="post" enctype="multipart/form-data" class="form-inline">
                <input type="hidden" name="action" value="add_barber">
                <label for="name">ชื่อ:</label>
                <input type="text" id="name" name="name" required>
                <label for="description">รายละเอียด:</label>
                <input type="text" id="description" name="description" required>
                <label for="contact">ข้อมูลติดต่อ:</label>
                <input type="text" id="contact" name="contact" required>
                <label for="photo">รูปช่างตัดผม:</label>
                <input type="file" id="photo" name="photo" required>
                <button type="submit">เพิ่มช่างตัดผม</button>
            </form>
        </section>
    </main>
</body>
</html>
