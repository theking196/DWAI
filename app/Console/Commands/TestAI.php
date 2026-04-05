<?php

namespace App\Console\Commands;

use App\Services\AI\AIService;
use Illuminate\Console\Command;

class TestAI extends Command
{
    protected $signature = 'ai:test {type=text : Type of generation: text, image, storyboard}';
    protected $description = 'Test the AI service with mock provider';

    public function handle()
    {
        $type = $this->argument('type');
        $ai = app(AIService::class);

        $this->info('AI Provider: ' . $ai->getProvider()->getName());
        $this->info('Available: ' . ($ai->isAvailable() ? 'Yes' : 'No'));
        $this->line('');

        match($type) {
            'text' => $this->testText($ai),
            'image' => $this->testImage($ai),
            'storyboard' => $this->testStoryboard($ai),
            default => $this->error('Unknown type: ' . $type),
        };
    }

    protected function testText(AIService $ai)
    {
        $this->info('Testing text generation...');
        
        $result = $ai->generateText('Write a creative scene about a hero at a crossroads', [
            'project' => ['name' => 'Test Project'],
            'session' => ['name' => 'Test Session'],
        ]);

        if ($result['success']) {
            $this->info('✅ Text generated!');
            $this->line('');
            $this->line($result['content']);
            $this->line('');
            $this->line('Model: ' . $result['model']);
        } else {
            $this->error('❌ Failed: ' . ($result['error'] ?? 'Unknown'));
        }
    }

    protected function testImage(AIService $ai)
    {
        $this->info('Testing image generation...');
        
        $result = $ai->generateImage('A brave hero standing at a crossroads', [
            'style_images' => [],
        ]);

        if ($result['success']) {
            $this->info('✅ Image generated!');
            $this->line('URL: ' . $result['images'][0]['url']);
            $this->line('Seed: ' . $result['images'][0]['seed']);
            $this->line('Model: ' . $result['model']);
        } else {
            $this->error('❌ Failed: ' . ($result['error'] ?? 'Unknown'));
        }
    }

    protected function testStoryboard(AIService $ai)
    {
        $this->info('Testing storyboard generation...');
        
        $result = $ai->generateStoryboard('A hero embarks on a journey', [
            'frame_count' => 4,
            'project' => ['name' => 'Test Project'],
        ]);

        if ($result['success']) {
            $this->info('✅ Storyboard generated!');
            $this->line('Frames: ' . $result['total_frames']);
            
            foreach ($result['frames'] as $frame) {
                $this->line("  Frame {$frame['frame_number']}: {$frame['image_url']}");
            }
        } else {
            $this->error('❌ Failed: ' . ($result['error'] ?? 'Unknown'));
        }
    }
}
