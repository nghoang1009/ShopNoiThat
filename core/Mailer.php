<?php
/**
 * Core Mailer - Gửi email qua 3 driver:
 *  - log  : ghi nội dung vào storage/mail.log (mặc định khi dev, không gửi thật)
 *  - smtp : SMTP thuần qua socket (STARTTLS / SSL, AUTH LOGIN) - dùng được với Gmail, Mailtrap...
 *  - mail : hàm mail() của PHP (cần cấu hình sendmail trên server)
 */
class Mailer {
    private $socket = null;
    public string $lastError = '';

    /**
     * Gửi 1 email HTML (kèm bản text). Trả về true nếu gửi / ghi log thành công.
     */
    public static function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool {
        $mailer = new self();
        return $mailer->deliver($to, $subject, $htmlBody, $textBody ?? trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>#i', "\n", $htmlBody)))));
    }

    public function deliver(string $to, string $subject, string $html, string $text): bool {
        $to = $this->clean($to);
        $subject = $this->clean($subject);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Địa chỉ người nhận không hợp lệ.';
            return false;
        }

        try {
            switch (MAIL_DRIVER) {
                case 'smtp':
                    return $this->viaSmtp($to, $subject, $html, $text);
                case 'mail':
                    return $this->viaMail($to, $subject, $html, $text);
                default:
                    return $this->viaLog($to, $subject, $text);
            }
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log('[Mailer] ' . $e->getMessage());
            return false;
        } finally {
            if (is_resource($this->socket)) {
                @fclose($this->socket);
            }
        }
    }

    /** Tên miền dùng cho EHLO / Message-ID (chỉ giữ ký tự hợp lệ của hostname) */
    private function hostName(): string {
        $host = preg_replace('/[^A-Za-z0-9.\-]/', '', (string)parse_url(APP_URL, PHP_URL_HOST));
        return $host !== '' ? $host : 'localhost';
    }

    /** Loại bỏ ký tự xuống dòng để chống tiêm header email */
    private function clean(string $value): string {
        return trim(str_replace(["\r", "\n", "\0"], '', $value));
    }

    private function encodeHeader(string $value): string {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    /** Dựng nội dung MIME multipart/alternative (text + html) */
    private function buildMessage(string $to, string $subject, string $html, string $text): array {
        $boundary = 'b' . bin2hex(random_bytes(12));
        $fromName = $this->clean(MAIL_FROM_NAME);
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $this->encodeHeader($fromName) . ' <' . $this->clean(MAIL_FROM) . '>',
            'To: <' . $to . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . $this->hostName() . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($text))
              . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($html))
              . "--$boundary--\r\n";
        return [$headers, $body];
    }

    private function viaLog(string $to, string $subject, string $text): bool {
        $dir = __DIR__ . '/../storage';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $entry = "==== " . date('Y-m-d H:i:s') . " ====\nTo: $to\nSubject: $subject\n\n$text\n\n";
        if (@file_put_contents($dir . '/mail.log', $entry, FILE_APPEND | LOCK_EX) === false) {
            $this->lastError = 'Không ghi được storage/mail.log';
            return false;
        }
        return true;
    }

    private function viaMail(string $to, string $subject, string $html, string $text): bool {
        [$headers, $body] = $this->buildMessage($to, $subject, $html, $text);
        // mail() tự thêm To/Subject, nên bỏ khỏi danh sách header
        $headers = array_values(array_filter($headers, fn($h) => !str_starts_with($h, 'To:') && !str_starts_with($h, 'Subject:')));
        $ok = @mail($to, $this->encodeHeader($subject), $body, implode("\r\n", $headers));
        if (!$ok) {
            $this->lastError = 'Hàm mail() của PHP thất bại (kiểm tra cấu hình sendmail).';
        }
        return $ok;
    }

    // ---------------- SMTP ----------------

    private function readReply(): array {
        $code = 0;
        $text = '';
        while (($line = fgets($this->socket, 1024)) !== false) {
            $text .= $line;
            $code = (int)substr($line, 0, 3);
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if ($text === '') {
            throw new RuntimeException('SMTP: máy chủ không phản hồi (timeout).');
        }
        return [$code, trim($text)];
    }

    private function command(?string $cmd, array $expect): string {
        if ($cmd !== null) {
            fwrite($this->socket, $cmd . "\r\n");
        }
        [$code, $text] = $this->readReply();
        if (!in_array($code, $expect, true)) {
            $shown = (stripos((string)$cmd, 'AUTH') === 0 || (strlen((string)$cmd) > 0 && !preg_match('/^[A-Z]{4}/', (string)$cmd))) ? '***' : $cmd;
            throw new RuntimeException("SMTP lỗi {$code} sau lệnh [{$shown}]: {$text}");
        }
        return $text;
    }

    private function viaSmtp(string $to, string $subject, string $html, string $text): bool {
        if (MAIL_HOST === '') {
            throw new RuntimeException('Chưa cấu hình MAIL_HOST cho driver smtp.');
        }
        $scheme = MAIL_ENCRYPTION === 'ssl' ? 'ssl://' : 'tcp://';
        $this->socket = @stream_socket_client($scheme . MAIL_HOST . ':' . MAIL_PORT, $errno, $errstr, 10);
        if (!$this->socket) {
            throw new RuntimeException("Không kết nối được SMTP {$errstr} ({$errno}).");
        }
        stream_set_timeout($this->socket, 15);

        $this->command(null, [220]);
        $this->command('EHLO ' . $this->hostName(), [250]);

        if (MAIL_ENCRYPTION === 'tls') {
            $this->command('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Không bật được mã hóa TLS với máy chủ SMTP.');
            }
            $this->command('EHLO ' . $this->hostName(), [250]);
        }

        if (MAIL_USER !== '') {
            $this->command('AUTH LOGIN', [334]);
            $this->command(base64_encode(MAIL_USER), [334]);
            $this->command(base64_encode(MAIL_PASS), [235]);
        }

        $this->command('MAIL FROM:<' . $this->clean(MAIL_FROM) . '>', [250]);
        $this->command('RCPT TO:<' . $to . '>', [250, 251]);
        $this->command('DATA', [354]);

        [$headers, $body] = $this->buildMessage($to, $subject, $html, $text);
        $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        $data = preg_replace('/^\./m', '..', $data); // dot-stuffing
        fwrite($this->socket, $data . "\r\n.\r\n");
        $this->command(null, [250]);
        $this->command('QUIT', [221]);
        return true;
    }
}
