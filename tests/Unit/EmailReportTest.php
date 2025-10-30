<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\EmailReport;

class EmailReportTest extends TestCase
{
    public function testGroupBySubject()
    {
        $emails = [
            ['subject' => 'Test 1', 'cad_statusemail' => 6, 'statuscode' => 3],
            ['subject' => 'Test 1', 'cad_statusemail' => 7, 'statuscode' => 3],
            ['subject' => 'Test 2', 'cad_statusemail' => 6, 'statuscode' => 3],
        ];
        
        $grouped = EmailReport::groupBySubject($emails);
        
        $this->assertCount(2, $grouped);
        $this->assertCount(2, $grouped['Test 1']);
        $this->assertCount(1, $grouped['Test 2']);
    }

    public function testCalculateReportWithEmptyArray()
    {
        $report = EmailReport::calculateReport([]);
        
        $this->assertArrayHasKey('metricas', $report);
        $this->assertEquals(0, $report['metricas']['Total de Envios']);
    }

    public function testCalculateReportMetrics()
    {
        $emails = [
            ['subject' => 'Test', 'cad_statusemail' => 3, 'statuscode' => 3, 'senton' => '2025-10-20T12:00:00Z'],
            ['subject' => 'Test', 'cad_statusemail' => 6, 'statuscode' => 3, 'senton' => '2025-10-20T12:30:00Z'],
            ['subject' => 'Test', 'cad_statusemail' => 7, 'statuscode' => 3, 'senton' => '2025-10-20T13:00:00Z'],
            ['subject' => 'Test', 'cad_statusemail' => 2, 'statuscode' => 3, 'senton' => '2025-10-20T13:30:00Z'],
        ];
        
        $report = EmailReport::calculateReport($emails);
        
        $this->assertEquals(4, $report['metricas']['Total de Envios']);
        $this->assertEquals(3, $report['metricas']['Total de Recebidos']);
        $this->assertEquals(75, $report['metricas']['Taxa de Entrega (%)']);
    }

    public function testCalculateReportCounters()
    {
        $emails = [
            ['subject' => 'Test', 'cad_statusemail' => 6, 'statuscode' => 3],
            ['subject' => 'Test', 'cad_statusemail' => 6, 'statuscode' => 3],
            ['subject' => 'Test', 'cad_statusemail' => 7, 'statuscode' => 3],
        ];
        
        $report = EmailReport::calculateReport($emails);
        
        $this->assertEquals(2, $report['contadoresEmail']['Aberto']);
        $this->assertEquals(1, $report['contadoresEmail']['Clique']);
        $this->assertEquals(3, $report['contadoresHeader']['Enviado']);
    }

    public function testGenerateGroupedReports()
    {
        $emails = [
            ['subject' => 'Campaign A', 'cad_statusemail' => 6, 'statuscode' => 3],
            ['subject' => 'Campaign A', 'cad_statusemail' => 7, 'statuscode' => 3],
            ['subject' => 'Campaign B', 'cad_statusemail' => 3, 'statuscode' => 3],
        ];
        
        $reports = EmailReport::generateGroupedReports($emails);
        
        $this->assertCount(2, $reports);
        $this->assertArrayHasKey('Campaign A', $reports);
        $this->assertArrayHasKey('Campaign B', $reports);
        $this->assertEquals(2, $reports['Campaign A']['metricas']['Total de Envios']);
        $this->assertEquals(1, $reports['Campaign B']['metricas']['Total de Envios']);
    }

    public function testCalculateOpenRate()
    {
        $emails = [
            ['subject' => 'Test', 'cad_statusemail' => 3, 'statuscode' => 3],
            ['subject' => 'Test', 'cad_statusemail' => 3, 'statuscode' => 3],
            ['subject' => 'Test', 'cad_statusemail' => 6, 'statuscode' => 3],
            ['subject' => 'Test', 'cad_statusemail' => 7, 'statuscode' => 3],
        ];
        
        $report = EmailReport::calculateReport($emails);
        
        $this->assertEquals(50, $report['metricas']['Taxa de Abertura (%)']);
    }

