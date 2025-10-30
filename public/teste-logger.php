<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>TESTE DO LOGGER</h1>";

try {
    $bootstrap = Bootstrap::getInstance();
    echo "<p>✅ Bootstrap OK</p>";
    
    $logger = $bootstrap->getLogger();
    echo "<p>✅ Logger obtido</p>";
    
    // Testa escrita
    $logger->debug('Teste DEBUG');
    $logger->info('Teste INFO');
    $logger->warning('Teste WARNING');
    $logger->error('Teste ERROR');
    
    echo "<h2>✅ Testes de log enviados!</h2>";
    
    $logPath = $bootstrap->getConfig('app.log.path');
    echo "<p><strong>Caminho do log:</strong> $logPath</p>";
    
    if (file_exists($logPath)) {
        echo "<p style='color: green;'>✅ Arquivo de log existe!</p>";
        
        $size = filesize($logPath);
        echo "<p><strong>Tamanho:</strong> $size bytes</p>";
        
        if (is_writable($logPath)) {
            echo "<p style='color: green;'>✅ Arquivo é gravável!</p>";
        } else {
            echo "<p style='color: red;'>❌ Arquivo NÃO é gravável!</p>";
            echo "<p>Execute: <code>chmod 666 $logPath</code></p>";
        }
        
        // Mostra últimas linhas
        $content = file_get_contents($logPath);
        if (!empty($content)) {
            $lines = explode("\n", $content);
            $lastLines = array_slice($lines, -10);
            
            echo "<h3>Últimas 10 linhas do log:</h3>";
            echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
            echo htmlspecialchars(implode("\n", $lastLines));
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠️ Arquivo está vazio!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Arquivo de log NÃO existe!</p>";
        echo "<p>Tentando criar...</p>";
        
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "<p>✅ Diretório criado: $dir</p>";
        }
        
        if (touch($logPath)) {
            chmod($logPath, 0666);
            echo "<p style='color: green;'>✅ Arquivo criado com sucesso!</p>";
        } else {
            echo "<p style='color: red;'>❌ Não foi possível criar o arquivo!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO:</h2>";
    echo "<pre>";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "</pre>";
}
?>