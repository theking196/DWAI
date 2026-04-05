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
        
        $options = [
            'model' => $validated['model'] ?? 'gpt-4',
            'temperature' => $validated['temperature'] ?? 0.7,
        ];
        
        if ($validated['async'] ?? true) {
            GenerateAIOutput::dispatch($session->id, $validated['prompt'], 'text', $options);
            
            return response()->json([
                'success' => true,
                'message' => 'Generation queued',
                'status' => 'pending',
            ]);
        }
        
        try {
            $output = $this->ai->generateText($session, $validated['prompt'], $options);
            
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
     * Generate image AI response - uses primary reference.
     */
    public function generateImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'prompt' => 'required|string|min:1|max:2000',
            'size' => 'nullable|in:256x256,512x512,1024x1024',
            'model' => 'nullable|string',
            'reference_id' => 'nullable|exists:reference_images,id',
            'async' => 'nullable|boolean',
        ]);
        
        $session = Session::with('project')->findOrFail($validated['session_id']);
        
        $options = [
            'size' => $validated['size'] ?? '1024x1024',
            'model' => $validated['model'] ?? 'dall-e-3',
        ];
        
        // Get reference: specific one OR primary OR first available
        $referenceId = $validated['reference_id'] ?? null;
        
        if ($referenceId) {
            // Use specific reference
            $reference = $session->project->referenceImages()->find($referenceId);
            $references = $reference ? [$reference->path] : [];
        } else {
            // Try primary first, then first available
            $primary = $session->project->referenceImages()
                ->where('is_primary', true)
                ->first();
            
            if ($primary) {
                $references = [$primary->path];
            } else {
                $first = $session->project->referenceImages()->first();
                $references = $first ? [$first->path] : [];
            }
        }
        
        $options['references'] = $references;
        
        if ($validated['async'] ?? true) {
            GenerateAIOutput::dispatch($session->id, $validated['prompt'], 'image', $options);
            
            return response()->json([
                'success' => true,
                'message' => 'Image generation queued',
                'status' => 'pending',
                'reference_used' => $referenceId ? 'specific' : ($primary->title ?? 'auto'),
            ]);
        }
        
        try {
            $output = $this->ai->generateImage($session, $validated['prompt'], $options);
            
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
