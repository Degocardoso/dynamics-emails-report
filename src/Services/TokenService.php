<?php

namespace App\Services;

use App\Bootstrap;
use GuzzleHttp\Client;
use Psr\Cache\InvalidArgumentException;

class TokenService
{
    private $config;
    private $cache;
    private $logger;
    private $client;
    
    private const CACHE_KEY = 'dynamics_oauth_token';

    public function __construct()
    {
        $bootstrap = Bootstrap::getInstance();
        $this->config = $bootstrap->getConfig('dynamics');
        $this->cache = $bootstrap->getCache();
        $this->logger = $bootstrap->getLogger();
        $this->client = new Client([
            'timeout' => $this->config['api']['timeout'],
            'verify' => false, // Desabilitado para evitar problemas de certificado
        ]);
    }

    /**
     * Obtém o token de acesso (do cache ou via nova requisição)
     */
    public function getAccessToken(): ?string
    {
        try {
            // Tenta buscar do cache
            $cacheItem = $this->cache->getItem(self::CACHE_KEY);
            
            if ($cacheItem->isHit()) {
                $this->logger->debug('Token obtido do cache');
                return $cacheItem->get();
            }
            
            // Se não existe em cache, requisita novo
            $token = $this->requestNewToken();
            
            if ($token) {
                // Armazena no cache
                $cacheItem->set($token);
                $cacheItem->expiresAfter($this->config['oauth']['cache_ttl']);
                $this->cache->save($cacheItem);
                
                $this->logger->info('Novo token OAuth obtido e armazenado em cache');
            }
            
            return $token;
            
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Erro ao acessar cache de token', ['error' => $e->getMessage()]);
            // Fallback: tenta obter token sem cache
            return $this->requestNewToken();
        } catch (\Exception $e) {
            $this->logger->error('Erro inesperado ao obter token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Requisita um novo token OAuth ao Microsoft
     */
    private function requestNewToken(): ?string
    {
        try {
            $this->logger->debug('Requisitando novo token OAuth');
            
            $response = $this->client->post($this->config['oauth']['token_url'], [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'resource' => $this->config['resource'],
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['access_token'])) {
                $this->logger->info('Token OAuth obtido com sucesso');
                return $data['access_token'];
            }
            
            $this->logger->error('Resposta da API OAuth sem access_token', ['response' => $data]);
            return null;
            
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $responseBody = $e->getResponse()->getBody()->getContents();
            
            $this->logger->error('Erro 4xx ao obter token OAuth', [
                'status_code' => $statusCode,
                'response' => $responseBody,
            ]);
            return null;
            
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            
            $this->logger->error('Erro 5xx ao obter token OAuth', [
                'status_code' => $statusCode,
                'message' => $e->getMessage(),
            ]);
            return null;
            
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->logger->error('Erro Guzzle ao obter token OAuth', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Erro inesperado ao obter token', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    /**
     * Invalida o token em cache (útil para forçar renovação)
     */
    public function invalidateToken(): void
    {
        try {
            $this->cache->deleteItem(self::CACHE_KEY);
            $this->logger->info('Token invalidado do cache');
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Erro ao invalidar token', ['error' => $e->getMessage()]);
        }
    }
}   