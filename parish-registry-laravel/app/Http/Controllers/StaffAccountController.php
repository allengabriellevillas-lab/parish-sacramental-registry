<?php

namespace App\Http\Controllers;

use App\Models\Parish;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StaffAccountController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->can_manage_parishes, 403);
        return response()->json(['data' => User::where('role', 'staff')->with('parish:id,name')->orderBy('full_name')->get()->map(fn (User $user) => [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'email' => $user->email,
            'parish_id' => $user->parish_id,
            'parish_name' => $user->parish?->name,
            'is_active' => $user->is_active,
        ])]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->can_manage_parishes, 403);
        $request->merge([
            'full_name' => trim((string) $request->input('full_name')),
            'username' => trim((string) $request->input('username')),
            'email' => trim((string) $request->input('email')) ?: null,
        ]);
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:150',
            'username' => 'required|string|max:80|alpha_dash|unique:staff_users,username',
            'email' => 'nullable|email|max:150|unique:staff_users,email',
            'parish_id' => 'required|integer|exists:parishes,id',
            'password' => 'required|string|min:8|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Check the staff account details.', 'errors' => $validator->errors()->all()], 422);
        }

        $parish = Parish::whereKey($request->integer('parish_id'))->where('is_active', true)->first();
        if (! $parish) {
            return response()->json(['error' => 'Choose an active parish.'], 422);
        }

        $user = User::create([
            'full_name' => trim(strip_tags($request->input('full_name'))),
            'username' => trim($request->input('username')),
            'email' => $request->filled('email') ? trim($request->input('email')) : null,
            'parish_id' => $parish->id,
            'password_hash' => Hash::make($request->input('password')),
            'role' => 'staff',
            'is_active' => true,
        ]);
        if ($request->user()->role === 'admin') AdminController::log($request, 'staff.created', 'staff', $user->id, 'Created staff account ' . $user->username);

        return response()->json(['data' => [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'email' => $user->email,
            'parish_id' => $user->parish_id,
            'parish_name' => $parish->name,
            'is_active' => true,
        ]], 201);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->can_manage_parishes, 403);
        abort_unless($user->role === 'staff', 404);
        if ($user->is($request->user()) && $request->has('is_active') && ! $request->boolean('is_active')) {
            return response()->json(['error' => 'You cannot deactivate your own account.'], 422);
        }

        $values = [];
        if ($request->has('is_active')) {
            $active = $request->boolean('is_active');
            $values['is_active'] = $active;
        }
        if ($request->has('parish_id')) {
            $parish = Parish::whereKey($request->integer('parish_id'))->where('is_active', true)->first();
            if (! $parish) {
                return response()->json(['error' => 'Choose an active parish.'], 422);
            }
            $values['parish_id'] = $parish->id;
        }

        $active = $values['is_active'] ?? $user->is_active;
        if (! $active && $user->can_manage_parishes && ! User::where('can_manage_parishes', true)->where('is_active', true)->whereKeyNot($request->user()->id)->exists()) {
            return response()->json(['error' => 'Keep at least one active parish manager account.'], 422);
        }

        $user->update($values);
        if ($request->user()->role === 'admin' && $values) AdminController::log($request, 'staff.updated', 'staff', $user->id, 'Updated staff access or parish assignment for ' . $user->username);
        return response()->json(['data' => ['id' => $user->id, 'is_active' => $user->is_active, 'parish_id' => $user->parish_id]]);
    }

    public function resetPassword(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->can_manage_parishes, 403);
        abort_unless($user->role === 'staff', 404);
        $validator = Validator::make($request->all(), ['password' => 'required|string|min:8|max:200']);
        if ($validator->fails()) {
            return response()->json(['error' => 'Password must contain at least 8 characters.', 'errors' => $validator->errors()->all()], 422);
        }
        $user->update(['password_hash' => Hash::make($request->input('password'))]);
        if ($request->user()->role === 'admin') AdminController::log($request, 'staff.password_reset', 'staff', $user->id, 'Reset password for ' . $user->username);
        return response()->json(['data' => ['id' => $user->id, 'password_reset' => true]]);
    }
}
