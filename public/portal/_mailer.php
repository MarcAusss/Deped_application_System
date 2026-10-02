<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Build a PHPMailer instance wired to the same SMTP settings Laravel's
 * .env already defines (MAIL_HOST/MAIL_PORT/MAIL_USERNAME/MAIL_PASSWORD),
 * so the portal sends through the exact same mail account.
 */
function portal_mailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = portal_env('MAIL_HOST', '127.0.0.1');
    $mail->Port = (int) portal_env('MAIL_PORT', 587);
    $mail->SMTPAuth = portal_env('MAIL_USERNAME') !== null && portal_env('MAIL_USERNAME') !== '';
    $mail->Username = portal_env('MAIL_USERNAME');
    $mail->Password = portal_env('MAIL_PASSWORD');

    $scheme = portal_env('MAIL_SCHEME') ?: portal_env('MAIL_ENCRYPTION');
    if ($scheme === 'tls' || (int) $mail->Port === 587) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($scheme === 'ssl' || (int) $mail->Port === 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    }

    $mail->CharSet = 'UTF-8';
    $mail->setFrom(portal_env('MAIL_FROM_ADDRESS', 'hello@example.com'), portal_env('MAIL_FROM_NAME', 'SDO Albay CARES'));

    return $mail;
}

/**
 * Mirrors Laravel's "log" mail driver — when MAIL_MAILER=log (the app's
 * current dev-mode setting), don't attempt a real SMTP connection at all;
 * just record what would have been sent, the same way this whole system
 * has been diagnosed against storage/logs/laravel.log all along.
 */
function portal_mail_is_log_mode(): bool
{
    return portal_env('MAIL_MAILER', 'log') === 'log';
}

function portal_log_mail(string $from, string $to, string $subject, string $body): void
{
    $entry = sprintf(
        "[%s] portal.MAIL: From: %s\nTo: %s\nSubject: %s\n\n%s\n\n%s\n",
        date('Y-m-d H:i:s'),
        $from,
        $to,
        $subject,
        $body,
        str_repeat('-', 60)
    );

    @file_put_contents(PORTAL_ROOT.'/storage/logs/laravel.log', $entry, FILE_APPEND);
}

/**
 * Sends the password-reset code, from the dedicated cares.reset@ address —
 * matches App\Notifications\ApplicantPasswordResetCodeNotification exactly.
 */
function send_reset_code(string $toEmail, string $toName, string $code): bool
{
    $fromAddress = 'cares.reset@depedalbay.com';
    $subject = 'Your Password Reset Code';
    $safeName = portal_e($toName);

    $body = <<<HTML
        <p>Hello {$safeName},</p>
        <p>Use the verification code below to reset your SDO Albay CARES account password.</p>
        <div style="font-size:28px;font-weight:700;letter-spacing:8px;text-align:center;margin:24px 0;color:#123B6D;">{$code}</div>
        <p>This code will expire in 60 minutes.</p>
        <p>If you did not request a password reset, no further action is required.</p>
        HTML;

    if (portal_mail_is_log_mode()) {
        portal_log_mail($fromAddress, $toEmail, $subject, $body);

        return true;
    }

    try {
        $mail = portal_mailer();
        $mail->setFrom($fromAddress, 'SDO Albay CARES - Password Reset');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = "Your SDO Albay CARES password reset code is: {$code}\nThis code expires in 60 minutes.";

        return $mail->send();
    } catch (\Throwable $e) {
        error_log('[portal] send_reset_code failed: '.$e->getMessage());

        return false;
    }
}

/**
 * Application-received confirmation email — matches
 * resources/views/emails/application-submitted.blade.php.
 */
function send_application_submitted(string $toEmail, string $fullName, string $jobTitle, string $submittedAt): bool
{
    $fromAddress = portal_env('MAIL_FROM_ADDRESS', 'hello@example.com');
    $subject = 'Application Received - '.$jobTitle;

    $safeName = portal_e($fullName);
    $safeTitle = portal_e($jobTitle);
    $safeDate = portal_e($submittedAt);

    $body = <<<HTML
        <div style="font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
            <div style="background-color:#123B6D;padding:24px 32px;">
                <p style="margin:0;color:#fff;font-size:12px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;">SDO Albay (CARES)</p>
                <p style="margin:4px 0 0;color:#fff;font-size:18px;font-weight:bold;">Application Received</p>
            </div>
            <div style="padding:32px;">
                <p>Dear {$safeName},</p>
                <p>Thank you for applying. We have successfully received your application for the position below.</p>
                <div style="background-color:#EAF2F8;border-radius:8px;padding:16px 20px;margin:0 0 20px;">
                    <p style="margin:0 0 4px;font-size:11px;font-weight:bold;text-transform:uppercase;color:#1D4E89;">Position Applied</p>
                    <p style="margin:0 0 12px;font-size:16px;font-weight:bold;color:#123B6D;">{$safeTitle}</p>
                    <p style="margin:0 0 4px;font-size:11px;font-weight:bold;text-transform:uppercase;color:#1D4E89;">Date Submitted</p>
                    <p style="margin:0;font-size:16px;font-weight:bold;color:#123B6D;">{$safeDate}</p>
                </div>
                <p>Your application is now marked as <strong>Pending</strong> and will be reviewed by our evaluation team. You will be notified of any updates regarding the status of your application.</p>
                <p>Thank you for your interest in joining SDO Albay.</p>
            </div>
        </div>
        HTML;

    if (portal_mail_is_log_mode()) {
        portal_log_mail($fromAddress, $toEmail, $subject, $body);

        return true;
    }

    try {
        $mail = portal_mailer();
        $mail->addAddress($toEmail, $fullName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = "Dear {$fullName}, we have received your application for {$jobTitle} on {$submittedAt}.";

        return $mail->send();
    } catch (\Throwable $e) {
        error_log('[portal] send_application_submitted failed: '.$e->getMessage());

        return false;
    }
}
