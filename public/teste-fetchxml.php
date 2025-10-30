<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;
use App\Services\TokenService;
use App\Services\DynamicsApiFetchXmlService;

ini_set('display_errors', 1);
error_reporting(E_ALL);
// Aumenta o tempo limite para 5 minutos
set_time_limit(300);

echo "<h1>TESTE COM FETCHXML</h1>";

$bootstrap = Bootstrap::getInstance();
$tokenService = new TokenService();
$apiService = new DynamicsApiFetchXmlService($tokenService);

echo "<p>Buscando com FetchXML...</p>";
ob_flush(); flush();

// A busca de e-mails agora irá paginar automaticamente se houver mais de 5000 registros
$emails = $apiService->fetchEmailsWithFetchXml(['Sucesso News'], '2024-01-01');

if (isset($emails['error'])) {
    // Usando htmlspecialchars para evitar XSS e garantir que a mensagem seja exibida corretamente
    echo "<h2>❌ ERRO: " . htmlspecialchars($emails['error']) . "</h2>"; 
} else {
    $totalCount = count($emails);
    echo "<h2>✅ Total: " . $totalCount . " e-mails!</h2>";
    if ($totalCount > 5000) {
        echo "<p style='color: green; font-weight: bold;'>🎉 FETCH XML FUNCIONOU! Mais de 5000 registros!</p>";
    }
}