<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('id', 'desc')->get();
        $roles = Role::orderBy('name')->get();
        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => ['required', Rules\Password::defaults()],
            'role'         => 'required|exists:roles,name',
            'phone'        => 'nullable|string|max:255',
            'address'      => 'nullable|string',
            'nid'          => 'nullable|string|max:255',
            'designation'  => 'nullable|string|max:255',
            'basic_salary' => 'nullable|numeric|min:0',
            'join_date'    => 'nullable|date',
            'notes'        => 'nullable|string',
            'photo'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
            'phone'        => $validated['phone'] ?? null,
            'address'      => $validated['address'] ?? null,
            'nid'          => $validated['nid'] ?? null,
            'designation'  => $validated['designation'] ?? null,
            'basic_salary' => $validated['basic_salary'] ?? 0,
            'join_date'    => $validated['join_date'] ?? null,
            'notes'        => $validated['notes'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        if ($request->hasFile('photo')) {
            $user->addMediaFromRequest('photo')->toMediaCollection('photo');
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $payments = $user->salaryPayments()->with('journal')->paginate(10);
        $paymentMethods = \App\Models\ChartOfAccount::where('is_payment_method', true)->get();
        return view('users.show', compact('user', 'payments', 'paymentMethods'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email,' . $user->id,
            'role'         => 'required|exists:roles,name',
            'phone'        => 'nullable|string|max:255',
            'address'      => 'nullable|string',
            'nid'          => 'nullable|string|max:255',
            'designation'  => 'nullable|string|max:255',
            'basic_salary' => 'nullable|numeric|min:0',
            'join_date'    => 'nullable|date',
            'notes'        => 'nullable|string',
            'photo'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user->update([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'phone'        => $validated['phone'] ?? null,
            'address'      => $validated['address'] ?? null,
            'nid'          => $validated['nid'] ?? null,
            'designation'  => $validated['designation'] ?? null,
            'basic_salary' => $validated['basic_salary'] ?? 0,
            'join_date'    => $validated['join_date'] ?? null,
            'notes'        => $validated['notes'] ?? null,
        ]);

        if (!empty($request->password)) {
            $request->validate(['password' => [Rules\Password::defaults()]]);
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$validated['role']]);

        if ($request->hasFile('photo')) {
            $user->addMediaFromRequest('photo')->toMediaCollection('photo');
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
