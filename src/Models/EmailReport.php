<?php
namespace App\Models;

class EmailReport
{
    private const STATUS_EMAIL_MAP = [
        0 => 'Envio Pendente',
        1 => 'Processado',
        2 => 'Desistiu',
        3 => 'Entregue',
        4 => 'Diferidos',
        5 => 'Ressalto',
        6 => 'Aberto',
        7 => 'Clique',
        8 => 'RelatorioSpam',
        9 => 'CancelaSubscricao',
        10 => 'GroupUnsubscribe',
        11 => 'GroupResubscribe',
        12 => 'Default',
    ];

    private const STATUS_CODE_MAP = [
        1 => 'Rascunho',
        2 => 'Concluído',
        3 => 'Enviado',
        4 => 'Recebido',
        5 => 'Cancelado',
        6 => 'Envio Pendente',
        7 => 'Enviando',
        8 => 'Falha',
    ];

    /**
     * Agrupa e-mails por assunto
     */
    public static function groupBySubject(array $emails): array
    {
        $grouped = [];
        
        foreach ($emails as $email) {
            $subject = $email['subject'] ?? 'Sem Assunto';
            $grouped[$subject][] = $email;
        }
        
        return $grouped;
    }

    /**
     * Calcula relatório para um grupo de e-mails
     * ATUALIZADO: Aplica filtros APENAS no cálculo das métricas, não remove e-mails dos dados
     */
    public static function calculateReport(array $emails, bool $removeDefaults = false, array $testRecipients = []): array
    {
        if (empty($emails)) {
            return self::getEmptyReport();
        }

        // Filtra e-mails APENAS para cálculo das métricas
        $emailsForMetrics = self::filterEmails($emails, $removeDefaults, $testRecipients);

        $emailCounters = self::initializeCounters(self::STATUS_EMAIL_MAP);
        $statusCodeCounters = self::initializeCounters(self::STATUS_CODE_MAP);

        $minTimestamp = null;
        $maxTimestamp = null;

        // Usa e-mails filtrados para contadores e métricas
        foreach ($emailsForMetrics as $email) {
            self::incrementCounter($emailCounters, self::STATUS_EMAIL_MAP, $email['cad_statusemail'] ?? null);
            self::incrementCounter($statusCodeCounters, self::STATUS_CODE_MAP, $email['statuscode'] ?? null);

            if (!empty($email['senton'])) {
                $currentTimestamp = strtotime($email['senton']);
                if ($currentTimestamp !== false) {
                    if ($minTimestamp === null || $currentTimestamp < $minTimestamp) {
                        $minTimestamp = $currentTimestamp;
                    }
                    if ($maxTimestamp === null || $currentTimestamp > $maxTimestamp) {
                        $maxTimestamp = $currentTimestamp;
                    }
                }
            }
        }

        // Calcula métricas com base nos e-mails filtrados
        $metrics = self::calculateMetrics($emailCounters, count($emailsForMetrics), $minTimestamp, $maxTimestamp);

        return [
            'contadoresEmail' => $emailCounters,
            'contadoresHeader' => $statusCodeCounters,
            'metricas' => $metrics,
            'minTimestamp' => $minTimestamp,
            'maxTimestamp' => $maxTimestamp,
        ];
    }

