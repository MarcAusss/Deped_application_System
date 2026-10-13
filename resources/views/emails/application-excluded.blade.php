<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Excluded</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial, Helvetica, sans-serif;color:#1e293b;">
    @php
        $job = $application->jobPosition;
        $evaluation = $application->evaluation;

        $checkedMandatory = is_array($evaluation?->documentary_mandatory) ? $evaluation->documentary_mandatory : [];

        $missingMandatory = [];
        foreach (\App\Support\EvaluationChecklist::mandatoryRequirements() as $key => $label) {
            if (!in_array($key, $checkedMandatory, true)) {
                $missingMandatory[] = \Illuminate\Support\Str::after($label, '] ');
            }
        }

        $qsRows = [
            [
                'label' => 'Education',
                'required' => $job->education_requirement ?: '—',
                'yours' => \App\Support\EvaluationChecklist::applicantBachelorsDegree($application),
                'met' => $evaluation?->qs_education_met,
            ],
            [
                'label' => 'Experience',
                'required' => $job->experience_requirement ?: '—',
                'yours' => \App\Support\EvaluationChecklist::applicantYearsOfExperience($application),
                'met' => $evaluation?->qs_experience_met,
            ],
            [
                'label' => 'Training',
                'required' => $job->training_requirement ?: '—',
                'yours' => \App\Support\EvaluationChecklist::applicantHoursOfTraining($application),
                'met' => $evaluation?->qs_training_met,
            ],
            [
                'label' => 'Eligibility',
                'required' => $job->eligibility_requirement ?: '—',
                'yours' => \App\Support\EvaluationChecklist::applicantEligibility($application),
                'met' => $evaluation?->qs_eligibility_met,
            ],
        ];
    @endphp

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background-color:#b91c1c;padding:24px 32px;">
                            <p style="margin:0;color:#ffffff;font-size:12px;font-weight:bold;letter-spacing:0.08em;text-transform:uppercase;">
                                SDO Albay (CARES) &middot; Personnel Division
                            </p>
                            <p style="margin:4px 0 0;color:#ffffff;font-size:18px;font-weight:bold;">
                                Application Status: Excluded
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Dear {{ $application->profile->full_name }},
                            </p>

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Your application for <strong>{{ $job->title }}</strong> has been marked <strong style="color:#b91c1c;">Excluded</strong> because one or more mandatory requirements were not submitted with your application.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 20px;">
                                <tr>
                                    <td colspan="4" style="background-color:#991b1b;color:#ffffff;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.04em;padding:10px 12px;border:1px solid #7f1d1d;">
                                        Position Applied for: {{ $job->title }}
                                        @if($application->controlNumber)
                                            &nbsp;&middot;&nbsp;Control No: {{ $application->controlNumber->control_number }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="background-color:#fef2f2;color:#991b1b;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #fecaca;">CSC-approved QS</td>
                                    <td style="background-color:#fef2f2;color:#991b1b;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #fecaca;">Requirement</td>
                                    <td style="background-color:#fef2f2;color:#991b1b;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #fecaca;">Your Qualifications</td>
                                    <td style="background-color:#fef2f2;color:#991b1b;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #fecaca;">Remarks</td>
                                </tr>
                                @foreach($qsRows as $row)
                                    <tr>
                                        <td style="font-size:12px;font-weight:bold;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;">{{ $row['label'] }}</td>
                                        <td style="font-size:11.5px;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;">{{ $row['required'] }}</td>
                                        <td style="font-size:11.5px;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;">{{ $row['yours'] }}</td>
                                        <td style="font-size:11.5px;font-weight:bold;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;color:#b91c1c;">
                                            Excluded
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            @if($missingMandatory)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin:0 0 20px;">
                                    <tr>
                                        <td style="padding:16px 20px;">
                                            <p style="margin:0 0 8px;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.05em;color:#b91c1c;">
                                                Missing Mandatory Requirements
                                            </p>
                                            <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.7;color:#1e293b;">
                                                @foreach($missingMandatory as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if(filled($evaluation?->remarks))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;border-radius:8px;margin:0 0 20px;">
                                    <tr>
                                        <td style="padding:14px 18px;">
                                            <p style="margin:0 0 4px;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.05em;color:#475569;">
                                                Evaluator's Remarks
                                            </p>
                                            <p style="margin:0;font-size:14px;line-height:1.6;color:#1e293b;">
                                                {{ $evaluation->remarks }}
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                This decision is still subject to final review by our office. You do not need to take any action at this time.
                            </p>

                            <p style="margin:0 0 4px;font-size:15px;line-height:1.6;">
                                Very truly yours,
                            </p>
                            <p style="margin:0;font-size:15px;font-weight:bold;line-height:1.4;">
                                MARIANNE B. BUSTAMANTE
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.4;">
                                ADMINISTRATIVE OFFICER IV, HRMO
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.4;">
                                SDO Albay &middot; Personnel Division
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
