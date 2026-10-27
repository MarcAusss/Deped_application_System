<?php

if (!defined('PORTAL_BOOTED')) {
    http_response_code(404);
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Resolve the set of application IDs an IER preview/export request targets —
 * either an explicit selection (source=selected, ids[]) or the current
 * Applications-list filters (source=filtered, status/job), matching the
 * header-level "export filtered list" vs. per-row "export selected" split
 * that only exists on the Applications page in the source system.
 *
 * @return array<int, int>
 */
function eval_ier_resolve_ids(): array
{
    $source = $_POST['source'] ?? 'selected';

    if ($source === 'filtered') {
        $status = $_POST['status'] ?? '';
        $job = $_POST['job'] ?? '';

        $where = ["a.status NOT IN ('qualified','disqualified')"];
        $where[] = eval_visible_applications_sql();
        $params = [];
        if ($status === 'evaluated_excluded') {
            // The Evaluated page's combined list (evaluated + excluded,
            // both awaiting the admin's final decision) — not a single
            // status value, so it needs its own branch.
            $where[] = "a.status IN ('evaluated', 'excluded')";
        } elseif (in_array($status, ['pending', 'evaluated', 'excluded'], true)) {
            $where[] = 'a.status = :status';
            $params['status'] = $status;
        }
        if ($job !== '' && ctype_digit((string) $job)) {
            $where[] = 'a.job_position_id = :job';
            $params['job'] = $job;
        }

        $stmt = portal_pdo()->prepare('SELECT a.id FROM applications a WHERE '.implode(' AND ', $where));
        $stmt->execute($params);

        return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id'));
    }

    $ids = $_POST['ids'] ?? [];

    return array_values(array_unique(array_map('intval', array_filter($ids, fn ($v) => ctype_digit((string) $v)))));
}

/**
 * Load full IER data for the given application IDs, grouped by
 * job_position_id (or "unassigned"), each group carrying its job_positions
 * row and an ordered list of applications (each with profile/repeatable
 * rows/control number/evaluation attached) — mirrors ApplicationsExport's
 * groupBy() + eager-loaded relations.
 *
 * @param array<int, int> $ids
 * @param string $visibleWhere extra SQL condition on the `applications` table limiting
 *                             which applications this portal may export ('1=1' for none)
 * @return array<int, array{position: object|null, applications: array<int, object>}>
 */
function eval_ier_load_groups(array $ids, string $visibleWhere): array
{
    if ($ids === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = portal_pdo()->prepare("SELECT * FROM applications WHERE id IN ({$placeholders}) AND ({$visibleWhere}) ORDER BY id");
    $stmt->execute($ids);
    $applications = $stmt->fetchAll();

    $jobIds = array_values(array_unique(array_filter(array_map(fn ($a) => $a->job_position_id, $applications))));
    $jobs = [];
    if ($jobIds !== []) {
        $jp = implode(',', array_fill(0, count($jobIds), '?'));
        $stmt = portal_pdo()->prepare("SELECT * FROM job_positions WHERE id IN ({$jp})");
        $stmt->execute($jobIds);
        foreach ($stmt->fetchAll() as $job) {
            $jobs[$job->id] = $job;
        }
    }

    $fetchIndexed = function (string $table) use ($ids, $placeholders): array {
        $stmt = portal_pdo()->prepare("SELECT * FROM {$table} WHERE application_id IN ({$placeholders}) ORDER BY id");
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[$row->application_id][] = $row;
        }

        return $out;
    };

    $profiles = [];
    $stmt = portal_pdo()->prepare("SELECT * FROM applicant_profiles WHERE application_id IN ({$placeholders})");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $row) {
        $profiles[$row->application_id] = $row;
    }

    $controlNumbers = [];
    $stmt = portal_pdo()->prepare("SELECT * FROM application_control_numbers WHERE application_id IN ({$placeholders})");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $row) {
        $controlNumbers[$row->application_id] = $row->control_number;
    }

    $evaluations = [];
    $stmt = portal_pdo()->prepare("SELECT * FROM application_evaluations WHERE application_id IN ({$placeholders})");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $row) {
        $evaluations[$row->application_id] = $row;
    }

    $educations = $fetchIndexed('applicant_educations');
    $experiences = $fetchIndexed('applicant_experiences');
    $trainings = $fetchIndexed('applicant_trainings');
    $eligibilities = $fetchIndexed('applicant_eligibilities');

    $groups = [];
    foreach ($applications as $app) {
        $key = $app->job_position_id ?: 'unassigned';
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'position' => $app->job_position_id ? ($jobs[$app->job_position_id] ?? null) : null,
                'applications' => [],
            ];
        }

        $app->_profile = $profiles[$app->id] ?? null;
        $app->_control_number = $controlNumbers[$app->id] ?? null;
        $app->_evaluation = $evaluations[$app->id] ?? null;
        $app->_educations = $educations[$app->id] ?? [];
        $app->_experiences = $experiences[$app->id] ?? [];
        $app->_trainings = $trainings[$app->id] ?? [];
        $app->_eligibilities = $eligibilities[$app->id] ?? [];

        $groups[$key]['applications'][] = $app;
    }

    return array_values($groups);
}

