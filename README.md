# 📊 Sistema de Relatório de Engajamento - Dynamics 365

Sistema completo e refatorado para análise de campanhas de e-mail do Microsoft Dynamics 365 com arquitetura moderna, segurança aprimorada e performance otimizada.

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
- **Multibusca**: Pesquisa por múltiplos assuntos simultaneamente
- **Relatórios Agrupados**: Análise separada por assunto
- **Métricas Avançadas**: CTR, taxa de abertura, entrega, etc.
- **Exportação CSV Completa**: Com resumo geral e estatísticas
- **UI/UX Moderna**: Interface responsiva e intuitiva

## 📁 Estrutura do Projeto

```
dynamics-email-report/
├── config/
│   ├── app.php              # Configurações gerais
│   └── dynamics.php         # Configurações do Dynamics
├── logs/
│   └── app.log             # Logs da aplicação
├── public/
│   ├── .htaccess           # Configurações Apache
│   └── index.php           # Front Controller
├── src/
│   ├── Bootstrap.php       # Inicialização da aplicação
│   ├── Controllers/
│   │   └── EmailReportController.php
│   ├── Models/
│   │   └── EmailReport.php
│   ├── Services/
│   │   ├── CsvExporter.php
│   │   ├── DynamicsApiService.php
│   │   ├── RateLimiter.php
│   │   └── TokenService.php
│   ├── Validators/
│   │   └── ReportRequestValidator.php
│   └── Views/
│       └── report_form.php
├── storage/
│   ├── cache/              # Cache filesystem
│   └── sessions/           # Sessões PHP
├── vendor/                 # Dependências Composer
├── .env.example           # Template de configuração
├── .gitignore
├── composer.json
└── README.md
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

1. Acesse a aplicação no navegador
2. Preencha os campos:
   - **Assuntos**: Um ou mais assuntos separados por vírgula
   - **Data**: Data inicial para busca
3. Clique em "Gerar Relatório"
4. Visualize os resultados agrupados por assunto
5. Exporte para CSV se necessário

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

- **Total de Envios**: Quantidade total de e-mails enviados
- **Total de Recebidos**: E-mails entregues com sucesso
- **Taxa de Abertura**: Percentual de e-mails abertos sobre recebidos
- **Taxa de Entrega**: Percentual de entregas sobre envios
- **Taxa de Clique (CTR)**: Percentual de cliques sobre recebidos
- **Detalhamento por Status**: Contadores detalhados por status

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

## 📞 Suporte

Para issues e dúvidas:
- Abra uma issue no GitHub
- Verifique os logs em `logs/app.log`
- Consulte a documentação do Dynamics 365

## 📄 Licença

[Sua licença aqui]

---

**Desenvolvido com ❤️ para FECAP**