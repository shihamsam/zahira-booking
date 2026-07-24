<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        return Inertia::render('Admin/Admins/Index', [
            'admins' => User::orderBy('name')->get(['id', 'name', 'email', 'role', 'created_at']),
        ]);
    }

    public function store(StoreAdminUserRequest $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validated();

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'admin',
        ]);

        return back()->with('success', 'Admin account created.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot remove your own account.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'The super admin account cannot be removed.');
        }

        if (User::count() <= 1) {
            return back()->with('error', 'At least one admin account must remain.');
        }

        $user->delete();

        return back()->with('success', 'Admin account removed.');
    }
}