    /**
     * NOVO: Calcula intervalo entre disparos DENTRO de cada grupo
     * (Do primeiro ao último e-mail de cada assunto)
     * CORRIGIDO: Converte UTC para Brasília
     */
    public static function calculateIntervalAnalysis(array $groupedReports): array
    {
        $intervals = [];
        $timezone = new \DateTimeZone('America/Sao_Paulo');
        
        foreach ($groupedReports as $subject => $report) {
            $minTimestamp = $report['minTimestamp'] ?? null;
            $maxTimestamp = $report['maxTimestamp'] ?? null;
            
            // Se não houver timestamps válidos, pula
            if ($minTimestamp === null || $maxTimestamp === null) {
                continue;
            }
            
            // Calcula o intervalo DENTRO do grupo (primeiro → último)
            $intervalSeconds = $maxTimestamp - $minTimestamp;
            $intervalMinutes = round($intervalSeconds / 60);
            $intervalHours = round($intervalSeconds / 3600, 2);

            // Determina grau de perigo
            if ($intervalSeconds < 1800) { // < 30 min
                $danger = 'OK';
                $color = '#28a745';
            } elseif ($intervalSeconds < 3600) { // 30min - 1h
                $danger = 'Médio';
                $color = '#ffc107';
            } elseif ($intervalSeconds < 18000) { // 1h - 5h
                $danger = 'Crítico';
                $color = '#fd7e14';
            } else { // > 5h
                $danger = 'Urgente';
                $color = '#dc3545';
            }

            // CORRIGIDO: Converte timestamps UTC para Brasília
            $dateInicio = new \DateTime('@' . $minTimestamp);
            $dateInicio->setTimezone($timezone);
            
            $dateTermino = new \DateTime('@' . $maxTimestamp);
            $dateTermino->setTimezone($timezone);

            $intervals[] = [
                'assunto' => $subject,
                'inicio' => $dateInicio->format('d/m/Y H:i:s'),
                'termino' => $dateTermino->format('d/m/Y H:i:s'),
                'intervalo_segundos' => $intervalSeconds,
                'intervalo_minutos' => $intervalMinutes,
                'intervalo_horas' => $intervalHours,
                'intervalo_formatado' => self::formatInterval($intervalSeconds),
                'grau_perigo' => $danger,
                'cor' => $color,
            ];
        }

        return $intervals;
    }

