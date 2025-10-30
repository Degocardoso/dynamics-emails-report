# 🔄 Guia de Migração

Este documento guia a migração do código antigo (monolítico) para a nova arquitetura refatorada.

## 📋 Checklist de Migração

### Antes de Começar
- [ ] Faça backup completo do sistema atual
- [ ] Documente as credenciais atuais do .env
- [ ] Teste o sistema atual e documente o comportamento esperado
- [ ] Verifique a versão do PHP (mínimo 7.4)

### Passos de Migração

#### 1. Preparação do Ambiente

```bash
# Backup do sistema antigo
cp -r /caminho/antigo /caminho/antigo.backup

# Clone ou crie o novo diretório
mkdir dynamics-email-report-new
cd dynamics-email-report-new
```

#### 2. Instalação

```bash
# Copie todos os arquivos novos
# Execute o script de instalação
chmod +x install.sh
./install.sh
```

#### 3. Migração de Configurações

**Arquivo antigo:** `relatorio_emails.php` (variáveis dentro do código)

**Novo:** `.env`

```bash
# Copie credenciais do código antigo para .env
cp .env.example .env
nano .env
```

Mapeamento de variáveis:

| Antigo (código) | Novo (.env) |
|-----------------|-------------|
| `$_ENV['TENANT_ID']` | `TENANT_ID` |
| `$_ENV['CLIENT_ID']` | `CLIENT_ID` |
| `$_ENV['CLIENT_SECRET']` | `CLIENT_SECRET` |
| `$_ENV['RESOURCE']` | `RESOURCE` |

#### 4. Configuração do Servidor Web

**Apache:**
```bash
# Copie a configuração
sudo nano /etc/apache2/sites-available/dynamics-report.conf
```

Aponte `DocumentRoot` para `public/` do novo sistema.

**Nginx:**
```bash
sudo nano /etc/nginx/sites-available/dynamics-report
# Use o template em nginx.conf.example
```

#### 5. Teste de Compatibilidade

Execute este script de teste:

```php
<?php
// test_compatibility.php

require 'vendor/autoload.php';

use App\Bootstrap;
use App\Services\TokenService;

echo "=== Teste de Compatibilidade ===\n\n";

try {
    $bootstrap = Bootstrap::getInstance();
    echo "✅ Bootstrap inicializado\n";
    
    $config = $bootstrap->getConfig('dynamics');
    if (!empty($config['client_id'])) {
        echo "✅ Configurações carregadas\n";
    } else {
        echo "❌ Erro: Configurações vazias\n";
        exit(1);
    }
    
    $tokenService = new TokenService();
    $token = $tokenService->getAccessToken();
    
    if ($token) {
        echo "✅ Token OAuth obtido com sucesso\n";
        echo "\nSistema pronto para uso!\n";
    } else {
        echo "❌ Erro ao obter token\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
```

Execute:
```bash
php test_compatibility.php
```

#### 6. Migração de Dados

**Se você tinha dados armazenados em sessão:**

O novo sistema mantém compatibilidade com sessões. Não é necessário migração.

**Se você tinha logs customizados:**

```bash
# Os logs novos ficam em logs/app.log
# Você pode consolidar logs antigos se necessário
cat /caminho/antigo/logs/* >> logs/app.log
```

#### 7. Testes Funcionais

Execute estes testes para validar a migração:

1. **Teste de Busca Simples**
   - Assunto: "Test"
   - Data: Última semana
   - Resultado esperado: Lista de e-mails

2. **Teste de Multibusca**
   - Assuntos: "Campaign A, Campaign B"
   - Data: Último mês
   - Resultado esperado: Relatórios agrupados

3. **Teste de Exportação CSV**
   - Gere um relatório
   - Clique em "Exportar CSV"
   - Resultado esperado: Download do CSV

4. **Teste de Rate Limiting**
   - Execute 101 requisições rapidamente
   - Resultado esperado: Mensagem de limite excedido

#### 8. Cutover (Transição)

```bash
# 1. Coloque o site antigo em manutenção
# Crie um arquivo maintenance.html

# 2. Atualize DNS ou configuração do servidor
sudo nano /etc/apache2/sites-enabled/000-default.conf
# Altere DocumentRoot para o novo sistema

# 3. Reinicie o servidor
sudo systemctl restart apache2

# 4. Monitore os logs
tail -f logs/app.log
```

## 🔍 Comparação de Funcionalidades

