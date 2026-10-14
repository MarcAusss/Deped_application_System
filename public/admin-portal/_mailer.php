<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

/**
 * Reuses Phase 1's SMTP wiring (portal_mailer()/portal_mail_is_log_mode()/
 * portal_log_mail()) directly — those functions only call portal_env(),
 * which admin-portal/_boot.php already aliases to admin_env(), so no admin-
 * specific copy of the SMTP setup is needed.
 */
require PORTAL_ROOT.'/public/portal/_mailer.php';

/**
 * Builds the four QS comparison rows shared by both the Qualified and
 * Disqualified emails — mirrors the $qsRows array built inline in
 * resources/views/emails/application-qualified.blade.php and
 * application-disqualified.blade.php.
 *
 * @param array<int, object> $educations
 * @param array<int, object> $experiences
 * @param array<int, object> $trainings
 * @param array<int, object> $eligibilities
 * @return array<int, array{label: string, required: string, yours: string, met: ?bool}>
 */
function admin_build_qs_rows(object $job, ?object $evaluation, array $educations, array $experiences, array $trainings, array $eligibilities): array
{
    return [
        [
            'label' => 'Education',
            'required' => $job->education_requirement ?: '—',
            'yours' => eval_applicant_bachelors_degree($educations),
            'met' => $evaluation ? eval_bool($evaluation->qs_education_met) : null,
        ],
        [
            'label' => 'Experience',
            'required' => $job->experience_requirement ?: '—',
            'yours' => eval_applicant_years_of_experience($experiences),
            'met' => $evaluation ? eval_bool($evaluation->qs_experience_met) : null,
        ],
        [
            'label' => 'Training',
            'required' => $job->training_requirement ?: '—',
            'yours' => eval_applicant_hours_of_training($trainings),
            'met' => $evaluation ? eval_bool($evaluation->qs_training_met) : null,
        ],
        [
            'label' => 'Eligibility',
            'required' => $job->eligibility_requirement ?: '—',
            'yours' => eval_applicant_eligibility($eligibilities),
            'met' => $evaluation ? eval_bool($evaluation->qs_eligibility_met) : null,
        ],
    ];
}

function admin_qs_rows_html(array $qsRows, bool $isExcluded = false): string
{
    $rowsHtml = '';
    foreach ($qsRows as $row) {
        if ($isExcluded) {
            $color = '#b91c1c';
            $metLabel = 'Excluded';
        } else {
            $color = $row['met'] === false ? '#b91c1c' : ($row['met'] === true ? '#15803d' : '#64748b');
            $metLabel = $row['met'] === null ? '—' : ($row['met'] ? 'Qualified' : 'Not qualified');
        }

        $rowsHtml .= '<tr>'
            .'<td style="font-size:12px;font-weight:bold;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;">'.admin_e($row['label']).'</td>'
            .'<td style="font-size:11.5px;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;">'.admin_e($row['required']).'</td>'
            .'<td style="font-size:11.5px;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;">'.admin_e($row['yours']).'</td>'
            .'<td style="font-size:11.5px;font-weight:bold;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;color:'.$color.';">'.admin_e($metLabel).'</td>'
            .'</tr>';
    }

    return $rowsHtml;
}

/**
 * Qualified-decision email — matches
 * resources/views/emails/application-qualified.blade.php.
 */
