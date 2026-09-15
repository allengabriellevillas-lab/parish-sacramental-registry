<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class EnsureUserHasRole { public function handle(Request $request, Closure $next, ...$roles) { if (!$request->user() || !in_array($request->user()->role,$roles,true)) return response()->json(['error'=>'Forbidden'],403); return $next($request); } }
