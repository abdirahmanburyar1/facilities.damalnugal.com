<template>
  <div v-if="debug" class="mt-4 p-4 bg-gray-100 border border-gray-300 rounded-lg shadow">
    <div class="flex justify-between items-center mb-2">
      <div class="flex items-center">
        <h3 class="text-lg font-semibold">Pusher Debug Panel</h3>
        <div 
          :class="{
            'ml-3 w-4 h-4 rounded-full': true,
            'bg-green-500 animate-pulse': status === 'connected',
            'bg-red-500': status === 'failed' || status === 'error',
            'bg-yellow-500 animate-pulse': status === 'connecting',
            'bg-gray-500': status === 'disconnected'
          }"
          :title="status"
        ></div>
      </div>
      <div class="flex items-center space-x-2">
        <span 
          :class="{
            'px-2 py-1 text-xs font-bold rounded': true,
            'bg-green-100 text-green-800': status === 'connected',
            'bg-red-100 text-red-800': status === 'failed' || status === 'error',
            'bg-yellow-100 text-yellow-800': status === 'connecting',
            'bg-gray-100 text-gray-800': status === 'disconnected'
          }"
        >
          {{ status }}
        </span>
        <button 
          @click="toggleCollapsed" 
          class="text-sm px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
        >
          {{ collapsed ? 'Expand' : 'Collapse' }}
        </button>
      </div>
    </div>
    
    <div v-if="!collapsed">
      <div class="grid grid-cols-2 gap-4 mb-3">
        <div>
          <div class="flex items-center">
            <span class="font-medium mr-2">Channel:</span>
            <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-sm font-mono">{{ channel }}</span>
          </div>
          <div class="mt-1">
            <span class="font-medium mr-2">Last Event:</span>
            <span>{{ lastEventTime ? new Date(lastEventTime).toLocaleString() : 'None' }}</span>
          </div>
        </div>
        <div>
          <div>
            <span class="font-medium mr-2">Events Received:</span>
            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm font-bold">{{ eventCount }}</span>
          </div>
          <div class="mt-1">
            <span class="font-medium mr-2">Listen For:</span>
            <span class="font-mono text-sm bg-gray-200 px-1 rounded">.inventory-updated</span>
          </div>
        </div>
      </div>
      
      <div v-if="error" class="mt-2 p-2 bg-red-100 border border-red-300 rounded text-red-800">
        <p class="font-medium">Error:</p>
        <p class="text-sm">{{ error }}</p>
      </div>
      
      <div class="mt-3">
        <h4 class="font-medium mb-1 flex items-center">
          Recent Events:
          <span v-if="events.length === 0" class="ml-2 text-sm text-gray-500">(None yet)</span>
        </h4>
        <div v-if="events.length > 0" class="max-h-32 overflow-y-auto bg-white border border-gray-200 rounded p-2">
          <div 
            v-for="(event, index) in events.slice().reverse()" 
            :key="index" 
            :class="{
              'text-sm border-b border-gray-100 last:border-b-0 py-1': true,
              'bg-green-50': event.name === 'inventory-updated',
              'bg-yellow-50': event.name === 'connecting' || event.name === 'initialized',
              'bg-red-50': event.name === 'error' || event.name === 'failed'
            }"
          >
            <div class="flex justify-between">
              <span class="font-bold">{{ event.name }}</span>
              <span class="text-gray-500">{{ new Date(event.time).toLocaleTimeString() }}</span>
            </div>
            <div v-if="event.data" class="mt-1 text-xs font-mono bg-gray-50 p-1 rounded">
              {{ typeof event.data === 'object' ? JSON.stringify(event.data) : event.data }}
            </div>
          </div>
        </div>
      </div>
      
      <div class="mt-3 text-xs text-gray-500 grid grid-cols-2 gap-2">
        <div>
          <p><span class="font-medium">Pusher Key:</span> <span class="font-mono">{{ pusherKey }}</span></p>
          <p><span class="font-medium">Pusher Cluster:</span> <span class="font-mono">{{ pusherCluster }}</span></p>
        </div>
        <div>
          <p><span class="font-medium">Broadcast Driver:</span> <span class="font-mono">{{ broadcastDriver }}</span></p>
          <p><span class="font-medium">Listening Since:</span> {{ initTime }}</p>
        </div>
      </div>
      
      <div class="mt-3 flex space-x-2">
        <button 
          @click="testEvent" 
          class="text-sm px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition-colors"
        >
          Test Event
        </button>
        <button 
          @click="clearEvents" 
          class="text-sm px-2 py-1 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
        >
          Clear Events
        </button>
        <button 
          @click="checkConnection" 
          class="text-sm px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
        >
          Check Connection
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  debug: {
    type: Boolean,
    default: true
  },
  channel: {
    type: String,
    default: 'inventory'
  }
});

const status = ref('disconnected');
const events = ref([]);
const error = ref(null);
const lastEventTime = ref(null);
const collapsed = ref(localStorage.getItem('pusherDebugCollapsed') === 'true');
const pusherConnection = ref(null);
const pusherChannel = ref(null);
const initTime = ref(new Date().toLocaleString());
const broadcastDriver = ref('unknown');