function send_qualified(string $toEmail, string $fullName, string $jobTitle, ?string $controlNumber, array $qsRows, ?string $remarks): bool
{
    $fromAddress = portal_env('MAIL_FROM_ADDRESS', 'hello@example.com');
    $subject = 'Qualified - '.$jobTitle;

    $safeName = admin_e($fullName);
    $safeTitle = admin_e($jobTitle);
    $controlLine = $controlNumber ? '&nbsp;&middot;&nbsp;Control No: '.admin_e($controlNumber) : '';
    $qsRowsHtml = admin_qs_rows_html($qsRows);

    $remarksHtml = '';
    if ($remarks !== null && $remarks !== '') {
        $remarksHtml = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;border-radius:8px;margin:0 0 20px;">'
            .'<tr><td style="padding:14px 18px;">'
            .'<p style="margin:0 0 4px;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.05em;color:#475569;">Evaluator\'s Remarks</p>'
            .'<p style="margin:0;font-size:14px;line-height:1.6;color:#1e293b;">'.admin_e($remarks).'</p>'
            .'</td></tr></table>';
    }

    $body = <<<HTML
        <div style="font-family:Arial,Helvetica,sans-serif;color:#1e293b;max-width:640px;">
            <div style="background-color:#15803d;padding:24px 32px;">
                <p style="margin:0;color:#fff;font-size:12px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;">SDO Albay (CARES) &middot; Personnel Division</p>
                <p style="margin:4px 0 0;color:#fff;font-size:18px;font-weight:bold;">Congratulations!</p>
            </div>
            <div style="padding:32px;">
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Dear {$safeName},</p>
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">We are pleased to inform you that based on the initial evaluation, we have found your qualifications to be substantially in accordance with the Civil Service Commission (CSC) approved Qualification Standards (QS) of the position applied for, <strong>{$safeTitle}</strong>. Below are the results of the initial evaluation:</p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 20px;">
                    <tr><td colspan="4" style="background-color:#15803d;color:#ffffff;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.04em;padding:10px 12px;border:1px solid #166534;">Position Applied for: {$safeTitle}{$controlLine}</td></tr>
                    <tr>
                        <td style="background-color:#f0fdf4;color:#166534;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #bbf7d0;">CSC-approved QS</td>
                        <td style="background-color:#f0fdf4;color:#166534;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #bbf7d0;">Requirement</td>
                        <td style="background-color:#f0fdf4;color:#166534;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #bbf7d0;">Your Qualifications</td>
                        <td style="background-color:#f0fdf4;color:#166534;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #bbf7d0;">Remarks</td>
                    </tr>
                    {$qsRowsHtml}
                </table>
                {$remarksHtml}
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Please wait to be contacted by our office regarding the next steps in the hiring process.</p>
                <p style="margin:0 0 4px;font-size:15px;line-height:1.6;">Very truly yours,</p>
                <p style="margin:0;font-size:15px;font-weight:bold;line-height:1.4;">MARIANNE B. BUSTAMANTE</p>
                <p style="margin:0;font-size:13px;line-height:1.4;">ADMINISTRATIVE OFFICER IV, HRMO</p>
                <p style="margin:0;font-size:13px;line-height:1.4;">SDO Albay &middot; Personnel Division</p>
            </div>
            <div style="padding:20px 32px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                <p style="margin:0;font-size:12px;color:#64748b;line-height:1.6;">This is an automated message from the SDO Albay Recruitment Portal. Please do not reply to this email.</p>
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
        $mail->AltBody = "Dear {$fullName}, you have been marked Qualified for {$jobTitle}.";

        return $mail->send();
    } catch (\Throwable $e) {
        error_log('[admin-portal] send_qualified failed: '.$e->getMessage());

        return false;
    }
}

/**
 * Disqualified/Excluded-decision email — matches
 * resources/views/emails/application-disqualified.blade.php, including its
 * Excluded-origin branch (missing-mandatory-requirements list).
 *
 * @param array<int, string> $missingMandatory
 */
