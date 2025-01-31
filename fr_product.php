<?php
include 'condb.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มสินค้า</title>
    <!-- Bootstrap -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-sm-10 mx-auto">
                <div class="alert alert-primary h5 text-center mb-4 mt-4" role="alert">เพิ่มข้อมูลสินค้า</div>

                <form name="forml" method="post" action="insert_product.php" enctype="multipart/form-data">
                    <label>ชื่อสินค้า</label>
                    <input type="text" name="pname" class="form-control" placeholder="ชื่อสินค้า" required> <br>

                    <label>ประเภทสินค้า</label>
                    <select class="form-select" name="typeID" required>
                        <option value="" selected disabled>เลือกประเภทสินค้า</option>
                        <?php
                        $sql = "SELECT * FROM type ORDER BY type_name";
                        $result = mysqli_query($conn, $sql);
                        while ($row = mysqli_fetch_array($result)) {
                            echo "<option value='{$row['type_id']}'>{$row['type_name']}</option>";
                        }
                        ?>
                    </select> <br>

                    <label>ราคา</label>
                    <input type="number" name="price" class="form-control" placeholder="ราคา" required> <br>

                    <label>จำนวน</label>
                    <input type="number" name="num" class="form-control" placeholder="จำนวน" required> <br>

                    <label>รูปสินค้า</label>
                    <input type="file" name="file1" class="form-control" required> <br>

                    <button type="submit" class="btn btn-success">เพิ่มสินค้า</button>
                    <input class="btn btn-danger" type="reset" value="ลบข้อมูล">
                    <a class="btn btn-primary" href="show_product.php" role="button">ข้อมูลสินค้า</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
