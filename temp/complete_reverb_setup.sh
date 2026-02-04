#!/bin/bash

echo "🔧 Completing Reverb Setup for CentOS..."

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

# Step 1: Create Reverb configuration
print_info "Step 1: Creating Reverb configuration..."
mkdir -p /etc/reverb

cat > /etc/reverb/config.json << 'EOF'
{
    "host": "0.0.0.0",
    "port": 8080,
    "tls": {
        "cert": null,
        "key": null
    },
    "app_id": "facilities-app",
    "app_key": "facilities-key",
    "app_secret": "facilities-secret",
    "allowed_origins": ["*"],
    "max_connections": 1000,
    "max_payload_size": 65536,
    "heartbeat_interval": 30,
    "heartbeat_timeout": 60,
    "log_level": "info"
}
EOF

print_success "Reverb configuration created"

# Step 2: Create systemd service file
print_info "Step 2: Creating systemd service file..."
cat > /etc/systemd/system/reverb.service << 'EOF'
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=root
Group=root
WorkingDirectory=/var/www/facilities.damalnugal.com
ExecStart=/usr/local/bin/reverb start --config=/etc/reverb/config.json
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

print_success "Systemd service file created"

# Step 3: Reload systemd and enable service
print_info "Step 3: Reloading systemd and enabling service..."
systemctl daemon-reload
systemctl enable reverb.service

print_success "Service enabled"

# Step 4: Start Reverb service
print_info "Step 4: Starting Reverb service..."
systemctl start reverb.service

# Wait a moment for service to start
sleep 3

# Step 5: Check service status
print_info "Step 5: Checking service status..."
if systemctl is-active --quiet reverb.service; then
    print_success "Reverb service is running!"
    systemctl status reverb.service --no-pager -l
else
    print_error "Reverb service failed to start!"
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

# Step 7: Update Nginx configuration
print_info "Step 7: Updating Nginx configuration..."
if [ -f /etc/nginx/conf.d/facilities.damalnugal.com.conf ]; then
    # Backup original config
    cp /etc/nginx/conf.d/facilities.damalnugal.com.conf /etc/nginx/conf.d/facilities.damalnugal.com.conf.backup
    
    # Add WebSocket proxy to Nginx config
    cat >> /etc/nginx/conf.d/facilities.damalnugal.com.conf << 'EOF'

    # WebSocket proxy for Reverb
    location /reverb {
        proxy_pass http://127.0.0.1:8080;
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
    
    print_success "Nginx configuration updated"
    
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
    print_warning "Nginx configuration file not found"
fi

# Step 8: Final verification
print_info "Step 8: Final verification..."
echo ""
print_info "Checking if Reverb is listening on port 8080..."
if netstat -tlnp 2>/dev/null | grep :8080 >/dev/null; then
    print_success "Reverb is listening on port 8080"
    netstat -tlnp | grep :8080
else
    print_error "Reverb is not listening on port 8080"
fi

echo ""
print_info "Checking service status..."
systemctl status reverb.service --no-pager -l

echo ""
print_success "🎉 Reverb setup completed!"
print_info "WebSocket endpoint: wss://facilities.damalnugal.com/reverb"
print_info "Local endpoint: ws://localhost:8080"
print_info "Service: systemctl status reverb.service"
print_info "Logs: journalctl -u reverb.service -f" 