    public function testCalculateCTR()
    {
        $emails = [
            ['subject' => 'Test', 'cad_statusemail' => 3, 'statuscode' => 3],
            ['subject' => 'Test', 'cad_statusemail' => 6, 'statuscode' => 3],
            ['subject' => 'Test', 'cad_statusemail' => 7, 'statuscode' => 3],
            ['subject' => 'Test', 'cad_statusemail' => 7, 'statuscode' => 3],
        ];
        
        $report = EmailReport::calculateReport($emails);
        
        $this->assertEquals(50, $report['metricas']['Taxa de Clique - CTR (%)']);
    }

    // ========== NOVOS TESTES DE FILTROS ==========

    /**
     * Testa filtro de e-mails padrão
     */
    public function testFilterDefaultEmails()
    {
        // Cria arquivo de configuração temporário
        $configDir = __DIR__ . '/../../config';
        $configFile = $configDir . '/default_emails.php';
        
        if (!is_dir($configDir)) {
            mkdir($configDir, 0777, true);
        }
        
        file_put_contents($configFile, "<?php\nreturn ['default_subjects' => ['Confirmação', 'Automático']];");
        
        $emails = [
            ['subject' => 'Newsletter Semanal', 'cad_statusemail' => 6],
            ['subject' => 'Confirmação de Cadastro', 'cad_statusemail' => 6],
            ['subject' => 'Campanha Black Friday', 'cad_statusemail' => 6],
            ['subject' => 'E-mail Automático', 'cad_statusemail' => 6],
        ];
        
        $filtered = EmailReport::filterEmails($emails, true, []);
        
        $this->assertCount(2, $filtered, 'Deve remover 2 e-mails padrão');
        $this->assertEquals('Newsletter Semanal', $filtered[0]['subject']);
        $this->assertEquals('Campanha Black Friday', $filtered[1]['subject']);
        
        // Limpa arquivo temporário
        if (file_exists($configFile)) {
            unlink($configFile);
        }
    }

    /**
     * Testa filtro de destinatários de teste
     */
    public function testFilterTestRecipients()
    {
        $emails = [
            [
                'subject' => 'Essa vaga está te esperando! | Boletim de Oportunidades da Semana',
                'torecipients' => 'antonio.ferreira@fecap.br',
                'cad_statusemail' => 6
            ],
            [
                'subject' => 'Essa vaga está te esperando! | Boletim de Oportunidades da Semana',
                'torecipients' => 'Sara.alves@fecap.b',
                'cad_statusemail' => 6
            ],
            [
                'subject' => 'Test 3',
                'torecipients' => 'cliente@fecap.br',
                'cad_statusemail' => 6
            ],
            [
                'subject' => 'Test 4',
                'torecipients' => 'teste2@fecap.br',
                'cad_statusemail' => 6
            ],
        ];
        
        $testRecipients = ['antonio.ferreira@fecap.br', 'Sara.alves@fecap.b'];
        $filtered = EmailReport::filterEmails($emails, false, $testRecipients);
        
        $this->assertCount(2, $filtered, 'Deve remover 2 destinatários de teste');
        $this->assertEquals('usuario@exemplo.com', $filtered[0]['torecipients']);
        $this->assertEquals('cliente@fecap.br', $filtered[1]['torecipients']);
    }

