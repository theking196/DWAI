<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\AIOutput;
use App\Services\AI\AIService;
use App\Services\DWJobs;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function generateText(Request $request, int $sessionId)
    {
        $session = Session::findOrFail($sessionId);
        
        // Queue for async
        DWJobs::generateText($sessionId, $request->prompt, [
            'save_to_draft' => $request->get('save_to_draft', false),
        ]);
        
        return response()->json(['queued' => true]);
    }

    public function generateImage(Request $request, int $sessionId)
    {
        $session = Session::findOrFail($sessionId);
        
        DWJobs::generateImage($sessionId, $request->prompt);
        
        return response()->json(['queued' => true]);
    }

    public function outputs(int $sessionId)
    {
        return AIOutput::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($o) => $o->getSummary());
    }
}
