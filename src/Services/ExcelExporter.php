<?php

namespace App\Services;

class ExcelExporter
{
    /**
     * ATUALIZADO: Exporta em formato TABULAR para XLSX
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
        
        // ========== CABEÇALHO ==========
        $sheet->setCellValue('A' . $row, 'RELATÓRIO DE ENGAJAMENTO - DYNAMICS 365');
        $sheet->mergeCells('A' . $row . ':L' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1AA97F');
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row += 2;
        
        // ========== INFORMAÇÕES ==========
        $sheet->setCellValue('A' . $row, 'Gerado em:');
        $sheet->setCellValue('B' . $row, date('d/m/Y H:i:s'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'FILTROS APLICADOS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;
        
        if (isset($filters['assunto'])) {
            $sheet->setCellValue('A' . $row, 'Tipo de Busca:');
            $sheet->setCellValue('B' . $row, 'Por Assunto');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Assunto(s):');
            $sheet->setCellValue('B' . $row, $filters['assunto']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Data Início:');
            $sheet->setCellValue('B' . $row, $this->formatDate($filters['data_inicio']));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        } else {
            $sheet->setCellValue('A' . $row, 'Tipo de Busca:');
            $sheet->setCellValue('B' . $row, 'Por Intervalo de Data');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Data Início:');
            $sheet->setCellValue('B' . $row, $this->formatDate($filters['data_inicio']));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Data Fim:');
            $sheet->setCellValue('B' . $row, $this->formatDate($filters['data_fim']));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }
        
        $row += 2;
        
        // ========== CABEÇALHOS DA TABELA ==========
        $headers = [
            'A' => 'Assunto',
            'B' => 'Início do Disparo',
            'C' => 'Término do Disparo',
            'D' => 'Intervalo do Disparo',
            'E' => 'Total de Envios',
            'F' => 'Total de Recebidos',
            'G' => 'Taxa de Entrega (%)',
            'H' => 'Taxa de Abertura (%)',
            'I' => 'Taxa de Clique - CTR (%)',
            'J' => 'Total de Aberturas',
            'K' => 'Total de Cliques',
            'L' => 'Total de Entregas',
            'M' => 'Total de Falhas',
        ];
        
        $headerRow = $row;
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . $row, $header);
        }
        
        // Estilo do cabeçalho
        $sheet->getStyle('A' . $row . ':M' . $row)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A' . $row . ':M' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('495057');
        $sheet->getStyle('A' . $row . ':M' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $row . ':M' . $row)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        $row++;
        
        // ========== DADOS ==========
        $dataStartRow = $row;
        
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
            
            // Formatação
            $sheet->getStyle('G' . $row . ':I' . $row)->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle('E' . $row . ':F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('J' . $row . ':M' . $row)->getNumberFormat()->setFormatCode('#,##0');
            
            $sheet->getStyle('A' . $row . ':M' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            if (($row - $dataStartRow) % 2 == 0) {
                $sheet->getStyle('A' . $row . ':M' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8F9FA');
            }
            
            $row++;
        }
        
        $row += 2;
        
        // ========== RESUMO ==========
        $summary = $this->calculateSummary($groupedReports);
        
        $sheet->setCellValue('A' . $row, 'RESUMO GERAL');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E9ECEF');
        $row++;
        
        $summaryData = [
            'Total de Grupos' => count($groupedReports),
            'Total Geral de Envios' => $summary['total_sends'],
            'Total Geral de Recebidos' => $summary['total_delivered'],
            'Taxa Média de Abertura (%)' => number_format($summary['avg_open_rate'], 2, '.', ''),
            'Taxa Média de Entrega (%)' => number_format($summary['avg_delivery_rate'], 2, '.', ''),
        ];
        
        foreach ($summaryData as $label => $value) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            
            if (strpos($label, '%') !== false) {
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('0.00');
            }
            
            $row++;
        }
        
        // Auto-size
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $sheet->freezePane('A' . ($headerRow + 1));
        $sheet->setAutoFilter('A' . $headerRow . ':M' . ($row - 1));
        
        // Exportação
        $filename = 'relatorio_engajamento_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    private function calculateSummary(array $groupedReports): array
    {
        $totalSends = 0;
        $totalDelivered = 0;
        $sumOpenRates = 0;
        $sumDeliveryRates = 0;
        $count = count($groupedReports);
        
        foreach ($groupedReports as $report) {
            $totalSends += $report['metricas']['Total de Envios'] ?? 0;
            $totalDelivered += $report['metricas']['Total de Recebidos'] ?? 0;
            $sumOpenRates += $report['metricas']['Taxa de Abertura (%)'] ?? 0;
            $sumDeliveryRates += $report['metricas']['Taxa de Entrega (%)'] ?? 0;
        }
        
        return [
            'total_sends' => $totalSends,
            'total_delivered' => $totalDelivered,
            'avg_open_rate' => $count > 0 ? $sumOpenRates / $count : 0,
            'avg_delivery_rate' => $count > 0 ? $sumDeliveryRates / $count : 0,
        ];
    }
    
    private function formatDate(string $date): string
    {
        try {
            $dateObj = new \DateTime($date, new \DateTimeZone('UTC'));
            $dateObj->setTimezone(new \DateTimeZone('America/Sao_Paulo'));
            return $dateObj->format('d/m/Y H:i:s');
        } catch (\Exception $e) {
            return $date;
        }
    }
}