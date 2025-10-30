<?php

namespace App\Services;

use App\Bootstrap;
use GuzzleHttp\Client;

class DynamicsApiFetchXmlService
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
            'timeout' => 120, // Aumentado para 120s para acomodar buscas longas
            'verify' => false,
        ]);
    }

    /**
     * Busca e-mails usando FetchXML com paginação
     * @param array $subjects Assuntos a serem filtrados
     * @param string $startDate Data inicial para filtro 'senton'
     * @return array
     */
    public function fetchEmailsWithFetchXml(array $subjects, string $startDate): array
    {
        $accessToken = $this->tokenService->getAccessToken();
        
        if (!$accessToken) {
            return ['error' => 'Falha na autenticação'];
        }

        $allEmails = [];
        $page = 1;
        $pageSize = 5000;
        $moreRecords = true;
        $pagingCookie = null;
        $maxPages = 100; // Limite de 100 páginas para segurança

        while ($moreRecords && $page <= $maxPages) { 
            $this->logger->info("Buscando página $page com FetchXML");

            $fetchXml = $this->buildFetchXml($subjects, $startDate, $page, $pageSize, $pagingCookie);
            
            // A requisição usa GET com o FetchXML codificado na URL (Web API)
            // A entidade "emails" é o Entity Set Name para a entidade "email" (minúsculo e plural).
            $url = $this->config['api']['base_url'] . '/emails?fetchXml=' . urlencode($fetchXml);

            try {
                $response = $this->client->get($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                        'OData-Version' => '4.0',
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                $emails = $data['value'] ?? [];
                $allEmails = array_merge($allEmails, $emails);

                $this->logger->info("Página $page: " . count($emails) . " registros | Total: " . count($allEmails));

                // Extração dos atributos de paginação para a próxima iteração
                $moreRecords = isset($data['@Microsoft.Dynamics.CRM.morerecords']) && $data['@Microsoft.Dynamics.CRM.morerecords'];
                $pagingCookie = $data['@Microsoft.Dynamics.CRM.pagingcookie'] ?? null;

                if (!$moreRecords) {
                    $this->logger->info("Fim da paginação na página $page");
                    break;
                }

                $page++;
                usleep(100000); // Pausa de 0.1s para evitar sobrecarga na API

            } catch (\Exception $e) {
                $this->logger->error("Erro na página $page: " . $e->getMessage());
                return ['error' => 'Erro na página ' . $page . ': ' . $e->getMessage()];
            }
        }

        if ($page > $maxPages) {
            $this->logger->warning("Limite máximo de páginas ($maxPages) atingido.");
        }
        
        $this->logger->info("Total de páginas lidas: " . ($page - 1) . " | Total de e-mails: " . count($allEmails));
        return $allEmails;
    }

    /**
     * Monta o FetchXML, incluindo atributos de paginação (page, count, paging-cookie).
     */
    private function buildFetchXml(array $subjects, string $startDate, int $page, int $pageSize, $pagingCookie): string
    {
        $dateFormatted = date('Y-m-d', strtotime($startDate));
        
        $subjectFilters = '';
        foreach ($subjects as $subject) {
            // Garante que o assunto está seguro para ser inserido no XML
            $subject = htmlspecialchars(trim($subject), ENT_XML1); 
            $subjectFilters .= "<condition attribute='subject' operator='like' value='%{$subject}%' />";
        }

        // Adiciona o paging-cookie. Ele deve ser HTML-encoded antes de ser inserido no XML.
        $pagingAttr = $pagingCookie ? " paging-cookie='" . htmlspecialchars($pagingCookie, ENT_XML1) . "'" : '';

        $fetchXml = <<<XML
<fetch mapping="logical" page="{$page}" count="{$pageSize}"{$pagingAttr}>
  <entity name="email">
    <attribute name="subject" />
    <attribute name="senton" />
    <attribute name="cad_statusemail" />
    <attribute name="statuscode" />
    <attribute name="sender" />
    <filter type="and">
      <filter type="or">
        {$subjectFilters}
      </filter>
      <condition attribute="senton" operator="ge" value="{$dateFormatted}" />
      <condition attribute="sender" operator="eq" value="sucessoalvarista@fecap.br" />
    </filter>
    <order attribute="senton" descending="true" />
  </entity>
</fetch>
XML;

        return $fetchXml;
    }
}