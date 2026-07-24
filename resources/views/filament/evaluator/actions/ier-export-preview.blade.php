@php
    /*
     * Filament modal content may be rendered after the page stylesheet has
     * already been compiled. Keep the preview self-contained with inline
     * styles so the table remains intact in every panel/theme.
     */
    $fontStyle = 'color:#143a52;font-family:Arial,Helvetica,sans-serif;';
    $summaryStyle = 'align-items:center;background:#f0f9ff;border:1px solid #bae6fd;border-radius:14px;display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;margin-bottom:16px;padding:13px 16px;';
    $summaryTextStyle = 'color:#075985;font-size:13px;line-height:1.5;';
    $badgesStyle = 'display:flex;flex-wrap:wrap;gap:8px;';
    $badgeStyle = 'align-items:center;background:#ffffff;border:1px solid #7dd3fc;border-radius:999px;color:#0369a1;display:inline-flex;font-size:11px;font-weight:700;padding:7px 11px;white-space:nowrap;';
    $stageStyle = 'background:#eef7fb;border:1px solid #dbeafe;border-radius:16px;max-height:72vh;overflow:auto;padding:24px;';
    $paperStyle = 'background:#ffffff;border:1px solid #cbd5e1;border-radius:4px;box-shadow:0 18px 45px rgba(15,23,42,.16);box-sizing:border-box;margin:0 auto 28px;min-height:720px;padding:28px;width:1760px;';
    $titleStyle = 'color:#0f2940;font-size:17px;font-weight:800;letter-spacing:.025em;margin:0 0 20px;text-align:center;';
    $metaTableStyle = 'border-collapse:collapse;margin-bottom:18px;table-layout:fixed;width:100%;';
    $metaLabelStyle = 'background:#e9faff;border:1px solid #7dd3fc;color:#075985;font-size:10px;font-weight:800;padding:9px 11px;text-align:left;vertical-align:middle;';
    $metaValueStyle = 'background:#ffffff;border:1px solid #7dd3fc;color:#1e293b;font-size:10px;font-weight:600;line-height:1.4;padding:9px 11px;text-align:left;vertical-align:middle;';
    $qualificationTitleStyle = 'background:#0284c7;border:1px solid #075985;color:#ffffff;font-size:10px;font-weight:800;letter-spacing:.03em;padding:8px 11px;text-align:left;text-transform:uppercase;';
    $qualificationLabelStyle = 'background:#f0f9ff;border:1px solid #bae6fd;color:#0369a1;font-size:9.5px;font-weight:800;padding:8px 10px;text-align:left;vertical-align:top;';
    $qualificationValueStyle = 'background:#ffffff;border:1px solid #bae6fd;color:#334155;font-size:9.5px;line-height:1.4;padding:8px 10px;text-align:left;vertical-align:top;';
    $tableFrameStyle = 'border:2px solid #075985;overflow:hidden;';
    $tableStyle = 'border-collapse:collapse;table-layout:fixed;width:100%;';
    $headerTopStyle = 'background:#0284c7;border:1px solid #075985;color:#ffffff;font-size:8px;font-weight:800;letter-spacing:.015em;line-height:1.25;padding:8px 5px;text-align:center;vertical-align:middle;';
    $headerSubStyle = 'background:#0ea5e9;border:1px solid #075985;color:#ffffff;font-size:7.5px;font-weight:800;line-height:1.25;padding:7px 4px;text-align:center;vertical-align:middle;';
    $cellStyle = 'border:1px solid #94a3b8;color:#263b4b;font-size:7.5px;line-height:1.4;overflow-wrap:anywhere;padding:8px 5px;vertical-align:top;white-space:pre-line;word-break:break-word;';
    $centerCellStyle = $cellStyle . 'text-align:center;';
    $nameCellStyle = $cellStyle . 'color:#0f2940;font-weight:700;';
    $footerStyle = 'align-items:center;color:#64748b;display:flex;font-size:10px;justify-content:space-between;margin-top:12px;';
    $footerStrongStyle = 'color:#0369a1;font-weight:800;';
    $emptyStyle = 'background:#fffbeb;border:1px solid #fde68a;border-radius:14px;color:#92400e;font-size:13px;padding:28px;text-align:center;';
