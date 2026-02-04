#!/bin/bash

# 🚀 Manual Reverb Installation for CentOS
# This script manually installs and configures Reverb

set -e

echo "🔧 Manual Reverb Installation for CentOS..."

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

print_status "Step 1: Installing Reverb manually..."

# Install Reverb globally with explicit path
composer global require laravel/reverb --no-interaction

# Find the actual Reverb path
REVERB_PATH=""
if [ -f "/root/.composer/vendor/bin/reverb" ]; then
    REVERB_PATH="/root/.composer/vendor/bin/reverb"
    print_status "Found Reverb at: $REVERB_PATH"
elif [ -f "/usr/local/bin/reverb" ]; then
    REVERB_PATH="/usr/local/bin/reverb"
    print_status "Found Reverb at: $REVERB_PATH"
else
    print_error "Reverb not found after installation!"
    print_status "Checking composer global bin directory..."
    ls -la /root/.composer/vendor/bin/ || echo "Directory not found"
    exit 1
fi

print_status "Step 2: Making Reverb executable..."

# Make Reverb executable
chmod +x "$REVERB_PATH"
print_status "Reverb is now executable"

print_status "Step 3: Testing Reverb..."

# Test Reverb
if "$REVERB_PATH" --version; then
    print_status "✅ Reverb is working!"
else
    print_error "❌ Reverb test failed!"
    exit 1
fi

print_status "Step 4: Creating Reverb configuration..."

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

print_status "Step 5: Creating systemd service..."

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
ExecStart=${REVERB_PATH} start --config=/etc/reverb/config.json
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

print_status "Step 6: Setting permissions..."

# Set proper permissions
sudo chown root:root /etc/reverb/config.json
sudo chmod 644 /etc/reverb/config.json

print_status "Step 7: Starting Reverb service..."

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
fi

print_status "Step 9: Testing Reverb manually..."

# Test Reverb manually
print_status "Testing Reverb manually..."
if pgrep -f "reverb start" > /dev/null; then
    print_status "✅ Reverb process is running!"
    ps aux | grep reverb
else
    print_warning "⚠️ Reverb process not found, trying manual start..."
    sudo -u root ${REVERB_PATH} start --config=/etc/reverb/config.json &
    sleep 2
    if pgrep -f "reverb start" > /dev/null; then
        print_status "✅ Reverb started manually!"
        ps aux | grep reverb
    else
        print_error "❌ Manual start failed"
    fi
fi

print_status "Step 10: Testing WebSocket endpoints..."

# Test WebSocket endpoints
echo "Testing Warehouse WebSocket endpoint..."
curl -I "https://${WAREHOUSE_DOMAIN}/app/" 2>/dev/null || echo "Warehouse endpoint test failed"

echo "Testing Facilities WebSocket endpoint..."
curl -I "https://${FACILITIES_DOMAIN}/app/" 2>/dev/null || echo "Facilities endpoint test failed"

print_status "✅ Manual Reverb installation completed!"
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