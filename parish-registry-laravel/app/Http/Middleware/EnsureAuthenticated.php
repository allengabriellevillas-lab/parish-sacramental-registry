<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class EnsureAuthenticated { public function handle(Request $request, Closure $next) { return $request->user() ? $next($request) : response()->json(['error'=>'Authentication required'],401); } }