const pusherKey = ref(import.meta.env.VITE_PUSHER_APP_KEY || 'unknown');
const pusherCluster = ref(import.meta.env.VITE_PUSHER_APP_CLUSTER || 'unknown');

const eventCount = computed(() => events.value.length);

// Auto-expand the panel if new events come in and it's not too frequent
let expandTimeout = null;
watch(events, (newEvents, oldEvents) => {
  if (newEvents.length > oldEvents.length && collapsed.value) {
    // Only auto-expand if we haven't received an event in the last 5 seconds
    if (expandTimeout) clearTimeout(expandTimeout);
    expandTimeout = setTimeout(() => {
      collapsed.value = false;
      localStorage.setItem('pusherDebugCollapsed', 'false');
    }, 300);
  }
});

function toggleCollapsed() {
  collapsed.value = !collapsed.value;
  localStorage.setItem('pusherDebugCollapsed', collapsed.value);
}

function clearEvents() {
  events.value = [];
  lastEventTime.value = null;
}

function addEvent(name, data = null) {
  const now = new Date();
  events.value.push({
    name,
    data,
    time: now
  });
  
  lastEventTime.value = now;
  
  // Keep only the last 10 events
  if (events.value.length > 10) {
    events.value.shift();
  }
  
  // Log to console as well
  console.log(`[PUSHER-EVENT] ${name}:`, data);
}

function testEvent() {
  addEvent('test-event-attempt', { message: 'Triggering debug endpoint...' });
  
  axios.get(route('inventories.debug-pusher'))
    .then(response => {
      addEvent('debug-pusher-triggered', response.data);
    })
    .catch(err => {
      error.value = `Failed to trigger debug event: ${err.message}`;
      addEvent('error', { message: error.value });
    });
}

function checkConnection() {
  addEvent('connection-check', { currentState: status.value });
  
  if (!window.Echo?.connector?.pusher) {
    error.value = 'Echo or Pusher not available';
    return;
  }
  
  const state = window.Echo.connector.pusher.connection.state;
  status.value = state;
  
  addEvent('connection-status', { 
    state: state,
    socketId: window.Echo.socketId(),
    channels: Object.keys(window.Echo.connector.channels.channels || {})
  });
  
  if (state !== 'connected') {
    // Try to reconnect
    window.Echo.connector.pusher.connect();
  }
}

onMounted(() => {
  // Try to get the broadcast driver
  axios.get(route('inventories.debug-pusher'))
    .then(response => {
      if (response.data?.config?.broadcast_driver) {
        broadcastDriver.value = response.data.config.broadcast_driver;
      }
    })
    .catch(() => {
      // Ignore errors
    });

  if (!window.Echo) {
    error.value = 'Echo is not available. Make sure Laravel Echo is properly configured.';
    return;
  }
  
  try {
    pusherConnection.value = window.Echo.connector.pusher.connection;
    
    // Initial state
    status.value = pusherConnection.value.state;
    
    // Connection events
    pusherConnection.value.bind('connecting', () => {
      status.value = 'connecting';
      addEvent('connecting');
    });
    
    pusherConnection.value.bind('connected', () => {
      status.value = 'connected';
      addEvent('connected', { socketId: window.Echo.socketId() });
    });
    
    pusherConnection.value.bind('disconnected', () => {
      status.value = 'disconnected';
      addEvent('disconnected');
    });
    
    pusherConnection.value.bind('failed', () => {
      status.value = 'failed';
      addEvent('failed');
    });
    
    pusherConnection.value.bind('error', (err) => {
      status.value = 'error';
      error.value = err?.message || 'Unknown error';
      addEvent('error', { message: error.value });
    });
    
    // Subscribe to channel
    pusherChannel.value = window.Echo.channel(props.channel);
    
    // Listen for the inventory-updated event
    pusherChannel.value.listen('.inventory-updated', (eventData) => {
      addEvent('inventory-updated', eventData);
      
      // Flash notification that event was received
      const notification = document.createElement('div');
      notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
      notification.textContent = 'Pusher event received!';
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.remove();
      }, 3000);
    });
    
    addEvent('initialized', { 
      channel: props.channel,
      connectionState: pusherConnection.value.state,
      socketId: window.Echo.socketId() 
    });
    
    // Check current status
    if (pusherConnection.value.state === 'connected') {
      status.value = 'connected';
    }
  } catch (err) {
    error.value = `Error setting up Pusher: ${err.message}`;
    console.error('Pusher Debug Setup Error:', err);
  }
});

onUnmounted(() => {
  // Clean up event listeners
  if (pusherConnection.value) {
    pusherConnection.value.unbind('connecting');
    pusherConnection.value.unbind('connected');
    pusherConnection.value.unbind('disconnected');
    pusherConnection.value.unbind('failed');
    pusherConnection.value.unbind('error');
  }
  
  if (pusherChannel.value) {
    window.Echo.leaveChannel(props.channel);
  }
});
</script> 