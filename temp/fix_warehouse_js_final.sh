#!/bin/bash

echo "🔧 Final Warehouse JavaScript Fix..."

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

# Step 1: Fix the warehouse bootstrap.js file with proper initialization
print_info "Step 1: Fixing warehouse bootstrap.js file with proper initialization..."
if [ -f /var/www/warehouse.damalnugal.com/resources/js/bootstrap.js ]; then
    # Backup original file
    cp /var/www/warehouse.damalnugal.com/resources/js/bootstrap.js /var/www/warehouse.damalnugal.com/resources/js/bootstrap.js.backup
    
    # Update the Echo configuration with proper initialization and error handling
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

// Add error handling with proper initialization checks
function setupEchoEventHandlers() {
    if (window.Echo && window.Echo.connector && window.Echo.connector.connection) {
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

        window.Echo.connector.connection.on('connecting', () => {
            console.log('Echo connecting...');
        });

        console.log('Echo event handlers set up successfully');
    } else {
        console.warn('Echo connector not ready, retrying in 1 second...');
        setTimeout(setupEchoEventHandlers, 1000);
    }
}

// Wait for Echo to be fully initialized
setTimeout(setupEchoEventHandlers, 100);

// Also try to set up handlers when the page loads
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(setupEchoEventHandlers, 500);
});

// Fallback for immediate setup
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupEchoEventHandlers);
} else {
    setupEchoEventHandlers();
}
EOF
    
    print_success "Warehouse bootstrap.js updated with proper initialization"
else
    print_warning "Warehouse bootstrap.js file not found"
fi

# Step 2: Create a simple test page to verify Echo is working
print_info "Step 2: Creating a test page to verify Echo functionality..."
cat > /var/www/warehouse.damalnugal.com/public/test-echo.html << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Echo Test - Warehouse</title>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.0.2/dist/echo.iife.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
</head>
<body>
    <h1>Warehouse Echo Test</h1>
    <div id="status">Initializing...</div>
    <div id="logs"></div>

    <script>
        window.Pusher = Pusher;
        
        // Configure Echo
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: 'warehouse-key',
            wsHost: window.location.hostname,
            wsPort: 443,
            wssPort: 443,
            forceTLS: true,
            enabledTransports: ['wss'],
            authEndpoint: '/broadcasting/auth',
            disableStats: true,
            enableLogging: true,
            reverb: {
                appId: 'warehouse-app',
                appKey: 'warehouse-key',
                appSecret: 'warehouse-secret',
            }
        });

        function log(message) {
            const logs = document.getElementById('logs');
            const timestamp = new Date().toLocaleTimeString();
            logs.innerHTML += `<div>[${timestamp}] ${message}</div>`;
            console.log(message);
        }

        function setupEchoEventHandlers() {
            if (window.Echo && window.Echo.connector && window.Echo.connector.connection) {
                log('Setting up Echo event handlers...');
                
                window.Echo.connector.connection.on('error', (error) => {
                    log('Echo connection error: ' + JSON.stringify(error));
                });

                window.Echo.connector.connection.on('connected', () => {
                    log('Echo connected successfully');
                    document.getElementById('status').innerHTML = 'Connected';
                    document.getElementById('status').style.color = 'green';
                });

                window.Echo.connector.connection.on('disconnected', () => {
                    log('Echo disconnected');
                    document.getElementById('status').innerHTML = 'Disconnected';
                    document.getElementById('status').style.color = 'red';
                });

                window.Echo.connector.connection.on('connecting', () => {
                    log('Echo connecting...');
                    document.getElementById('status').innerHTML = 'Connecting...';
                    document.getElementById('status').style.color = 'orange';
                });

                log('Echo event handlers set up successfully');
            } else {
                log('Echo connector not ready, retrying...');
                setTimeout(setupEchoEventHandlers, 1000);
            }
        }

        // Initialize
        log('Initializing Echo...');
        setTimeout(setupEchoEventHandlers, 100);
    </script>
</body>
</html>
EOF

print_success "Test page created at /test-echo.html"

# Step 3: Clear Laravel caches
print_info "Step 3: Clearing Laravel caches..."
cd /var/www/warehouse.damalnugal.com
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

print_success "Laravel caches cleared"

# Step 4: Build frontend assets
print_info "Step 4: Building frontend assets..."
if [ -f /var/www/warehouse.damalnugal.com/package.json ]; then
    cd /var/www/warehouse.damalnugal.com
    npm run build
    print_success "Frontend assets built"
else
    print_warning "package.json not found, skipping frontend build"
fi

# Step 5: Final verification
print_info "Step 5: Final verification..."
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
print_success "🎉 Warehouse JavaScript fix completed!"
print_info "WebSocket endpoint: wss://warehouse.damalnugal.com/reverb"
print_info "Local endpoint: ws://localhost:8081"
print_info "Test page: https://warehouse.damalnugal.com/test-echo.html"
print_info ""
print_info "📋 Next steps:"
print_info "1. Clear your browser cache (Ctrl+F5)"
print_info "2. Visit https://warehouse.damalnugal.com/test-echo.html to test Echo"
print_info "3. Check browser console for proper initialization logs"
print_info "4. If test page works, refresh your main app" 