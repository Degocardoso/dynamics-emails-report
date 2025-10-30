# 📊 Sistema de Relatório de Engajamento de E-mails - Dynamics 365

Sistema completo e refatorado para análise de campanhas de e-mail do Microsoft Dynamics 365 com arquitetura moderna, segurança aprimorada e performance otimizada. Gerencie relatórios de engajamento com métricas detalhadas, múltiplos formatos de exportação e análise avançada de campanhas de marketing.

## 🚀 Melhorias Implementadas

### ✅ Arquitetura
- **Padrão MVC**: Separação clara entre Models, Views e Controllers
- **PSR-4 Autoloading**: Organização moderna de classes
- **Dependency Injection**: Melhor testabilidade e manutenção
- **Single Responsibility**: Cada classe com uma responsabilidade específica

### 🔒 Segurança
- **SSL Verification Habilitado**: Comunicação segura com APIs
- **Sanitização Robusta**: Proteção contra XSS e SQL Injection
- **Rate Limiting**: Proteção contra abuso (100 req/hora por padrão)
- **Input Validation**: Validação completa com Respect/Validation
- **Headers de Segurança**: XSS Protection, X-Frame-Options, etc.

### ⚡ Performance
- **Cache de Token OAuth**: Token reutilizado por 55 minutos
- **Suporte a Redis**: Cache distribuído (opcional)
- **Sessões Otimizadas**: Armazenamento em filesystem separado
- **Compressão Gzip**: Arquivos estáticos comprimidos

### 📝 Logging & Monitoramento
- **Monolog Integration**: Logs estruturados e níveis configuráveis
- **Error Tracking**: Captura de exceções e erros PHP
- **Audit Trail**: Registro de todas as requisições importantes

### 🎯 Funcionalidades
- **Multibusca por Assunto**: Pesquisa por múltiplos assuntos simultaneamente (separados por `;;`)
- **Busca por Intervalo de Datas**: Pesquisa emails em um período específico (até 1 ano)
- **Relatórios Agrupados**: Análise separada e detalhada por assunto
- **Métricas Avançadas**: CTR, taxa de abertura, taxa de entrega, análise de intervalo
- **Múltiplos Formatos de Exportação**: CSV, Excel (.xlsx), PDF e XML
- **Processamento em Lote**: Suporte para grandes volumes de dados (1000+ emails)
- **Filtragem Inteligente**: Remove emails padrão/automatizados e destinatários de teste
- **UI/UX Moderna**: Interface responsiva e intuitiva com feedback em tempo real

## 🔍 Visão Geral Técnica

### O Que Este Sistema Faz?

Este sistema é uma **aplicação web PHP moderna** que conecta-se ao **Microsoft Dynamics 365 CRM** para:

1. **Extrair dados** de campanhas de e-mail usando a API OData v9.2
2. **Analisar métricas** de engajamento (aberturas, cliques, entregas)
3. **Gerar relatórios** detalhados agrupados por campanha
4. **Exportar resultados** em múltiplos formatos para análise externa

### Arquitetura

```
┌─────────────┐      OAuth 2.0      ┌──────────────────┐
│  Navegador  │ ◄─────────────────► │  Dynamics Email  │
│   (Client)  │                     │  Report System   │
└─────────────┘                     └────────┬─────────┘
                                             │
                                    ┌────────┴─────────┐
                                    │                  │
                              ┌─────▼─────┐    ┌──────▼──────┐
                              │   Cache    │    │  Dynamics   │
                              │  (Redis/   │    │  365 API    │
                              │   Files)   │    │  (OData)    │
                              └────────────┘    └─────────────┘
```

### Stack Tecnológico

- **Backend**: PHP 7.4+ com arquitetura MVC
- **API**: Microsoft Dynamics 365 Web API v9.2 (OData)
- **Autenticação**: OAuth 2.0 (Microsoft Identity Platform)
- **Cache**: Symfony Cache (Filesystem ou Redis)
- **Logs**: Monolog (estruturado)
- **HTTP Client**: Guzzle 7.x
- **Validação**: Respect/Validation
- **Exportação**: PhpSpreadsheet (Excel), TCPDF (PDF)
- **Servidor Web**: Apache 2.4+ ou Nginx 1.10+

### Fluxo de Requisição

