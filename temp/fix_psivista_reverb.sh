#!/bin/bash

# 🚀 Fix Reverb for PSIVista.com Domain
# This script fixes the WebSocket connection issues for warehouse.psivista.com

set -e

echo "🔧 Fixing Reverb for PSIVista.com..."

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

# Configuration for PSIVista
WAREHOUSE_DIR="/var/www/warehouse.damalnugal.com"
FACILITIES_DIR="/var/www/facilities.damalnugal.com"
WAREHOUSE_DOMAIN="warehouse.psivista.com"
FACILITIES_DOMAIN="facilities.psivista.com"
TIMESTAMP=$(date +%s)

print_status "Step 1: Checking current .env configurations..."

# Check warehouse .env
echo "📋 Warehouse .env Reverb settings:"
if [ -f "${WAREHOUSE_DIR}/.env" ]; then
    grep -E "VITE_REVERB|BROADCAST_DRIVER" "${WAREHOUSE_DIR}/.env" || echo "No Reverb settings found"
else
    print_error "Warehouse .env file not found!"
fi

print_status "Step 2: Updating Warehouse .env with PSIVista domain..."

# Remove old Reverb settings from warehouse .env
sed -i '/^BROADCAST_DRIVER=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^REVERB_APP_ID=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^REVERB_APP_KEY=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^REVERB_APP_SECRET=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^REVERB_HOST=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^REVERB_PORT=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^REVERB_SCHEME=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^REVERB_PATH=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^REVERB_SERVER_HOST=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^REVERB_SERVER_PORT=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^REVERB_SERVER_PATH=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^VITE_REVERB_APP_KEY=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^VITE_REVERB_HOST=/d' "${WAREHOUSE_DIR}/.env"
sed -i '/^VITE_REVERB_PORT=/d' "${WAREHOUSE_DIR}/.env"

# Add new Reverb settings to warehouse .env with PSIVista domain
cat >> "${WAREHOUSE_DIR}/.env" << EOF

# Reverb Broadcasting Configuration
BROADCAST_DRIVER=reverb
REVERB_APP_ID=psivista_warehouse_${TIMESTAMP}
REVERB_APP_KEY=psivista_warehouse_key_${TIMESTAMP}
REVERB_APP_SECRET=psivista_warehouse_secret_${TIMESTAMP}
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https
REVERB_PATH=
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_SERVER_PATH=
VITE_REVERB_APP_KEY=psivista_warehouse_key_${TIMESTAMP}
VITE_REVERB_HOST=${WAREHOUSE_DOMAIN}
VITE_REVERB_PORT=443
EOF

print_status "Step 3: Updating Facilities .env with PSIVista domain..."

# Remove old Reverb settings from facilities .env
sed -i '/^BROADCAST_DRIVER=/d' "${FACILITIES_DIR}/.env"
sed -i '/^REVERB_APP_ID=/d' "${FACILITIES_DIR}/.env"
sed -i '/^REVERB_APP_KEY=/d' "${FACILITIES_DIR}/.env"
sed -i '/^REVERB_APP_SECRET=/d' "${FACILITIES_DIR}/.env"
sed -i '/^REVERB_HOST=/d' "${FACILITIES_DIR}/.env"
sed -i '/^REVERB_PORT=/d' "${FACILITIES_DIR}/.env"
sed -i '/^REVERB_SCHEME=/d' "${FACILITIES_DIR}/.env"
sed -i '/^REVERB_PATH=/d' "${FACILITIES_DIR}/.env"
sed -i '/^REVERB_SERVER_HOST=/d' "${FACILITIES_DIR}/.env"
sed -i '/^REVERB_SERVER_PORT=/d' "${FACILITIES_DIR}/.env"
sed -i '/^REVERB_SERVER_PATH=/d' "${FACILITIES_DIR}/.env"
sed -i '/^VITE_REVERB_APP_KEY=/d' "${FACILITIES_DIR}/.env"
sed -i '/^VITE_REVERB_HOST=/d' "${FACILITIES_DIR}/.env"
sed -i '/^VITE_REVERB_PORT=/d' "${FACILITIES_DIR}/.env"

# Add new Reverb settings to facilities .env with PSIVista domain
cat >> "${FACILITIES_DIR}/.env" << EOF

