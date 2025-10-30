#!/bin/bash

# Script de instalação automatizada
# Sistema de Relatório de Engajamento - Dynamics 365

echo "=================================="
echo "  Instalação do Sistema"
echo "=================================="
echo ""

# Verifica PHP
echo "[1/6] Verificando PHP..."
if ! command -v php &> /dev/null; then
    echo "❌ PHP não encontrado. Instale PHP >= 7.4"
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "✅ PHP $PHP_VERSION encontrado"

# Verifica Composer
echo "[2/6] Verificando Composer..."
if ! command -v composer &> /dev/null; then
    echo "❌ Composer não encontrado. Instalando..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    php -r "unlink('composer-setup.php');"
fi
echo "✅ Composer disponível"

# Instala dependências
echo "[3/6] Instalando dependências..."
composer install --no-interaction --prefer-dist --optimize-autoloader
if [ $? -eq 0 ]; then
    echo "✅ Dependências instaladas"
else
    echo "❌ Erro ao instalar dependências"
    exit 1
fi

# Cria diretórios
echo "[4/6] Criando estrutura de diretórios..."
mkdir -p logs storage/cache storage/sessions
chmod 755 logs storage/cache storage/sessions
echo "✅ Diretórios criados"

# Configura .env
echo "[5/6] Configurando ambiente..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✅ Arquivo .env criado. Configure suas credenciais!"
    echo ""
    echo "⚠️  IMPORTANTE: Edite o arquivo .env com suas credenciais do Dynamics 365"
else
    echo "ℹ️  Arquivo .env já existe"
fi

# Verifica extensões PHP
echo "[6/6] Verificando extensões PHP..."
REQUIRED_EXTENSIONS=("mbstring" "curl" "json" "openssl")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -i "^$ext$" > /dev/null; then
        MISSING_EXTENSIONS+=($ext)
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -eq 0 ]; then
    echo "✅ Todas as extensões necessárias estão instaladas"
else
    echo "⚠️  Extensões faltando: ${MISSING_EXTENSIONS[*]}"
    echo "   Instale com: sudo apt-get install php-${MISSING_EXTENSIONS[0]}"
fi

echo ""
echo "=================================="
echo "  Instalação Concluída!"
echo "=================================="
echo ""
echo "Próximos passos:"
echo "1. Configure o arquivo .env com suas credenciais"
echo "2. Configure o Apache/Nginx para apontar para a pasta public/"
echo "3. Acesse a aplicação no navegador"
echo ""
echo "Documentação completa: README.md"
echo ""