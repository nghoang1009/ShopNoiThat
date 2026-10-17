<?php
// Sao chép file này thành config/mail.local.php (đã được .gitignore) rồi điền thông tin thật.
// Ví dụ Gmail: bật xác minh 2 bước và tạo "Mật khẩu ứng dụng" (App Password) cho MAIL_PASS.
return [
    'driver'     => 'smtp',               // 'log' = chỉ ghi vào storage/mail.log (mặc định khi dev)
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',                // 'tls' = STARTTLS (587), 'ssl' = SMTPS (465)
    'user'       => 'your-account@gmail.com',
    'pass'       => 'your-app-password',
    'from'       => 'your-account@gmail.com',
    'from_name'  => 'Shop Nội Thất',
    'app_url'    => 'http://localhost/ShopNoiThat',   // URL công khai của website, dùng trong link email
];
