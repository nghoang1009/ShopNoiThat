<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Quản Trị Hệ Thống' : 'Quản Trị Shop Nội Thất'; ?></title>
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/admin/admin.css'); ?>">
    <script>
        window.BASE_URL = "<?php echo BASE_URL; ?>";
        window.CSRF_TOKEN = "<?php echo csrf_token(); ?>";
        (function () {
            var nativeFetch = window.fetch;
            if (!nativeFetch) return;
            window.fetch = function (input, init) {
                init = init || {};
                var method = (init.method || 'GET').toUpperCase();
                if (method !== 'GET' && method !== 'HEAD') {
                    var headers = new Headers(init.headers || {});
                    headers.set('X-Requested-With', 'XMLHttpRequest');
                    headers.set('X-CSRF-Token', window.CSRF_TOKEN);
                    init.headers = headers;
                }
                return nativeFetch.call(this, input, init);
            };
        })();
    </script>
</head>
<body>
<div class="admin-wrapper">
