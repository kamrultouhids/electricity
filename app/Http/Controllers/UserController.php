<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List users with search and filter.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search by name or email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by user type
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->input('user_type'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('users.index', [
            'users'     => $users,
            'userTypes' => User::USER_TYPES,
        ]);
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        return view('users.create', [
            'userTypes' => User::USER_TYPES,
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email',
            'password'  => 'required|string|min:8|confirmed',
            'user_type' => ['required', Rule::in(array_keys(User::USER_TYPES))],
            'status'    => 'required|in:0,1',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        User::create($data);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully!');
    }

    /**
     * Show a single user.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the edit form.
     */
    public function edit(User $user)
    {
        return view('users.edit', [
            'user'      => $user,
            'userTypes' => User::USER_TYPES,
        ]);
    }

    /**
     * Update a user.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'  => 'nullable|string|min:8|confirmed',
            'user_type' => ['required', Rule::in(array_keys(User::USER_TYPES))],
            'status'    => 'required|in:0,1',
        ]);

        // Only change the password when a new one is provided.
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['updated_by'] = auth()->id();

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully!');
    }
}
