<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withCount('orders')->with('roles')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    // UM-002: Admin view user profile and order history
    public function show(User $user): View
    {
        $user->loadCount('orders')->load('roles');
        $orders = $user->orders()->latest()->take(10)->get();

        return view('admin.users.show', compact('user', 'orders'));
    }

    // UM-004: Admin assign or change user role
    public function assignRole(Request $request, User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.show', $user)
                ->with('error', 'You cannot change your own role.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:user,admin'],
        ]);

        $oldRole = $user->roles->pluck('name')->first() ?? 'none';
        $newRole = $validated['role'];

        $user->syncRoles([$newRole]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'user.role_changed',
            'subject_type' => 'User',
            'subject_id' => $user->id,
            'old_values' => ['role' => $oldRole],
            'new_values' => ['role' => $newRole],
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "Role updated to '{$newRole}' successfully.");
    }

    // UM-003: Admin toggle user active/suspended status
    public function toggleStatus(User $user): RedirectResponse
    {
        // Prevent admin from suspending their own account
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.show', $user)
                ->with('error', 'You cannot suspend your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $action = $user->is_active ? 'activated' : 'suspended';

        return redirect()->route('admin.users.show', $user)
            ->with('success', "Account {$action} successfully.");
    }
}
