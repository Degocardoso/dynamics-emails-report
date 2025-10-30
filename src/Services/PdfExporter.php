<?php

namespace App\Services;

class PdfExporter
{
    /**
     * Exporta relatórios para formato PDF (formato tela)
     */
    public function export(array $groupedReports, array $filters): void
    {
        if (!class_exists('\TCPDF')) {
            die('❌ Erro: Execute "composer require tecnickcom/tcpdf" para habilitar exportação PDF');
        }
        
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        
        // Configurações
        $pdf->SetCreator('Dynamics Email Report - FECAP');
        $pdf->SetAuthor('FECAP');
        $pdf->SetTitle('Relatório de Engajamento');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        
        $pdf->AddPage();
        
        // ========== TÍTULO ==========
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(26, 169, 127);
        $pdf->Cell(0, 10, 'Relatório de Engajamento', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);
        
        // ========== FILTROS ==========
        $pdf->SetFont('helvetica', '', 10);
        
        if (isset($filters['assunto'])) {
            $pdf->Cell(0, 5, 'Tipo de Busca: Por Assunto', 0, 1);
            $pdf->Cell(0, 5, 'Assunto(s): ' . $filters['assunto'], 0, 1);
            $pdf->Cell(0, 5, 'Data Início: ' . $this->formatDate($filters['data_inicio'] ?? 'N/A'), 0, 1);
        } else {
            $pdf->Cell(0, 5, 'Tipo de Busca: Por Intervalo de Data', 0, 1);
            $pdf->Cell(0, 5, 'Data Início: ' . $this->formatDate($filters['data_inicio'] ?? 'N/A'), 0, 1);
            $pdf->Cell(0, 5, 'Data Fim: ' . $this->formatDate($filters['data_fim'] ?? 'N/A'), 0, 1);
        }
        
        if (isset($filters['remove_defaults']) && $filters['remove_defaults'] === 'yes') {
            $pdf->Cell(0, 5, 'Filtro: E-mails padrão removidos', 0, 1);
        }
        
        if (!empty($filters['test_recipients'])) {
            $pdf->Cell(0, 5, 'Testes removidos: ' . $filters['test_recipients'], 0, 1);
        }
        
        $pdf->Ln(10);
        
        // ========== DADOS POR ASSUNTO ==========
        foreach ($groupedReports as $subject => $report) {
            // Adiciona nova página se necessário
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
            }
            
            // Título do assunto
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetTextColor(26, 169, 127);
            $pdf->MultiCell(0, 8, $subject, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(3);
            
            // Métricas Principais
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 6, 'Métricas Principais', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            
            foreach ($report['metricas'] as $metrica => $valor) {
                // Formata valor
                if (strpos($metrica, 'Taxa') !== false || strpos($metrica, 'CTR') !== false || strpos($metrica, '%') !== false) {
                    $valorFormatado = number_format($valor, 2, ',', '.') . '%';
                } elseif (is_numeric($valor)) {
                    $valorFormatado = number_format($valor, 0, ',', '.');
                } else {
                    $valorFormatado = $valor;
                }
                
                $pdf->Cell(120, 6, $metrica, 1, 0, 'L');
                $pdf->Cell(60, 6, $valorFormatado, 1, 1, 'R');
            }
            
            $pdf->Ln(5);
            
            // Detalhes por Status do E-mail
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 6, 'Detalhes por Status do E-mail', 0, 1);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(100, 5, 'Status', 1, 0, 'L');
            $pdf->Cell(40, 5, 'Quantidade', 1, 0, 'R');
            $pdf->Cell(40, 5, 'Percentual', 1, 1, 'R');
            
            $pdf->SetFont('helvetica', '', 9);
            $total = $report['metricas']['Total de Envios'];
            
            foreach ($report['contadoresEmail'] as $status => $count) {
                if ($count > 0) {
                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                    $pdf->Cell(100, 5, $status, 1, 0, 'L');
                    $pdf->Cell(40, 5, number_format($count, 0, ',', '.'), 1, 0, 'R');
                    $pdf->Cell(40, 5, number_format($percentage, 2, ',', '.') . '%', 1, 1, 'R');
                }
            }
            
            $pdf->Ln(3);
            
            // Razão do Status
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 6, 'Razão do Status', 0, 1);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(100, 5, 'Status', 1, 0, 'L');
            $pdf->Cell(40, 5, 'Quantidade', 1, 0, 'R');
            $pdf->Cell(40, 5, 'Percentual', 1, 1, 'R');
            
            $pdf->SetFont('helvetica', '', 9);
            
            foreach ($report['contadoresHeader'] as $status => $count) {
                if ($count > 0) {
                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                    $pdf->Cell(100, 5, $status, 1, 0, 'L');
                    $pdf->Cell(40, 5, number_format($count, 0, ',', '.'), 1, 0, 'R');
                    $pdf->Cell(40, 5, number_format($percentage, 2, ',', '.') . '%', 1, 1, 'R');
                }
            }
            
            $pdf->Ln(10);
        }
        
        // ========== RODAPÉ ==========
        $pdf->SetY(-15);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 10, 'Gerado em ' . date('d/m/Y H:i:s') . ' - Página ' . $pdf->getAliasNumPage() . ' de ' . $pdf->getAliasNbPages(), 0, 0, 'C');
        
        // ========== EXPORTAÇÃO ==========
        $pdf->Output('relatorio_engajamento_' . date('Y-m-d_His') . '.pdf', 'D');
        exit;
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