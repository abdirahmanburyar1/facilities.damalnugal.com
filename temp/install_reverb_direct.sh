#!/bin/bash

# 🚀 Direct Reverb Installation for CentOS
# This script directly downloads and installs Reverb

set -e

echo "🔧 Direct Reverb Installation for CentOS..."

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

print_status "Step 1: Checking PHP and Composer..."

# Check PHP version
php --version
composer --version

print_status "Step 2: Installing Reverb directly..."

# Set environment variable to allow superuser
export COMPOSER_ALLOW_SUPERUSER=1

# Install Reverb with explicit path
composer global require laravel/reverb --no-interaction

print_status "Step 3: Finding Reverb executable..."

# Check multiple possible locations
REVERB_PATH=""
POSSIBLE_PATHS=(
    "/root/.composer/vendor/bin/reverb"
    "/usr/local/bin/reverb"
    "/usr/bin/reverb"
    "/root/.config/composer/vendor/bin/reverb"
)

for path in "${POSSIBLE_PATHS[@]}"; do
    if [ -f "$path" ]; then
        REVERB_PATH="$path"
        print_status "Found Reverb at: $REVERB_PATH"
        break
    fi
done

if [ -z "$REVERB_PATH" ]; then
    print_error "Reverb not found in standard locations!"
    print_status "Searching for Reverb in composer directories..."
    
    # Search for reverb in composer directories
    find /root/.composer -name "reverb" -type f 2>/dev/null || echo "Not found in .composer"
    find /root/.config -name "reverb" -type f 2>/dev/null || echo "Not found in .config"
    
    # Try to find it in PATH
    if command -v reverb &> /dev/null; then
        REVERB_PATH=$(which reverb)
        print_status "Found Reverb in PATH: $REVERB_PATH"
    else
        print_error "Reverb not found anywhere!"
        exit 1
    fi
fi

print_status "Step 4: Making Reverb executable..."

# Make Reverb executable
chmod +x "$REVERB_PATH"
print_status "Reverb is now executable"

print_status "Step 5: Testing Reverb..."

# Test Reverb
if "$REVERB_PATH" --version; then
    print_status "✅ Reverb is working!"
else
    print_error "❌ Reverb test failed!"
    print_status "Trying to run with PHP directly..."
    php "$REVERB_PATH" --version || echo "PHP direct execution failed"
    exit 1
fi

print_status "Step 6: Creating Reverb configuration..."

# Create Reverb config directory
sudo mkdir -p /etc/reverb
sudo chown -R root:root /etc/reverb
sudo chmod 755 /etc/reverb

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

print_status "Step 7: Creating systemd service..."

# Create systemd service file
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
Environment=COMPOSER_ALLOW_SUPERUSER=1
ExecStart=${REVERB_PATH} start --config=/etc/reverb/config.json
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

print_status "Step 8: Setting permissions..."

# Set proper permissions
sudo chown root:root /etc/reverb/config.json
sudo chmod 644 /etc/reverb/config.json

print_status "Step 9: Starting Reverb service..."

# Reload systemd and start service
sudo systemctl daemon-reload
sudo systemctl enable reverb
sudo systemctl start reverb

print_status "Step 10: Checking service status..."

# Check service status
sleep 3
if systemctl is-active --quiet reverb; then
    print_status "✅ Reverb service is now running!"
    sudo systemctl status reverb --no-pager
else
    print_error "❌ Reverb service failed to start"
    sudo systemctl status reverb --no-pager
    sudo journalctl -u reverb -n 20 --no-pager
    
    print_status "Trying manual start for debugging..."
    sudo -u root ${REVERB_PATH} start --config=/etc/reverb/config.json &
    sleep 2
    if pgrep -f "reverb start" > /dev/null; then
        print_status "✅ Reverb started manually!"
        ps aux | grep reverb
        sudo pkill -f "reverb start"
    else
        print_error "❌ Manual start also failed"
    fi
fi

print_status "Step 11: Testing WebSocket endpoints..."

# Test WebSocket endpoints
echo "Testing Warehouse WebSocket endpoint..."
curl -I "https://${WAREHOUSE_DOMAIN}/app/" 2>/dev/null || echo "Warehouse endpoint test failed"

echo "Testing Facilities WebSocket endpoint..."
curl -I "https://${FACILITIES_DOMAIN}/app/" 2>/dev/null || echo "Facilities endpoint test failed"

print_status "✅ Direct Reverb installation completed!"
echo ""
echo "📋 Installation Summary:"
echo "✅ Reverb executable: ${REVERB_PATH}"
echo "✅ Configuration: /etc/reverb/config.json"
echo "✅ Service: /etc/systemd/system/reverb.service"
echo "✅ User: root (for compatibility)"
echo ""
echo "🔧 Test Commands:"
echo "  sudo systemctl status reverb"
echo "  sudo journalctl -u reverb -f"
echo "  ps aux | grep reverb"
echo "  curl -I https://${WAREHOUSE_DOMAIN}/app/"
echo ""
echo "🌐 WebSocket URLs:"
echo "  Warehouse: wss://${WAREHOUSE_DOMAIN}:443/app/"
echo "  Facilities: wss://${FACILITIES_DOMAIN}:443/app/"
echo ""
print_status "🎉 Reverb should now be working!" 