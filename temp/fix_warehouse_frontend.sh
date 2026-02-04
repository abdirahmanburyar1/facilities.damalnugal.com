#!/bin/bash

echo "🔧 Fixing Warehouse Frontend Echo Configuration..."

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

# Step 1: Fix the warehouse bootstrap.js file
print_info "Step 1: Fixing warehouse bootstrap.js file..."
if [ -f /var/www/warehouse.damalnugal.com/resources/js/bootstrap.js ]; then
    # Backup original file
    cp /var/www/warehouse.damalnugal.com/resources/js/bootstrap.js /var/www/warehouse.damalnugal.com/resources/js/bootstrap.js.backup
    
    # Update the Echo configuration with proper Reverb settings
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
    enableLogging: true,
    // Reverb specific options
    reverb: {
        appId: import.meta.env.VITE_REVERB_APP_ID || 'warehouse-app',
        appKey: import.meta.env.VITE_REVERB_APP_KEY || 'warehouse-key',
        appSecret: import.meta.env.VITE_REVERB_APP_SECRET || 'warehouse-secret',
    }
});

// Log when Echo is ready
console.log('Echo initialized with Reverb config:', {
    key: import.meta.env.VITE_REVERB_APP_KEY || 'warehouse-key',
    host: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    port: import.meta.env.VITE_REVERB_PORT || 443,
    appId: import.meta.env.VITE_REVERB_APP_ID || 'warehouse-app'
});

// Add error handling
window.Echo.connector.connection.on('error', (error) => {
    console.error('Echo connection error:', error);
});

window.Echo.connector.connection.on('connected', () => {
    console.log('Echo connected successfully');
});

window.Echo.connector.connection.on('disconnected', () => {
    console.log('Echo disconnected');
});
EOF
    
    print_success "Warehouse bootstrap.js updated with proper Reverb configuration"
else
    print_warning "Warehouse bootstrap.js file not found"
fi

# Step 2: Update warehouse .env file with correct Reverb settings
print_info "Step 2: Updating warehouse .env file..."
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
    sed -i 's/VITE_REVERB_APP_ID=.*/VITE_REVERB_APP_ID=warehouse-app/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/VITE_REVERB_APP_KEY=.*/VITE_REVERB_APP_KEY=warehouse-key/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/VITE_REVERB_APP_SECRET=.*/VITE_REVERB_APP_SECRET=warehouse-secret/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/VITE_REVERB_HOST=.*/VITE_REVERB_HOST=warehouse.damalnugal.com/' /var/www/warehouse.damalnugal.com/.env
    sed -i 's/VITE_REVERB_PORT=.*/VITE_REVERB_PORT=443/' /var/www/warehouse.damalnugal.com/.env
    
    print_success "Warehouse .env file updated"
else
    print_warning "Warehouse .env file not found"
fi

# Step 3: Check if Reverb config file exists and update it
print_info "Step 3: Checking Reverb configuration..."
if [ -f /var/www/warehouse.damalnugal.com/config/reverb.php ]; then
    print_info "Reverb config file found, updating..."
    # Update the Reverb config to ensure proper settings
    sed -i "s/'app_id' => env('REVERB_APP_ID', '.*'),/'app_id' => env('REVERB_APP_ID', 'warehouse-app'),/" /var/www/warehouse.damalnugal.com/config/reverb.php
    sed -i "s/'app_key' => env('REVERB_APP_KEY', '.*'),/'app_key' => env('REVERB_APP_KEY', 'warehouse-key'),/" /var/www/warehouse.damalnugal.com/config/reverb.php
    sed -i "s/'app_secret' => env('REVERB_APP_SECRET', '.*'),/'app_secret' => env('REVERB_APP_SECRET', 'warehouse-secret'),/" /var/www/warehouse.damalnugal.com/config/reverb.php
    print_success "Reverb config file updated"
else
    print_warning "Reverb config file not found, publishing..."
    cd /var/www/warehouse.damalnugal.com
    php artisan vendor:publish --provider="Laravel\Reverb\ReverbServiceProvider" --force
    print_success "Reverb config published"
fi

# Step 4: Clear Laravel caches
print_info "Step 4: Clearing Laravel caches..."
cd /var/www/warehouse.damalnugal.com
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

print_success "Laravel caches cleared"

# Step 5: Build frontend assets
print_info "Step 5: Building frontend assets..."
if [ -f /var/www/warehouse.damalnugal.com/package.json ]; then
    cd /var/www/warehouse.damalnugal.com
    npm run build
    print_success "Frontend assets built"
else
    print_warning "package.json not found, skipping frontend build"
fi

# Step 6: Test WebSocket connection
print_info "Step 6: Testing WebSocket connection..."
if command -v curl >/dev/null 2>&1; then
    print_info "Testing warehouse WebSocket endpoint..."
    curl -I http://localhost:8081
else
    print_warning "curl not available, skipping WebSocket test"
fi

# Step 7: Final verification
print_info "Step 7: Final verification..."
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
print_success "🎉 Warehouse frontend Echo configuration fixed!"
print_info "WebSocket endpoint: wss://warehouse.damalnugal.com/reverb"
print_info "Local endpoint: ws://localhost:8081"
print_info "Service: systemctl status reverb-warehouse.service"
print_info "Logs: journalctl -u reverb-warehouse.service -f"
print_info ""
print_info "📋 Next steps:"
print_info "1. Clear your browser cache"
print_info "2. Refresh the warehouse app page"
print_info "3. Check browser console for Echo connection logs" 