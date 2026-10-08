<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private function shape(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'parish_id' => $user->parish_id,
            'parish_name' => $user->parish?->name,
            'can_manage_parishes' => (bool) $user->can_manage_parishes,
            'avatar_path' => $user->avatar_path,
        ];
    }

    private function loginAs(Request $request, string $role)
    {
        $key = trim(strip_tags((string) $request->input('username', '')));
        $user = User::where(fn ($query) => $query->where('username', $key)->orWhere('email', $key))
            ->where('role', $role)
            ->where('is_active', true)
            ->when($role === 'staff', fn ($query) => $query->whereHas('parish', fn ($parish) => $parish->where('is_active', true)))
            ->first();

        if (! $user || ! password_verify((string) $request->input('password', ''), $user->password_hash)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        Auth::login($user);
        $request->session()->regenerate();
        if ($role === 'admin') AdminController::log($request, 'admin.login', 'staff', $user->id, 'Administrator signed in');
        return response()->json(['data' => $this->shape($user)]);
    }

    public function login(Request $request)
    {
        return $this->loginAs($request, 'staff');
    }

    public function adminLogin(Request $request)
    {
        return $this->loginAs($request, 'admin');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['data' => true]);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $this->shape($request->user())]);
    }
}
