#!/bin/bash

echo "🔧 Simple Warehouse Reverb Fix..."

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

# Step 2: Update the systemd service to use Laravel Artisan
print_info "Step 2: Updating systemd service to use Laravel Artisan..."
cat > /etc/systemd/system/reverb-warehouse.service << 'EOF'
[Unit]
Description=Laravel Reverb WebSocket Server (Warehouse)
After=network.target

[Service]
Type=simple
User=root
Group=root
WorkingDirectory=/var/www/warehouse.damalnugal.com
ExecStart=/usr/bin/php /var/www/warehouse.damalnugal.com/artisan reverb:start --host=0.0.0.0 --port=8081
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

print_success "Systemd service updated"

# Step 3: Reload systemd and start service
print_info "Step 3: Reloading systemd and starting service..."
systemctl daemon-reload
systemctl start reverb-warehouse.service

# Wait a moment for service to start
sleep 5

# Step 4: Check service status
print_info "Step 4: Checking warehouse service status..."
if systemctl is-active --quiet reverb-warehouse.service; then
    print_success "Warehouse Reverb service is running!"
    systemctl status reverb-warehouse.service --no-pager -l
else
    print_error "Warehouse Reverb service failed to start!"
    systemctl status reverb-warehouse.service --no-pager -l
    journalctl -u reverb-warehouse.service --no-pager -l -n 20
fi

# Step 5: Test WebSocket connection
print_info "Step 5: Testing warehouse WebSocket connection..."
if command -v curl >/dev/null 2>&1; then
    print_info "Testing warehouse WebSocket endpoint..."
    curl -I http://localhost:8081
else
    print_warning "curl not available, skipping WebSocket test"
fi

# Step 6: Update warehouse .env file with correct Reverb settings
print_info "Step 6: Updating warehouse .env file..."
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

# Step 7: Clear Laravel caches
print_info "Step 7: Clearing Laravel caches..."
cd /var/www/warehouse.damalnugal.com
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

print_success "Laravel caches cleared"

# Step 8: Build frontend assets
print_info "Step 8: Building frontend assets..."
if [ -f /var/www/warehouse.damalnugal.com/package.json ]; then
    cd /var/www/warehouse.damalnugal.com
    npm run build
    print_success "Frontend assets built"
else
    print_warning "package.json not found, skipping frontend build"
fi

# Step 9: Final verification
print_info "Step 9: Final verification..."
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
print_success "🎉 Warehouse Reverb simple fix completed!"
print_info "WebSocket endpoint: wss://warehouse.damalnugal.com/reverb"
print_info "Local endpoint: ws://localhost:8081"
print_info "Service: systemctl status reverb-warehouse.service"
print_info "Logs: journalctl -u reverb-warehouse.service -f" 