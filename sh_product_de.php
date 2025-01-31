<?php include 'condb.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyShop</title>
    <!-- Bootstrap CSS -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include 'condb.php'; ?>    

<div class="container mt-6">
  <div class="row">
<?php
$ids = $_GET['id'];
$sql = "SELECT * FROM product, type WHERE product.type_id = type.type_id AND product.po_id = '$ids'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($result);          
?>

    <div class="col-md-4">
        <img src="img/<?=$row['image']?>" width="350px" class="mt-5 p-2 my-2 border" />
    </div>
    <div class="col-md-6">
        ID: <?=$row['po_id']?> <br>
        <h5 class="text-success"><?=$row['po_name']?></h5>
        ประเภทสินค้า: <?=$row['type_name']?> <br>
        ราคา: <b class="text-danger"><?=$row['price']?></b> บาท <br>
        คงเหลือ: <b class="text-info"><?=$row['amount']?></b> ชิ้น <br>
        <?php if ($row['amount'] > 0) { ?>
            <a class="btn btn-outline-success mt-2" href="order.php?id=<?=$row['po_id']?>">ซื้อ</a>
        <?php } else { ?>
            <button class="btn btn-outline-secondary mt-2" disabled>สินค้าหมด</button>
        <?php } ?>
    </div>
  </div>
</div>

<?php mysqli_close($conn); ?>
</body>
</html>
