<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

function sendOtp($email, $subject, $code, $username, $date, $usage)
{
    $mail = new PHPMailer(true);

    try {
        // ================= SMTP SETTINGS =================
        $mail->isSMTP();
        $mail->Host       = 'cp1.dnspark.in';
        $mail->SMTPAuth   = true;

        // Domain email credentials
        $mail->Username   = 'support@devxjin.site';
        $mail->Password   = 'Devxjin@120'; // 🔐 change immediately

        // SSL (recommended by host)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // ================= EMAIL META =================
        $mail->setFrom('support@devxjin.site', 'DevzON eSports');
        $mail->addAddress($email, $username);

        $mail->isHTML(true);
        $mail->Subject = $subject;

        // ================= TEMPLATE =================
        $templatePath = __DIR__ . "/../email/$usage";

        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found: $usage");
        }

        $templateContent = file_get_contents($templatePath);
        $templateContent = str_replace(
            ['{username}', '{date}', '{code}'],
            [$username, $date, $code],
            $templateContent
        );

        $mail->Body = $templateContent;
        $mail->AltBody = "Hello $username, your OTP is $code";

        // ================= SEND =================
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
