<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;
use App\Services\TokenService;
use App\Services\DynamicsApiService;

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

echo "<style>
body { font-family: monospace; background: #f0f0f0; padding: 20px; }
.debug-box { background: white; padding: 15px; margin: 15px 0; border: 2px solid #333; border-radius: 5px; }
.key { color: #0066cc; font-weight: bold; }
.value { color: #cc0000; }
pre { background: #fafafa; padding: 10px; overflow-x: auto; }
</style>";

echo "<h1>🔍 DEBUG COMPLETO - TESTE DE PAGINAÇÃO</h1>";
echo "<hr>";

try {
    $bootstrap = Bootstrap::getInstance();
    echo "<p>✅ Bootstrap OK</p>";
    
    $tokenService = new TokenService();
    echo "<p>✅ TokenService OK</p>";
    
    // Fazer requisição MANUAL para ver TUDO
    $token = $tokenService->getAccessToken();
    
    if (!$token) {
        die("<h2>❌ Não conseguiu obter token</h2>");
    }
    
    echo "<p>✅ Token obtido</p>";
    echo "<hr>";
    
    // ========== REQUISIÇÃO MANUAL ==========
    $client = new \GuzzleHttp\Client(['timeout' => 30, 'verify' => false]);
    
    $url = "https://fecap.api.crm2.dynamics.com/api/data/v9.2/emails";
    
    $query = [
        '$select' => 'subject,senton,cad_statusemail,statuscode,sender',
        '$filter' => "contains(subject, 'Sucesso News') and senton ge 2024-01-01T00:00:00Z and sender eq 'sucessoalvarista@fecap.br'",
        // '$top' => '5000', // <<< REMOVIDO DAQUI! Este era o erro.
        '$count' => 'true',
        '$orderby' => 'senton desc', // Boa prática adicionar orderby para paginar
    ];
    
    echo "<div class='debug-box'>";
    echo "<h2>📤 REQUISIÇÃO</h2>";
    echo "<p><strong>URL:</strong> $url</p>";
    echo "<p><strong>Query:</strong></p>";
    echo "<pre>" . print_r($query, true) . "</pre>";
    echo "</div>";
    
    echo "<p>⏳ Fazendo requisição...</p>";
    ob_flush(); flush();
    
    $startTime = microtime(true);
    
    $response = $client->get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'OData-Version' => '4.0',
            
            // Este header é o que PEDE a paginação
            'Prefer' => 'odata.maxpagesize=5000,odata.include-annotations="*"',
        ],
        'query' => $query,
    ]);
    
    $duration = round(microtime(true) - $startTime, 2);
    
    echo "<p>✅ Resposta recebida em {$duration}s</p>";
    
    $statusCode = $response->getStatusCode();
    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);
    
    echo "<div class='debug-box'>";
    echo "<h2>📥 RESPOSTA - STATUS HTTP</h2>";
    echo "<p><strong>Status Code:</strong> $statusCode</p>";
    echo "</div>";
    
    // ========== MOSTRAR TODOS OS HEADERS DA RESPOSTA ==========
    echo "<div class='debug-box'>";
    echo "<h2>📋 HEADERS DA RESPOSTA</h2>";
    echo "<pre>";
    foreach ($response->getHeaders() as $name => $values) {
        echo "<span class 'key'>$name:</span> <span class='value'>" . implode(', ', $values) . "</span>\n";
    }
    echo "</pre>";
    echo "</div>";
    
    // ========== MOSTRAR TODAS AS CHAVES DO JSON ==========
    echo "<div class='debug-box'>";
    echo "<h2>🔑 TODAS AS CHAVES RETORNADAS NO JSON (NÍVEL RAIZ)</h2>";
    echo "<pre>";
    $keys = array_keys($data);
    foreach ($keys as $key) {
        echo "• <span class='key'>$key</span>\n";
    }
    echo "</pre>";
    echo "<p><strong>Total de chaves:</strong> " . count($keys) . "</p>";
    echo "</div>";
    
    // ========== MOSTRAR TODOS OS VALORES DAS CHAVES @ ==========
    echo "<div class='debug-box'>";
    echo "<h2>@ VALORES DE TODAS AS CHAVES QUE COMEÇAM COM @</h2>";
    echo "<pre>";
    $atKeys = [];
    foreach ($data as $key => $value) {
        if (strpos($key, '@') === 0) {
            $atKeys[$key] = $value;
            echo "<span class='key'>$key</span> = <span class='value'>";
            if (is_bool($value)) {
                echo $value ? 'TRUE' : 'FALSE';
            } elseif (is_array($value)) {
                echo json_encode($value, JSON_PRETTY_PRINT);
            } else {
                echo htmlspecialchars($value);
            }
            echo "</span>\n";
        }
    }
    echo "</pre>";
    echo "<p><strong>Total de chaves @:</strong> " . count($atKeys) . "</p>";
    echo "</div>";
    
    // ========== PROCURAR POR QUALQUER CAMPO COM "next", "more", "paging", "link" ==========
    echo "<div class='debug-box'>";
    echo "<h2>🔎 BUSCA POR PALAVRAS-CHAVE (next, more, paging, link, continuation)</h2>";
    echo "<pre>";
    $keywords = ['next', 'more', 'paging', 'link', 'continuation', 'cookie', 'token', 'skip'];
    $found = [];
    
    foreach ($data as $key => $value) {
        foreach ($keywords as $keyword) {
            if (stripos($key, $keyword) !== false) {
                $found[$key] = $value;
                echo "🎯 <span class='key'>$key</span> = <span class='value'>";
                if (is_bool($value)) {
                    echo $value ? 'TRUE' : 'FALSE';
                } elseif (is_array($value)) {
                    echo json_encode($value);
                } else {
                    echo htmlspecialchars($value);
                }
                echo "</span>\n";
                break;
            }
        }
    }
    
    if (empty($found)) {
        echo "<span style='color: red;'>❌ NENHUMA chave encontrada com essas palavras!</span>\n";
    }
    echo "</pre>";
    echo "</div>";
    
    // ========== MOSTRAR QUANTIDADE DE REGISTROS ==========
    echo "<div class='debug-box'>";
    echo "<h2>📊 INFORMAÇÕES SOBRE REGISTROS</h2>";
    $valueCount = isset($data['value']) ? count($data['value']) : 0;
    echo "<p><strong>Registros na propriedade 'value':</strong> $valueCount</p>";
    
    if (isset($data['@odata.count'])) {
        echo "<p><strong>@odata.count:</strong> " . $data['@odata.count'] . "</p>";
    }
    if (isset($data['@Microsoft.Dynamics.CRM.totalrecordcount'])) {
        echo "<p><strong>@Microsoft.Dynamics.CRM.totalrecordcount:</strong> " . $data['@Microsoft.Dynamics.CRM.totalrecordcount'] . "</p>";
    }
    if (isset($data['@Microsoft.Dynamics.CRM.totalrecordcountlimitexceeded'])) {
        echo "<p><strong>@Microsoft.Dynamics.CRM.totalrecordcountlimitexceeded:</strong> " . ($data['@Microsoft.Dynamics.CRM.totalrecordcountlimitexceeded'] ? 'TRUE ⚠️' : 'FALSE') . "</p>";
    }
    echo "</div>";
    
    // ========== DUMP COMPLETO DO JSON (PRIMEIROS 50 LINHAS) ==========
    echo "<div class='debug-box'>";
    echo "<h2>📄 JSON COMPLETO (Primeiras 100 linhas)</h2>";
    echo "<pre>";
    $jsonFormatted = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $lines = explode("\n", $jsonFormatted);
    $first100 = array_slice($lines, 0, 100);
    echo htmlspecialchars(implode("\n", $first100));
    if (count($lines) > 100) {
        echo "\n\n... (" . (count($lines) - 100) . " linhas restantes omitidas)";
    }
    echo "</pre>";
    echo "</div>";
    
    // ========== ANÁLISE FINAL ==========
    echo "<div class='debug-box' style='background: #e6f7ff; border-color: #0066cc;'>";
    echo "<h2>🎯 ANÁLISE FINAL</h2>";
    
    $hasNextLink = isset($data['@odata.nextLink']);
    $hasMoreRecords = isset($data['@Microsoft.Dynamics.CRM.morerecords']) && $data['@Microsoft.Dynamics.CRM.morerecords'];
    $limitExceeded = isset($data['@Microsoft.Dynamics.CRM.totalrecordcountlimitexceeded']) && $data['@Microsoft.Dynamics.CRM.totalrecordcountlimitexceeded'];
    
    echo "<p><strong>Tem @odata.nextLink?</strong> " . ($hasNextLink ? '✅ SIM! COPIE E COLE O RESULTADO!' : '❌ NÃO') . "</p>";
    echo "<p><strong>Tem @Microsoft.Dynamics.CRM.morerecords = true?</strong> " . ($hasMoreRecords ? '✅ SIM' : '❌ NÃO') . "</p>";
    echo "<p><strong>Limite excedido?</strong> " . ($limitExceeded ? '⚠️ SIM (há mais registros que o limite)' : '✅ NÃO') . "</p>";
    
    if ($hasNextLink) {
         echo "<hr>";
         echo "<p style='color: green; font-weight: bold;'>🎉 SUCESSO! A paginação está funcionando.</p>";
         echo "<p>A API retornou o link para a próxima página:</p>";
         echo "<pre style='background: #d4edda; color: #155724;'>" . htmlspecialchars($data['@odata.nextLink']) . "</pre>";
    }
    
    if (!$hasNextLink && $valueCount < 5000) {
         echo "<hr>";
         echo "<p style='color: blue; font-weight: bold;'>ℹ️ INFO: A API não retornou 'nextLink' porque provavelmente há menos de 5000 registros no total para esse filtro.</p>";
    }
    
    if (!$hasNextLink && $valueCount === 5000 && $limitExceeded) {
         echo "<hr>";
         echo "<p style='color: red; font-weight: bold;'>⚠️ FALHA: Mesmo sem o $top, a API não retornou o nextLink. Isso pode ser um problema de configuração no Dynamics.</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='debug-box' style='background: #ffcccc; border-color: #cc0000;'>";
    echo "<h2>❌ ERRO</h2>";
    echo "<pre>";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Teste concluído!</strong> Copie TUDO e me envie para análise.</p>";
?>