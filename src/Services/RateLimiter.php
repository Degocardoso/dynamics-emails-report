<?php

namespace App\Services;

use App\Bootstrap;
use Psr\Cache\InvalidArgumentException;

class RateLimiter
{
    private $cache;
    private $logger;
    private $maxRequests;
    private $periodMinutes;

    public function __construct()
    {
        $bootstrap = Bootstrap::getInstance();
        $this->cache = $bootstrap->getCache();
        $this->logger = $bootstrap->getLogger();
        
        $rateLimitConfig = $bootstrap->getConfig('dynamics.rate_limit');
        $this->maxRequests = $rateLimitConfig['max_requests'];
        $this->periodMinutes = $rateLimitConfig['period_minutes'];
    }

    /**
     * Verifica se o identificador (ex: IP, user_id) pode fazer uma requisição
     */
    public function allowRequest(string $identifier): bool
    {
        try {
            $cacheKey = $this->getCacheKey($identifier);
            $cacheItem = $this->cache->getItem($cacheKey);
            
            if (!$cacheItem->isHit()) {
                // Primeira requisição no período
                $cacheItem->set(1);
                $cacheItem->expiresAfter($this->periodMinutes * 60);
                $this->cache->save($cacheItem);
                return true;
            }
            
            $currentCount = (int) $cacheItem->get();
            
            if ($currentCount >= $this->maxRequests) {
                $this->logger->warning('Rate limit excedido', [
                    'identifier' => $identifier,
                    'count' => $currentCount,
                    'max' => $this->maxRequests,
                ]);
                return false;
            }
            
            // Incrementa contador
            $cacheItem->set($currentCount + 1);
            $this->cache->save($cacheItem);
            
            return true;
            
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Erro no rate limiter', ['error' => $e->getMessage()]);
            // Em caso de erro, permite a requisição (fail-open)
            return true;
        }
    }

    /**
     * Obtém o número de requisições restantes
     */
    public function getRemainingRequests(string $identifier): int
    {
        try {
            $cacheKey = $this->getCacheKey($identifier);
            $cacheItem = $this->cache->getItem($cacheKey);
            
            if (!$cacheItem->isHit()) {
                return $this->maxRequests;
            }
            
            $currentCount = (int) $cacheItem->get();
            return max(0, $this->maxRequests - $currentCount);
            
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Erro ao obter requisições restantes', ['error' => $e->getMessage()]);
            return $this->maxRequests;
        }
    }

    /**
     * Reseta o contador para um identificador
     */
    public function resetLimit(string $identifier): void
    {
        try {
            $cacheKey = $this->getCacheKey($identifier);
            $this->cache->deleteItem($cacheKey);
            $this->logger->info('Rate limit resetado', ['identifier' => $identifier]);
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Erro ao resetar rate limit', ['error' => $e->getMessage()]);
        }
    }

    private function getCacheKey(string $identifier): string
    {
        return 'rate_limit_' . md5($identifier);
    }

    public function getMaxRequests(): int
    {
        return $this->maxRequests;
    }

    public function getPeriodMinutes(): int
    {
        return $this->periodMinutes;
    }
}