    /**
     * Testa filtro combinado (padrão + teste)
     */
    public function testFilterCombined()
    {
        $configDir = __DIR__ . '/../../config';
        $configFile = $configDir . '/default_emails.php';
        
        if (!is_dir($configDir)) {
            mkdir($configDir, 0777, true);
        }
        
        file_put_contents($configFile, "<?php\nreturn ['default_subjects' => ['Automático']];");
        
        $emails = [
            [
                'subject' => 'Newsletter',
                'torecipients' => 'usuario@exemplo.com',
                'cad_statusemail' => 6
            ],
            [
                'subject' => 'E-mail Automático',
                'torecipients' => 'usuario@exemplo.com',
                'cad_statusemail' => 6
            ],
            [
                'subject' => 'Campanha',
                'torecipients' => 'teste@exemplo.com',
                'cad_statusemail' => 6
            ],
            [
                'subject' => 'Promoção',
                'torecipients' => 'cliente@fecap.br',
                'cad_statusemail' => 6
            ],
        ];
        
        $testRecipients = ['teste@exemplo.com'];
        $filtered = EmailReport::filterEmails($emails, true, $testRecipients);
        
        $this->assertCount(2, $filtered, 'Deve remover 1 padrão e 1 teste');
        
        if (file_exists($configFile)) {
            unlink($configFile);
        }
    }

    /**
     * Testa se filtro não remove quando não deve
     */
    public function testFilterDoesNotRemoveValid()
    {
        $emails = [
            [
                'subject' => 'Newsletter',
                'torecipients' => 'usuario@exemplo.com',
                'cad_statusemail' => 6
            ],
            [
                'subject' => 'Campanha',
                'torecipients' => 'cliente@fecap.br',
                'cad_statusemail' => 6
            ],
        ];
        
        $filtered = EmailReport::filterEmails($emails, false, []);
        
        $this->assertCount(2, $filtered, 'Não deve remover nenhum e-mail válido');
    }

    /**
     * Testa cálculo de intervalo entre disparos
     */
    public function testCalculateIntervalAnalysis()
    {
        $groupedReports = [
            'Campaign A' => [
                'minTimestamp' => strtotime('2025-10-20 10:00:00'),
                'maxTimestamp' => strtotime('2025-10-20 10:25:00'), // 25 minutos
                'metricas' => ['Total de Envios' => 100]
            ],
            'Campaign B' => [
                'minTimestamp' => strtotime('2025-10-20 11:00:00'),
                'maxTimestamp' => strtotime('2025-10-20 11:45:00'), // 45 minutos
                'metricas' => ['Total de Envios' => 200]
            ],
            'Campaign C' => [
                'minTimestamp' => strtotime('2025-10-20 14:00:00'),
                'maxTimestamp' => strtotime('2025-10-20 16:30:00'), // 2h 30min
                'metricas' => ['Total de Envios' => 150]
            ],
        ];
        
        $intervals = EmailReport::calculateIntervalAnalysis($groupedReports);
        
        $this->assertCount(3, $intervals);
        
        // Verifica Campaign A: 25 min = OK
        $this->assertEquals('Campaign A', $intervals[0]['assunto']);
        $this->assertEquals('OK', $intervals[0]['grau_perigo']);
        $this->assertEquals(1500, $intervals[0]['intervalo_segundos']); // 25 * 60
        
        // Verifica Campaign B: 45 min = Médio
        $this->assertEquals('Campaign B', $intervals[1]['assunto']);
        $this->assertEquals('Médio', $intervals[1]['grau_perigo']);
        
        // Verifica Campaign C: 2h 30min = Crítico
        $this->assertEquals('Campaign C', $intervals[2]['assunto']);
        $this->assertEquals('Crítico', $intervals[2]['grau_perigo']);
    }

    /**
     * Testa formatação de intervalo
     */
    public function testIntervalFormatting()
    {
        $groupedReports = [
            'Test' => [
                'minTimestamp' => strtotime('2025-10-20 10:00:00'),
                'maxTimestamp' => strtotime('2025-10-20 11:35:00'), // 1h 35min
                'metricas' => ['Total de Envios' => 100]
            ],
        ];
        
        $intervals = EmailReport::calculateIntervalAnalysis($groupedReports);
        
        $this->assertStringContainsString('h', $intervals[0]['intervalo_formatado']);
        $this->assertStringContainsString('min', $intervals[0]['intervalo_formatado']);
    }
}