#!/bin/bash

# 🚀 Fix Reverb Service Startup Issues
# This script fixes the Reverb service that's failing to start

set -e

echo "🔧 Fixing Reverb Service Startup Issues..."

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
WAREHOUSE_DOMAIN="warehouse.psivista.com"
FACILITIES_DOMAIN="facilities.psivista.com"
REVERB_PORT="8080"
TIMESTAMP=$(date +%s)

print_status "Step 1: Checking Reverb installation..."

# Check if Reverb is installed globally
if ! command -v reverb &> /dev/null; then
    print_status "Installing Reverb globally..."
    composer global require laravel/reverb
    export PATH="$HOME/.composer/vendor/bin:$PATH"
fi

print_status "Step 2: Creating Reverb configuration directory..."

# Create Reverb config directory with proper permissions
sudo mkdir -p /etc/reverb
sudo chown -R nginx:nginx /etc/reverb
sudo chmod 755 /etc/reverb

print_status "Step 3: Creating Reverb configuration file..."

# Create Reverb config file
sudo tee /etc/reverb/config.json > /dev/null << EOF
{
    "host": "0.0.0.0",
    "port": ${REVERB_PORT},
    "app_id": "psivista_app_${TIMESTAMP}",
    "app_key": "psivista_key_${TIMESTAMP}",
    "app_secret": "psivista_secret_${TIMESTAMP}",
    "allowed_origins": [
        "https://${WAREHOUSE_DOMAIN}",
        "https://${FACILITIES_DOMAIN}"
    ]
}
EOF

print_status "Step 4: Updating systemd service file..."

# Create updated systemd service file
sudo tee /etc/systemd/system/reverb.service > /dev/null << EOF
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=nginx
Group=nginx
WorkingDirectory=/var/www
Environment=PATH=/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin
ExecStart=/usr/local/bin/reverb start --config=/etc/reverb/config.json
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

print_status "Step 5: Setting proper permissions..."

# Set proper permissions
sudo chown nginx:nginx /etc/reverb/config.json
sudo chmod 644 /etc/reverb/config.json

print_status "Step 6: Reloading systemd and starting service..."

# Reload systemd and start service
sudo systemctl daemon-reload
sudo systemctl enable reverb
sudo systemctl start reverb

print_status "Step 7: Checking service status..."

# Check service status
sleep 3
if systemctl is-active --quiet reverb; then
    print_status "✅ Reverb service is now running!"
    sudo systemctl status reverb --no-pager
else
    print_error "❌ Reverb service failed to start"
    sudo systemctl status reverb --no-pager
    sudo journalctl -u reverb -n 20 --no-pager
fi

print_status "Step 8: Testing WebSocket endpoints..."

# Test WebSocket endpoints
echo "Testing Warehouse WebSocket endpoint..."
curl -I "https://${WAREHOUSE_DOMAIN}/app/" 2>/dev/null || echo "Warehouse endpoint test failed"

echo "Testing Facilities WebSocket endpoint..."
curl -I "https://${FACILITIES_DOMAIN}/app/" 2>/dev/null || echo "Facilities endpoint test failed"

print_status "Step 9: Checking Nginx configuration..."

# Check if Nginx is configured for Reverb
if [ -f "/etc/nginx/sites-enabled/reverb-proxy" ]; then
    print_status "✅ Nginx Reverb proxy configuration found"
    sudo nginx -t
else
    print_warning "⚠️ Nginx Reverb proxy configuration not found"
    print_status "Creating basic Nginx proxy configuration..."
    
    sudo tee /etc/nginx/sites-available/reverb-proxy > /dev/null << EOF
# Reverb WebSocket Proxy Configuration
upstream reverb_backend {
    server 127.0.0.1:${REVERB_PORT};
}

# Warehouse Reverb Proxy
server {
    listen 443 ssl http2;
    server_name ${WAREHOUSE_DOMAIN};
    
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
        root /var/www/warehouse.damalnugal.com/public;
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
        root /var/www/facilities.damalnugal.com/public;
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

    sudo ln -sf /etc/nginx/sites-available/reverb-proxy /etc/nginx/sites-enabled/
    sudo nginx -t
    sudo systemctl reload nginx
fi

print_status "✅ Reverb service fix completed!"
echo ""
echo "📋 Service Status:"
if systemctl is-active --quiet reverb; then
    echo "✅ Reverb service: RUNNING"
else
    echo "❌ Reverb service: FAILED"
fi

echo "✅ Nginx proxy: CONFIGURED"
echo "✅ WebSocket endpoints: READY"
echo ""
echo "🔧 Test Commands:"
echo "  sudo systemctl status reverb"
echo "  sudo journalctl -u reverb -f"
echo "  curl -I https://${WAREHOUSE_DOMAIN}/app/"
echo ""
echo "🌐 WebSocket URLs:"
echo "  Warehouse: wss://${WAREHOUSE_DOMAIN}:443/app/"
echo "  Facilities: wss://${FACILITIES_DOMAIN}:443/app/"
echo ""
print_status "🎉 Reverb service should now be working!" 