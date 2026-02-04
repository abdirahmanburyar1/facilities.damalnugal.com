#!/bin/bash

echo "🔧 Warehouse Reverb Setup for CentOS..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Step 1: Create Reverb executable for warehouse
print_info "Step 1: Creating Reverb executable for warehouse..."
cat > /usr/local/bin/reverb-warehouse << 'EOF'
#!/usr/bin/env php
<?php

require_once __DIR__ . '/../../var/www/warehouse.damalnugal.com/vendor/autoload.php';

use Laravel\Reverb\Server\Server;
use Laravel\Reverb\Server\ServerManager;

if ($argc < 2 || $argv[1] !== 'start') {
    echo "Usage: reverb-warehouse start [--config=config.json]\n";
    exit(1);
}

$configFile = null;
if (isset($argv[2]) && strpos($argv[2], '--config=') === 0) {
    $configFile = substr($argv[2], 9);
}

// Default configuration
$config = [
    'host' => '0.0.0.0',
    'port' => 8081,
    'tls' => [
        'cert' => null,
        'key' => null
    ],
    'app_id' => 'warehouse-app',
    'app_key' => 'warehouse-key',
    'app_secret' => 'warehouse-secret',
    'allowed_origins' => ['*'],
    'max_connections' => 1000,
    'max_payload_size' => 65536,
    'heartbeat_interval' => 30,
    'heartbeat_timeout' => 60,
    'log_level' => 'info'
];

// Load config file if provided
if ($configFile && file_exists($configFile)) {
    $fileConfig = json_decode(file_get_contents($configFile), true);
    if ($fileConfig) {
        $config = array_merge($config, $fileConfig);
    }
}

echo "Starting WebSocket server on {$config['host']}:{$config['port']}\n";

// Create server
$server = new Server($config);
$server->start();

echo "WebSocket server is running...\n";

// Keep the script running
while (true) {
    sleep(1);
}
EOF

chmod +x /usr/local/bin/reverb-warehouse
print_success "Warehouse Reverb executable created"

# Step 2: Create Reverb configuration for warehouse
print_info "Step 2: Creating Reverb configuration for warehouse..."
mkdir -p /etc/reverb-warehouse

cat > /etc/reverb-warehouse/config.json << 'EOF'
{
    "host": "0.0.0.0",
    "port": 8081,
    "tls": {
        "cert": null,
        "key": null
    },
    "app_id": "warehouse-app",
    "app_key": "warehouse-key",
    "app_secret": "warehouse-secret",
    "allowed_origins": ["*"],
    "max_connections": 1000,
    "max_payload_size": 65536,
    "heartbeat_interval": 30,
    "heartbeat_timeout": 60,
    "log_level": "info"
}
EOF

print_success "Warehouse Reverb configuration created"

# Step 3: Create systemd service file for warehouse
print_info "Step 3: Creating systemd service file for warehouse..."
cat > /etc/systemd/system/reverb-warehouse.service << 'EOF'
[Unit]
Description=Laravel Reverb WebSocket Server (Warehouse)
After=network.target

[Service]
Type=simple
User=root
Group=root
WorkingDirectory=/var/www/warehouse.damalnugal.com
ExecStart=/usr/local/bin/reverb-warehouse start --config=/etc/reverb-warehouse/config.json
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

print_success "Warehouse systemd service file created"

# Step 4: Reload systemd and enable service
print_info "Step 4: Reloading systemd and enabling warehouse service..."
systemctl daemon-reload
systemctl enable reverb-warehouse.service

print_success "Warehouse service enabled"

# Step 5: Start Reverb warehouse service
print_info "Step 5: Starting Reverb warehouse service..."
systemctl start reverb-warehouse.service

# Wait a moment for service to start
sleep 3

# Step 6: Check service status
print_info "Step 6: Checking warehouse service status..."
if systemctl is-active --quiet reverb-warehouse.service; then
    print_success "Warehouse Reverb service is running!"
    systemctl status reverb-warehouse.service --no-pager -l
else
    print_error "Warehouse Reverb service failed to start!"
    systemctl status reverb-warehouse.service --no-pager -l
    journalctl -u reverb-warehouse.service --no-pager -l -n 20
fi

# Step 7: Test WebSocket connection
print_info "Step 7: Testing warehouse WebSocket connection..."
if command -v curl >/dev/null 2>&1; then
    print_info "Testing warehouse WebSocket endpoint..."
    curl -I http://localhost:8081
