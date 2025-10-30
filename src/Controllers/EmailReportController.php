<?php
namespace App\Controllers;

use App\Bootstrap;
use App\Services\TokenService;
use App\Services\DynamicsApiService;
use App\Services\RateLimiter;
use App\Services\CsvExporter;
use App\Models\EmailReport;
use App\Validators\ReportRequestValidator;
use App\Validators\ReportDateValidator;

class EmailReportController
{
    private $logger;
    private $tokenService;
    private $apiService;
    private $rateLimiter;
    private $subjectValidator;
    private $dateValidator;
    private $csvExporter;
    private $batchProcessor;

    public function __construct()
    {
        $bootstrap = Bootstrap::getInstance();
        $this->logger = $bootstrap->getLogger();
        $this->tokenService = new TokenService();
        $this->apiService = new DynamicsApiService($this->tokenService);
        $this->rateLimiter = new RateLimiter();
        $this->subjectValidator = new ReportRequestValidator();
        $this->dateValidator = new ReportDateValidator();
        $this->csvExporter = new CsvExporter();
        $this->batchProcessor = new \App\Services\BatchEmailProcessor();
    }

    /**
     * Exibe o formulário de busca
     */
    public function index(): void
    {
        $data = [
            'error' => null,
            'result' => null,
            'intervals' => null,
            'search_type' => 'subject',
            'assunto' => '',
            'data_inicio' => '',
            'data_fim' => '',
            'remove_defaults' => 'no',
            'test_recipients' => '',
        ];
        
        $this->render('report_form', $data);
    }

    /**
     * ATUALIZADO: Suporta busca por assunto OU por data
     */
    public function generateReport(): array
    {
        $identifier = $this->getUserIdentifier();
        
        if (!$this->rateLimiter->allowRequest($identifier)) {
            return [
                'error' => sprintf(
                    'Limite de requisições excedido. Máximo: %d requisições a cada %d minutos.',
                    $this->rateLimiter->getMaxRequests(),
                    $this->rateLimiter->getPeriodMinutes()
                ),
                'search_type' => $_POST['search_type'] ?? 'subject',
            ];
        }

        // Identifica tipo de busca
        $searchType = $_POST['search_type'] ?? 'subject';

        $this->logger->info('=== INICIANDO GERAÇÃO DE RELATÓRIO ===', [
            'search_type' => $searchType,
            'post_data' => $_POST
        ]);

        if ($searchType === 'date') {
            return $this->generateReportByDate();
        } else {
            return $this->generateReportBySubject();
        }
    }

    /**
     * Busca por ASSUNTO (método original)
     */
    private function generateReportBySubject(): array
    {
        $postData = [
            'assunto' => $_POST['assunto'] ?? '',
            'data_inicio' => $_POST['data_inicio'] ?? '',
        ];

        $this->logger->info('Dados recebidos para busca por assunto', $postData);

        if (!$this->subjectValidator->validate($postData)) {
            $error = $this->subjectValidator->getFirstError();
            $this->logger->error('Validação falhou (assunto)', ['error' => $error]);
            
            return [
                'error' => $error,
                'search_type' => 'subject',
                'assunto' => $postData['assunto'],
                'data_inicio' => $postData['data_inicio'],
            ];
        }

        $subjects = ReportRequestValidator::sanitizeSubjects($postData['assunto']);
        $startDate = $postData['data_inicio'];

        $this->logger->info('Buscando emails por assunto', [
            'subjects' => $subjects,
            'start_date' => $startDate,
        ]);

        $emails = $this->apiService->fetchEmails($subjects, $startDate);

        if (isset($emails['error'])) {
            $this->logger->error('Erro na API (assunto)', ['error' => $emails['error']]);
            
            return [
                'error' => 'Erro ao consultar API: ' . $emails['error'],
                'search_type' => 'subject',
                'assunto' => $postData['assunto'],
                'data_inicio' => $startDate,
            ];
        }

        if (empty($emails)) {
            $this->logger->warning('Nenhum email encontrado (assunto)');
            
            return [
                'error' => 'Nenhum e-mail encontrado com os filtros informados.',
                'search_type' => 'subject',
                'assunto' => $postData['assunto'],
                'data_inicio' => $startDate,
            ];
        }

        return $this->processResults($emails, 'subject', [
            'assunto' => $postData['assunto'],
            'data_inicio' => $startDate,
        ]);
    }

