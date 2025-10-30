<?php

namespace App\Services;

class ExcelExporter
{
    /**
     * Exporta em formato TABULAR - APENAS DADOS (sem cabeçalhos)
     */
    public function export(array $groupedReports, array $filters): void
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            die('❌ Erro: Execute "composer require phpoffice/phpspreadsheet" para habilitar exportação Excel');
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Relatório de Engajamento');

        $row = 1;

        // ========== DADOS ==========
        
        foreach ($groupedReports as $subject => $report) {
            $sheet->setCellValue('A' . $row, $subject);
            $sheet->setCellValue('B' . $row, $report['metricas']['Início do Disparo'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, $report['metricas']['Término do Disparo'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $report['metricas']['Intervalo do Disparo'] ?? 'N/A');
            $sheet->setCellValue('E' . $row, $report['metricas']['Total de Envios'] ?? 0);
            $sheet->setCellValue('F' . $row, $report['metricas']['Total de Recebidos'] ?? 0);
            $sheet->setCellValue('G' . $row, $report['metricas']['Taxa de Entrega (%)'] ?? 0);
            $sheet->setCellValue('H' . $row, $report['metricas']['Taxa de Abertura (%)'] ?? 0);
            $sheet->setCellValue('I' . $row, $report['metricas']['Taxa de Clique - CTR (%)'] ?? 0);
            $sheet->setCellValue('J' . $row, $report['metricas']['Total de Aberturas'] ?? 0);
            $sheet->setCellValue('K' . $row, $report['metricas']['Total de Cliques'] ?? 0);
            $sheet->setCellValue('L' . $row, $report['metricas']['Total de Entregas'] ?? 0);
            $sheet->setCellValue('M' . $row, $report['metricas']['Total de Falhas'] ?? 0);
            
            // Formatação numérica
            $sheet->getStyle('G' . $row . ':I' . $row)->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle('E' . $row . ':F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('J' . $row . ':M' . $row)->getNumberFormat()->setFormatCode('#,##0');

            $row++;
        }

        // Auto-size
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Exportação
        $filename = 'relatorio_engajamento_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}