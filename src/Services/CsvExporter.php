<?php

namespace App\Services;

class CsvExporter
{
    /**
     * ATUALIZADO: Exporta em formato TABULAR (banco de dados) - APENAS DADOS
     */
    public function export(array $groupedReports, array $filters): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_engajamento_' . date('Y-m-d_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // FORMATO TABULAR - APENAS CABEÇALHOS E DADOS
        $headers = [
            'Assunto',
            'Início do Disparo',
            'Término do Disparo',
            'Intervalo do Disparo',
            'Total de Envios',
            'Total de Recebidos',
            'Taxa de Entrega (%)',
            'Taxa de Abertura (%)',
            'Taxa de Clique - CTR (%)',
            'Total de Aberturas',
            'Total de Cliques',
            'Total de Entregas',
            'Total de Falhas',
        ];
        
        fputcsv($output, $headers);

        // DADOS (uma linha por assunto)
        foreach ($groupedReports as $subject => $report) {
            $row = [
                $subject,
                $report['metricas']['Início do Disparo'] ?? 'N/A',
                $report['metricas']['Término do Disparo'] ?? 'N/A',
                $report['metricas']['Intervalo do Disparo'] ?? 'N/A',
                $report['metricas']['Total de Envios'] ?? 0,
                $report['metricas']['Total de Recebidos'] ?? 0,
                number_format($report['metricas']['Taxa de Entrega (%)'] ?? 0, 2, ',', '.'),
                number_format($report['metricas']['Taxa de Abertura (%)'] ?? 0, 2, ',', '.'),
                number_format($report['metricas']['Taxa de Clique - CTR (%)'] ?? 0, 2, ',', '.'),
                $report['metricas']['Total de Aberturas'] ?? 0,
                $report['metricas']['Total de Cliques'] ?? 0,
                $report['metricas']['Total de Entregas'] ?? 0,
                $report['metricas']['Total de Falhas'] ?? 0,
            ];
            
            fputcsv($output, $row);
        }
        
        fclose($output);
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