function send_disqualified(string $toEmail, string $fullName, string $jobTitle, ?string $controlNumber, array $qsRows, bool $isExcluded, array $missingMandatory, ?string $remarks): bool
{
    $fromAddress = portal_env('MAIL_FROM_ADDRESS', 'hello@example.com');
    $subject = 'Initial Evaluation Result - '.$jobTitle;

    $safeName = admin_e($fullName);
    $safeTitle = admin_e($jobTitle);
    $controlLine = $controlNumber ? '&nbsp;&middot;&nbsp;Control No: '.admin_e($controlNumber) : '';
    $qsRowsHtml = admin_qs_rows_html($qsRows, $isExcluded);

    $missingHtml = '';
    if ($missingMandatory !== []) {
        $items = '';
        foreach ($missingMandatory as $item) {
            $items .= '<li>'.admin_e($item).'</li>';
        }
        $missingHtml = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin:0 0 20px;">'
            .'<tr><td style="padding:16px 20px;">'
            .'<p style="margin:0 0 8px;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.05em;color:#b91c1c;">Missing Mandatory Requirements</p>'
            .'<ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.7;color:#1e293b;">'.$items.'</ul>'
            .'</td></tr></table>';
    }

    $remarksHtml = '';
    if ($remarks !== null && $remarks !== '') {
        $remarksHtml = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;border-radius:8px;margin:0 0 20px;">'
            .'<tr><td style="padding:14px 18px;">'
            .'<p style="margin:0 0 4px;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.05em;color:#475569;">Evaluator\'s Remarks</p>'
            .'<p style="margin:0;font-size:14px;line-height:1.6;color:#1e293b;">'.admin_e($remarks).'</p>'
            .'</td></tr></table>';
    }

    $closingReason = $isExcluded
        ? 'We regret to inform you that your application did not include all of the mandatory requirements listed above. You may, however, continue to submit applications in response to other vacancy announcements, which we publish at'
        : 'While your qualifications made a favorable impression, we regret to inform you that you did not meet the minimum QS set for this position. You may, however, continue to submit applications in response to other vacancy announcements, which we publish at';

    $body = <<<HTML
        <div style="font-family:Arial,Helvetica,sans-serif;color:#1e293b;max-width:640px;">
            <div style="background-color:#123B6D;padding:24px 32px;">
                <p style="margin:0;color:#fff;font-size:12px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;">SDO Albay (CARES) &middot; Personnel Division</p>
                <p style="margin:4px 0 0;color:#fff;font-size:18px;font-weight:bold;">Initial Evaluation Result</p>
            </div>
            <div style="padding:32px;">
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Dear {$safeName},</p>
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Please be informed of the results of the initial evaluation of your qualifications vis-&agrave;-vis the Civil Service Commission (CSC) approved Qualification Standards (QS) of the position applied for, <strong>{$safeTitle}</strong>, as follows:</p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 20px;">
                    <tr><td colspan="4" style="background-color:#1D4E89;color:#ffffff;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.04em;padding:10px 12px;border:1px solid #123B6D;">Position Applied for: {$safeTitle}{$controlLine}</td></tr>
                    <tr>
                        <td style="background-color:#EAF2F8;color:#1D4E89;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #c7d2fe;">CSC-approved QS</td>
                        <td style="background-color:#EAF2F8;color:#1D4E89;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #c7d2fe;">Requirement</td>
                        <td style="background-color:#EAF2F8;color:#1D4E89;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #c7d2fe;">Your Qualifications</td>
                        <td style="background-color:#EAF2F8;color:#1D4E89;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #c7d2fe;">Remarks</td>
                    </tr>
                    {$qsRowsHtml}
                </table>
                {$missingHtml}
                {$remarksHtml}
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">{$closingReason} <a href="https://cares.depedalbay.com" style="color:#1D4E89;">cares.depedalbay.com</a>.</p>
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Thank you for your interest in joining SDO Albay, and we wish you the best of luck in your future success.</p>
                <p style="margin:0 0 4px;font-size:15px;line-height:1.6;">Very truly yours,</p>
                <p style="margin:0;font-size:15px;font-weight:bold;line-height:1.4;">MARIANNE B. BUSTAMANTE</p>
                <p style="margin:0;font-size:13px;line-height:1.4;">ADMINISTRATIVE OFFICER IV, HRMO</p>
                <p style="margin:0;font-size:13px;line-height:1.4;">SDO Albay &middot; Personnel Division</p>
            </div>
            <div style="padding:20px 32px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                <p style="margin:0;font-size:12px;color:#64748b;line-height:1.6;">This is an automated message from the SDO Albay Recruitment Portal. Please do not reply to this email.</p>
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
        $mail->AltBody = "Dear {$fullName}, you have been marked Disqualified for {$jobTitle}.";

        return $mail->send();
    } catch (\Throwable $e) {
        error_log('[admin-portal] send_disqualified failed: '.$e->getMessage());

        return false;
    }
}
