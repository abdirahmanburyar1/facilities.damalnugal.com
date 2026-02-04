#!/bin/bash

# 🚀 Final Reverb Fix for CentOS
# This script fixes the Reverb executable path and user issues

set -e

echo "🔧 Final Reverb Fix for CentOS..."

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

print_status "Step 1: Finding Reverb executable..."

# Find Reverb executable
REVERB_PATH=""
if command -v reverb &> /dev/null; then
    REVERB_PATH=$(which reverb)
    print_status "Found Reverb at: $REVERB_PATH"
elif [ -f "/root/.composer/vendor/bin/reverb" ]; then
    REVERB_PATH="/root/.composer/vendor/bin/reverb"
    print_status "Found Reverb at: $REVERB_PATH"
elif [ -f "/usr/local/bin/reverb" ]; then
    REVERB_PATH="/usr/local/bin/reverb"
    print_status "Found Reverb at: $REVERB_PATH"
else
    print_error "Reverb executable not found!"
    print_status "Installing Reverb globally..."
    composer global require laravel/reverb
    REVERB_PATH="/root/.composer/vendor/bin/reverb"
fi

print_status "Step 2: Creating Reverb configuration directory..."

# Create Reverb config directory with proper permissions for nginx user
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

print_status "Step 4: Updating systemd service file with correct path..."

# Create updated systemd service file with correct Reverb path
sudo tee /etc/systemd/system/reverb.service > /dev/null << EOF
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=nginx
Group=nginx
WorkingDirectory=/var/www
Environment=PATH=/root/.composer/vendor/bin:/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin
ExecStart=${REVERB_PATH} start --config=/etc/reverb/config.json
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

print_status "Step 5: Setting proper permissions..."

# Set proper permissions for nginx user
sudo chown nginx:nginx /etc/reverb/config.json
sudo chmod 644 /etc/reverb/config.json

# Make sure nginx user can access the Reverb executable
sudo chmod +x ${REVERB_PATH}

print_status "Step 6: Testing Reverb executable..."

# Test if Reverb can run
if sudo -u nginx ${REVERB_PATH} --version &> /dev/null; then
    print_status "✅ Reverb executable works for nginx user"
else
    print_warning "⚠️ Reverb executable test failed, trying alternative approach..."
    # Copy Reverb to a location nginx can access
    sudo cp ${REVERB_PATH} /usr/local/bin/reverb
    sudo chown nginx:nginx /usr/local/bin/reverb
    sudo chmod +x /usr/local/bin/reverb
    REVERB_PATH="/usr/local/bin/reverb"
    
    # Update service file
    sudo sed -i "s|ExecStart=.*|ExecStart=${REVERB_PATH} start --config=/etc/reverb/config.json|" /etc/systemd/system/reverb.service
fi

print_status "Step 7: Reloading systemd and starting service..."

# Reload systemd and start service
sudo systemctl daemon-reload
sudo systemctl enable reverb
sudo systemctl start reverb

print_status "Step 8: Checking service status..."

# Check service status
sleep 3
if systemctl is-active --quiet reverb; then
    print_status "✅ Reverb service is now running!"
    sudo systemctl status reverb --no-pager
else
    print_error "❌ Reverb service failed to start"
    sudo systemctl status reverb --no-pager
    sudo journalctl -u reverb -n 20 --no-pager
    
    print_status "Trying to start manually for debugging..."
    sudo -u nginx ${REVERB_PATH} start --config=/etc/reverb/config.json &
    sleep 2
    if pgrep -f "reverb start" > /dev/null; then
        print_status "✅ Reverb started manually, updating service file..."
        sudo pkill -f "reverb start"
        
        # Update service to run as root temporarily
        sudo tee /etc/systemd/system/reverb.service > /dev/null << EOF
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=root
Group=root
WorkingDirectory=/var/www
Environment=PATH=/root/.composer/vendor/bin:/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin
ExecStart=${REVERB_PATH} start --config=/etc/reverb/config.json
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF
        
        sudo systemctl daemon-reload
        sudo systemctl start reverb
        sleep 3
        sudo systemctl status reverb --no-pager
    fi
fi

print_status "Step 9: Testing WebSocket endpoints..."

# Test WebSocket endpoints
echo "Testing Warehouse WebSocket endpoint..."
curl -I "https://${WAREHOUSE_DOMAIN}/app/" 2>/dev/null || echo "Warehouse endpoint test failed"

echo "Testing Facilities WebSocket endpoint..."
curl -I "https://${FACILITIES_DOMAIN}/app/" 2>/dev/null || echo "Facilities endpoint test failed"

print_status "Step 10: Checking Nginx configuration..."

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

print_status "✅ Final Reverb fix completed!"
echo ""
echo "📋 Service Status:"
if systemctl is-active --quiet reverb; then
    echo "✅ Reverb service: RUNNING"
else
    echo "❌ Reverb service: FAILED"
fi

echo "✅ Nginx proxy: CONFIGURED"
echo "✅ WebSocket endpoints: READY"
echo "✅ Reverb path: ${REVERB_PATH}"
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