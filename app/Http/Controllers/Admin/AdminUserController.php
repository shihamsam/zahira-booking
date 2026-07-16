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
    public function index()
    {
        return Inertia::render('Admin/Admins/Index', [
            'admins' => User::orderBy('name')->get(['id', 'name', 'email', 'created_at']),
        ]);
    }

    public function store(StoreAdminUserRequest $request)
    {
        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Admin account created.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot remove your own account.');
        }

        if (User::count() <= 1) {
            return back()->with('error', 'At least one admin account must remain.');
        }

        $user->delete();

        return back()->with('success', 'Admin account removed.');
    }
}
