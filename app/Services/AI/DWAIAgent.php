<?php

namespace App\Services\AI;

/**
 * Main DWAI Agent - combines agent loop with response assembly.
 */
class DWAIAgent
{
    protected AIAgent $agent;

    public function __construct()
    {
        $this->agent = new AIAgent();
    }

    /**
     * Run agent and assemble response.
     */
    public function run(string $prompt, array $context = []): array
    {
        // Run the agent loop
        $result = $this->agent->run($prompt, $context);
        
        // Assemble final response
        $response = ResponseAssembler::assemble($result);
        
        // Add director notes if applicable
        $response['director_notes'] = $this->generateDirectorNotes($result);
        
        return $response;
    }

    /**
     * Generate director notes based on output.
     */
    protected function generateDirectorNotes(array $result): ?string
    {
        $output = $result['final_output'] ?? [];
        
        if (isset($output['frames'])) {
            $count = count($output['frames']);
            return "Generated {$count} frames. Consider reviewing timing and transitions.";
        }
        
        if (isset($output['images'])) {
            return "Image generated. Style consistency can be adjusted in post-processing.";
        }
        
        if (isset($output['content'])) {
            $length = strlen($output['content']);
            return "Text generated ({$length} chars). Check for tone consistency.";
        }
        
        return null;
    }

    /**
     * Run and return raw result (for debugging).
     */
    public function runRaw(string $prompt, array $context = []): array
    {
        return $this->agent->run($prompt, $context);
    }

    /**
     * Get registered tools.
     */
    public function getTools(): array
    {
        return $this->agent->getTools();
    }
}