| Funcionalidade | Sistema Antigo | Sistema Novo | Status |
|----------------|----------------|--------------|--------|
| Busca por assunto | ✅ | ✅ | Melhorado |
| Multibusca | ✅ | ✅ | Mantido |
| Relatórios agrupados | ✅ | ✅ | Melhorado |
| Exportação CSV | ✅ | ✅ | Melhorado |
| Cache de token | ❌ | ✅ | **Novo** |
| Rate limiting | ❌ | ✅ | **Novo** |
| Validação robusta | ❌ | ✅ | **Novo** |
| Logs estruturados | ❌ | ✅ | **Novo** |
| Arquitetura MVC | ❌ | ✅ | **Novo** |
| Testes unitários | ❌ | ✅ | **Novo** |

## 🚨 Diferenças Importantes

### 1. Estrutura de Pastas

**Antigo:**
```
relatorio_emails.php (tudo em um arquivo)
vendor/
.env
```

**Novo:**
```
public/index.php (front controller)
src/ (código da aplicação)
config/ (configurações)
logs/ (logs)
storage/ (cache e sessões)
vendor/ (dependências)
```

### 2. Ponto de Entrada

**Antigo:** `relatorio_emails.php`

**Novo:** `public/index.php`

⚠️ **Importante:** Configure o DocumentRoot para `public/`

### 3. Variáveis de Sessão

**Compatibilidade total**. As mesmas variáveis são usadas:
- `$_SESSION['last_result']`
- `$_SESSION['last_filters']`

### 4. Formato de Resposta da API

**Mantido 100% compatível**. O formato dos dados retornados é idêntico.

### 5. Exportação CSV

**Melhorado**. Agora inclui:
- Resumo geral
- Percentuais
- Formatação aprimorada

### 6. URLs

**Antigo:**
- `relatorio_emails.php` (formulário)
- `relatorio_emails.php?export=csv` (exportação)

**Novo:**
- `/` (formulário)
- `/?export=csv` (exportação)

## 🐛 Troubleshooting Comum

### Erro: "Class not found"

```bash
# Regenere o autoloader
composer dump-autoload
```

### Erro: "Permission denied" nos logs

```bash
chmod 755 logs storage/cache storage/sessions
chown -R www-data:www-data logs storage
```

### Token não é cacheado (requisições lentas)

```bash
# Verifique se o cache está funcionando
php -r "
require 'vendor/autoload.php';
\$b = App\Bootstrap::getInstance();
\$cache = \$b->getCache();
echo 'Cache: ' . get_class(\$cache);
"
```

### CSS/JS não carregam

Verifique se o `.htaccess` está ativo:
```bash
# Apache
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Relatório não exibe dados

```bash
# Verifique os logs
tail -f logs/app.log

# Teste a API manualmente
php -r "
require 'vendor/autoload.php';
use App\Services\TokenService;
use App\Services\DynamicsApiService;
\$ts = new TokenService();
\$api = new DynamicsApiService(\$ts);
\$emails = \$api->fetchEmails(['Test'], '2025-01-01');
print_r(\$emails);
"
```

## 📊 Rollback Plan

Se algo der errado, faça rollback:

```bash
# 1. Pare o novo sistema
sudo systemctl stop apache2

# 2. Restaure o backup
rm -rf /var/www/dynamics-email-report
cp -r /var/www/dynamics-email-report.backup /var/www/dynamics-email-report

# 3. Restaure a configuração do servidor
sudo cp /etc/apache2/sites-available/old-dynamics.conf.backup \
        /etc/apache2/sites-available/dynamics-report.conf

# 4. Reinicie
sudo systemctl start apache2

# 5. Verifique
curl http://seu-dominio.com
```

## ✅ Validação Pós-Migração

Execute esta checklist após a migração:

- [ ] Site acessível sem erros
- [ ] Formulário de busca funciona
- [ ] Busca por assunto único retorna resultados
- [ ] Busca por múltiplos assuntos retorna resultados agrupados
- [ ] Exportação CSV funciona
- [ ] Métricas calculadas corretamente
- [ ] Rate limiting ativo
- [ ] Logs sendo gerados em `logs/app.log`
- [ ] Cache de token funcionando (verificar velocidade)
- [ ] SSL ativo (se configurado)
- [ ] Headers de segurança presentes
- [ ] Performance igual ou melhor que sistema antigo

## 📞 Suporte

Se encontrar problemas durante a migração:

1. Verifique os logs: `tail -f logs/app.log`
2. Execute o modo debug: `APP_DEBUG=true` no `.env`
3. Consulte o README.md
4. Abra uma issue com logs completos

## 🎉 Próximos Passos

Após migração bem-sucedida:

1. Configure monitoramento de logs
2. Configure backup automático
3. Configure Redis para cache (produção)
4. Implemente SSL com Let's Encrypt
5. Configure rate limiting customizado
6. Execute testes de carga
7. Documente procedimentos internos

---

**Boa sorte na migração! 🚀**