```
1. Usuário envia formulário
   ↓
2. EmailReportController valida entrada
   ↓
3. TokenService obtém token OAuth (cache de 55 min)
   ↓
4. DynamicsApiService busca emails (paginação automática)
   ↓
5. BatchEmailProcessor processa em lotes
   ↓
6. EmailReport calcula métricas
   ↓
7. View renderiza resultados ou Exporter gera arquivo
```

## 📁 Estrutura do Projeto

```
dynamics-email-report/
├── config/                            # Configurações
│   ├── app.php                       # Configurações gerais (cache, logs, sessões)
│   ├── dynamics.php                  # Configurações do Dynamics 365 API
│   └── default_emails.php            # Lista de assuntos padrão para filtragem
│
├── logs/                              # Logs da aplicação
│   └── app.log                       # Logs estruturados (Monolog)
│
├── public/                            # Diretório público (DocumentRoot)
│   ├── .htaccess                     # Configurações Apache + segurança
│   ├── index.php                     # Front Controller (ponto de entrada)
│   └── teste-*.php                   # Arquivos de teste/debug
│
├── src/                               # Código-fonte da aplicação (PSR-4)
│   ├── Bootstrap.php                 # Inicialização da aplicação (Singleton)
│   │
│   ├── Controllers/                  # Controladores MVC
│   │   └── EmailReportController.php # Gerencia requisições e respostas
│   │
│   ├── Models/                       # Modelos de negócio
│   │   └── EmailReport.php           # Lógica de cálculo de métricas
│   │
│   ├── Services/                     # Camada de serviços
│   │   ├── TokenService.php          # Gerenciamento de tokens OAuth 2.0
│   │   ├── DynamicsApiService.php    # Integração com Dynamics 365 (OData)
│   │   ├── DynamicsApiFetchXmlService.php # Alternativa com FetchXML
│   │   ├── RateLimiter.php           # Controle de taxa de requisições
│   │   ├── BatchEmailProcessor.php   # Processamento em lote
│   │   ├── CsvExporter.php           # Exportação para CSV
│   │   ├── ExcelExporter.php         # Exportação para Excel (.xlsx)
│   │   ├── PdfExporter.php           # Exportação para PDF (TCPDF)
│   │   └── XmlExporter.php           # Exportação para XML
│   │
│   ├── Validators/                   # Validadores de entrada
│   │   ├── ReportRequestValidator.php # Valida buscas por assunto
│   │   └── ReportDateValidator.php   # Valida buscas por data
│   │
│   └── Views/                        # Camada de visualização
│       └── report_form.php           # Template HTML do formulário
│
├── storage/                          # Armazenamento em runtime
│   ├── cache/                        # Cache de arquivos
│   └── sessions/                     # Sessões PHP
│
├── tests/                            # Testes automatizados
│   └── Unit/
│       └── EmailReportTest.php       # Testes unitários
│
├── vendor/                           # Dependências Composer
│   └── autoload.php                  # Autoloader PSR-4
│
├── Arquivos de configuração
│   ├── .env                          # Variáveis de ambiente (gitignored)
│   ├── .env.example                  # Template de configuração
│   ├── .gitignore                    # Exclusões do Git
│   ├── .htaccess                     # Reescrita de URLs (Apache)
│   ├── composer.json                 # Dependências PHP
│   ├── composer.lock                 # Versões travadas
│   ├── phpunit.xml.dist              # Configuração de testes
│   └── nginx.conf.example            # Configuração Nginx
│
└── Documentação
    ├── README.md                     # Documentação principal (este arquivo)
    ├── STRUCTURE.md                  # Guia detalhado de arquitetura
    ├── QUICKSTART.md                 # Guia rápido de instalação
    ├── MIGRATION_GUIDE.md            # Guia de migração
    ├── CHANGES.md                    # Resumo de refatorações
    └── DEV_COMMANDS.md               # Comandos de desenvolvimento
```

## 🛠️ Instalação

### Requisitos
- PHP >= 7.4
- Composer
- Extensões: mbstring, curl, json, openssl
- Apache/Nginx com mod_rewrite
- (Opcional) Redis para cache distribuído

### Passo a Passo

1. **Clone o repositório**
```bash
git clone <seu-repo>
cd dynamics-email-report
```

2. **Instale as dependências**
```bash
composer install
```

3. **Configure o ambiente**
```bash
cp .env.example .env
```

Edite o `.env` com suas credenciais:
```env
TENANT_ID=seu-tenant-id
CLIENT_ID=seu-client-id
CLIENT_SECRET=seu-client-secret
RESOURCE=https://sua-instancia.crm.dynamics.com
```

