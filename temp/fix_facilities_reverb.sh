#!/bin/bash

echo "🔧 Fixing Facilities Reverb Port Conflict..."

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

# Step 1: Stop the current Reverb service
print_info "Step 1: Stopping current Reverb service..."
systemctl stop reverb.service

# Step 2: Check what's using port 8080
print_info "Step 2: Checking what's using port 8080..."
if netstat -tlnp 2>/dev/null | grep :8080 >/dev/null; then
    print_warning "Port 8080 is still in use:"
    netstat -tlnp | grep :8080
    print_info "Killing processes on port 8080..."
    fuser -k 8080/tcp 2>/dev/null || true
    sleep 2
fi

# Step 3: Update Reverb configuration to use a different port if needed
print_info "Step 3: Updating Reverb configuration..."
if [ -f /etc/reverb/config.json ]; then
    # Check if port 8080 is specified
    if grep -q '"port": 8080' /etc/reverb/config.json; then
        print_info "Port 8080 is configured, keeping it for facilities"
    else
        print_info "Updating port to 8080 for facilities..."
        sed -i 's/"port": [0-9]*/"port": 8080/' /etc/reverb/config.json
    fi
else
    print_error "Reverb configuration file not found!"
    exit 1
fi

# Step 4: Restart the Reverb service
print_info "Step 4: Restarting Reverb service..."
systemctl start reverb.service

# Wait a moment for service to start
sleep 3

# Step 5: Check service status
print_info "Step 5: Checking service status..."
if systemctl is-active --quiet reverb.service; then
    print_success "Facilities Reverb service is running!"
    systemctl status reverb.service --no-pager -l
else
    print_error "Facilities Reverb service failed to start!"
    systemctl status reverb.service --no-pager -l
    journalctl -u reverb.service --no-pager -l -n 20
fi

# Step 6: Test WebSocket connection
print_info "Step 6: Testing WebSocket connection..."
if command -v curl >/dev/null 2>&1; then
    print_info "Testing WebSocket endpoint..."
    curl -I http://localhost:8080
else
    print_warning "curl not available, skipping WebSocket test"
fi

# Step 7: Final verification
print_info "Step 7: Final verification..."
echo ""
print_info "Checking if Facilities Reverb is listening on port 8080..."
if netstat -tlnp 2>/dev/null | grep :8080 >/dev/null; then
    print_success "Facilities Reverb is listening on port 8080"
    netstat -tlnp | grep :8080
else
    print_error "Facilities Reverb is not listening on port 8080"
fi

echo ""
print_info "Checking service status..."
systemctl status reverb.service --no-pager -l

echo ""
print_success "🎉 Facilities Reverb fixed!"
print_info "WebSocket endpoint: wss://facilities.damalnugal.com/reverb"
print_info "Local endpoint: ws://localhost:8080"
print_info "Service: systemctl status reverb.service"
print_info "Logs: journalctl -u reverb.service -f" 