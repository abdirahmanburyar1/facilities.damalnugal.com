#!/bin/bash

# 🚀 Production Reverb Deployment Script
# This script deploys Reverb for production with SSL support

set -e

echo "🚀 Deploying Reverb for Production..."

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Configuration
WAREHOUSE_DIR="/var/www/warehouse.damalnugal.com"
FACILITIES_DIR="/var/www/facilities.damalnugal.com"
WAREHOUSE_DOMAIN="warehouse.damalnugal.com"
FACILITIES_DOMAIN="facilities.damalnugal.com"
REVERB_PORT="8080"  # Internal port for Reverb server
TIMESTAMP=$(date +%s)

print_status "Step 1: Installing Reverb..."
# Install Reverb globally
composer global require laravel/reverb

print_status "Step 2: Creating Reverb configuration..."
# Create Reverb config directory
sudo mkdir -p /etc/reverb
sudo chown -R $USER:$USER /etc/reverb

# Create Reverb config file
cat > /etc/reverb/config.json << EOF
{
    "host": "0.0.0.0",
    "port": ${REVERB_PORT},
    "app_id": "production_app_${TIMESTAMP}",
    "app_key": "production_key_${TIMESTAMP}",
    "app_secret": "production_secret_${TIMESTAMP}",
    "allowed_origins": [
        "https://${WAREHOUSE_DOMAIN}",
        "https://${FACILITIES_DOMAIN}"
    ],
    "ssl": {
        "local_cert": "/etc/letsencrypt/live/${WAREHOUSE_DOMAIN}/fullchain.pem",
        "local_pk": "/etc/letsencrypt/live/${WAREHOUSE_DOMAIN}/privkey.pem",
        "passphrase": ""
    }
}
EOF

print_status "Step 3: Creating Reverb systemd service..."
# Create systemd service file
sudo tee /etc/systemd/system/reverb.service > /dev/null << EOF
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www
ExecStart=/usr/local/bin/reverb start --config=/etc/reverb/config.json
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

print_status "Step 4: Updating Warehouse .env..."
# Update warehouse .env
cat >> "${WAREHOUSE_DIR}/.env" << EOF

# Reverb Broadcasting Configuration
BROADCAST_DRIVER=reverb
REVERB_APP_ID=production_app_${TIMESTAMP}
REVERB_APP_KEY=production_key_${TIMESTAMP}
REVERB_APP_SECRET=production_secret_${TIMESTAMP}
REVERB_HOST=0.0.0.0
REVERB_PORT=${REVERB_PORT}
REVERB_SCHEME=https
REVERB_PATH=
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=${REVERB_PORT}
REVERB_SERVER_PATH=
VITE_REVERB_APP_KEY=production_key_${TIMESTAMP}
VITE_REVERB_HOST=${WAREHOUSE_DOMAIN}
VITE_REVERB_PORT=443
EOF

print_status "Step 5: Updating Facilities .env..."
# Update facilities .env
cat >> "${FACILITIES_DIR}/.env" << EOF

# Reverb Broadcasting Configuration
BROADCAST_DRIVER=reverb
REVERB_APP_ID=production_app_${TIMESTAMP}
REVERB_APP_KEY=production_key_${TIMESTAMP}
REVERB_APP_SECRET=production_secret_${TIMESTAMP}
REVERB_HOST=0.0.0.0
REVERB_PORT=${REVERB_PORT}
REVERB_SCHEME=https
REVERB_PATH=
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=${REVERB_PORT}
REVERB_SERVER_PATH=
VITE_REVERB_APP_KEY=production_key_${TIMESTAMP}
VITE_REVERB_HOST=${FACILITIES_DOMAIN}
VITE_REVERB_PORT=443
EOF

print_status "Step 6: Creating Nginx proxy configuration..."
# Create Nginx config for Reverb proxy
sudo tee /etc/nginx/sites-available/reverb-proxy << EOF
# Reverb WebSocket Proxy Configuration
upstream reverb_backend {
    server 127.0.0.1:${REVERB_PORT};
}

# Warehouse Reverb Proxy
server {
    listen 443 ssl http2;
    server_name ${WAREHOUSE_DOMAIN};
    
    ssl_certificate /etc/letsencrypt/live/${WAREHOUSE_DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${WAREHOUSE_DOMAIN}/privkey.pem;
    
    # WebSocket proxy for Reverb
    location /app/ {
        proxy_pass http://reverb_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
        proxy_read_timeout 86400;
    }
    
    # Broadcasting auth
    location /broadcasting/auth {
        proxy_pass http://reverb_backend;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
    
    # Main application
    location / {
        root ${WAREHOUSE_DIR}/public;
        try_files \$uri \$uri/ /index.php?\$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
            include fastcgi_params;
        }
    }
}

# Facilities Reverb Proxy
server {
    listen 443 ssl http2;
    server_name ${FACILITIES_DOMAIN};
    
    ssl_certificate /etc/letsencrypt/live/${FACILITIES_DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${FACILITIES_DOMAIN}/privkey.pem;
    
    # WebSocket proxy for Reverb
    location /app/ {
        proxy_pass http://reverb_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
        proxy_read_timeout 86400;
    }
    
    # Broadcasting auth
    location /broadcasting/auth {
        proxy_pass http://reverb_backend;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
    
    # Main application
    location / {
        root ${FACILITIES_DIR}/public;
        try_files \$uri \$uri/ /index.php?\$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
            include fastcgi_params;
        }
    }
}
EOF

print_status "Step 7: Enabling Nginx configuration..."
# Enable the new Nginx config
sudo ln -sf /etc/nginx/sites-available/reverb-proxy /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

print_status "Step 8: Starting Reverb service..."
# Start and enable Reverb service
sudo systemctl daemon-reload
sudo systemctl enable reverb
sudo systemctl start reverb

print_status "Step 9: Clearing Laravel caches..."
# Clear caches for both applications
cd "${WAREHOUSE_DIR}"
php artisan config:clear
php artisan cache:clear

cd "${FACILITIES_DIR}"
php artisan config:clear
php artisan cache:clear

print_status "Step 10: Testing Nginx configuration..."
# Test Nginx config
sudo nginx -t

print_status "Step 11: Restarting Nginx..."
# Restart Nginx
sudo systemctl restart nginx

print_status "Step 12: Checking Reverb status..."
# Check Reverb status
sudo systemctl status reverb --no-pager

print_status "✅ Production Reverb deployment completed!"
echo ""
echo "📋 Configuration Summary:"
echo "✅ Reverb server running on port ${REVERB_PORT}"
echo "✅ SSL certificates configured"
echo "✅ Nginx proxy configured for both domains"
echo "✅ WebSocket connections via WSS (port 443)"
echo "✅ Both apps configured with same Reverb credentials"
echo ""
echo "🔧 Test Commands:"
echo "  sudo systemctl status reverb"
echo "  sudo journalctl -u reverb -f"
echo "  curl -I https://${WAREHOUSE_DOMAIN}/app/"
echo ""
echo "🌐 Access URLs:"
echo "  Warehouse: https://${WAREHOUSE_DOMAIN}"
echo "  Facilities: https://${FACILITIES_DOMAIN}"
echo ""
print_status "🎉 Reverb is now running in production with SSL!" 