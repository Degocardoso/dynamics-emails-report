<?php

namespace App\Services;

use App\Models\EmailReport;

class BatchEmailProcessor
{
    private $logger;
    private $batchSize = 1000; // Processa 1000 e-mails por vez

    public function __construct()
    {
        $bootstrap = \App\Bootstrap::getInstance();
        $this->logger = $bootstrap->getLogger();
    }

    /**
     * Processa e-mails em lotes para evitar timeout
     * ATUALIZADO: Não filtra os e-mails antes, apenas passa os filtros para cálculo de métricas
     */
    public function processInBatches(array $emails, bool $removeDefaults = false, array $testRecipients = []): array
    {
        $totalEmails = count($emails);
        $this->logger->info('Iniciando processamento em batches', [
            'total' => $totalEmails,
            'batch_size' => $this->batchSize,
            'remove_defaults' => $removeDefaults,
            'test_recipients_count' => count($testRecipients)
        ]);

        // Passo 1: Agrupa por assunto (SEM filtrar - mantém todos os e-mails)
        $startTime = microtime(true);
        $grouped = EmailReport::groupBySubject($emails);
        $groupTime = microtime(true) - $startTime;

        $this->logger->info('Agrupamento concluído', [
            'tempo' => round($groupTime, 2) . 's',
            'grupos' => count($grouped)
        ]);

        // Passo 2: Calcula relatórios para cada grupo, aplicando filtros APENAS nas métricas
        $reports = [];
        $processedGroups = 0;
        $startTime = microtime(true);

        foreach ($grouped as $subject => $emailGroup) {
            // Passa os filtros para serem aplicados apenas no cálculo das métricas
            $reports[$subject] = EmailReport::calculateReport($emailGroup, $removeDefaults, $testRecipients);
            $processedGroups++;

            // Log a cada 10 grupos
            if ($processedGroups % 10 === 0) {
                $elapsed = microtime(true) - $startTime;
                $avgTimePerGroup = $elapsed / $processedGroups;
                $remaining = count($grouped) - $processedGroups;
                $estimatedTimeLeft = $remaining * $avgTimePerGroup;

                $this->logger->info('Progresso do processamento', [
                    'processados' => $processedGroups,
                    'total' => count($grouped),
                    'percentual' => round(($processedGroups / count($grouped)) * 100, 1) . '%',
                    'tempo_decorrido' => round($elapsed, 1) . 's',
                    'tempo_estimado_restante' => round($estimatedTimeLeft, 1) . 's'
                ]);
            }
        }

        $totalTime = microtime(true) - $startTime;
        $this->logger->info('Cálculo de relatórios concluído', [
            'tempo' => round($totalTime, 2) . 's',
            'grupos' => count($reports)
        ]);

        return $reports;
    }

    /**
     * Define tamanho do batch
     */
    public function setBatchSize(int $size): void
    {
        $this->batchSize = $size;
    }
}