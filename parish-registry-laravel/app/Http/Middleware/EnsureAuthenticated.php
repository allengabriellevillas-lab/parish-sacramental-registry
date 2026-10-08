<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class EnsureAuthenticated { public function handle(Request $request, Closure $next) { $user=$request->user(); $validWorkspace=$user&&($user->role==='admin'||($user->role==='staff'&&$user->parish?->is_active)); return $user&&$user->is_active&&$validWorkspace ? $next($request) : response()->json(['error'=>'Authentication required'],401); } }
