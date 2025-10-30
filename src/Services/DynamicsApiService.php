<?php
namespace App\Services;

use App\Bootstrap;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class DynamicsApiService
{
    private $config;
    private $logger;
    private $tokenService;
    private $client;

    public function __construct(TokenService $tokenService)
    {
        $bootstrap = Bootstrap::getInstance();
        $this->config = $bootstrap->getConfig('dynamics');
        $this->logger = $bootstrap->getLogger();
        $this->tokenService = $tokenService;
        $this->client = new Client([
            'timeout' => 300, // 5 minutos
            'connect_timeout' => 60, // 1 minuto para conectar
            'read_timeout' => 300, // 5 minutos para ler resposta
            'verify' => false,
            'http_errors' => false, // Não lança exceção em erros HTTP
        ]);
    }

    /**
     * Busca e-mails com PAGINAÇÃO por ASSUNTO
     */
    public function fetchEmails(array $subjects, string $startDate, ?string $sender = null): array
    {
        $accessToken = $this->tokenService->getAccessToken();
        
        if (!$accessToken) {
            $this->logger->error('Falha ao obter token de acesso');
            return ['error' => 'Falha na autenticação'];
        }

        $sender = $sender ?? $this->config['email']['default_sender'];
        $filter = $this->buildFilterBySubject($subjects, $startDate, $sender);
        
        if (!$filter) {
            return ['error' => 'Nenhum assunto válido fornecido'];
        }

        return $this->executeQueryWithPagination($accessToken, $filter);
    }

    /**
     * Busca e-mails por INTERVALO DE DATAS
     */
    public function fetchEmailsByDateRange(string $startDate, string $endDate, ?string $sender = null): array
    {
        $accessToken = $this->tokenService->getAccessToken();
        
        if (!$accessToken) {
            $this->logger->error('Falha ao obter token de acesso');
            return ['error' => 'Falha na autenticação'];
        }

        $sender = $sender ?? $this->config['email']['default_sender'];
        $filter = $this->buildFilterByDateRange($startDate, $endDate, $sender);

        return $this->executeQueryWithPagination($accessToken, $filter);
    }

    /**
     * Executa query com paginação usando @odata.nextLink
     */
    private function executeQueryWithPagination(string $accessToken, string $filter): array
    {
        $allEmails = [];
        $pageSize = $this->config['email']['max_results_per_request'];
        $pageNumber = 1;
        $maxPages = 100;
        $retryCount = 0;
        $maxRetries = 3;
        
        $currentUrl = $this->config['api']['base_url'] . '/emails';
        
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'OData-Version' => '4.0',
                'Prefer' => 'odata.maxpagesize=' . $pageSize . ',odata.include-annotations="*"',
            ],
            'query' => [
                '$select' => 'subject,senton,cad_statusemail,statuscode,sender,torecipients',
                '$filter' => $filter,
                '$orderby' => 'senton desc',
                '$count' => 'true',
            ],
        ];
        
        $this->logger->info('========== INICIANDO BUSCA ==========');
        $this->logger->info('Filtro', ['filter' => $filter]);
        $this->logger->info('Timeout configurado', ['timeout' => '300s (5 minutos)']);

        try {
            do {
                $this->logger->info(">>> Página $pageNumber");
                
                // RETRY LOOP
                $success = false;
                $response = null;
                
                for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                    try {
                        $response = $this->client->get($currentUrl, $options);
                        $success = true;
                        break; // Sucesso, sai do retry
                    } catch (\Exception $e) {
                        $this->logger->warning("⚠️ Tentativa $attempt/$maxRetries falhou", [
                            'error' => $e->getMessage()
                        ]);
                        
                        if ($attempt < $maxRetries) {
                            $waitTime = $attempt * 2; // Espera progressiva: 2s, 4s, 6s
                            $this->logger->info("Aguardando {$waitTime}s antes de tentar novamente...");
                            sleep($waitTime);
                        }
                    }
                }
                
                if (!$success || !$response) {
                    throw new \Exception('Falha após ' . $maxRetries . ' tentativas');
                }
                
                $data = json_decode($response->getBody()->getContents(), true);
                $emails = $data['value'] ?? [];
                $emailCount = count($emails);
                
                if ($emailCount > 0) {
                    $allEmails = array_merge($allEmails, $emails);
                    $this->logger->info("✅ Página $pageNumber: $emailCount e-mails | Total: " . count($allEmails));
                }
                
                $nextLink = $data['@odata.nextLink'] ?? null;
                $currentUrl = $nextLink;
                
                if ($currentUrl) {
                    unset($options['query']);
                } else {
                    $this->logger->info("🏁 Fim da paginação");
                    break;
                }
                
                $pageNumber++;
                
                // Reduz delay entre páginas
                usleep(25000); // 25ms ao invés de 50ms
                
                if ($pageNumber > $maxPages) {
                    $this->logger->warning("⚠️ Limite de $maxPages páginas atingido!");
                    break;
                }
                
            } while ($currentUrl !== null);
            
            $this->logger->info('========== BUSCA CONCLUÍDA ==========', [
                'total_pages' => $pageNumber - 1,
                'total_emails' => count($allEmails),
            ]);
            
            return $allEmails;
            
        } catch (RequestException $e) {
            // RequestException TEM hasResponse()
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Sem resposta';
            
            $this->logger->error('❌ ERRO RequestException', [
                'page' => $pageNumber,
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
                'response' => $responseBody,
            ]);
            
            if ($statusCode === 401) {
                $this->tokenService->invalidateToken();
                return ['error' => 'Falha na autenticação. Token inválido.'];
            }
            
            return ['error' => 'Erro HTTP na página ' . $pageNumber . ': ' . $e->getMessage()];
            
        } catch (GuzzleException $e) {
            // Outras exceções do Guzzle (como ConnectException) NÃO têm hasResponse()
            $this->logger->error('❌ ERRO GuzzleException', [
                'page' => $pageNumber,
                'error' => $e->getMessage(),
                'type' => get_class($e),
            ]);
            
            // Mensagens específicas para erros comuns
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'cURL error 6') !== false || strpos($errorMsg, 'Could not resolve host') !== false) {
                return ['error' => 'Erro de conexão: Não foi possível conectar ao servidor Dynamics. Verifique a URL e conexão de rede.'];
            }
            if (strpos($errorMsg, 'cURL error 7') !== false) {
                return ['error' => 'Erro de conexão: Falha ao conectar. Verifique se o servidor está acessível.'];
            }
            if (strpos($errorMsg, 'cURL error 28') !== false) {
                return ['error' => 'Erro de timeout: A requisição demorou muito. Tente novamente.'];
            }
            
            return ['error' => 'Erro de conexão na página ' . $pageNumber . ': ' . $errorMsg];
            
        } catch (\Exception $e) {
            // Qualquer outra exceção
            $this->logger->error('❌ ERRO Genérico', [
                'page' => $pageNumber,
                'error' => $e->getMessage(),
                'type' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return ['error' => 'Erro inesperado: ' . $e->getMessage()];
        }
    }

    /**
     * FILTRO POR ASSUNTO
     */
    private function buildFilterBySubject(array $subjects, string $startDate, string $sender): ?string
    {
        $subjectConditions = [];
        
        foreach ($subjects as $subject) {
            $cleanSubject = trim($subject);
            if (empty($cleanSubject)) {
                continue;
            }
            
            $escapedSubject = $this->escapeODataString($cleanSubject);
            $subjectConditions[] = "contains(subject, '{$escapedSubject}')";
        }

        if (empty($subjectConditions)) {
            return null;
        }

        $subjectFilter = '(' . implode(' or ', $subjectConditions) . ')';
        $formattedDate = $this->formatDateForOData($startDate);
        $escapedSender = $this->escapeODataString($sender);
        
        return "{$subjectFilter} and senton ge {$formattedDate} and sender eq '{$escapedSender}'";
    }

    /**
     * FILTRO POR INTERVALO DE DATAS
     */
    private function buildFilterByDateRange(string $startDate, string $endDate, string $sender): string
    {
        $formattedStartDate = $this->formatDateForOData($startDate);
        $formattedEndDate = $this->formatDateForOData($endDate, true); // fim do dia
        $escapedSender = $this->escapeODataString($sender);
        
        return "senton ge {$formattedStartDate} and senton le {$formattedEndDate} and sender eq '{$escapedSender}'";
    }

    private function escapeODataString(string $value): string
    {
        $value = str_replace("'", "''", $value);
        $value = preg_replace('/[^\w\s\-@.,:;!?()áéíóúàèìòùâêîôûãõçÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕÇ\'\/]/u', '', $value);
        return $value;
    }

    /**
     * Formata data para OData com suporte a fim do dia
     */
    private function formatDateForOData(string $date, bool $endOfDay = false): string
    {
        try {
            $dateObj = new \DateTime($date);
            
            if ($endOfDay) {
                $dateObj->setTime(23, 59, 59);
            } else {
                $dateObj->setTime(0, 0, 0);
            }
            
            $dateObj->setTimezone(new \DateTimeZone('UTC'));
            
            return $dateObj->format('Y-m-d\TH:i:s\Z');
        } catch (\Exception $e) {
            $this->logger->error('Data inválida', ['date' => $date]);
            return (new \DateTime('today', new \DateTimeZone('UTC')))->format('Y-m-d\T00:00:00\Z');
        }
    }
}