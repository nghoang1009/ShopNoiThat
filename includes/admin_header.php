<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Quản Trị Hệ Thống' : 'Quản Trị Shop Nội Thất'; ?></title>
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/admin/admin.css'); ?>">
    <script>
        window.BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
</head>
<body>
<div class="admin-wrapper">
