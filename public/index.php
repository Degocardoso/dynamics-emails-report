<?php

/**
 * Front Controller - Ponto de entrada da aplicação
 */

set_time_limit(600); // 10 minutos
ini_set('max_execution_time', '600');
ini_set('default_socket_timeout', '600');
ini_set('memory_limit', '1024M');

// Ativa exibição de erros (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;
use App\Controllers\EmailReportController;

// Inicia sessão
session_start();

// Inicializa a aplicação
$bootstrap = Bootstrap::getInstance();
$logger = $bootstrap->getLogger();

$logger->info('=== REQUISIÇÃO RECEBIDA ===', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'post' => $_POST,
    'get' => $_GET
]);

// Tratamento global de erros
set_exception_handler(function ($exception) use ($logger, $bootstrap) {
    $logger->error('Exceção não capturada', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);

    $data = [
        'error' => 'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
        'result' => null,
        'intervals' => null,
        'search_type' => $_POST['search_type'] ?? 'subject',
        'assunto' => $_POST['assunto'] ?? '',
        'data_inicio' => $_POST['data_inicio'] ?? '',
        'data_fim' => $_POST['data_fim'] ?? '',
    ];
    
    if ($bootstrap->getConfig('app.debug')) {
        ob_start();
        echo '<pre>';
        echo 'Erro: ' . htmlspecialchars($exception->getMessage()) . "\n";
        echo 'Arquivo: ' . htmlspecialchars($exception->getFile()) . "\n";
        echo 'Linha: ' . htmlspecialchars($exception->getLine()) . "\n";
        echo "\n\nStack Trace:\n";
        echo htmlspecialchars($exception->getTraceAsString());
        echo '</pre>';
        $debugError = ob_get_clean();
        $data['error'] = $debugError;
    }
    
    extract($data);
    require __DIR__ . '/../src/Views/report_form.php';
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logger) {
    $logger->error('Erro PHP', [
        'errno' => $errno,
        'errstr' => $errstr,
        'errfile' => $errfile,
        'errline' => $errline,
    ]);
    
    return false;
});

// Instancia o controller
$controller = new EmailReportController();

try {
    // Roteamento simples
    if (isset($_GET['export'])) {
        // Exportações
        switch ($_GET['export']) {
            case 'csv':
                $controller->exportCsv();
                break;
            case 'excel':
                $controller->exportExcel();
                break;
            case 'pdf':
                $controller->exportPdf();
                break;
            case 'xml':
                $controller->exportXml();
                break;
            default:
                header('Location: /');
                exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Processa o formulário
        $data = $controller->generateReport();
        extract($data);
        require __DIR__ . '/../src/Views/report_form.php';
    } else {
        // GET request: Mostra o formulário vazio
        $controller->index();
    }
} catch (Exception $e) {
    $logger->error('Erro no controller', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    $data = [
        'error' => 'Ocorreu um erro ao processar sua requisição: ' . htmlspecialchars($e->getMessage()),
        'result' => null,
        'intervals' => null,
        'search_type' => $_POST['search_type'] ?? 'subject',
        'assunto' => $_POST['assunto'] ?? '',
        'data_inicio' => $_POST['data_inicio'] ?? '',
        'data_fim' => $_POST['data_fim'] ?? '',
    ];
    
    extract($data);
    require __DIR__ . '/../src/Views/report_form.php';
}