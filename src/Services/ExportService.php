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

          $festivalMap = array_column($festivals, null, 'id');

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
               'J' => 'Sortiment',
               'K' => 'Sociální sítě',   // ← nový
               'L' => 'Festivaly',
               'M' => 'Cena celkem (Kč)',
               'N' => 'Datum registrace',
          ];

          foreach ($headers as $col => $label) {
               $sheet->setCellValue("{$col}1", $label);
          }

          $sheet->getStyle('A1:N1')->applyFromArray([
               'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
               'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '743a25']],
               'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER
               ],
          ]);
          $sheet->getRowDimension(1)->setRowHeight(22);

          // ── Data ─────────────────────────────────────────────────────────
          $row        = 2;
          $grandTotal = 0;

          foreach ($exhibitors as $ex) {

               // Sestavení textu festivalů do jedné buňky
               $festivalLines = [];
               if (!empty($ex['festival_ids'])) {
                    $festivalIds   = explode(',', $ex['festival_ids']);
                    $spaces        = $ex['spaces']       ? explode(',', $ex['spaces'])       : [];
                    $electricities = $ex['electricities'] ? explode(',', $ex['electricities']) : [];
                    $pricesTotal   = $ex['prices_total'] ? explode(',', $ex['prices_total']) : [];

                    foreach ($festivalIds as $i => $fid) {
                         $fid      = (int) trim($fid);
                         $festival = $festivalMap[$fid] ?? null;
                         if (!$festival) continue;

                         $space = isset($spaces[$i])        ? trim($spaces[$i])        : '—';
                         $elec  = isset($electricities[$i]) ? trim($electricities[$i]) : '—';
                         $price = isset($pricesTotal[$i])   ? (int) trim($pricesTotal[$i]) : 0;

                         $festivalLines[] = sprintf(
                              '%s – %s | %s | %s | %s Kč',
                              $festival['city'],
                              $festival['name'],
                              strtoupper($festival['type']),
                              $space,
                              number_format($price, 0, ',', '.')
                         );
                    }
               }

               $totalPrice  = (int) ($ex['total_price'] ?? 0);
               $grandTotal += $totalPrice;

               $sortiment      = str_replace(["\r\n", "\r", "\n"], ', ', trim($ex['sortiment']       ?? ''));
               $socialNetworks = str_replace(["\r\n", "\r", "\n"], ', ', trim($ex['social_networks'] ?? ''));

               $sheet->setCellValue("A{$row}", $ex['id']);
               $sheet->setCellValue("B{$row}", $ex['ico']          ?? '');
               $sheet->setCellValue("C{$row}", $ex['company']);
               $sheet->setCellValue("D{$row}", $ex['address']);
               $sheet->setCellValue("E{$row}", $ex['dic']          ?? '');
               $sheet->setCellValue("F{$row}", $ex['contact_name']);
               $sheet->setCellValue("G{$row}", $ex['email']);
               $sheet->setCellValue("H{$row}", $ex['phone']);
               $sheet->setCellValue("I{$row}", $ex['website']      ?? '');
               $sheet->setCellValue("J{$row}", $sortiment);
               $sheet->setCellValue("K{$row}", $socialNetworks);  // ← nový
               $sheet->setCellValue("L{$row}", implode("\n", $festivalLines));
               $sheet->setCellValue("M{$row}", $totalPrice);
               $sheet->setCellValue("N{$row}", $ex['created_at']);

               // Zalamování textu ve sloupci festivalů
               $sheet->getStyle("K{$row}")
                    ->getAlignment()
                    ->setWrapText(true);

               // Formát čísla
               $sheet->getStyle("L{$row}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

               // Zebra
               if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                         'fill' => [
                              'fillType' => Fill::FILL_SOLID,
                              'startColor' => ['rgb' => 'FDF5F2']
                         ],
                    ]);
               }

               $row++;
          }

          // ── Suma ─────────────────────────────────────────────────────────
          $sheet->setCellValue("L{$row}", 'CELKEM:');
          $sheet->setCellValue("M{$row}", $grandTotal);
          $sheet->getStyle("L{$row}:M{$row}")->applyFromArray([
               'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
               'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '743a25']
               ],
          ]);
          $sheet->getStyle("M{$row}")->getNumberFormat()->setFormatCode('#,##0');

          // ── Šířky sloupců ────────────────────────────────────────────────
          $widths = [
               'A' => 6,
               'B' => 12,
               'C' => 28,
               'D' => 30,
               'E' => 14,
               'F' => 22,
               'G' => 26,
               'H' => 16,
               'I' => 25,
               'J' => 35,
               'K' => 30,
               'L' => 50,
               'M' => 18,
               'N' => 20,  // ← K nový
          ];
          foreach ($widths as $col => $width) {
               $sheet->getColumnDimension($col)->setWidth($width);
          }

          $sheet->freezePane('A2');

          $tmpFile = tempnam(sys_get_temp_dir(), 'registrace_') . '.xlsx';
          (new Xlsx($spreadsheet))->save($tmpFile);

          return $tmpFile;
     }

     public function generateXlsxFestivals(array $exhibitors, array $festivals, array $pricing): string
     {
          $spreadsheet = new Spreadsheet();
          $sheet       = $spreadsheet->getActiveSheet();
          $sheet->setTitle('Festivaly');

          $festivalMap = array_column($festivals, null, 'id');

          // ── Záhlaví ──────────────────────────────────────────────────────
          $headers = [
               'A' => 'ID',
               'B' => 'Firma',
               'C' => 'IČ',
               'D' => 'Odpovědná osoba',
               'E' => 'E-mail',
               'F' => 'Telefon',
               'G' => 'Festival',
               'H' => 'Město',
               'I' => 'Datum',
               'J' => 'Typ',
               'K' => 'Prostor',
               'L' => 'Elektřina',
               'M' => 'Cena prostor (Kč)',
               'N' => 'Cena elektřina (Kč)',
               'O' => 'Cena celkem (Kč)',
          ];

          foreach ($headers as $col => $label) {
               $sheet->setCellValue("{$col}1", $label);
          }

          $sheet->getStyle('A1:O1')->applyFromArray([
               'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
               'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '743a25']],
               'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
               ],
          ]);
          $sheet->getRowDimension(1)->setRowHeight(22);

          // ── Data ─────────────────────────────────────────────────────────
          $row        = 2;
          $grandTotal = 0;

          foreach ($exhibitors as $ex) {
               $festivalIds   = !empty($ex['festival_ids'])   ? explode(',', $ex['festival_ids'])   : [];
               $spaces        = !empty($ex['spaces'])         ? explode(',', $ex['spaces'])         : [];
               $electricities = !empty($ex['electricities'])  ? explode(',', $ex['electricities'])  : [];
               $pricesSpace   = !empty($ex['prices_space'])   ? explode(',', $ex['prices_space'])   : [];
               $pricesElec    = !empty($ex['prices_elec'])    ? explode(',', $ex['prices_elec'])    : [];
               $pricesTotal   = !empty($ex['prices_total'])   ? explode(',', $ex['prices_total'])   : [];

               $isFirst = true;

               foreach ($festivalIds as $i => $fid) {
                    $fid      = (int) trim($fid);
                    $festival = $festivalMap[$fid] ?? null;
                    if (!$festival) continue;

                    $priceSpace = isset($pricesSpace[$i]) ? (int) trim($pricesSpace[$i]) : 0;
                    $priceElec  = isset($pricesElec[$i])  ? (int) trim($pricesElec[$i])  : 0;
                    $priceTotal = isset($pricesTotal[$i]) ? (int) trim($pricesTotal[$i]) : 0;

                    $grandTotal += $priceTotal;

                    // Kontaktní info jen na prvním řádku vystavovatele
                    if ($isFirst) {
                         $sheet->setCellValue("A{$row}", $ex['id']);
                         $sheet->setCellValue("B{$row}", $ex['company']);
                         $sheet->setCellValue("C{$row}", $ex['ico'] ?? '');
                         $sheet->setCellValue("D{$row}", $ex['contact_name']);
                         $sheet->setCellValue("E{$row}", $ex['email']);
                         $sheet->setCellValue("F{$row}", $ex['phone']);
                         $isFirst = false;
                    }

                    // Získej label prostoru
                    $spaceId    = isset($spaces[$i]) ? trim($spaces[$i]) : '';
                    $spaceLabel = $spaceId;
                    foreach ($pricing['spaces'][$festival['type']] ?? [] as $s) {
                         if ($s['id'] === $spaceId) {
                              $spaceLabel = $s['label'];
                              break;
                         }
                    }

                    // Získej label elektřiny
                    $elecId    = isset($electricities[$i]) ? trim($electricities[$i]) : '';
                    $elecLabel = $elecId;
                    foreach ($pricing['electricity'][$festival['type']] ?? [] as $e) {
                         if ($e['id'] === $elecId) {
                              $elecLabel = $e['label'];
                              break;
                         }
                    }

                    // Datum festivalu
                    $dateFrom = new \DateTime($festival['date_from']);
                    $dateTo   = new \DateTime($festival['date_to']);
                    if ($dateFrom->format('m') === $dateTo->format('m')) {
                         $dateLabel = $dateFrom->format('j') . '–' . $dateTo->format('j. n. Y');
                    } else {
                         $dateLabel = $dateFrom->format('j. n.') . ' – ' . $dateTo->format('j. n. Y');
                    }

                    $sheet->setCellValue("G{$row}", $festival['name']);
                    $sheet->setCellValue("H{$row}", $festival['city']);
                    $sheet->setCellValue("I{$row}", $dateLabel);
                    $sheet->setCellValue("J{$row}", strtoupper($festival['type']));
                    $sheet->setCellValue("K{$row}", $spaceLabel);
                    $sheet->setCellValue("L{$row}", $elecLabel);
                    $sheet->setCellValue("M{$row}", $priceSpace);
                    $sheet->setCellValue("N{$row}", $priceElec);
                    $sheet->setCellValue("O{$row}", $priceTotal);

                    // Formát čísel
                    $sheet->getStyle("M{$row}:O{$row}")
                         ->getNumberFormat()
                         ->setFormatCode('#,##0');

                    // Zebra — podle vystavovatele ne řádku
                    if ($ex['id'] % 2 === 0) {
                         $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                              'fill' => [
                                   'fillType' => Fill::FILL_SOLID,
                                   'startColor' => ['rgb' => 'FDF5F2']
                              ],
                         ]);
                    }

                    $row++;
               }

               // Prázdný řádek mezi vystavovateli
               $row++;
          }

          // ── Grand total ───────────────────────────────────────────────────
          $sheet->setCellValue("N{$row}", 'CELKEM:');
          $sheet->setCellValue("O{$row}", $grandTotal);
          $sheet->getStyle("N{$row}:O{$row}")->applyFromArray([
               'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
               'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '743a25']],
          ]);
          $sheet->getStyle("O{$row}")
               ->getNumberFormat()
               ->setFormatCode('#,##0');

          // ── Šířky sloupců ────────────────────────────────────────────────
          $widths = [
               'A' => 6,
               'B' => 28,
               'C' => 12,
               'D' => 22,
               'E' => 26,
               'F' => 16,
               'G' => 25,
               'H' => 18,
               'I' => 18,
               'J' => 8,
               'K' => 16,
               'L' => 35,
               'M' => 18,
               'N' => 20,
               'O' => 18,
          ];
          foreach ($widths as $col => $width) {
               $sheet->getColumnDimension($col)->setWidth($width);
          }

          $sheet->freezePane('A2');

          $tmpFile = tempnam(sys_get_temp_dir(), 'festivaly_') . '.xlsx';
          (new Xlsx($spreadsheet))->save($tmpFile);

          return $tmpFile;
     }
}
