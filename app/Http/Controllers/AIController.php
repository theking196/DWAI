<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\AIOutput;
use App\Jobs\GenerateAIOutput;
use App\Services\AI\AIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    protected AIService $ai;
    
    public function __construct(AIService $ai)
    {
        $this->ai = $ai;
    }
    
    /**
     * Generate text AI response (async via queue).
     */
    public function generateText(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'prompt' => 'required|string|min:1|max:5000',
            'model' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'async' => 'nullable|boolean',
        ]);
        
        $session = Session::with('project')->findOrFail($validated['session_id']);
        
        // Check for async mode
        if ($validated['async'] ?? true) {
            // Dispatch to queue
            GenerateAIOutput::dispatch(
                $session->id,
                $validated['prompt'],
                'text',
                [
                    'model' => $validated['model'] ?? 'gpt-4',
                    'temperature' => $validated['temperature'] ?? 0.7,
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Generation queued',
                'status' => 'pending',
            ]);
        }
        
        // Sync mode (immediate)
        try {
            $output = $this->ai->generateText(
                $session,
                $validated['prompt'],
                [
                    'model' => $validated['model'] ?? 'gpt-4',
                    'temperature' => $validated['temperature'] ?? 0.7,
                ]
            );
            
            return response()->json([
                'success' => true,
                'output' => [
                    'id' => $output->id,
                    'text' => $output->result,
                    'model' => $output->model,
                    'status' => 'completed',
                    'created_at' => $output->created_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Text Generation Failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'AI generation failed. Please try again.',
            ], 500);
        }
    }
    
    /**
     * Generate image AI response (async via queue).
     */
    public function generateImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'prompt' => 'required|string|min:1|max:2000',
            'size' => 'nullable|in:256x256,512x512,1024x1024',
            'model' => 'nullable|string',
            'async' => 'nullable|boolean',
        ]);
        
        $session = Session::with('project')->findOrFail($validated['session_id']);
        
        // Async mode (default)
        if ($validated['async'] ?? true) {
            GenerateAIOutput::dispatch(
                $session->id,
                $validated['prompt'],
                'image',
                [
                    'size' => $validated['size'] ?? '1024x1024',
                    'model' => $validated['model'] ?? 'dall-e-3',
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Image generation queued',
                'status' => 'pending',
            ]);
        }
        
        // Sync mode
        try {
            $output = $this->ai->generateImage(
                $session,
                $validated['prompt'],
                [
                    'size' => $validated['size'] ?? '1024x1024',
                    'model' => $validated['model'] ?? 'dall-e-3',
                ]
            );
            
            return response()->json([
                'success' => true,
                'output' => [
                    'id' => $output->id,
                    'url' => $output->result,
                    'prompt' => $validated['prompt'],
                    'model' => $output->model,
                    'status' => 'completed',
                    'created_at' => $output->created_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Image Generation Failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'error' => 'Image generation failed. Please try again.',
            ], 500);
        }
    }
    
    /**
     * Get session AI outputs with status.
     */
    public function outputs(int $sessionId): JsonResponse
    {
        $session = Session::findOrFail($sessionId);
        $outputs = $session->aiOutputs()
            ->select('id', 'session_id', 'prompt', 'result', 'type', 'model', 'status', 'error_message', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'outputs' => $outputs,
        ]);
    }
    
    /**
     * Check output status.
     */
    public function status(int $outputId): JsonResponse
    {
        $output = AIOutput::findOrFail($outputId);
        
        return response()->json([
            'success' => true,
            'status' => $output->status,
            'result' => $output->status === 'completed' ? $output->result : null,
            'error' => $output->status === 'failed' ? $output->error_message : null,
        ]);
    }
    
    /**
     * List available AI providers.
     */
    public function providers(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'providers' => $this->ai->getAvailableProviders(),
        ]);
    }
}
