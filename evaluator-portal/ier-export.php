<?php
define('PORTAL_BOOTED', true);
require __DIR__.'/_boot.php';
require __DIR__.'/_ier.php';

evaluator_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_verify();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

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

    $sheet->getStyle('A1:S1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 13],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);

    $sheet->getStyle('A3:G9')->applyFromArray([
        'font' => ['size' => 9],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    ]);
    $sheet->getStyle('A3:A9')->getFont()->setBold(true);
    $sheet->getStyle('A5:G5')->getFont()->setBold(true);

    foreach (['C3:G3', 'D4:G4', 'C6:G6', 'C7:G7', 'C8:G8', 'C9:G9'] as $range) {
        $sheet->getStyle($range)->getBorders()->getBottom()->applyFromArray(['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]);
    }

    $sheet->getStyle('A10:S11')->applyFromArray([
        'font' => ['bold' => true, 'size' => 8],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
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

$ids = $_POST['ids'] ?? [];
$ids = array_values(array_unique(array_map('intval', array_filter($ids, fn ($v) => ctype_digit((string) $v)))));
$groups = eval_ier_load_groups($ids);

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

$isSelected = count($ids) > 0 && ($_POST['source'] ?? '') !== 'filtered';
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
