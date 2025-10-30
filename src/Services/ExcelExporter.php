<?php

namespace App\Services;

class ExcelExporter
{
    /**
     * Exporta em formato TABULAR com cabeçalhos personalizados
     */
    public function export(array $groupedReports, array $filters): void
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            die('❌ Erro: Execute "composer require phpoffice/phpspreadsheet" para habilitar exportação Excel');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Relatório de Engajamento');

        // ========== CABEÇALHOS ==========
        $headers = [
            'A1' => 'Assunto',
            'B1' => 'Início do Disparo',
            'C1' => 'Término do Disparo',
            'D1' => 'Intervalo do Disparo',
            'E1' => 'Total de Envios',
            'F1' => 'Total de Recebidos',
            'G1' => 'Taxa de Entrega (%)',
            'H1' => 'Taxa de Abertura (%)',
            'I1' => 'Taxa de Clique - CTR (%)',
            'J1' => 'Total de Aberturas',
            'K1' => 'Total de Cliques',
            'L1' => 'Total de Entregas',
            'M1' => 'Total de Falhas'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Estilização do cabeçalho com as cores do projeto
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1aa97f'] // Cor principal do projeto
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '168a68']
                ]
            ]
        ];

        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // ========== DADOS ==========
        $row = 2;

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

            // Estilo zebrado nas linhas (alternando cores)
            $rowColor = ($row % 2 == 0) ? 'F4F7F6' : 'FFFFFF'; // Cor de fundo alternada
            $dataStyle = [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $rowColor]
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'E9ECEF']
                    ]
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];
            $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($dataStyle);

            $row++;
        }

        // Auto-size e ajustes finais
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Congelar painel do cabeçalho
        $sheet->freezePane('A2');
        
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