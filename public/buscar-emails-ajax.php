<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;
use App\Services\TokenService;
use App\Services\DynamicsApiService;
use App\Validators\ReportRequestValidator;

header('Content-Type: application/json');

$bootstrap = Bootstrap::getInstance();
$logger = $bootstrap->getLogger();

try {
    $assunto = $_POST['assunto'] ?? '';
    $dataInicio = $_POST['data_inicio'] ?? '';
    
    $validator = new ReportRequestValidator();
    if (!$validator->validate(['assunto' => $assunto, 'data_inicio' => $dataInicio])) {
        echo json_encode(['error' => $validator->getFirstError()]);
        exit;
    }
    
    $subjects = ReportRequestValidator::sanitizeSubjects($assunto);
    
    $tokenService = new TokenService();
    $apiService = new DynamicsApiService($tokenService);
    
    // Callback para enviar progresso
    $emails = $apiService->fetchEmails($subjects, $dataInicio);
    
    if (isset($emails['error'])) {
        echo json_encode(['error' => $emails['error']]);
    } else {
        echo json_encode([
            'success' => true,
            'total' => count($emails),
            'data' => $emails
        ]);
    }
    
} catch (Exception $e) {
    $logger->error('Erro no AJAX', ['error' => $e->getMessage()]);
    echo json_encode(['error' => 'Erro ao processar requisição']);
}
