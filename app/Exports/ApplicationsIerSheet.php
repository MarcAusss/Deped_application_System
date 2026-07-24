<?php

namespace App\Exports;

use App\Models\JobPosition;
use App\Support\IerApplicationFormatter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ApplicationsIerSheet implements FromArray, WithColumnWidths, WithEvents, WithStrictNullComparison, WithTitle
{
    private const HEADER_START_ROW = 10;

    private const DATA_START_ROW = 12;

    public function __construct(
        protected Collection $applications,
        protected ?JobPosition $position,
        protected string $sheetTitle
    ) {
    }

    public function array(): array
    {
        $position = IerApplicationFormatter::positionSummary($this->position);

        $rows = [
            $this->row(['INITIAL EVALUATION RESULT (IER)']),
            $this->row([]),
            $this->row(['Position:', null, $position['position']]),
            $this->row(['Salary Grade and Monthly Salary:', null, null, $position['salary']]),
            $this->row(['Qualification Standards:']),
            $this->row(['Education:', null, $position['education_requirement']]),
            $this->row(['Training:', null, $position['training_requirement']]),
            $this->row(['Experience:', null, $position['experience_requirement']]),
            $this->row(['Eligibility:', null, $position['eligibility_requirement']]),
            $this->row([
                'No.',
                'Application Code',
                'Name of Applicant',
                'Personal Information',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                'Education',
                'Training',
                null,
                'Experience',
                null,
                'Eligibility',
                'Remarks',
            ]),
            $this->row([
                null,
                null,
                null,
                'Address',
                'Age',
                'Sex',
                'Civil Status',
                'Religion',
                'Disability',
                'Ethnic Group',
                'Email Address',
                'Contact No.',
                null,
                'Title',
                'Hours',
                'Details',
                'Years',
                null,
                null,
            ]),
        ];

        $this->applications
            ->values()
            ->each(function ($application, int $index) use (&$rows): void {
                $formatted = IerApplicationFormatter::row($application, $index + 1);

                $rows[] = [
                    $formatted['number'],
                    $formatted['application_code'],
                    $formatted['name'],
                    $formatted['address'],
                    $formatted['age'],
                    $formatted['sex'],
                    $formatted['civil_status'],
                    $formatted['religion'],
                    $formatted['disability'],
                    $formatted['ethnic_group'],
                    $formatted['email'],
                    $formatted['contact_number'],
                    $formatted['education'],
                    $formatted['training_title'],
                    $formatted['training_hours'],
                    $formatted['experience_details'],
                    $formatted['experience_years'],
                    $formatted['eligibility'],
                    $formatted['remarks'],
                ];
            });

        return $rows;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 16,
            'C' => 25,
            'D' => 26,
            'E' => 6,
            'F' => 8,
            'G' => 11,
            'H' => 11,
            'I' => 13,
            'J' => 13,
            'K' => 25,
            'L' => 15,
            'M' => 30,
            'N' => 25,
            'O' => 9,
            'P' => 30,
            'Q' => 11,
            'R' => 27,
            'S' => 24,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(self::DATA_START_ROW, self::DATA_START_ROW - 1 + $this->applications->count());

                $this->mergeLayout($sheet);

                $sheet->setShowGridlines(false);
                $sheet->freezePane('A'.self::DATA_START_ROW);
                $sheet->setSelectedCell('A1');

                $sheet->getStyle("A1:S{$lastRow}")
                    ->getFont()
                    ->setName('Arial')
                    ->setSize(8);

                $sheet->getStyle('A1:S1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 13,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A3:G9')->applyFromArray([
                    'font' => [
                        'size' => 9,
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle('A3:A9')->getFont()->setBold(true);
                $sheet->getStyle('A5:G5')->getFont()->setBold(true);

                foreach (['C3:G3', 'D4:G4', 'C6:G6', 'C7:G7', 'C8:G8', 'C9:G9'] as $range) {
                    $sheet->getStyle($range)->getBorders()->getBottom()->applyFromArray([
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ]);
                }

                $sheet->getStyle('A10:S11')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 8,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF2F2F2'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle("A10:S{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                if ($this->applications->isNotEmpty()) {
                    $sheet->getStyle('A12:S'.$lastRow)->applyFromArray([
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_TOP,
                            'wrapText' => true,
                        ],
                    ]);

                    foreach (['A', 'B', 'E', 'F', 'G', 'H', 'I', 'J', 'L', 'O', 'Q'] as $column) {
                        $sheet->getStyle("{$column}12:{$column}{$lastRow}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    for ($row = self::DATA_START_ROW; $row <= $lastRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(52);
                    }
                }

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(7);
                $sheet->getRowDimension(5)->setRowHeight(19);
                $sheet->getRowDimension(10)->setRowHeight(20);
                $sheet->getRowDimension(11)->setRowHeight(30);

                $pageSetup = $sheet->getPageSetup();
                $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A3);
                $pageSetup->setFitToWidth(1);
                $pageSetup->setFitToHeight(0);
                $pageSetup->setRowsToRepeatAtTopByStartAndEnd(self::HEADER_START_ROW, self::DATA_START_ROW - 1);
                $pageSetup->setPrintArea("A1:S{$lastRow}");
                $pageSetup->setHorizontalCentered(true);

                $sheet->getPageMargins()
                    ->setTop(0.35)
                    ->setRight(0.20)
                    ->setBottom(0.35)
                    ->setLeft(0.20)
                    ->setHeader(0.10)
                    ->setFooter(0.15);

                $sheet->getHeaderFooter()
                    ->setOddFooter('&LInitial Evaluation Result&CPage &P of &N&R'.$this->sheetTitle);
            },
        ];
    }

    private function mergeLayout($sheet): void
    {
        foreach ([
            'A1:S1',
            'A3:B3',
            'C3:G3',
            'A4:C4',
            'D4:G4',
            'A5:G5',
            'A6:B6',
            'C6:G6',
            'A7:B7',
            'C7:G7',
            'A8:B8',
            'C8:G8',
            'A9:B9',
            'C9:G9',
            'A10:A11',
            'B10:B11',
            'C10:C11',
            'D10:L10',
            'M10:M11',
            'N10:O10',
            'P10:Q10',
            'R10:R11',
            'S10:S11',
        ] as $range) {
            $sheet->mergeCells($range);
        }
    }

    private function row(array $values): array
    {
        return array_pad($values, 19, null);
    }
}