else
    print_warning "curl not available, skipping WebSocket test"
fi

# Step 8: Update Nginx configuration for warehouse
print_info "Step 8: Updating Nginx configuration for warehouse..."
if [ -f /etc/nginx/conf.d/warehouse.damalnugal.com.conf ]; then
    # Backup original config
    cp /etc/nginx/conf.d/warehouse.damalnugal.com.conf /etc/nginx/conf.d/warehouse.damalnugal.com.conf.backup
    
    # Add WebSocket proxy to Nginx config
    cat >> /etc/nginx/conf.d/warehouse.damalnugal.com.conf << 'EOF'

    # WebSocket proxy for Reverb (Warehouse)
    location /reverb {
        proxy_pass http://127.0.0.1:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }
EOF
    
    print_success "Warehouse Nginx configuration updated"
    
    # Test Nginx configuration
    print_info "Testing Nginx configuration..."
    if nginx -t; then
        print_success "Nginx configuration is valid"
        systemctl reload nginx
        print_success "Nginx reloaded"
    else
        print_error "Nginx configuration is invalid!"
    fi
else
    print_warning "Warehouse Nginx configuration file not found"
fi

# Step 9: Update warehouse .env file
print_info "Step 9: Updating warehouse .env file..."
if [ -f /var/www/warehouse.damalnugal.com/.env ]; then
    # Backup original .env
    cp /var/www/warehouse.damalnugal.com/.env /var/www/warehouse.damalnugal.com/.env.backup
    
    # Update Reverb configuration
    sed -i 's/BROADCAST_DRIVER=.*/BROADCAST_DRIVER=reverb/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/REVERB_APP_ID=.*/REVERB_APP_ID=warehouse-app/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/REVERB_APP_KEY=.*/REVERB_APP_KEY=warehouse-key/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/REVERB_APP_SECRET=.*/REVERB_APP_SECRET=warehouse-secret/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/REVERB_HOST=.*/REVERB_HOST=127.0.0.1/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/REVERB_PORT=.*/REVERB_PORT=8081/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/REVERB_SCHEME=.*/REVERB_SCHEME=https/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/REVERB_PATH=.*/REVERB_PATH=/' /var/www/warehouse.damalnugal.com/.env
    
    # Update frontend environment variables
    sed -i 's/VITE_REVERB_APP_KEY=.*/VITE_REVERB_APP_KEY=warehouse-key/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/VITE_REVERB_HOST=.*/VITE_REVERB_HOST=warehouse.damalnugal.com/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/VITE_REVERB_PORT=.*/VITE_REVERB_PORT=443/' /var/www/warehouse.damalnugal.com/.env
    
    print_success "Warehouse .env file updated"
else
    print_warning "Warehouse .env file not found"
fi

# Step 10: Clear Laravel caches
print_info "Step 10: Clearing Laravel caches..."
cd /var/www/warehouse.damalnugal.com
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

print_success "Laravel caches cleared"

# Step 11: Build frontend assets
print_info "Step 11: Building frontend assets..."
if [ -f /var/www/warehouse.damalnugal.com/package.json ]; then
    cd /var/www/warehouse.damalnugal.com
    npm run build
    print_success "Frontend assets built"
else
    print_warning "package.json not found, skipping frontend build"
fi

# Step 12: Final verification
print_info "Step 12: Final verification..."
echo ""
print_info "Checking if Warehouse Reverb is listening on port 8081..."
if netstat -tlnp 2>/dev/null | grep :8081 >/dev/null; then
    print_success "Warehouse Reverb is listening on port 8081"
    netstat -tlnp | grep :8081
else
    print_error "Warehouse Reverb is not listening on port 8081"
fi

echo ""
print_info "Checking warehouse service status..."
systemctl status reverb-warehouse.service --no-pager -l

echo ""
print_success "🎉 Warehouse Reverb setup completed!"
print_info "WebSocket endpoint: wss://warehouse.damalnugal.com/reverb"
print_info "Local endpoint: ws://localhost:8081"
print_info "Service: systemctl status reverb-warehouse.service"
print_info "Logs: journalctl -u reverb-warehouse.service -f"
print_info ""
print_info "📋 Summary:"
print_info "- Facilities Reverb: Port 8080 (wss://facilities.damalnugal.com/reverb)"
print_info "- Warehouse Reverb: Port 8081 (wss://warehouse.damalnugal.com/reverb)" 