    /**
     * NOVO: Formata intervalo de tempo
     */
    private static function formatInterval(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return $minutes . 'min';
        } elseif ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'min' : '');
        } else {
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            return $days . 'd' . ($hours > 0 ? ' ' . $hours . 'h' : '');
        }
    }

    private static function initializeCounters(array $map): array
    {
        $counters = array_fill_keys(array_values($map), 0);
        $counters['Status Desconhecido'] = 0;
        return $counters;
    }

    private static function incrementCounter(array &$counters, array $map, $statusValue): void
    {
        if (isset($map[$statusValue])) {
            $counters[$map[$statusValue]]++;
        } else {
            $counters['Status Desconhecido']++;
        }
    }

    /**
     * ATUALIZADO: Ordem lógica das métricas + Intervalo do Disparo
     */
    private static function calculateMetrics(array $emailCounters, int $totalEmails, ?int $minTimestamp, ?int $maxTimestamp): array
    {
        $inicioDisparoStr = 'N/A';
        $terminoDisparoStr = 'N/A';
        $intervaloDisparoStr = 'N/A';
        
        if ($minTimestamp !== null) {
            $fusoHorarioLocal = new \DateTimeZone('America/Sao_Paulo');
            
            $dataInicio = new \DateTime('@' . $minTimestamp);
            $dataInicio->setTimezone($fusoHorarioLocal);
            
            $dataFim = new \DateTime('@' . $maxTimestamp);
            $dataFim->setTimezone($fusoHorarioLocal);
            
            $formatoCompleto = 'd/m/Y \à\s H:i\h';
            $inicioDisparoStr = $dataInicio->format($formatoCompleto);
            $terminoDisparoStr = $dataFim->format($formatoCompleto);
            
            // NOVO: Calcula e formata o intervalo
            $intervalSeconds = $maxTimestamp - $minTimestamp;
            $intervaloDisparoStr = self::formatInterval($intervalSeconds);
        }

        $totalDelivered = $emailCounters['Entregue'] + $emailCounters['Aberto'] + $emailCounters['Clique'];
        $totalOpened = $emailCounters['Aberto'] + $emailCounters['Clique'];
        
        $openRate = $totalDelivered > 0 ? ($totalOpened / $totalDelivered) * 100 : 0;
        $deliveryRate = $totalEmails > 0 ? ($totalDelivered / $totalEmails) * 100 : 0;
        $ctr = $totalDelivered > 0 ? ($emailCounters['Clique'] / $totalDelivered) * 100 : 0;

        // ORDEM LÓGICA DAS MÉTRICAS + INTERVALO
        return [
            // 1. Período
            'Início do Disparo' => $inicioDisparoStr,
            'Término do Disparo' => $terminoDisparoStr,
            'Intervalo do Disparo' => $intervaloDisparoStr, // NOVO
            
            // 2. Totais
            'Total de Envios' => $totalEmails,
            'Total de Recebidos' => $totalDelivered,
            
            // 3. Taxas principais
            'Taxa de Entrega (%)' => round($deliveryRate, 2),
            'Taxa de Abertura (%)' => round($openRate, 2),
            'Taxa de Clique - CTR (%)' => round($ctr, 2),
            
            // 4. Detalhamento
            'Total de Aberturas' => $emailCounters['Aberto'],
            'Total de Cliques' => $emailCounters['Clique'],
            'Total de Entregas' => $emailCounters['Entregue'],
            'Total de Falhas' => $emailCounters['Desistiu'] + $emailCounters['Status Desconhecido'],
        ];
    }

    private static function getEmptyReport(): array
    {
        return [
            'contadoresEmail' => self::initializeCounters(self::STATUS_EMAIL_MAP),
            'contadoresHeader' => self::initializeCounters(self::STATUS_CODE_MAP),
            'metricas' => [
                'Início do Disparo' => 'N/A',
                'Término do Disparo' => 'N/A',
                'Intervalo do Disparo' => 'N/A', // NOVO
                'Total de Envios' => 0,
                'Total de Recebidos' => 0,
                'Taxa de Entrega (%)' => 0,
                'Taxa de Abertura (%)' => 0,
                'Taxa de Clique - CTR (%)' => 0,
                'Total de Aberturas' => 0,
                'Total de Cliques' => 0,
                'Total de Entregas' => 0,
                'Total de Falhas' => 0,
            ],
            'minTimestamp' => null,
            'maxTimestamp' => null,
        ];
    }

    public static function generateGroupedReports(array $emails, bool $removeDefaults = false, array $testRecipients = []): array
    {
        $grouped = self::groupBySubject($emails);
        $reports = [];

        foreach ($grouped as $subject => $emailGroup) {
            $reports[$subject] = self::calculateReport($emailGroup, $removeDefaults, $testRecipients);
        }

        return $reports;
    }

    /**
     * NOVO: Filtra e-mails removendo padrões e destinatários de teste
     * CAMPO CORRETO: torecipients
     */
    public static function filterEmails(array $emails, bool $removeDefaults = false, array $testRecipients = []): array
    {
        $filtered = $emails;

        // Remove e-mails padrão/automáticos
        if ($removeDefaults) {
            $defaultSubjects = self::getDefaultSubjects();
            $filtered = array_filter($filtered, function($email) use ($defaultSubjects) {
                $subject = strtolower(trim($email['subject'] ?? ''));
                foreach ($defaultSubjects as $defaultSubject) {
                    if (stripos($subject, strtolower($defaultSubject)) !== false) {
                        return false; // Remove este e-mail
                    }
                }
                return true; // Mantém este e-mail
            });
        }

        // Remove destinatários de teste
        if (!empty($testRecipients)) {
            $testRecipients = array_map('strtolower', array_map('trim', $testRecipients));
            
            $filtered = array_filter($filtered, function($email) use ($testRecipients) {
                // Campo principal da API Dynamics: torecipients
                $torecipients = strtolower(trim($email['torecipients'] ?? ''));
                
                if (empty($torecipients)) {
                    return true; // Se não tem destinatário, mantém
                }
                
                // Verifica se algum e-mail de teste está presente
                foreach ($testRecipients as $testEmail) {
                    if (stripos($torecipients, $testEmail) !== false) {
                        return false; // Remove este e-mail
                    }
                }
                
                return true; // Mantém este e-mail
            });
        }

        return array_values($filtered); // Reindexar array
    }

    /**
     * NOVO: Carrega assuntos padrão do arquivo de configuração
     */
    private static function getDefaultSubjects(): array
    {
        $configFile = __DIR__ . '/../../config/default_emails.php';
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            return $config['default_subjects'] ?? [];
        }
        
        return [];
    }
}