4. **Crie os diretórios necessários**
```bash
mkdir -p logs storage/cache storage/sessions
chmod 755 logs storage/cache storage/sessions
```

5. **Configure o Apache**

Aponte o DocumentRoot para a pasta `public/`:
```apache
<VirtualHost *:80>
    ServerName seu-dominio.com
    DocumentRoot /caminho/para/dynamics-email-report/public
    
    <Directory /caminho/para/dynamics-email-report/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

6. **Acesse a aplicação**
```
http://seu-dominio.com
```

## ⚙️ Configuração

### Variáveis de Ambiente (.env)

```env
# Dynamics 365
TENANT_ID=your-tenant-id
CLIENT_ID=your-client-id
CLIENT_SECRET=your-client-secret
RESOURCE=https://your-instance.crm.dynamics.com

# Aplicação
APP_ENV=production          # production|development
APP_DEBUG=false            # true|false
LOG_LEVEL=error           # debug|info|warning|error

# Cache (opcional - Redis)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Rate Limiting
RATE_LIMIT_MAX_REQUESTS=100      # Máximo de requisições
RATE_LIMIT_PERIOD_MINUTES=60     # Por período em minutos

# Sessão
SESSION_LIFETIME=120             # Minutos
```

### Cache

**Filesystem (padrão)**
- Não requer configuração adicional
- Armazena em `storage/cache/`

**Redis (recomendado para produção)**
- Configure as variáveis REDIS_* no .env
- Instale a extensão PHP Redis: `pecl install redis`
- Habilite: `echo "extension=redis.so" >> /etc/php/php.ini`

## 🔧 Uso

### Interface Web

#### Busca por Assunto
1. Acesse a aplicação no navegador
2. Preencha os campos:
   - **Assuntos**: Um ou mais assuntos separados por `;;` (duplo ponto e vírgula)
     - Exemplo: `Newsletter Janeiro;;Campanha Black Friday;;Promoção Natal`
   - **Data Inicial** (opcional): Data de início para filtrar resultados
3. Opções adicionais:
   - ☑️ **Remover emails padrão**: Filtra assuntos automatizados (ASA, RE:, etc.)
   - ☑️ **Remover testes**: Exclui destinatários de teste
4. Clique em "Gerar Relatório"
5. Visualize os resultados agrupados por assunto

#### Busca por Intervalo de Datas
1. Selecione o modo "Busca por Data"
2. Defina o período:
   - **Data Inicial**: Data de início (formato: AAAA-MM-DD)
   - **Data Final**: Data de término (formato: AAAA-MM-DD)
   - Máximo: 1 ano de intervalo
3. Aplique as mesmas opções de filtragem
4. Clique em "Gerar Relatório"

#### Exportação de Dados
Após gerar o relatório, exporte nos formatos:
- **CSV**: Formato tabular com cabeçalhos, compatível com Excel (UTF-8 BOM)
- **Excel (.xlsx)**: Planilha formatada com cores e estilos
- **PDF**: Relatório profissional para apresentações
- **XML**: Dados estruturados para integração com outros sistemas

### API (Programática)

```php
use App\Services\TokenService;
use App\Services\DynamicsApiService;
use App\Models\EmailReport;

// Obter emails
$tokenService = new TokenService();
$apiService = new DynamicsApiService($tokenService);

$emails = $apiService->fetchEmails(
    ['Campanha Black Friday', 'Newsletter'],
    '2025-01-01'
);

