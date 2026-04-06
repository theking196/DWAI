<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return response()->json(Setting::all());
    }

    public function get(string $key)
    {
        $value = Setting::get($key);
        return response()->json(['key' => $key, 'value' => $value]);
    }

    public function set(Request $request, string $key)
    {
        Setting::set($key, $request->value, $request->type ?? 'string');
        return response()->json(['success' => true]);
    }

    public function delete(string $key)
    {
        Setting::forget($key);
        return response()->json(['success' => true]);
    }

    public function aiConfig()
    {
        return response()->json(Setting::getAIConfig());
    }

    public function behavior()
    {
        return response()->json(Setting::getBehaviorConfig());
    }
}
