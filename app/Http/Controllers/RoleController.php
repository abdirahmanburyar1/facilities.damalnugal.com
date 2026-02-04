<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index()
    {
        $roles = Role::all();
        
        return Inertia::render('Role/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Get all roles for AJAX requests.
     */
    public function getAllRoles()
    {
        $roles = Role::all();
        
        return response()->json([
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'role' => $role
            ]);
        }

        // Check if request from settings page
        $isFromSettings = $request->header('X-From-Settings') || 
                         ($request->has('_headers') && $request->_headers && isset($request->_headers['X-From-Settings']));
        
        if ($isFromSettings) {
            return redirect()->route('settings.index', ['tab' => 'roles'])->with('success', 'Role created successfully');
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully');
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);

        $role->update(['name' => $request->name]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'role' => $role
            ]);
        }

        // Check if request from settings page
        $isFromSettings = $request->header('X-From-Settings') || 
                         ($request->has('_headers') && $request->_headers && isset($request->_headers['X-From-Settings']));
        
        if ($isFromSettings) {
            return redirect()->route('settings.index', ['tab' => 'roles'])->with('success', 'Role updated successfully');
        }

        return redirect()->route('roles.index')->with('success', 'Role updated successfully');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            // Check if request from settings page
            $isFromSettings = request()->header('X-From-Settings') || 
                             (request()->has('_headers') && request()->_headers && isset(request()->_headers['X-From-Settings']));
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the admin role'
                ], 403);
            }
            
            if ($isFromSettings) {
                return redirect()->route('settings.index', ['tab' => 'roles'])->with('error', 'Cannot delete the admin role');
            }
            
            return redirect()->route('roles.index')->with('error', 'Cannot delete the admin role');
        }
        
        $role->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        }

        // Check if request from settings page
        $isFromSettings = request()->header('X-From-Settings') || 
                         (request()->has('_headers') && request()->_headers && isset(request()->_headers['X-From-Settings']));
        
        if ($isFromSettings) {
            return redirect()->route('settings.index', ['tab' => 'roles'])->with('success', 'Role deleted successfully');
        }

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully');
    }
    
    /**
     * Assign roles to a user.
     */
    public function assignRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user->syncRoles($request->roles);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Roles assigned successfully',
                'user' => $user->load('roles')
            ]);
        }

        return back()->with('success', 'Roles assigned successfully');
    }
}
