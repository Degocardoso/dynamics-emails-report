<?php

namespace App;

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class Bootstrap
{
    private static $instance = null;
    private $config = [];
    private $logger;
    private $cache;

    private function __construct()
    {
        $this->loadEnvironment();
        $this->loadConfigs();
        $this->setupLogger();
        $this->setupCache();
        $this->setupSession();
        $this->setupDirectories();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnvironment(): void
    {
        $envPath = dirname(__DIR__);
        
        if (file_exists($envPath . '/.env')) {
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->load();
            $dotenv->required(['TENANT_ID', 'CLIENT_ID', 'CLIENT_SECRET', 'RESOURCE']);
        }
    }

    private function loadConfigs(): void
    {
        $this->config['app'] = require __DIR__ . '/../config/app.php';
        $this->config['dynamics'] = require __DIR__ . '/../config/dynamics.php';
    }

    private function setupLogger(): void
{
    $logConfig = $this->config['app']['log'];
    $this->logger = new Logger('dynamics_report');
    
    $logDir = dirname($logConfig['path']);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Garante que o arquivo existe e tem permissão de escrita
    $logFile = $logConfig['path'];
    if (!file_exists($logFile)) {
        touch($logFile);
        chmod($logFile, 0666);
    }
    
    // Verifica se é gravável
    if (!is_writable($logFile)) {
        chmod($logFile, 0666);
    }
    
    $this->logger->pushHandler(
        new StreamHandler($logFile, $this->mapLogLevel($logConfig['level']))
    );
    
    // Teste de gravação
    $this->logger->info('Logger inicializado com sucesso', [
        'log_file' => $logFile,
        'log_level' => $logConfig['level']
    ]);
}

    private function setupCache(): void
    {
        $cacheConfig = $this->config['app']['cache'];
        
        if ($cacheConfig['driver'] === 'redis' && extension_loaded('redis')) {
            try {
                $redis = RedisAdapter::createConnection(
                    "redis://{$cacheConfig['redis']['host']}:{$cacheConfig['redis']['port']}",
                    ['auth' => $cacheConfig['redis']['password']]
                );
                $this->cache = new RedisAdapter($redis, 'dynamics_', 3600);
            } catch (\Exception $e) {
                $this->logger->warning('Redis não disponível, usando filesystem', ['error' => $e->getMessage()]);
                $this->cache = $this->createFilesystemCache($cacheConfig);
            }
        } else {
            $this->cache = $this->createFilesystemCache($cacheConfig);
        }
    }

    private function createFilesystemCache(array $config): FilesystemAdapter
    {
        $cachePath = $config['filesystem']['path'];
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        return new FilesystemAdapter('dynamics_', 3600, $cachePath);
    }

    private function setupSession(): void
{
    // Só configura sessão se ainda não foi iniciada
    if (session_status() === PHP_SESSION_NONE) {
        $sessionConfig = $this->config['app']['session'];
        $sessionPath = $sessionConfig['path'];
        
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0755, true);
        }
        
        // Configura ANTES de iniciar a sessão
        ini_set('session.save_path', $sessionPath);
        ini_set('session.gc_maxlifetime', $sessionConfig['lifetime'] * 60);
        
        session_start();
    }
}

    private function setupDirectories(): void
    {
        $dirs = [
            __DIR__ . '/../logs',
            __DIR__ . '/../storage/cache',
            __DIR__ . '/../storage/sessions',
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    private function mapLogLevel(string $level): int
    {
        $levels = [
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
        ];
        
        return $levels[strtolower($level)] ?? Logger::ERROR;
    }

    public function getConfig(string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function getCache()
    {
        return $this->cache;
    }
}