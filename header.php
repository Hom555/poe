<?php
session_start();
include 'condb.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yaz Shop - <?= $title ?? 'หน้าหลัก' ?></title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background-color: #f8f9fa;
            padding: 20px;
            height: 100vh;
        }
        .sidebar a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        .sidebar a:hover {
            background-color: #e9ecef;
            transform: translateX(10px);
        }
        .content {
            padding: 20px;
        }
        .card {
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            border: none;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        .table {
            margin-bottom: 0;
        }
        .btn {
            border-radius: 5px;
        }
        .img-thumbnail {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar">
                <h4 class="text-center mb-4">Yaz</h4>
                <a href="yaz.php">หน้าหลัก</a>
                <a href="sh_product_ad.php">สินค้า</a>
                <a href="type_product.php">ประเภทสินค้า</a>
                <a href="order_detail.php">คำสั่งซื้อ</a>
                <a href="add_product.php">เพิ่มข้อมูลสินค้า</a>
                <a href="type_product.php">จัดการประเภทสินค้า</a>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 content"> 