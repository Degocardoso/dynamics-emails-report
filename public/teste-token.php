<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>TESTE DE AUTENTICAÇÃO DETALHADO</h1>";

try {
    $bootstrap = Bootstrap::getInstance();
    echo "<p>✅ Bootstrap OK</p>";
    
    // Verifica credenciais
    echo "<h2>1. Verificando Credenciais no .env:</h2>";
    echo "<pre>";
    echo "TENANT_ID: " . ($_ENV['TENANT_ID'] ?? '❌ VAZIO') . "\n";
    echo "CLIENT_ID: " . ($_ENV['CLIENT_ID'] ?? '❌ VAZIO') . "\n";
    echo "CLIENT_SECRET: " . (isset($_ENV['CLIENT_SECRET']) && !empty($_ENV['CLIENT_SECRET']) ? '✅ DEFINIDO' : '❌ VAZIO') . "\n";
    echo "RESOURCE: " . ($_ENV['RESOURCE'] ?? '❌ VAZIO') . "\n";
    echo "</pre>";
    
    // Testa conexão com Microsoft
    echo "<h2>2. Testando Conexão com Microsoft:</h2>";
    $tokenUrl = "https://login.microsoftonline.com/{$_ENV['TENANT_ID']}/oauth2/token";
    echo "<p>URL: $tokenUrl</p>";
    
    // Faz requisição manual
    $client = new \GuzzleHttp\Client(['timeout' => 30, 'verify' => false]);
    
    echo "<h2>3. Fazendo Requisição OAuth...</h2>";
    
    try {
        $response = $client->post($tokenUrl, [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $_ENV['CLIENT_ID'],
                'client_secret' => $_ENV['CLIENT_SECRET'],
                'resource' => $_ENV['RESOURCE'],
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);
        
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        echo "<h2>✅ SUCESSO! Status: $statusCode</h2>";
        echo "<pre>";
        echo "Token Type: " . ($data['token_type'] ?? 'N/A') . "\n";
        echo "Expires In: " . ($data['expires_in'] ?? 'N/A') . " segundos\n";
        echo "Access Token (primeiros 50): " . substr($data['access_token'] ?? '', 0, 50) . "...\n";
        echo "</pre>";
        
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        // Erro 4xx (credenciais inválidas, etc)
        $statusCode = $e->getResponse()->getStatusCode();
        $body = $e->getResponse()->getBody()->getContents();
        
        echo "<h2>❌ ERRO HTTP $statusCode</h2>";
        echo "<pre>";
        echo "Resposta da Microsoft:\n";
        echo $body;
        echo "</pre>";
        
        // Tenta parsear JSON
        $jsonData = json_decode($body, true);
        if ($jsonData) {
            echo "<h3>Detalhes do Erro:</h3>";
            echo "<pre>";
            echo "Erro: " . ($jsonData['error'] ?? 'N/A') . "\n";
            echo "Descrição: " . ($jsonData['error_description'] ?? 'N/A') . "\n";
            echo "</pre>";
        }
        
    } catch (\GuzzleHttp\Exception\ServerException $e) {
        // Erro 5xx (problema no servidor Microsoft)
        echo "<h2>❌ ERRO NO SERVIDOR MICROSOFT</h2>";
        echo "<pre>" . $e->getMessage() . "</pre>";
        
    } catch (\Exception $e) {
        // Outros erros (timeout, conexão, etc)
        echo "<h2>❌ ERRO DE CONEXÃO</h2>";
        echo "<pre>";
        echo "Tipo: " . get_class($e) . "\n";
        echo "Mensagem: " . $e->getMessage() . "\n";
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO GERAL:</h2>";
    echo "<pre>";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}