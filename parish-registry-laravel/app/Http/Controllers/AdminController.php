<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function overview()
    {
        return response()->json(['data' => [
            'parishes' => DB::table('parishes')->count(),
            'active_parishes' => DB::table('parishes')->where('is_active', true)->count(),
            'staff_accounts' => DB::table('staff_users')->where('role', 'staff')->count(),
            'active_staff_accounts' => DB::table('staff_users')->where('role', 'staff')->where('is_active', true)->count(),
            'recent_activity' => DB::table('admin_activity_logs')->count(),
        ]]);
    }

    public function activity()
    {
        $rows = DB::table('admin_activity_logs')
            ->leftJoin('staff_users', 'admin_activity_logs.admin_user_id', '=', 'staff_users.id')
            ->orderByDesc('admin_activity_logs.created_at')
            ->limit(100)
            ->get(['admin_activity_logs.id', 'admin_activity_logs.action', 'admin_activity_logs.target_type', 'admin_activity_logs.target_id', 'admin_activity_logs.summary', 'admin_activity_logs.created_at', 'staff_users.full_name as admin_name']);

        return response()->json(['data' => $rows]);
    }

    public function system()
    {
        try {
            DB::select('select 1');
            $database = 'Connected';
        } catch (\Throwable) {
            $database = 'Unavailable';
        }

        return response()->json(['data' => [
            'application' => config('app.name'),
            'environment' => config('app.env'),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'database' => $database,
            'public_storage' => Storage::disk('public')->exists('.gitignore') || is_writable(storage_path('app/public')) ? 'Available' : 'Check storage permissions',
        ]]);
    }

    public static function log(Request $request, string $action, ?string $type, ?int $id, string $summary): void
    {
        DB::table('admin_activity_logs')->insert([
            'admin_user_id' => $request->user()?->id,
            'action' => $action,
            'target_type' => $type,
            'target_id' => $id,
            'summary' => $summary,
            'created_at' => now(),
        ]);
    }
}
