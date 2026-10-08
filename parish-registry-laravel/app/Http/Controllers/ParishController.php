<?php

namespace App\Http\Controllers;

use App\Models\Parish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParishController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Parish::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function management(Request $request)
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->can_manage_parishes, 403);
        return response()->json(['data' => Parish::orderBy('name')->get(['id', 'name', 'is_active'])]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->can_manage_parishes, 403);
        $name = trim(strip_tags((string) $request->input('name')));
        $validator = Validator::make(['name' => $name], ['name' => 'required|string|max:200|unique:parishes,name']);
        if ($validator->fails()) {
            return response()->json(['error' => 'Enter a unique parish name.', 'errors' => $validator->errors()->all()], 422);
        }

        $parish = Parish::create(['name' => $name, 'is_active' => true]);
        if ($request->user()->role === 'admin') AdminController::log($request, 'parish.created', 'parish', $parish->id, 'Created parish ' . $parish->name);
        return response()->json(['data' => ['id' => $parish->id, 'name' => $parish->name, 'is_active' => $parish->is_active]], 201);
    }

    public function update(Request $request, Parish $parish)
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->can_manage_parishes, 403);
        $active = $request->boolean('is_active');
        $name = trim(strip_tags((string) $request->input('name', $parish->name)));
        $validator = Validator::make(['name' => $name], ['name' => 'required|string|max:200|unique:parishes,name,' . $parish->id]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Enter a unique parish name.', 'errors' => $validator->errors()->all()], 422);
        }
        if (! $active && $request->has('is_active') && (int) $request->user()->parish_id === (int) $parish->id) {
            return response()->json(['error' => 'You cannot deactivate your own parish.'], 422);
        }
        $parish->update(['name' => $name, 'is_active' => $request->has('is_active') ? $active : $parish->is_active]);
        if ($request->user()->role === 'admin') AdminController::log($request, 'parish.updated', 'parish', $parish->id, 'Updated parish ' . $parish->name);
        return response()->json(['data' => ['id' => $parish->id, 'name' => $parish->name, 'is_active' => $parish->is_active]]);
    }
}
