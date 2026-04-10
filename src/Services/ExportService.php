<?php
declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportService
{
    /**
     * Vygeneruje XLSX a vrátí cestu k dočasnému souboru
     */
    public function generateXlsx(array $exhibitors, array $festivals): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registrace');

        // Mapa festival ID → název
        $festivalMap = array_column($festivals, 'name', 'id');

        // ── Záhlaví ──────────────────────────────────────────────────────
        $headers = [
            'A' => 'ID',
            'B' => 'IČ',
            'C' => 'Firma',
            'D' => 'Adresa',
            'E' => 'DIČ',
            'F' => 'Odpovědná osoba',
            'G' => 'E-mail',
            'H' => 'Telefon',
            'I' => 'Web',
            'J' => 'Sociální sítě',
            'K' => 'Sortiment',
            'L' => 'Festivaly',
            'M' => 'Souhlas s OP',
            'N' => 'IP adresa',
            'O' => 'Datum registrace',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        // Styl záhlaví
        $headerStyle = [
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '743a25'], // indigo-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'FFFFFF'],
                ],
            ],
        ];

        $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // ── Data ─────────────────────────────────────────────────────────
        $row = 2;
        foreach ($exhibitors as $ex) {

            // Sestavení názvů festivalů
            $festivalNames = [];
            if (!empty($ex['festival_ids'])) {
                foreach (explode(',', $ex['festival_ids']) as $fid) {
                    $fid = (int) trim($fid);
                    if (isset($festivalMap[$fid])) {
                        $festivalNames[] = $festivalMap[$fid];
                    }
                }
            }

            $sheet->setCellValue("A{$row}", $ex['id']);
            $sheet->setCellValue("B{$row}", $ex['ico']             ?? '');
            $sheet->setCellValue("C{$row}", $ex['company']);
            $sheet->setCellValue("D{$row}", $ex['address']);
            $sheet->setCellValue("E{$row}", $ex['dic']             ?? '');
            $sheet->setCellValue("F{$row}", $ex['contact_name']);
            $sheet->setCellValue("G{$row}", $ex['email']);
            $sheet->setCellValue("H{$row}", $ex['phone']);
            $sheet->setCellValue("I{$row}", $ex['website']         ?? '');
            $sheet->setCellValue("J{$row}", $ex['social_networks'] ?? '');
            $sheet->setCellValue("K{$row}", $ex['sortiment']);
            $sheet->setCellValue("L{$row}", implode(', ', $festivalNames));
            $sheet->setCellValue("M{$row}", $ex['terms_agreed'] ? 'Ano' : 'Ne');
            $sheet->setCellValue("N{$row}", $ex['ip_address']      ?? '');
            $sheet->setCellValue("O{$row}", $ex['created_at']);

            // Zebra řádkování
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FDF5F2'], // indigo-50
                    ],
                ]);
            }

            $row++;
        }

        // ── Šířky sloupců ────────────────────────────────────────────────
        $widths = [
            'A' => 6,  'B' => 12, 'C' => 30, 'D' => 35, 'E' => 14,
            'F' => 25, 'G' => 28, 'H' => 18, 'I' => 30, 'J' => 30,
            'K' => 40, 'L' => 35, 'M' => 12, 'N' => 16, 'O' => 20,
        ];

        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Zalamování textu pro dlouhé sloupce
        $sheet->getStyle('J1:K' . ($row - 1))
              ->getAlignment()
              ->setWrapText(true);

        // Ukotvení záhlaví
        $sheet->freezePane('A2');

        // ── Uložení do temp souboru ───────────────────────────────────────
        $tmpFile = tempnam(sys_get_temp_dir(), 'registrace_') . '.xlsx';
        $writer  = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        return $tmpFile;
    }
}