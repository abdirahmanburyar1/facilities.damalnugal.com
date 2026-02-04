<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Only authenticated users can access the inventory channel
Broadcast::channel('inventory', function ($user) {
    return auth()->check();
});

// Private channel for general inventory updates
Broadcast::channel('private-inventory', function ($user) {
    return auth()->check();
});

// Private channel for facility-specific inventory updates
Broadcast::channel('private-facility-inventory.{facilityId}', function ($user, $facilityId) {
    // Debug logging
    \Log::info('[CHANNEL-AUTH] Attempting to authorize facility channel', [
        'user_id' => $user->id ?? 'null',
        'user_facility_id' => $user->facility_id ?? 'null',
        'requested_facility_id' => $facilityId,
        'authenticated' => auth()->check(),
        'matches' => auth()->check() && $user->facility_id == $facilityId
    ]);
    
    // Only allow access if user belongs to the facility where inventory changes happen
    return auth()->check() && $user->facility_id == $facilityId;
});

// Private channel for general facility inventory updates
Broadcast::channel('private-facility-inventory', function ($user) {
    return auth()->check();
});

// Private channel for user-specific events
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for transfer events
Broadcast::channel('transfer.{id}', function ($user, $id) {
    // Allow access to all authenticated users
    return auth()->check();
});
