<?php

namespace App\Services;

class XmlExporter
{
    /**
     * Exporta em formato XML estruturado com dados tabulares
     */
    public function export(array $groupedReports, array $filters): void
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><relatorio_engajamento></relatorio_engajamento>');
        $xml->addAttribute('gerado_em', date('Y-m-d H:i:s'));
        $xml->addAttribute('total_registros', count($groupedReports));

        $dados = $xml->addChild('dados');

        foreach ($groupedReports as $subject => $report) {
            $registro = $dados->addChild('registro');
            $registro->addAttribute('id', md5($subject));

            // Campos principais (estruturados como cabeçalhos e dados)
            $registro->addChild('assunto', htmlspecialchars($subject, ENT_XML1, 'UTF-8'));
            $registro->addChild('inicio_disparo', htmlspecialchars($report['metricas']['Início do Disparo'] ?? 'N/A', ENT_XML1, 'UTF-8'));
            $registro->addChild('termino_disparo', htmlspecialchars($report['metricas']['Término do Disparo'] ?? 'N/A', ENT_XML1, 'UTF-8'));
            $registro->addChild('intervalo_disparo', htmlspecialchars($report['metricas']['Intervalo do Disparo'] ?? 'N/A', ENT_XML1, 'UTF-8'));
            $registro->addChild('total_envios', $report['metricas']['Total de Envios'] ?? 0);
            $registro->addChild('total_recebidos', $report['metricas']['Total de Recebidos'] ?? 0);
            $registro->addChild('taxa_entrega', number_format($report['metricas']['Taxa de Entrega (%)'] ?? 0, 2, '.', ''));
            $registro->addChild('taxa_abertura', number_format($report['metricas']['Taxa de Abertura (%)'] ?? 0, 2, '.', ''));
            $registro->addChild('taxa_clique_ctr', number_format($report['metricas']['Taxa de Clique - CTR (%)'] ?? 0, 2, '.', ''));
            $registro->addChild('total_aberturas', $report['metricas']['Total de Aberturas'] ?? 0);
            $registro->addChild('total_cliques', $report['metricas']['Total de Cliques'] ?? 0);
            $registro->addChild('total_entregas', $report['metricas']['Total de Entregas'] ?? 0);
            $registro->addChild('total_falhas', $report['metricas']['Total de Falhas'] ?? 0);
        }
        
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
}