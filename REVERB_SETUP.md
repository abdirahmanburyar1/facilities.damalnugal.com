# Reverb Real-time Setup Guide

## Environment Variables

Add these variables to your `.env` file:

### Reverb Configuration
```env
# Broadcasting Driver
BROADCAST_DRIVER=reverb

# Reverb App Credentials
REVERB_APP_ID=your-reverb-app-id
REVERB_APP_KEY=your-reverb-app-key
REVERB_APP_SECRET=your-reverb-app-secret

# Reverb Server Configuration
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_PATH=/api

# Reverb Server Settings
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_SERVER_PATH=

# Frontend Environment Variables (for Vite)
VITE_REVERB_APP_KEY=your-reverb-app-key
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
```

### Remove Old Pusher Variables
```env
# Remove or comment out these old Pusher variables
# PUSHER_APP_ID=
# PUSHER_APP_KEY=
# PUSHER_APP_SECRET=
# PUSHER_APP_CLUSTER=
# VITE_PUSHER_APP_KEY=
# VITE_PUSHER_APP_CLUSTER=
```

## Starting Reverb Server

1. **Install Reverb globally:**
   ```bash
   composer global require laravel/reverb
   ```

2. **Start Reverb server:**
   ```bash
   reverb start
   ```

3. **For production, use a process manager like Supervisor:**
   ```bash
   # Example supervisor config
   [program:reverb]
   process_name=%(program_name)s
   command=reverb start
   autostart=true
   autorestart=true
   user=www-data
   redirect_stderr=true
   stdout_logfile=/var/log/reverb.log
   ```

## Testing Real-time Events

1. **Check if Reverb is running:**
   ```bash
   curl http://127.0.0.1:8080/api/health
   ```

2. **Test broadcasting:**
   ```php
   // In your Laravel app
   broadcast(new \App\Events\InventoryEvent());
   ```

3. **Check browser console for connection logs**

## Troubleshooting

- **Connection refused:** Make sure Reverb server is running
- **Authentication failed:** Check REVERB_APP_KEY and REVERB_APP_SECRET
- **Events not received:** Verify channel names and event listeners
- **CORS issues:** Check allowed origins in reverb.php config

## Kafka Integration

Kafka continues to work alongside Reverb for inter-app communication:
- **Kafka:** App-to-app communication (warehouse ↔ facilities)
- **Reverb:** Real-time frontend updates 