// Gerar relatório
$reports = EmailReport::generateGroupedReports($emails);
```

## 📊 Métricas Disponíveis

### Métricas Principais
- **Total de Envios**: Quantidade total de e-mails enviados
- **Total de Entregas**: E-mails entregues com sucesso
- **Total de Aberturas**: E-mails abertos pelos destinatários
- **Total de Cliques**: Links clicados nos e-mails

### Taxas de Engajamento
- **Taxa de Entrega**: Percentual de entregas sobre envios
  - Fórmula: `(Entregas / Envios) × 100`
- **Taxa de Abertura**: Percentual de e-mails abertos sobre entregas
  - Fórmula: `(Aberturas / Entregas) × 100`
- **Taxa de Clique (CTR)**: Percentual de cliques sobre entregas
  - Fórmula: `(Cliques / Entregas) × 100`

### Análise Temporal
- **Data do Primeiro Envio**: Timestamp do primeiro e-mail da campanha
- **Data do Último Envio**: Timestamp do último e-mail da campanha
- **Intervalo de Envio**: Tempo decorrido entre primeiro e último envio
  - Formato: "X dias, Y horas, Z minutos"

### Detalhamento por Status
Contadores individuais para cada status de e-mail:
- **Delivered** (Entregue)
- **Opened** (Aberto)
- **Clicked** (Clicado)
- **Sent** (Enviado)
- **Pending Send** (Aguardando envio)
- **Failed** (Falha)
- **Canceled** (Cancelado)
- E outros status do Dynamics 365

### Agrupamento
Todos as métricas são calculadas e apresentadas:
- **Por assunto**: Relatórios individuais para cada campanha
- **Consolidado**: Resumo geral de todas as campanhas analisadas

## 🔍 Logs e Debugging

### Logs
- Localização: `logs/app.log`
- Níveis: debug, info, warning, error, critical
- Rotação: Configure logrotate para ambientes produção

### Debug Mode
```env
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=debug
```

**⚠️ NUNCA habilite debug em produção!**

## 📦 Dependências

### Bibliotecas PHP (via Composer)

| Pacote | Versão | Uso |
|--------|--------|-----|
| **guzzlehttp/guzzle** | ^7.0 | Cliente HTTP para comunicação com API do Dynamics 365 |
| **vlucas/phpdotenv** | ^5.0 | Carregamento de variáveis de ambiente (.env) |
| **monolog/monolog** | ^2.0 | Sistema de logs estruturados e níveis configuráveis |
| **respect/validation** | ^2.0 | Validação robusta de entrada de dados |
| **symfony/cache** | ^5.0 | Sistema de cache (Filesystem/Redis) |
| **phpoffice/phpspreadsheet** | ^5.1 | Geração de planilhas Excel (.xlsx) |
| **tecnickcom/tcpdf** | ^6.10 | Geração de documentos PDF |

### Dependências de Desenvolvimento
- **phpunit/phpunit** (^9.0): Framework de testes unitários

### Extensões PHP Necessárias
- **mbstring**: Manipulação de strings multibyte (UTF-8)
- **curl**: Requisições HTTP/HTTPS
- **json**: Processamento JSON (API responses)
- **openssl**: Comunicação SSL/TLS segura
- **zip**: Requerida pelo PhpSpreadsheet
- **gd** ou **imagick**: Manipulação de imagens (opcional, para PDFs)

## 🧪 Testes

```bash
# Instalar PHPUnit
composer require --dev phpunit/phpunit

# Executar testes
./vendor/bin/phpunit tests/
```

## 🚀 Deploy em Produção

### Checklist

- [ ] Configurar .env com credenciais reais
- [ ] `APP_ENV=production` e `APP_DEBUG=false`
- [ ] SSL verificado habilitado
- [ ] Redis configurado (recomendado)
- [ ] Logs com rotação configurada
- [ ] Permissões de diretório corretas (755)
- [ ] Backup automático configurado
- [ ] Monitoramento de erros ativo

### Performance

```bash
# Otimizar Composer
composer install --no-dev --optimize-autoloader

