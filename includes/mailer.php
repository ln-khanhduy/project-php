<?php
declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if (!defined('MAIL_FROM_EMAIL')) {
    throw new RuntimeException('MAIL_FROM_EMAIL không tồn tại');
}

function send_html_email(string $to, string $subject, string $body): bool {
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return send_html_email_with_phpmailer($to, $subject, $body);
    }

    return send_html_email_via_mail($to, $subject, $body);
}

function send_html_email_via_mail(string $to, string $subject, string $body): bool {
    $cleanTo = filter_var($to, FILTER_VALIDATE_EMAIL);
    if (!$cleanTo) {
        return false;
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM_EMAIL . "\r\n";

    return (bool) mail($cleanTo, $subject, $body, $headers);
}

function send_html_email_with_phpmailer(string $to, string $subject, string $body): bool {
    $phpMailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
    $mail = new $phpMailerClass(true);
    try {
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        if (defined('SMTP_USE_SMTP') && SMTP_USE_SMTP) {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAutoTLS = false;
            $mail->SMTPKeepAlive = true;//Giữ kết nối gữi nhanh hơn từ lần gữi thứ 2
            if (!empty(SMTP_ENCRYPTION)) {
                $mail->SMTPSecure = SMTP_ENCRYPTION;
            }
            $mail->SMTPAuth = !empty(SMTP_USERNAME) && !empty(SMTP_PASSWORD);
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            if (defined('SMTP_TIMEOUT')) {
                $mail->Timeout = SMTP_TIMEOUT;
            }
        }

        $mail->send();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

function send_otp_email(string $recipient, string $otpCode): bool {
    $body = sprintf(
        '<p>Xin chào,</p><p>Mã xác thực đặt lại mật khẩu của bạn là <strong>%s</strong>.</p><p>Mã có hiệu lực trong %d phút. Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email.</p><p>Trân trọng,<br>%s</p>',
        htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8'),
        ceil(OTP_EXPIRE_SECONDS / 60),
        htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8')
    );

    return send_html_email($recipient, OTP_EMAIL_SUBJECT, $body);
}