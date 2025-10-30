<?php

namespace App\Services;

class XmlExporter
{
    /**
     * ATUALIZADO: Exporta em formato TABULAR para XML
     */
    public function export(array $groupedReports, array $filters): void
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><relatorio></relatorio>');
        
        // ========== CABEÇALHO ==========
        $cabecalho = $xml->addChild('cabecalho');
        $cabecalho->addChild('titulo', 'Relatório de Engajamento de E-mails - Dynamics 365');
        $cabecalho->addChild('data_geracao', date('Y-m-d H:i:s'));
        $cabecalho->addChild('total_grupos', count($groupedReports));
        
        // ========== FILTROS ==========
        $filtrosXml = $xml->addChild('filtros');
        
        if (isset($filters['assunto'])) {
            $filtrosXml->addChild('tipo_busca', 'Por Assunto');
            $filtrosXml->addChild('assunto', htmlspecialchars($filters['assunto'], ENT_XML1, 'UTF-8'));
            $filtrosXml->addChild('data_inicio', $this->formatDate($filters['data_inicio']));
        } else {
            $filtrosXml->addChild('tipo_busca', 'Por Intervalo de Data');
            $filtrosXml->addChild('data_inicio', $this->formatDate($filters['data_inicio']));
            $filtrosXml->addChild('data_fim', $this->formatDate($filters['data_fim']));
        }
        
        // ========== DADOS ==========
        $dados = $xml->addChild('dados');
        
        foreach ($groupedReports as $subject => $report) {
            $registro = $dados->addChild('registro');
            $registro->addAttribute('id', md5($subject));
            
            // Campos principais
            $registro->addChild('assunto', htmlspecialchars($subject, ENT_XML1, 'UTF-8'));
            $registro->addChild('inicio_disparo', htmlspecialchars($report['metricas']['Início do Disparo'] ?? 'N/A', ENT_XML1, 'UTF-8'));
            $registro->addChild('termino_disparo', htmlspecialchars($report['metricas']['Término do Disparo'] ?? 'N/A', ENT_XML1, 'UTF-8'));
            $registro->addChild('total_envios', $report['metricas']['Total de Envios'] ?? 0);
            $registro->addChild('total_recebidos', $report['metricas']['Total de Recebidos'] ?? 0);
            $registro->addChild('taxa_entrega', number_format($report['metricas']['Taxa de Entrega (%)'] ?? 0, 2, '.', ''));
            $registro->addChild('taxa_abertura', number_format($report['metricas']['Taxa de Abertura (%)'] ?? 0, 2, '.', ''));
            $registro->addChild('taxa_clique_ctr', number_format($report['metricas']['Taxa de Clique - CTR (%)'] ?? 0, 2, '.', ''));
            $registro->addChild('total_aberturas', $report['metricas']['Total de Aberturas'] ?? 0);
            $registro->addChild('total_cliques', $report['metricas']['Total de Cliques'] ?? 0);
            $registro->addChild('total_entregas', $report['metricas']['Total de Entregas'] ?? 0);
            $registro->addChild('total_falhas', $report['metricas']['Total de Falhas'] ?? 0);
            
            // Detalhamento adicional
            $statusEmails = $registro->addChild('detalhamento_status_email');
            foreach ($report['contadoresEmail'] as $status => $count) {
                if ($count > 0) {
                    $statusItem = $statusEmails->addChild('status');
                    $statusItem->addChild('nome', htmlspecialchars($status, ENT_XML1, 'UTF-8'));
                    $statusItem->addChild('quantidade', $count);
                    
                    $total = $report['metricas']['Total de Envios'] ?? 1;
                    $percentual = $total > 0 ? ($count / $total) * 100 : 0;
                    $statusItem->addChild('percentual', number_format($percentual, 2, '.', ''));
                }
            }
            
            $statusCodes = $registro->addChild('detalhamento_razao_status');
            foreach ($report['contadoresHeader'] as $status => $count) {
                if ($count > 0) {
                    $statusItem = $statusCodes->addChild('status');
                    $statusItem->addChild('nome', htmlspecialchars($status, ENT_XML1, 'UTF-8'));
                    $statusItem->addChild('quantidade', $count);
                    
                    $total = $report['metricas']['Total de Envios'] ?? 1;
                    $percentual = $total > 0 ? ($count / $total) * 100 : 0;
                    $statusItem->addChild('percentual', number_format($percentual, 2, '.', ''));
                }
            }
        }
        
        // ========== RESUMO ==========
        $summary = $this->calculateSummary($groupedReports);
        
        $resumo = $xml->addChild('resumo_geral');
        $resumo->addChild('total_grupos', count($groupedReports));
        $resumo->addChild('total_geral_envios', $summary['total_sends']);
        $resumo->addChild('total_geral_recebidos', $summary['total_delivered']);
        $resumo->addChild('taxa_media_abertura', number_format($summary['avg_open_rate'], 2, '.', ''));
        $resumo->addChild('taxa_media_entrega', number_format($summary['avg_delivery_rate'], 2, '.', ''));
        
        // ========== EXPORTAÇÃO ==========
        $filename = 'relatorio_engajamento_' . date('Y-m-d_His') . '.xml';
        
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Formata XML com indentação
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        
        echo $dom->saveXML();
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
            $dateObj = new \DateTime($date);
            return $dateObj->format('d/m/Y');
        } catch (\Exception $e) {
            return $date;
        }
    }
}