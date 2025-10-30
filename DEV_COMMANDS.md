# 🛠️ Comandos Úteis para Desenvolvimento

Referência rápida de comandos para trabalhar no projeto.

---

## 🚀 Instalação e Setup

```bash
# Instalação completa automatizada
./install.sh

# Instalação manual passo a passo
composer install
mkdir -p logs storage/cache storage/sessions
chmod 755 logs storage/cache storage/sessions
cp .env.example .env
```

---

## 🧪 Testes

```bash
# Executar todos os testes
./vendor/bin/phpunit

# Executar testes específicos
./vendor/bin/phpunit tests/Unit/EmailReportTest.php

# Executar com cobertura
./vendor/bin/phpunit --coverage-html coverage/

# Executar apenas um teste
./vendor/bin/phpunit --filter testCalculateReportMetrics

# Modo verbose
./vendor/bin/phpunit --verbose
```

---

## 📦 Composer

```bash
# Instalar dependências
composer install

# Instalar dependências (produção)
composer install --no-dev --optimize-autoloader

# Atualizar dependências
composer update

# Adicionar nova dependência
composer require vendor/package

# Remover dependência
composer remove vendor/package

# Validar composer.json
composer validate

# Verificar atualizações disponíveis
composer outdated

# Dump autoload (após adicionar classes)
composer dump-autoload

# Otimizar autoload (produção)
composer dump-autoload --optimize --classmap-authoritative
```

---

## 🔍 Debug e Logs

```bash
# Assistir logs em tempo real
tail -f logs/app.log

# Últimas 100 linhas do log
tail -n 100 logs/app.log

# Buscar erros nos logs
grep -i "error" logs/app.log

# Buscar warnings
grep -i "warning" logs/app.log

# Logs de hoje
grep "$(date +%Y-%m-%d)" logs/app.log

# Limpar logs
> logs/app.log

# Logs do Apache
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log

# Logs do Nginx
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
```

---

## 🗄️ Cache

```bash
# Limpar todo o cache
rm -rf storage/cache/*

# Limpar cache de token específico
php -r "
require 'vendor/autoload.php';
use App\Services\TokenService;
\$ts = new TokenService();
\$ts->invalidateToken();
echo 'Cache limpo!';
"

# Ver conteúdo do cache (filesystem)
ls -lah storage/cache/

# Verificar se Redis está funcionando
redis-cli ping

# Limpar todo cache do Redis
redis-cli FLUSHDB

# Ver chaves no Redis
redis-cli KEYS "dynamics_*"

# Ver valor de uma chave
redis-cli GET "dynamics_oauth_token"
```

---

## 🔐 Segurança

```bash
# Verificar permissões
ls -lah logs/ storage/

# Corrigir permissões
chmod 755 logs storage/cache storage/sessions
chown -R www-data:www-data logs storage

# Verificar arquivo .env
cat .env | grep -v "^#" | grep -v "^$"

# Validar credenciais
php -r "
require 'vendor/autoload.php';
use App\Services\TokenService;
\$ts = new TokenService();
\$token = \$ts->getAccessToken();
echo \$token ? 'Credenciais OK' : 'Credenciais INVÁLIDAS';
"

# Testar rate limiting
for i in {1..105}; do 
    curl -s http://localhost/ > /dev/null
    echo "Request \$i"
done
```

---

## 🌐 Servidor Web

### Apache

```bash
# Reiniciar Apache
sudo systemctl restart apache2

# Ver status
sudo systemctl status apache2

# Testar configuração
sudo apache2ctl configtest

# Habilitar site
sudo a2ensite dynamics-report

# Desabilitar site
sudo a2dissite dynamics-report

# Habilitar mod_rewrite
sudo a2enmod rewrite

# Ver logs de erro
sudo tail -f /var/log/apache2/error.log

# Ver configuração ativa
apache2ctl -S
```

### Nginx

```bash
# Reiniciar Nginx
sudo systemctl restart nginx

# Ver status
sudo systemctl status nginx

# Testar configuração
sudo nginx -t

# Recarregar configuração (sem downtime)
sudo systemctl reload nginx

# Ver logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log
```

### PHP-FPM

```bash
# Reiniciar PHP-FPM
sudo systemctl restart php8.1-fpm

# Ver status
sudo systemctl status php8.1-fpm

# Ver configuração
php --ini

# Ver extensões instaladas
php -m

# Verificar versão
php -v

# Executar script PHP
php script.php

# Servidor built-in (desenvolvimento)
php -S localhost:8000 -t public/
```

---

## 🔧 Desenvolvimento