# Configurar OPcache
echo "opcache.enable=1" >> /etc/php/php.ini
echo "opcache.memory_consumption=128" >> /etc/php/php.ini
```

## 🛡️ Segurança

### Boas Práticas Implementadas

✅ HTTPS obrigatório em produção  
✅ Validação e sanitização de inputs  
✅ Rate limiting configurável  
✅ Headers de segurança (CSP, XSS, etc.)  
✅ Proteção contra CSRF  
✅ Logs de auditoria  
✅ Credenciais fora do repositório  

### Rate Limiting

Por padrão: **100 requisições por hora por IP**

Ajuste no `.env`:
```env
RATE_LIMIT_MAX_REQUESTS=50
RATE_LIMIT_PERIOD_MINUTES=30
```

## 🐛 Troubleshooting

### Erro: "Dependências não instaladas"
```bash
composer install
```

### Erro: "Falha na autenticação"
- Verifique credenciais no .env
- Confirme que o Service Principal tem permissões
- Teste token manualmente

### Erro: "Permission denied"
```bash
chmod 755 logs storage/cache storage/sessions
chown -R www-data:www-data logs storage
```

### Cache não funciona
```bash
# Limpar cache
rm -rf storage/cache/*

# Verificar Redis
redis-cli ping
```

## 📚 Documentação Adicional

Este projeto inclui documentação abrangente para diferentes necessidades:

| Documento | Descrição |
|-----------|-----------|
| **[README.md](README.md)** | Documentação principal (este arquivo) - instalação, configuração e uso |
| **[QUICKSTART.md](QUICKSTART.md)** | Guia rápido de 5 minutos para começar imediatamente |
| **[STRUCTURE.md](STRUCTURE.md)** | Arquitetura detalhada, padrões de design e fluxo de requisições |
| **[MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)** | Guia passo a passo para migração do sistema antigo |
| **[CHANGES.md](CHANGES.md)** | Resumo completo das refatorações e melhorias implementadas |
| **[DEV_COMMANDS.md](DEV_COMMANDS.md)** | Comandos úteis para desenvolvimento e troubleshooting |

### Principais Componentes Documentados

#### TokenService (src/Services/TokenService.php:1)
Gerencia autenticação OAuth 2.0 com cache de 55 minutos:
- `getAccessToken()`: Obtém token (cache ou novo)
- `requestNewToken()`: Autentica com Azure AD
- `invalidateToken()`: Limpa cache

#### DynamicsApiService (src/Services/DynamicsApiService.php:1)
Interface com Dynamics 365 usando OData v9.2:
- `fetchEmails()`: Busca por assunto
- `fetchEmailsByDateRange()`: Busca por período
- Paginação automática para grandes volumes

#### EmailReport (src/Models/EmailReport.php:1)
Processamento e cálculo de métricas:
- `groupBySubject()`: Agrupa emails por assunto
- `calculateReport()`: Calcula todas as métricas
- `filterEmails()`: Remove emails padrão e testes

#### BatchEmailProcessor (src/Services/BatchEmailProcessor.php:1)
Processamento otimizado de grandes conjuntos:
- Lotes configuráveis
- Logs de progresso a cada 10 grupos
- Métricas de performance

#### Exportadores (src/Services/)
- `CsvExporter`: UTF-8 BOM, formato tabular
- `ExcelExporter`: PhpSpreadsheet com estilos
- `PdfExporter`: TCPDF para relatórios profissionais
- `XmlExporter`: Dados estruturados XML

## 🎯 Roadmap

### Próximas Funcionalidades
- [ ] Dashboard com gráficos interativos
- [ ] Agendamento de relatórios recorrentes
- [ ] Integração com Power BI
- [ ] API REST para integração externa
- [ ] Suporte a múltiplos idiomas (i18n)
- [ ] Análise comparativa entre campanhas
- [ ] Alertas automáticos por e-mail
- [ ] Exportação para Google Sheets

### Melhorias Planejadas
- [ ] Testes de integração automatizados
- [ ] CI/CD com GitHub Actions
- [ ] Containerização com Docker
- [ ] Cache distribuído com Memcached
- [ ] Suporte a banco de dados (PostgreSQL/MySQL)

## 📞 Suporte

### Obtendo Ajuda

**Issues e Dúvidas:**
- Abra uma issue no GitHub com detalhes do problema
- Verifique os logs em `logs/app.log` para erros detalhados
- Consulte os arquivos de documentação listados acima

**Antes de Reportar:**
1. Verifique se o problema já foi reportado nas issues
2. Certifique-se de estar usando a versão mais recente
3. Revise a seção de Troubleshooting
4. Inclua logs relevantes e passos para reproduzir o problema

**Recursos Externos:**
- [Documentação do Dynamics 365 Web API](https://docs.microsoft.com/en-us/dynamics365/customer-engagement/web-api/)
- [Microsoft Identity Platform (OAuth 2.0)](https://docs.microsoft.com/en-us/azure/active-directory/develop/)
- [OData v4.0 Query Options](https://www.odata.org/documentation/)

## 👥 Contribuindo

Contribuições são bem-vindas! Por favor:
1. Faça fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

### Padrões de Código
- Siga PSR-12 para estilo de código PHP
- Adicione testes unitários para novas funcionalidades
- Documente todas as funções públicas com PHPDoc
- Mantenha a cobertura de testes acima de 70%

## 📄 Licença

Este projeto é desenvolvido para uso interno da FECAP.

## 🙏 Agradecimentos

- Equipe de Marketing da FECAP
- Microsoft Dynamics 365 Team
- Comunidade open-source PHP

---

**Desenvolvido com dedicação para FECAP**

*Versão 2.0 - Sistema Refatorado com Arquitetura Moderna*