function eval_ier_unique_sheet_title(string $title, array &$usedTitles): string
{
    $cleanTitle = trim((string) preg_replace('/[\\\\\/\?\*\[\]:]/', '', $title));
    $baseTitle = mb_substr($cleanTitle !== '' ? $cleanTitle : 'IER', 0, 31);
    $sheetTitle = $baseTitle;
    $counter = 2;

    while (in_array(mb_strtolower($sheetTitle), $usedTitles, true)) {
        $suffix = " ({$counter})";
        $sheetTitle = mb_substr($baseTitle, 0, 31 - mb_strlen($suffix)).$suffix;
        $counter++;
    }

    $usedTitles[] = mb_strtolower($sheetTitle);

    return $sheetTitle;
}

const IER_HEADER_START_ROW = 10;
const IER_DATA_START_ROW = 12;
const IER_COLUMN_WIDTHS = ['A' => 5, 'B' => 16, 'C' => 25, 'D' => 26, 'E' => 6, 'F' => 8, 'G' => 11, 'H' => 11, 'I' => 13, 'J' => 13, 'K' => 25, 'L' => 15, 'M' => 30, 'N' => 25, 'O' => 9, 'P' => 30, 'Q' => 11, 'R' => 27, 'S' => 24];

function ier_pad_row(array $values): array
{
    return array_pad($values, 19, null);
}

