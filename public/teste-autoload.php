<?php
require_once __DIR__ . '/../vendor/autoload.php';

echo "<h1>TESTE MONOLOG</h1>";

try {
    $logger = new \Monolog\Logger('test');
    echo "<h2>✅ Monolog instalado e funcionando!</h2>";
} catch (Error $e) {
    echo "<h2>❌ ERRO: Monolog não encontrado!</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

echo "<hr>";
echo "<h3>Verificando vendor/monolog:</h3>";
if (is_dir(__DIR__ . '/../vendor/monolog')) {
    echo "<p>✅ Pasta vendor/monolog existe!</p>";
} else {
    echo "<p>❌ Pasta vendor/monolog NÃO existe!</p>";
}

echo "<h3>Verificando composer.json:</h3>";
$composerJson = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
echo "<pre>";
print_r($composerJson['require']);
echo "</pre>";