@endphp

<div style="{{ $fontStyle }}">
    <div style="{{ $summaryStyle }}">
        <div style="{{ $summaryTextStyle }}">
            <strong>{{ number_format($totalApplications) }}</strong>
            filtered {{ \Illuminate\Support\Str::plural('application', $totalApplications) }}
            will be included in the Excel file.
        </div>

        <div style="{{ $badgesStyle }}">
            <span style="{{ $badgeStyle }}">A3 Landscape</span>
            <span style="{{ $badgeStyle }}">
                {{ $groups->count() }}
                {{ \Illuminate\Support\Str::plural('Worksheet', $groups->count()) }}
            </span>
            <span style="{{ $badgeStyle }}">Current Filters Applied</span>
        </div>
    </div>

    @if($groups->isNotEmpty())
        <div style="{{ $stageStyle }}">
            @foreach($groups as $group)
                <article style="{{ $paperStyle }}{{ $loop->last ? 'margin-bottom:0;' : '' }}">
                    <h3 style="{{ $titleStyle }}">
                        INITIAL EVALUATION RESULT (IER)
                    </h3>

                    <table aria-label="Position and qualification standards" style="{{ $metaTableStyle }}">
                        <colgroup>
                            <col style="width:150px;">
                            <col>
                            <col style="width:225px;">
                            <col style="width:360px;">
                        </colgroup>
                        <tbody>
                            <tr>
                                <th style="{{ $metaLabelStyle }}">Position</th>
                                <td style="{{ $metaValueStyle }}">
                                    {{ $group['position']['position'] ?: '—' }}
                                </td>
                                <th style="{{ $metaLabelStyle }}">Salary Grade and Monthly Salary</th>
                                <td style="{{ $metaValueStyle }}">
                                    {{ $group['position']['salary'] ?: '—' }}
                                </td>
                            </tr>
                            <tr>
                                <th colspan="4" style="{{ $qualificationTitleStyle }}">
                                    Qualification Standards
                                </th>
                            </tr>
                            <tr>
                                <th style="{{ $qualificationLabelStyle }}">Education</th>
                                <td style="{{ $qualificationValueStyle }}">
                                    {{ $group['position']['education_requirement'] ?: '—' }}
                                </td>
                                <th style="{{ $qualificationLabelStyle }}">Training</th>
                                <td style="{{ $qualificationValueStyle }}">
                                    {{ $group['position']['training_requirement'] ?: '—' }}
                                </td>
                            </tr>
                            <tr>
                                <th style="{{ $qualificationLabelStyle }}">Experience</th>
                                <td style="{{ $qualificationValueStyle }}">
                                    {{ $group['position']['experience_requirement'] ?: '—' }}
                                </td>
                                <th style="{{ $qualificationLabelStyle }}">Eligibility</th>
                                <td style="{{ $qualificationValueStyle }}">
                                    {{ $group['position']['eligibility_requirement'] ?: '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="{{ $tableFrameStyle }}">
                        <table aria-label="Initial evaluation result" style="{{ $tableStyle }}">
                            <colgroup>
                                <col style="width:34px;">
                                <col style="width:104px;">
                                <col style="width:130px;">
                                <col style="width:138px;">
                                <col style="width:36px;">
                                <col style="width:48px;">
                                <col style="width:64px;">
                                <col style="width:64px;">
                                <col style="width:64px;">
                                <col style="width:68px;">
                                <col style="width:132px;">
                                <col style="width:88px;">
                                <col style="width:148px;">
                                <col style="width:122px;">
                                <col style="width:44px;">
                                <col style="width:142px;">
                                <col style="width:58px;">
                                <col style="width:122px;">
                                <col style="width:108px;">
                            </colgroup>

                            <thead>
                                <tr>
                                    <th rowspan="2" style="{{ $headerTopStyle }}">No.</th>
                                    <th rowspan="2" style="{{ $headerTopStyle }}">Application Code</th>
                                    <th rowspan="2" style="{{ $headerTopStyle }}">Name of Applicant</th>
                                    <th colspan="9" style="{{ $headerTopStyle }}">Personal Information</th>
                                    <th rowspan="2" style="{{ $headerTopStyle }}">Education</th>
                                    <th colspan="2" style="{{ $headerTopStyle }}">Training</th>
                                    <th colspan="2" style="{{ $headerTopStyle }}">Experience</th>
                                    <th rowspan="2" style="{{ $headerTopStyle }}">Eligibility</th>
                                    <th rowspan="2" style="{{ $headerTopStyle }}">Remarks</th>
                                </tr>
                                <tr>
                                    <th style="{{ $headerSubStyle }}">Address</th>
                                    <th style="{{ $headerSubStyle }}">Age</th>
                                    <th style="{{ $headerSubStyle }}">Sex</th>
                                    <th style="{{ $headerSubStyle }}">Civil Status</th>
                                    <th style="{{ $headerSubStyle }}">Religion</th>
                                    <th style="{{ $headerSubStyle }}">Disability</th>
                                    <th style="{{ $headerSubStyle }}">Ethnic Group</th>
                                    <th style="{{ $headerSubStyle }}">Email Address</th>
                                    <th style="{{ $headerSubStyle }}">Contact No.</th>
                                    <th style="{{ $headerSubStyle }}">Title</th>
                                    <th style="{{ $headerSubStyle }}">Hours</th>
                                    <th style="{{ $headerSubStyle }}">Details</th>
                                    <th style="{{ $headerSubStyle }}">Years</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($group['rows'] as $row)
                                    @php
                                        $rowBackground = $loop->even ? 'background:#f8fafc;' : 'background:#ffffff;';
                                    @endphp
                                    <tr>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['number'] }}</td>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['application_code'] }}</td>
                                        <td style="{{ $nameCellStyle }}{{ $rowBackground }}">{{ $row['name'] }}</td>
                                        <td style="{{ $cellStyle }}{{ $rowBackground }}">{{ $row['address'] }}</td>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['age'] ?: '—' }}</td>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['sex'] }}</td>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['civil_status'] }}</td>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['religion'] }}</td>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['disability'] }}</td>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['ethnic_group'] }}</td>
                                        <td style="{{ $cellStyle }}{{ $rowBackground }}">{{ $row['email'] }}</td>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['contact_number'] }}</td>
                                        <td style="{{ $cellStyle }}{{ $rowBackground }}">{{ $row['education'] }}</td>
                                        <td style="{{ $cellStyle }}{{ $rowBackground }}">{{ $row['training_title'] }}</td>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['training_hours'] ?: '—' }}</td>
                                        <td style="{{ $cellStyle }}{{ $rowBackground }}">{{ $row['experience_details'] }}</td>
                                        <td style="{{ $centerCellStyle }}{{ $rowBackground }}">{{ $row['experience_years'] }}</td>
                                        <td style="{{ $cellStyle }}{{ $rowBackground }}">{{ $row['eligibility'] }}</td>
                                        <td style="{{ $cellStyle }}{{ $rowBackground }}">{{ $row['remarks'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <footer style="{{ $footerStyle }}">
                        <span>
                            Position:
                            <strong style="{{ $footerStrongStyle }}">
                                {{ $group['position']['position'] ?: 'Unassigned' }}
                            </strong>
                        </span>

                        <span>
                            Previewing
                            <strong style="{{ $footerStrongStyle }}">{{ count($group['rows']) }}</strong>
                            of
                            <strong style="{{ $footerStrongStyle }}">{{ $group['total'] }}</strong>
                            {{ \Illuminate\Support\Str::plural('record', $group['total']) }}
                        </span>
                    </footer>
                </article>
            @endforeach
        </div>
    @else
        <div style="{{ $emptyStyle }}">
            No applications match the current filters. The exported IER will contain an empty table.
        </div>
    @endif
</div>
