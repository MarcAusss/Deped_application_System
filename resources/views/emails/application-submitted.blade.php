<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Application Received</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial, Helvetica, sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background-color:#123B6D;padding:24px 32px;">
                            <p style="margin:0;color:#ffffff;font-size:12px;font-weight:bold;letter-spacing:0.08em;text-transform:uppercase;">
                                SDO Albay (CARES)
                            </p>
                            <p style="margin:4px 0 0;color:#ffffff;font-size:18px;font-weight:bold;">
                                Application Received
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Dear {{ $application->profile->full_name }},
                            </p>

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Thank you for applying. We have successfully received your application for the position below.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EAF2F8;border-radius:8px;margin:0 0 20px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0 0 4px;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.05em;color:#1D4E89;">
                                            Position Applied
                                        </p>
                                        <p style="margin:0 0 12px;font-size:16px;font-weight:bold;color:#123B6D;">
                                            {{ $application->jobPosition->title }}
                                        </p>

                                        <p style="margin:0 0 4px;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.05em;color:#1D4E89;">
                                            Date Submitted
                                        </p>
                                        <p style="margin:0;font-size:16px;font-weight:bold;color:#123B6D;">
                                            {{ $application->created_at->format('F d, Y - h:i A') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Your application is now marked as <strong>Pending</strong> and will be reviewed by our evaluation team. You will be notified of any updates regarding the status of your application.
                            </p>

                            <p style="margin:0;font-size:15px;line-height:1.6;">
                                Thank you for your interest in joining SDO Albay.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#64748b;line-height:1.6;">
                                This is an automated message from the SDO Albay Recruitment Portal. Please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