```bash
# Assistir mudanças e reiniciar (usando entr)
ls **/*.php | entr -r php public/index.php

# Formatar código (PHP CS Fixer)
vendor/bin/php-cs-fixer fix src/

# Análise estática (PHPStan)
vendor/bin/phpstan analyse src/

# Verificar code smell (PHP Mess Detector)
vendor/bin/phpmd src/ text cleancode,codesize,controversial,design,naming,unusedcode

# Git: Ver alterações
git status
git diff

# Git: Commit
git add .
git commit -m "feat: adiciona nova funcionalidade"
git push origin main

# Git: Ver histórico
git log --oneline --graph --all

# Git: Criar branch
git checkout -b feature/nova-funcionalidade
```

---

## 📊 Monitoramento

```bash
# Ver uso de disco
du -sh storage/* logs/*

# Ver tamanho do cache
du -sh storage/cache/

# Ver tamanho dos logs
du -sh logs/

# Limpar logs antigos (> 30 dias)
find logs/ -name "*.log" -mtime +30 -delete

# Ver processos PHP
ps aux | grep php

# Ver uso de memória
free -h

# Ver uso de CPU
top -u www-data

# Ver conexões ativas
netstat -an | grep :80 | wc -l

# Verificar se porta está aberta
netstat -tulpn | grep :80
```

---

## 🐛 Troubleshooting

```bash
# Verificar se todas as dependências estão instaladas
composer check-platform-reqs

# Verificar extensões PHP necessárias
php -m | grep -E "(mbstring|curl|json|openssl)"

# Testar conexão com API
curl -I https://sua-instancia.crm.dynamics.com

# Testar endpoint local
curl -v http://localhost/

# Ver headers HTTP
curl -I http://localhost/

# Testar com dados POST
curl -X POST http://localhost/ \
  -d "assunto=Test" \
  -d "data_inicio=2025-01-01"

# Debug de DNS
nslookup sua-instancia.crm.dynamics.com
dig sua-instancia.crm.dynamics.com

# Testar SSL
openssl s_client -connect sua-instancia.crm.dynamics.com:443
```

---

## 📦 Deploy

```bash
# Deploy em produção (exemplo)
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan cache:clear  # se usar Laravel
chmod 755 logs storage/cache storage/sessions
sudo systemctl restart apache2

# Backup antes do deploy
tar -czf backup-$(date +%Y%m%d-%H%M%S).tar.gz \
    --exclude='vendor' \
    --exclude='storage/cache' \
    --exclude='logs' \
    .

# Restaurar backup
tar -xzf backup-20251013-143000.tar.gz

# Deploy com zero downtime (usando symlinks)
ln -sfn /path/to/new/release /var/www/current
sudo systemctl reload apache2
```

---

## 🧹 Manutenção

```bash
# Rotação de logs (logrotate)
sudo logrotate -f /etc/logrotate.d/dynamics-report

# Limpeza automática de cache antigo
find storage/cache/ -type f -mtime +7 -delete

# Ver espaço em disco
df -h

# Otimizar banco de dados (se usar MySQL)
mysqlcheck -o --all-databases -u root -p

# Verificar integridade dos arquivos
find . -type f -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"
```

---

## 🔄 Utilitários Customizados

### Criar script de teste rápido
```bash
# test.php
<?php
require 'vendor/autoload.php';

use App\Bootstrap;
use App\Services\TokenService;
use App\Services\DynamicsApiService;

$bootstrap = Bootstrap::getInstance();
$tokenService = new TokenService();
$apiService = new DynamicsApiService($tokenService);

$emails = $apiService->fetchEmails(['Test'], '2025-01-01');
print_r($emails);
```

### Executar
```bash
php test.php
```

---

## 📋 Checklist Diário

```bash
# Manhã
✓ git pull origin main
✓ composer install
✓ tail -f logs/app.log &

# Durante desenvolvimento
✓ git status (frequentemente)
✓ composer dump-autoload (após adicionar classes)
✓ ./vendor/bin/phpunit (antes de commits)

# Antes de commit
✓ ./vendor/bin/phpunit
✓ git diff (revisar mudanças)
✓ git add . && git commit -m "mensagem clara"

# Fim do dia
✓ git push origin main
✓ Verificar logs/app.log
✓ Limpar cache se necessário
```

---

## 🎓 Dicas Pro

```bash
# Alias úteis (adicione ao ~/.bashrc)
alias logs='tail -f logs/app.log'
alias test='./vendor/bin/phpunit'
alias cache-clear='rm -rf storage/cache/*'
alias apache-restart='sudo systemctl restart apache2'

# Função para deploy rápido
deploy() {
    git pull origin main &&
    composer install --no-dev --optimize-autoloader &&
    chmod 755 logs storage/cache storage/sessions &&
    sudo systemctl restart apache2 &&
    echo "Deploy concluído!"
}

# Usar
deploy
```

---

**Salve este arquivo e use como referência rápida! 📌**