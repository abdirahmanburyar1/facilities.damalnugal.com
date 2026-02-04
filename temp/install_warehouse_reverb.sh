#!/bin/bash

echo "🔧 Installing Reverb in Warehouse App..."

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

# Step 2: Navigate to warehouse directory and install Reverb
print_info "Step 2: Installing Reverb in warehouse app..."
cd /var/www/warehouse.damalnugal.com

# Check if Reverb is already in composer.json
if grep -q "laravel/reverb" composer.json; then
    print_info "Reverb is already in composer.json, updating..."
    composer update laravel/reverb
else
    print_info "Adding Reverb to composer.json..."
    composer require laravel/reverb
fi

print_success "Reverb installed in warehouse app"

# Step 3: Publish Reverb configuration
print_info "Step 3: Publishing Reverb configuration..."
php artisan vendor:publish --provider="Laravel\Reverb\ReverbServiceProvider" --force

print_success "Reverb configuration published"

# Step 4: Create a proper Reverb executable for warehouse
print_info "Step 4: Creating proper Reverb executable for warehouse..."
cat > /usr/local/bin/reverb-warehouse << 'EOF'
#!/usr/bin/env php
<?php

// Change to warehouse directory
chdir('/var/www/warehouse.damalnugal.com');

// Load Laravel application
require_once __DIR__ . '/../../var/www/warehouse.damalnugal.com/vendor/autoload.php';

$app = require_once __DIR__ . '/../../var/www/warehouse.damalnugal.com/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

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

// Use Laravel's Reverb command
$command = new \Laravel\Reverb\Console\StartCommand();
$command->setLaravel($app);

// Set up the command arguments
$input = new \Symfony\Component\Console\Input\ArrayInput([
    '--host' => $config['host'],
    '--port' => $config['port'],
    '--config' => $configFile ?: '/etc/reverb-warehouse/config.json'
]);

$output = new \Symfony\Component\Console\Output\ConsoleOutput();

// Run the command
$command->run($input, $output);
EOF

chmod +x /usr/local/bin/reverb-warehouse
print_success "Warehouse Reverb executable created"

# Step 5: Test the executable
print_info "Step 5: Testing warehouse Reverb executable..."
/usr/local/bin/reverb-warehouse start --config=/etc/reverb-warehouse/config.json &
REVERB_PID=$!
sleep 5

if kill -0 $REVERB_PID 2>/dev/null; then
    print_success "Warehouse Reverb executable is working"
    kill $REVERB_PID
else
    print_error "Warehouse Reverb executable failed to start"
    # Check if there are any error messages
    echo "Checking for error messages..."
    journalctl -u reverb-warehouse.service --no-pager -l -n 10
fi

# Step 6: Start the warehouse Reverb service
print_info "Step 6: Starting warehouse Reverb service..."
systemctl start reverb-warehouse.service

# Wait a moment for service to start
sleep 5

# Step 7: Check service status
print_info "Step 7: Checking warehouse service status..."
if systemctl is-active --quiet reverb-warehouse.service; then
    print_success "Warehouse Reverb service is running!"
    systemctl status reverb-warehouse.service --no-pager -l
else
    print_error "Warehouse Reverb service failed to start!"
    systemctl status reverb-warehouse.service --no-pager -l
    journalctl -u reverb-warehouse.service --no-pager -l -n 20
fi

# Step 8: Test WebSocket connection
print_info "Step 8: Testing warehouse WebSocket connection..."
if command -v curl >/dev/null 2>&1; then
    print_info "Testing warehouse WebSocket endpoint..."
    curl -I http://localhost:8081
else
    print_warning "curl not available, skipping WebSocket test"
fi

# Step 9: Clear Laravel caches
print_info "Step 9: Clearing Laravel caches..."
cd /var/www/warehouse.damalnugal.com
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

print_success "Laravel caches cleared"

# Step 10: Build frontend assets
print_info "Step 10: Building frontend assets..."
if [ -f /var/www/warehouse.damalnugal.com/package.json ]; then
    cd /var/www/warehouse.damalnugal.com
    npm run build
    print_success "Frontend assets built"
else
    print_warning "package.json not found, skipping frontend build"
fi

# Step 11: Final verification
print_info "Step 11: Final verification..."
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
print_success "🎉 Warehouse Reverb installation completed!"
print_info "WebSocket endpoint: wss://warehouse.damalnugal.com/reverb"
print_info "Local endpoint: ws://localhost:8081"
print_info "Service: systemctl status reverb-warehouse.service"
print_info "Logs: journalctl -u reverb-warehouse.service -f" 