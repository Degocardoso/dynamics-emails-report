<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;
use App\Services\TokenService;
use App\Services\DynamicsApiService;

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>TESTE DE API COMPLETO</h1>";

$bootstrap = Bootstrap::getInstance();

$tokenService = new TokenService();
echo "<p>✅ TokenService criado</p>";

$token = $tokenService->getAccessToken();
if (!$token) {
    die("<h2>❌ Não conseguiu obter token</h2>");
}
echo "<p>✅ Token obtido: " . substr($token, 0, 30) . "...</p>";

$apiService = new DynamicsApiService($tokenService);
echo "<p>✅ DynamicsApiService criado</p>";

echo "<h2>Buscando e-mails...</h2>";

$emails = $apiService->fetchEmails(
    ['Pelas ruas de São Paulo'], 
    '2025-01-01'
);

if (isset($emails['error'])) {
    echo "<h2>❌ ERRO: " . $emails['error'] . "</h2>";
} elseif (empty($emails)) {
    echo "<h2>⚠️ Nenhum e-mail encontrado</h2>";
} else {
    echo "<h2>✅ SUCESSO! " . count($emails) . " e-mails encontrados</h2>";
    echo "<h3>Primeiro e-mail:</h3>";
    echo "<pre>";
    print_r($emails[0]);
    echo "</pre>";
}