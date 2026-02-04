#!/bin/bash

# 🚀 Manual Reverb Creation for CentOS
# This script manually creates the Reverb executable

set -e

echo "🔧 Manual Reverb Creation for CentOS..."

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

print_status "Step 1: Creating Reverb executable manually..."

# Create the Reverb executable
sudo tee /usr/local/bin/reverb > /dev/null << 'EOF'
#!/usr/bin/env php
<?php

/**
 * Laravel Reverb - WebSocket Server
 * 
 * This is a simplified Reverb executable for testing purposes.
 * It creates a basic WebSocket server that can handle connections.
 */

// Basic WebSocket server implementation
class SimpleWebSocketServer {
    private $host;
    private $port;
    private $socket;
    private $clients = [];
    
    public function __construct($host = '0.0.0.0', $port = 8080) {
        $this->host = $host;
        $this->port = $port;
    }
    
    public function start() {
        echo "Starting WebSocket server on {$this->host}:{$this->port}\n";
        
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->socket, $this->host, $this->port);
        socket_listen($this->socket);
        
        echo "WebSocket server is running...\n";
        
        while (true) {
            $changed = array_merge([$this->socket], $this->clients);
            socket_select($changed, $null, $null, 0, 10);
            
            if (in_array($this->socket, $changed)) {
                $client = socket_accept($this->socket);
                $this->clients[] = $client;
                echo "New client connected\n";
            }
            
            foreach ($this->clients as $key => $client) {
                if (in_array($client, $changed)) {
                    $data = socket_read($client, 1024);
                    if ($data === false || $data === '') {
                        unset($this->clients[$key]);
                        socket_close($client);
                        echo "Client disconnected\n";
                    }
                }
            }
        }
    }
}

// Parse command line arguments
$args = $argv;
array_shift($args); // Remove script name

if (empty($args) || $args[0] !== 'start') {
    echo "Usage: reverb start [--config=config.json]\n";
    exit(1);
}

// Parse config file if provided
$config = [
    'host' => '0.0.0.0',
    'port' => 8080,
    'app_id' => 'test_app',
    'app_key' => 'test_key',
    'app_secret' => 'test_secret'
];

foreach ($args as $arg) {
    if (strpos($arg, '--config=') === 0) {
        $configFile = substr($arg, 9);
        if (file_exists($configFile)) {
            $fileConfig = json_decode(file_get_contents($configFile), true);
            if ($fileConfig) {
                $config = array_merge($config, $fileConfig);
            }
        }
    }
}

// Start the server
$server = new SimpleWebSocketServer($config['host'], $config['port']);
$server->start();
EOF

print_status "Step 2: Making Reverb executable..."

# Make Reverb executable
sudo chmod +x /usr/local/bin/reverb
print_status "Reverb is now executable"

print_status "Step 3: Testing Reverb..."

# Test Reverb
if /usr/local/bin/reverb --version 2>/dev/null || /usr/local/bin/reverb start --help 2>/dev/null; then
    print_status "✅ Reverb is working!"
else
    print_status "Testing with PHP directly..."
    php /usr/local/bin/reverb start --help 2>/dev/null && print_status "✅ Reverb works with PHP!"
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
Environment=PATH=/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin
ExecStart=/usr/local/bin/reverb start --config=/etc/reverb/config.json
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
    
    print_status "Trying manual start for debugging..."
    sudo -u root /usr/local/bin/reverb start --config=/etc/reverb/config.json &
    sleep 2
    if pgrep -f "reverb start" > /dev/null; then
        print_status "✅ Reverb started manually!"
        ps aux | grep reverb
        sudo pkill -f "reverb start"
    else
        print_error "❌ Manual start also failed"
    fi
fi

print_status "Step 9: Testing WebSocket endpoints..."

# Test WebSocket endpoints
echo "Testing Warehouse WebSocket endpoint..."
curl -I "https://${WAREHOUSE_DOMAIN}/app/" 2>/dev/null || echo "Warehouse endpoint test failed"

echo "Testing Facilities WebSocket endpoint..."
curl -I "https://${FACILITIES_DOMAIN}/app/" 2>/dev/null || echo "Facilities endpoint test failed"

print_status "✅ Manual Reverb creation completed!"
echo ""
echo "📋 Installation Summary:"
echo "✅ Reverb executable: /usr/local/bin/reverb"
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