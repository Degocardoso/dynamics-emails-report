# 🚀 Quick Start Guide

Guia rápido para ter o sistema funcionando em **5 minutos**.

## ⚡ Instalação Rápida

```bash
# 1. Clone/Download do projeto
cd /var/www/
git clone <seu-repo> dynamics-email-report
cd dynamics-email-report

# 2. Execute o instalador
chmod +x install.sh
./install.sh

# 3. Configure as credenciais
nano .env
```

Cole suas credenciais no `.env`:
```env
TENANT_ID=seu-tenant-id-aqui
CLIENT_ID=seu-client-id-aqui
CLIENT_SECRET=seu-client-secret-aqui
RESOURCE=https://sua-instancia.crm.dynamics.com
```

```bash
# 4. Configure o Apache
sudo nano /etc/apache2/sites-available/dynamics-report.conf
```

Cole esta configuração:
```apache
<VirtualHost *:80>
    ServerName seu-dominio.com
    DocumentRoot /var/www/dynamics-email-report/public
    
    <Directory /var/www/dynamics-email-report/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/dynamics-error.log
    CustomLog ${APACHE_LOG_DIR}/dynamics-access.log combined
</VirtualHost>
```

```bash
# 5. Ative e reinicie
sudo a2ensite dynamics-report
sudo a2enmod rewrite
sudo systemctl restart apache2

# 6. Teste
curl http://seu-dominio.com
```

## ✅ Pronto!

Acesse `http://seu-dominio.com` no navegador e teste:

1. **Assunto:** Qualquer assunto de e-mail
2. **Data:** 2025-01-01
3. **Clique em:** Gerar Relatório

---

## 🔧 Troubleshooting de 1 Minuto

### Erro 500
```bash
# Verifique permissões
sudo chown -R www-data:www-data /var/www/dynamics-email-report
chmod 755 logs storage/cache storage/sessions

# Verifique logs
tail -f logs/app.log
tail -f /var/log/apache2/error.log
```

### "Falha na autenticação"
```bash
# Teste as credenciais
php -r "
require 'vendor/autoload.php';
use App\Services\TokenService;
\$ts = new TokenService();
\$token = \$ts->getAccessToken();
echo \$token ? 'Token OK' : 'Token FAILED';
"
```

### CSS não carrega
```bash
# Ative o mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 📖 Documentação Completa

- **README.md** - Documentação completa
- **MIGRATION_GUIDE.md** - Migração do sistema antigo
- **CHANGES.md** - Todas as mudanças detalhadas

---

## 💡 Dicas Rápidas

### Ativar Debug
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Limpar Cache
```bash
rm -rf storage/cache/*
```

### Ver Logs em Tempo Real
```bash
tail -f logs/app.log
```

### Testar Rate Limiting
```bash
# Execute 101 requisições
for i in {1..101}; do curl http://seu-dominio.com; done
```

---

**Está funcionando? Parabéns! 🎉**

Se precisar de mais ajuda, consulte o README.md completo.