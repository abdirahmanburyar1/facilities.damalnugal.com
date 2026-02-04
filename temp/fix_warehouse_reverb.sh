#!/bin/bash

echo "🔧 Fixing Warehouse Reverb Issues..."

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

# Step 1: Stop the warehouse Reverb service
print_info "Step 1: Stopping warehouse Reverb service..."
systemctl stop reverb-warehouse.service

# Step 2: Fix the warehouse Reverb executable
print_info "Step 2: Fixing warehouse Reverb executable..."
cat > /usr/local/bin/reverb-warehouse << 'EOF'
#!/usr/bin/env php
<?php

require_once '/var/www/warehouse.damalnugal.com/vendor/autoload.php';

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
print_success "Warehouse Reverb executable fixed"

# Step 3: Test the executable
print_info "Step 3: Testing warehouse Reverb executable..."
/usr/local/bin/reverb-warehouse start --config=/etc/reverb-warehouse/config.json &
REVERB_PID=$!
sleep 3

if kill -0 $REVERB_PID 2>/dev/null; then
    print_success "Warehouse Reverb executable is working"
    kill $REVERB_PID
else
    print_error "Warehouse Reverb executable failed to start"
fi

# Step 4: Start the warehouse Reverb service
print_info "Step 4: Starting warehouse Reverb service..."
systemctl start reverb-warehouse.service

# Wait a moment for service to start
sleep 3

# Step 5: Check service status
print_info "Step 5: Checking warehouse service status..."
if systemctl is-active --quiet reverb-warehouse.service; then
    print_success "Warehouse Reverb service is running!"
    systemctl status reverb-warehouse.service --no-pager -l
else
    print_error "Warehouse Reverb service failed to start!"
    systemctl status reverb-warehouse.service --no-pager -l
    journalctl -u reverb-warehouse.service --no-pager -l -n 10
fi

# Step 6: Fix the warehouse bootstrap.js file
print_info "Step 6: Fixing warehouse frontend configuration..."
if [ -f /var/www/warehouse.damalnugal.com/resources/js/bootstrap.js ]; then
    # Backup original file
    cp /var/www/warehouse.damalnugal.com/resources/js/bootstrap.js /var/www/warehouse.damalnugal.com/resources/js/bootstrap.js.backup
    
    # Update the Echo configuration
    cat > /var/www/warehouse.damalnugal.com/resources/js/bootstrap.js << 'EOF'
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
// Configure Reverb for real-time updates
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'warehouse-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT || 443,
    wssPort: import.meta.env.VITE_REVERB_PORT || 443,
    forceTLS: true,
    enabledTransports: ['wss'],
    authEndpoint: '/broadcasting/auth',
    disableStats: true,
    enableLogging: true // Enable Reverb debugging
});

// Log when Echo is ready
console.log('Echo initialized with Reverb config:', {
    key: import.meta.env.VITE_REVERB_APP_KEY || 'warehouse-key',
    host: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    port: import.meta.env.VITE_REVERB_PORT || 443
});
EOF
    
    print_success "Warehouse bootstrap.js updated"
else
    print_warning "Warehouse bootstrap.js file not found"
fi

# Step 7: Update warehouse .env file with correct Reverb settings
print_info "Step 7: Updating warehouse .env file..."
if [ -f /var/www/warehouse.damalnugal.com/.env ]; then
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

# Step 8: Clear Laravel caches
print_info "Step 8: Clearing Laravel caches..."
cd /var/www/warehouse.damalnugal.com
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

print_success "Laravel caches cleared"

# Step 9: Build frontend assets
print_info "Step 9: Building frontend assets..."
if [ -f /var/www/warehouse.damalnugal.com/package.json ]; then
    cd /var/www/warehouse.damalnugal.com
    npm run build
    print_success "Frontend assets built"
else
    print_warning "package.json not found, skipping frontend build"
fi

# Step 10: Final verification
print_info "Step 10: Final verification..."
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
print_success "🎉 Warehouse Reverb issues fixed!"
print_info "WebSocket endpoint: wss://warehouse.damalnugal.com/reverb"
print_info "Local endpoint: ws://localhost:8081"
print_info "Service: systemctl status reverb-warehouse.service"
print_info "Logs: journalctl -u reverb-warehouse.service -f" 