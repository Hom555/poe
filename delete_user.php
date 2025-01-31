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

// Delete user logic
if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);

    $delete_sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        header("Location: users_list.php");
        exit;
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>