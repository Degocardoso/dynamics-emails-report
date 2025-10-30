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
     */
    public function processInBatches(array $emails, bool $removeDefaults = false, array $testRecipients = []): array
    {
        $totalEmails = count($emails);
        $this->logger->info('Iniciando processamento em batches', [
            'total' => $totalEmails,
            'batch_size' => $this->batchSize
        ]);

        // Passo 1: Filtra e-mails (rápido, não agrupa)
        $startTime = microtime(true);
        $filteredEmails = EmailReport::filterEmails($emails, $removeDefaults, $testRecipients);
        $filterTime = microtime(true) - $startTime;

        $this->logger->info('Filtragem concluída', [
            'tempo' => round($filterTime, 2) . 's',
            'antes' => $totalEmails,
            'depois' => count($filteredEmails),
            'removidos' => $totalEmails - count($filteredEmails)
        ]);

        // Passo 2: Agrupa por assunto (rápido)
        $startTime = microtime(true);
        $grouped = EmailReport::groupBySubject($filteredEmails);
        $groupTime = microtime(true) - $startTime;

        $this->logger->info('Agrupamento concluído', [
            'tempo' => round($groupTime, 2) . 's',
            'grupos' => count($grouped)
        ]);

        // Passo 3: Calcula relatórios para cada grupo
        $reports = [];
        $processedGroups = 0;
        $startTime = microtime(true);

        foreach ($grouped as $subject => $emailGroup) {
            $reports[$subject] = EmailReport::calculateReport($emailGroup);
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