function ier_build_sheet(Spreadsheet $spreadsheet, ?object $position, array $applications, string $sheetTitle, bool $isFirst): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
{
    $sheet = $isFirst ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
    $sheet->setTitle($sheetTitle);

    $summary = eval_ier_position_summary($position);

    $rows = [
        ier_pad_row(['INITIAL EVALUATION RESULT (IER)']),
        ier_pad_row([]),
        ier_pad_row(['Position:', null, $summary['position']]),
        ier_pad_row(['Salary Grade and Monthly Salary:', null, null, $summary['salary']]),
        ier_pad_row(['Qualification Standards:']),
        ier_pad_row(['Education:', null, $summary['education_requirement']]),
        ier_pad_row(['Training:', null, $summary['training_requirement']]),
        ier_pad_row(['Experience:', null, $summary['experience_requirement']]),
        ier_pad_row(['Eligibility:', null, $summary['eligibility_requirement']]),
        ier_pad_row(['No.', 'Application Code', 'Name of Applicant', 'Personal Information', null, null, null, null, null, null, null, null, 'Education', 'Training', null, 'Experience', null, 'Eligibility', 'Remarks']),
        ier_pad_row([null, null, null, 'Address', 'Age', 'Sex', 'Civil Status', 'Religion', 'Disability', 'Ethnic Group', 'Email Address', 'Contact No.', null, 'Title', 'Hours', 'Details', 'Years', null, null]),
    ];

    foreach (array_values($applications) as $i => $app) {
        $r = eval_ier_row($i + 1, $app->_profile, $app->_educations, $app->_trainings, $app->_experiences, $app->_eligibilities, $app->_control_number, $app->_evaluation, $app->status);
        $rows[] = [
            $r['number'], $r['application_code'], $r['name'], $r['address'], $r['age'], $r['sex'], $r['civil_status'],
            $r['religion'], $r['disability'], $r['ethnic_group'], $r['email'], $r['contact_number'], $r['education'],
            $r['training_title'], $r['training_hours'], $r['experience_details'], $r['experience_years'], $r['eligibility'], $r['remarks'],
        ];
    }

    $sheet->fromArray($rows, null, 'A1');

    $applicationCount = count($applications);
    $lastRow = max(IER_DATA_START_ROW, IER_DATA_START_ROW - 1 + $applicationCount);

    foreach ([
        'A1:S1', 'A3:B3', 'C3:G3', 'A4:C4', 'D4:G4', 'A5:G5', 'A6:B6', 'C6:G6', 'A7:B7', 'C7:G7',
        'A8:B8', 'C8:G8', 'A9:B9', 'C9:G9', 'A10:A11', 'B10:B11', 'C10:C11', 'D10:L10', 'M10:M11',
        'N10:O10', 'P10:Q10', 'R10:R11', 'S10:S11',
    ] as $range) {
        $sheet->mergeCells($range);
    }

    $sheet->setShowGridlines(false);
    $sheet->freezePane('A'.IER_DATA_START_ROW);
    $sheet->setSelectedCell('A1');

    $sheet->getStyle("A1:S{$lastRow}")->getFont()->setName('Arial')->setSize(8);

    // System green (#16402B dark, #1E6B42 primary); borders stay black for printing.
    $sheet->getStyle('A1:S1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF16402B']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);

    $sheet->getStyle('A3:G9')->applyFromArray([
        'font' => ['size' => 9],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    ]);
    $sheet->getStyle('A3:A9')->getFont()->setBold(true)->getColor()->setARGB('FF16402B');
    $sheet->getStyle('A5:G5')->getFont()->setBold(true)->getColor()->setARGB('FF16402B');

    foreach (['C3:G3', 'D4:G4', 'C6:G6', 'C7:G7', 'C8:G8', 'C9:G9'] as $range) {
        $sheet->getStyle($range)->getBorders()->getBottom()->applyFromArray(['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]);
    }

    $sheet->getStyle('A10:S11')->applyFromArray([
        'font' => ['bold' => true, 'size' => 8, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E6B42']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    ]);

    $sheet->getStyle("A10:S{$lastRow}")->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
            'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
        ],
    ]);

    if ($applicationCount > 0) {
        $sheet->getStyle('A12:S'.$lastRow)->applyFromArray(['alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true]]);

        foreach (['A', 'B', 'E', 'F', 'G', 'H', 'I', 'J', 'L', 'O', 'Q'] as $column) {
            $sheet->getStyle("{$column}12:{$column}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        for ($row = IER_DATA_START_ROW; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(52);
        }
    }

    $sheet->getRowDimension(1)->setRowHeight(24);
    $sheet->getRowDimension(2)->setRowHeight(7);
    $sheet->getRowDimension(5)->setRowHeight(19);
    $sheet->getRowDimension(10)->setRowHeight(20);
    $sheet->getRowDimension(11)->setRowHeight(30);

    foreach (IER_COLUMN_WIDTHS as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }

    $pageSetup = $sheet->getPageSetup();
    $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A3);
    $pageSetup->setFitToWidth(1);
    $pageSetup->setFitToHeight(0);
    $pageSetup->setRowsToRepeatAtTopByStartAndEnd(IER_HEADER_START_ROW, IER_DATA_START_ROW - 1);
    $pageSetup->setPrintArea("A1:S{$lastRow}");
    $pageSetup->setHorizontalCentered(true);

    $sheet->getPageMargins()->setTop(0.35)->setRight(0.20)->setBottom(0.35)->setLeft(0.20)->setHeader(0.10)->setFooter(0.15);
    $sheet->getHeaderFooter()->setOddFooter('&LInitial Evaluation Result&CPage &P of &N&R'.$sheetTitle);

    return $sheet;
}

/**
 * Streams the IER workbook (one worksheet per position group, as built by
 * eval_ier_load_groups()) as an .xlsx download and ends the request. Shared
 * by the evaluator and admin ier-export.php pages.
 *
 * @param array<int, array{position: object|null, applications: array<int, object>}> $groups
 */
function eval_ier_send_xlsx(array $groups, bool $isSelected): never
{
    $spreadsheet = new Spreadsheet();
    $usedTitles = [];

    if (empty($groups)) {
        ier_build_sheet($spreadsheet, null, [], 'IER', true);
    } else {
        foreach ($groups as $i => $group) {
            $title = eval_ier_unique_sheet_title($group['position']->title ?? 'Unassigned Position', $usedTitles);
            ier_build_sheet($spreadsheet, $group['position'], $group['applications'], $title, $i === 0);
        }
    }

    $filename = 'initial-evaluation-result'.($isSelected ? '-selected' : '').'-'.date('Y-m-d-His').'.xlsx';

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
