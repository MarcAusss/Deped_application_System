<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Initial Evaluation Result</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial, Helvetica, sans-serif;color:#1e293b;">
    @php
        $job = $application->jobPosition;
        $evaluation = $application->evaluation;
        $isExcluded = $evaluation?->result === \App\Models\ApplicationEvaluation::RESULT_EXCLUDED;

        $missingMandatory = [];
        if ($isExcluded) {
            $checkedMandatory = is_array($evaluation->documentary_mandatory) ? $evaluation->documentary_mandatory : [];
            foreach (\App\Support\EvaluationChecklist::mandatoryRequirements() as $key => $label) {
                if (!in_array($key, $checkedMandatory, true)) {
                    $missingMandatory[] = \Illuminate\Support\Str::after($label, '] ');
                }
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
                        <td style="background-color:#123B6D;padding:24px 32px;">
                            <p style="margin:0;color:#ffffff;font-size:12px;font-weight:bold;letter-spacing:0.08em;text-transform:uppercase;">
                                SDO Albay (CARES) &middot; Personnel Division
                            </p>
                            <p style="margin:4px 0 0;color:#ffffff;font-size:18px;font-weight:bold;">
                                Initial Evaluation Result
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Dear {{ $application->profile->full_name }},
                            </p>

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Please be informed of the results of the initial evaluation of your qualifications vis-à-vis the Civil Service Commission (CSC) approved Qualification Standards (QS) of the position applied for, <strong>{{ $job->title }}</strong>, as follows:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 20px;">
                                <tr>
                                    <td colspan="4" style="background-color:#1D4E89;color:#ffffff;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:0.04em;padding:10px 12px;border:1px solid #123B6D;">
                                        Position Applied for: {{ $job->title }}
                                        @if($application->controlNumber)
                                            &nbsp;&middot;&nbsp;Control No: {{ $application->controlNumber->control_number }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="background-color:#EAF2F8;color:#1D4E89;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #c7d2fe;">CSC-approved QS</td>
                                    <td style="background-color:#EAF2F8;color:#1D4E89;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #c7d2fe;">Requirement</td>
                                    <td style="background-color:#EAF2F8;color:#1D4E89;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #c7d2fe;">Your Qualifications</td>
                                    <td style="background-color:#EAF2F8;color:#1D4E89;font-size:10.5px;font-weight:bold;text-transform:uppercase;padding:8px 10px;border:1px solid #c7d2fe;">Remarks</td>
                                </tr>
                                @foreach($qsRows as $row)
                                    <tr>
                                        <td style="font-size:12px;font-weight:bold;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;">{{ $row['label'] }}</td>
                                        <td style="font-size:11.5px;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;">{{ $row['required'] }}</td>
                                        <td style="font-size:11.5px;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;">{{ $row['yours'] }}</td>
                                        <td style="font-size:11.5px;font-weight:bold;padding:8px 10px;border:1px solid #e2e8f0;vertical-align:top;color:{{ $isExcluded ? '#b91c1c' : ($row['met'] === false ? '#b91c1c' : '#64748b') }};">
                                            @if($isExcluded)
                                                Excluded
                                            @else
                                                {{ $row['met'] === null ? '—' : ($row['met'] ? 'Qualified' : 'Not qualified') }}
                                            @endif
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
                                @if($isExcluded)
                                    We regret to inform you that your application did not include all of the mandatory requirements listed above. You may, however, continue to submit applications in response to other vacancy announcements, which we publish at
                                @else
                                    While your qualifications made a favorable impression, we regret to inform you that you did not meet the minimum QS set for this position. You may, however, continue to submit applications in response to other vacancy announcements, which we publish at
                                @endif
                                <a href="{{ route('jobs.index') }}" style="color:#1D4E89;">cares.depedalbay.com</a>.
                            </p>

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Thank you for your interest in joining SDO Albay, and we wish you the best of luck in your future success.
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
