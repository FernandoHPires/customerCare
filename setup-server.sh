#!/bin/bash

# ==============================================================================
# Script de instalação do servidor — UNI Gestão de Negócios
# Ubuntu 22.04 LTS + Apache + PHP 8.3 + MySQL 8.0
#
# Uso:
#   chmod +x setup-server.sh
#   sudo ./setup-server.sh
# ==============================================================================

set -e  # Para o script se qualquer comando falhar

# ── Cores para output ─────────────────────────────────────────────────────────
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ok()   { echo -e "${GREEN}[OK]${NC} $1"; }
info() { echo -e "${YELLOW}[..] $1${NC}"; }
fail() { echo -e "${RED}[ERRO] $1${NC}"; exit 1; }

# ── Verificar se está rodando como root ───────────────────────────────────────
if [ "$EUID" -ne 0 ]; then
    fail "Execute como root: sudo ./setup-server.sh"
fi

# ── Solicitar configurações ───────────────────────────────────────────────────
echo ""
echo "=================================================="
echo "   UNI Gestão de Negócios — Setup do Servidor"
echo "=================================================="
echo ""

read -p "Domínio do site (ex: seudominio.com.br): " DOMAIN
read -p "Diretório do projeto (ex: /var/www/customercare): " APP_DIR
read -p "Nome do banco de dados: " DB_NAME
read -p "Usuário do banco de dados: " DB_USER
read -s -p "Senha do banco de dados: " DB_PASS
echo ""
read -s -p "Senha do root do MySQL: " DB_ROOT_PASS
echo ""

echo ""
info "Iniciando instalação..."
echo ""

# ── 1. Atualizar sistema ──────────────────────────────────────────────────────
info "Atualizando sistema..."
apt-get update -qq && apt-get upgrade -y -qq
ok "Sistema atualizado"

# ── 2. Apache ─────────────────────────────────────────────────────────────────
info "Instalando Apache..."
apt-get install -y -qq apache2
a2enmod rewrite
a2enmod ssl
a2enmod headers
systemctl enable apache2
systemctl start apache2
ok "Apache instalado"

# ── 3. PHP 8.3 ────────────────────────────────────────────────────────────────
info "Instalando PHP 8.3..."
apt-get install -y -qq software-properties-common
add-apt-repository ppa:ondrej/php -y
apt-get update -qq
apt-get install -y -qq \
    php8.3 \
    php8.3-fpm \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-gd \
    php8.3-intl \
    libapache2-mod-php8.3
ok "PHP 8.3 instalado"

# ── 4. MySQL 8.0 ──────────────────────────────────────────────────────────────
info "Instalando MySQL 8.0..."
apt-get install -y -qq mysql-server
systemctl enable mysql
systemctl start mysql

# Criar banco e usuário
mysql -u root <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASS}';
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
ok "MySQL instalado e banco criado"

# ── 5. Composer ───────────────────────────────────────────────────────────────
info "Instalando Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ok "Composer instalado"

# ── 6. Node.js 20 LTS ─────────────────────────────────────────────────────────
info "Instalando Node.js 20 LTS..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y -qq nodejs
ok "Node.js $(node -v) instalado"

# ── 7. SSL — Let's Encrypt ────────────────────────────────────────────────────
info "Instalando Certbot (SSL)..."
apt-get install -y -qq certbot python3-certbot-apache
ok "Certbot instalado"

# ── 8. Clonar / copiar projeto ────────────────────────────────────────────────
info "Configurando diretório do projeto..."
mkdir -p "$APP_DIR"
chown -R www-data:www-data "$APP_DIR"
ok "Diretório $APP_DIR criado"

# ── 9. VirtualHost Apache ─────────────────────────────────────────────────────
info "Configurando VirtualHost Apache..."
cat > /etc/apache2/sites-available/${DOMAIN}.conf <<EOF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}-access.log combined
</VirtualHost>
EOF

a2ensite ${DOMAIN}.conf
a2dissite 000-default.conf
systemctl reload apache2
ok "VirtualHost configurado"

# ── 10. Permissões ────────────────────────────────────────────────────────────
info "Configurando permissões..."
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type f -exec chmod 644 {} \;
find "$APP_DIR" -type d -exec chmod 755 {} \;
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"
ok "Permissões configuradas"

# ── 11. Arquivo .env de produção ──────────────────────────────────────────────
info "Criando .env de produção..."
cat > "$APP_DIR/.env" <<EOF
APP_NAME="UNI Gestão de Negócios"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${DOMAIN}

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=60
SESSION_SECURE_COOKIE=true
EOF
ok ".env criado — complete as chaves restantes (APP_KEY, TURNSTILE, etc.)"

# ── 12. Resumo final ──────────────────────────────────────────────────────────
echo ""
echo "=================================================="
echo -e "${GREEN}   Instalação concluída!${NC}"
echo "=================================================="
echo ""
echo "Próximos passos manuais:"
echo ""
echo "  1. Copie o projeto para: $APP_DIR"
echo "  2. Dentro do projeto, rode:"
echo ""
echo "       composer install --no-dev"
echo "       npm install && npm run build"
echo "       php artisan key:generate"
echo "       php artisan migrate --force"
echo "       php artisan optimize"
echo ""
echo "  3. Ative o SSL:"
echo ""
echo "       certbot --apache -d ${DOMAIN}"
echo ""
echo "  4. Complete o .env em: $APP_DIR/.env"
echo "     (APP_KEY, TURNSTILE_SITE_KEY, TURNSTILE_SECRET_KEY, MAIL_*)"
echo ""
echo "=================================================="