    /**
     * NOVO: Busca por INTERVALO DE DATAS
     */
    private function generateReportByDate(): array
    {
        $postData = [
            'data_inicio' => $_POST['data_inicio'] ?? '',
            'data_fim' => $_POST['data_fim'] ?? '',
        ];

        $this->logger->info('Dados recebidos para busca por data', $postData);

        if (!$this->dateValidator->validate($postData)) {
            $error = $this->dateValidator->getFirstError();
            $this->logger->error('Validação falhou (data)', ['error' => $error]);
            
            return [
                'error' => $error,
                'search_type' => 'date',
                'data_inicio' => $postData['data_inicio'],
                'data_fim' => $postData['data_fim'],
            ];
        }

        $this->logger->info('Buscando emails por data', [
            'start_date' => $postData['data_inicio'],
            'end_date' => $postData['data_fim'],
        ]);

        $emails = $this->apiService->fetchEmailsByDateRange(
            $postData['data_inicio'],
            $postData['data_fim']
        );

        if (isset($emails['error'])) {
            $this->logger->error('Erro na API (data)', ['error' => $emails['error']]);
            
            return [
                'error' => 'Erro ao consultar API: ' . $emails['error'],
                'search_type' => 'date',
                'data_inicio' => $postData['data_inicio'],
                'data_fim' => $postData['data_fim'],
            ];
        }

        if (empty($emails)) {
            $this->logger->warning('Nenhum email encontrado (data)');
            
            return [
                'error' => 'Nenhum e-mail encontrado no período informado.',
                'search_type' => 'date',
                'data_inicio' => $postData['data_inicio'],
                'data_fim' => $postData['data_fim'],
            ];
        }

        return $this->processResults($emails, 'date', [
            'data_inicio' => $postData['data_inicio'],
            'data_fim' => $postData['data_fim'],
        ]);
    }

    /**
     * OTIMIZADO: Processa resultados usando BatchProcessor
     */
    private function processResults(array $emails, string $searchType, array $filters): array
    {
        $this->logger->info('Processando resultados', [
            'total_emails' => count($emails),
            'search_type' => $searchType
        ]);

        // Aplica filtros
        $removeDefaults = isset($_POST['remove_defaults']) && $_POST['remove_defaults'] === 'yes';
        $testRecipients = [];
        
        if (!empty($_POST['test_recipients'])) {
            $testRecipients = array_map('trim', explode(',', $_POST['test_recipients']));
        }

        $this->logger->info('Aplicando filtros', [
            'remove_defaults' => $removeDefaults,
            'test_recipients_count' => count($testRecipients),
            'test_recipients' => $testRecipients,
        ]);

        // DEBUG: Mostra exemplo de e-mail
        if (!empty($emails)) {
            $this->logger->info('EXEMPLO DE E-MAIL DA API', [
                'sample' => $emails[0] ?? []
            ]);
        }

        // USA BATCH PROCESSOR para otimizar performance
        $groupedReports = $this->batchProcessor->processInBatches($emails, $removeDefaults, $testRecipients);

        // Calcula análise de intervalos
        $intervals = EmailReport::calculateIntervalAnalysis($groupedReports);

        // Salva na sessão
        $_SESSION['last_result'] = $groupedReports;
        $_SESSION['last_intervals'] = $intervals;
        $_SESSION['last_filters'] = array_merge($filters, [
            'search_type' => $searchType,
            'remove_defaults' => $removeDefaults,
            'test_recipients' => $_POST['test_recipients'] ?? '',
        ]);

        $this->logger->info('Relatório gerado com sucesso', [
            'grupos' => count($groupedReports),
            'intervalos' => count($intervals),
        ]);

        return [
            'result' => $groupedReports,
            'intervals' => $intervals,
            'search_type' => $searchType,
            'remove_defaults' => $removeDefaults ? 'yes' : 'no',
            'test_recipients' => $_POST['test_recipients'] ?? '',
        ] + $filters;
    }

    public function exportCsv(): void
    {
        if (!isset($_SESSION['last_result']) || !isset($_SESSION['last_filters'])) {
            $this->logger->warning('Tentativa de exportar CSV sem dados em sessão');
            header('Location: /');
            exit;
        }

        $this->csvExporter->export(
            $_SESSION['last_result'],
            $_SESSION['last_filters']
        );
    }

    public function exportExcel(): void
    {
        if (!isset($_SESSION['last_result']) || !isset($_SESSION['last_filters'])) {
            header('Location: /');
            exit;
        }

        $exporter = new \App\Services\ExcelExporter();
        $exporter->export(
            $_SESSION['last_result'],
            $_SESSION['last_filters']
        );
    }

    public function exportPdf(): void
    {
        if (!isset($_SESSION['last_result']) || !isset($_SESSION['last_filters'])) {
            header('Location: /');
            exit;
        }

        $exporter = new \App\Services\PdfExporter();
        $exporter->export(
            $_SESSION['last_result'],
            $_SESSION['last_filters']
        );
    }

    public function exportXml(): void
    {
        if (!isset($_SESSION['last_result']) || !isset($_SESSION['last_filters'])) {
            header('Location: /');
            exit;
        }

        $exporter = new \App\Services\XmlExporter();
        $exporter->export(
            $_SESSION['last_result'],
            $_SESSION['last_filters']
        );
    }

    private function getUserIdentifier(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return md5($ip . $userAgent);
    }

    private function render(string $view, array $data): void
    {
        extract($data);
        require __DIR__ . '/../Views/' . $view . '.php';
    }
}