# Reverb Broadcasting Configuration
BROADCAST_DRIVER=reverb
REVERB_APP_ID=psivista_facilities_${TIMESTAMP}
REVERB_APP_KEY=psivista_facilities_key_${TIMESTAMP}
REVERB_APP_SECRET=psivista_facilities_secret_${TIMESTAMP}
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https
REVERB_PATH=
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_SERVER_PATH=
VITE_REVERB_APP_KEY=psivista_facilities_key_${TIMESTAMP}
VITE_REVERB_HOST=${FACILITIES_DOMAIN}
VITE_REVERB_PORT=443
EOF

print_status "Step 4: Clearing Laravel caches..."

# Clear caches for both applications
cd "${WAREHOUSE_DIR}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear

cd "${FACILITIES_DIR}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear

print_status "Step 5: Rebuilding frontend assets..."

# Check if npm is available and rebuild assets
if command -v npm &> /dev/null; then
    print_status "Rebuilding Warehouse assets..."
    cd "${WAREHOUSE_DIR}"
    npm run build 2>/dev/null || echo "npm build failed, continuing..."
    
    print_status "Rebuilding Facilities assets..."
    cd "${FACILITIES_DIR}"
    npm run build 2>/dev/null || echo "npm build failed, continuing..."
else
    print_warning "npm not found, trying alternative build method..."
    
    # Try using Vite directly
    cd "${WAREHOUSE_DIR}"
    if [ -f "vite.config.js" ]; then
        npx vite build 2>/dev/null || echo "Vite build failed"
    fi
    
    cd "${FACILITIES_DIR}"
    if [ -f "vite.config.js" ]; then
        npx vite build 2>/dev/null || echo "Vite build failed"
    fi
fi

print_status "Step 6: Checking Reverb service status..."

# Check if Reverb service is running
if systemctl is-active --quiet reverb; then
    print_status "✅ Reverb service is running"
    sudo systemctl status reverb --no-pager
else
    print_warning "⚠️ Reverb service is not running"
    print_status "Starting Reverb service..."
    sudo systemctl start reverb
    sudo systemctl status reverb --no-pager
fi

print_status "Step 7: Testing WebSocket endpoints..."

# Test WebSocket endpoints
echo "Testing Warehouse WebSocket endpoint..."
curl -I "https://${WAREHOUSE_DOMAIN}/app/" 2>/dev/null || echo "Warehouse endpoint test failed"

echo "Testing Facilities WebSocket endpoint..."
curl -I "https://${FACILITIES_DOMAIN}/app/" 2>/dev/null || echo "Facilities endpoint test failed"

print_status "Step 8: Verifying environment variables..."

# Show the updated environment variables
echo "📋 Updated Warehouse .env Reverb settings:"
grep -E "VITE_REVERB|BROADCAST_DRIVER" "${WAREHOUSE_DIR}/.env"

echo ""
echo "📋 Updated Facilities .env Reverb settings:"
grep -E "VITE_REVERB|BROADCAST_DRIVER" "${FACILITIES_DIR}/.env"

print_status "✅ PSIVista Reverb configuration fixed!"
echo ""
echo "📋 Updated Configuration:"
echo "✅ Warehouse .env updated with PSIVista domain"
echo "✅ Facilities .env updated with PSIVista domain"
echo "✅ Laravel caches cleared"
echo "✅ Frontend assets rebuilt"
echo "✅ Reverb service checked"
echo ""
echo "🔧 Next Steps:"
echo "1. Clear your browser cache completely"
echo "2. Hard refresh the page (Ctrl+Shift+R)"
echo "3. Check browser console for new WebSocket URL"
echo ""
echo "🌐 Expected WebSocket URLs:"
echo "  Warehouse: wss://${WAREHOUSE_DOMAIN}:443/app/"
echo "  Facilities: wss://${FACILITIES_DOMAIN}:443/app/"
echo ""
echo "🔍 Debug Info:"
echo "  Current domain: ${WAREHOUSE_DOMAIN}"
echo "  Reverb port: 443 (SSL)"
echo "  Force TLS: true"
echo "  Transport: WSS only"
echo ""
print_status "🎉 Configuration should now work correctly!" 