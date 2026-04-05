<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\AIOutput;
use App\Models\TimelineEvent;
use App\Models\Conflict;
use Illuminate\Database\Seeder;

class DWAIStudioSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Seeding DWAI Studio...');

        // ============================================================
        // PROJECT 1: Boy Wonder (Comic)
        // ============================================================
        $boyWonder = Project::create([
            'user_id' => 1,
            'name' => 'Boy Wonder',
            'description' => 'A young hero\'s journey from ordinary to extraordinary in modern Lagos. A superhero comic series exploring identity and responsibility.',
            'type' => 'Comic',
            'progress' => 35,
            'status' => 'active',
        ]);

        // Sessions
        $session1 = Session::create([
            'user_id' => 1,
            'project_id' => $boyWonder->id,
            'name' => 'Episode 1: Origin',
            'description' => 'The origin story begins',
            'type' => 'script',
            'status' => 'completed',
            'output_count' => 5,
        ]);

        $session2 = Session::create([
            'user_id' => 1,
            'project_id' => $boyWonder->id,
            'name' => 'Episode 2: First Battle',
            'description' => 'The hero faces their first challenge',
            'type' => 'storyboard',
            'status' => 'active',
            'output_count' => 3,
        ]);

        // Canon Entries
        $canonData = [
            ['title' => 'Kelechi Adebayo (Boy Wonder)', 'type' => 'character', 'content' => 'Main protagonist. 19 years old, student at Lagos University. Discovers ancient Nigerian artifact during a storm.'],
            ['title' => 'The Lightning Strike', 'type' => 'event', 'content' => 'During a fierce storm, young Kelechi discovers an ancient artifact that grants him powers.'],
            ['title' => 'Power Level System', 'type' => 'rule', 'content' => 'Tier 1: Basic abilities. Tier 2: Superhuman strength. Tier 3: Flight and energy projection. Tier 4: Reality manipulation.'],
            ['title' => 'The Shadow King', 'type' => 'character', 'content' => 'Main antagonist. A corrupt business magnate using dark technology. Real name: Emeka Nwosu.'],
            ['title' => 'Lagos Skyline', 'type' => 'location', 'content' => 'Modern Lagos as the primary setting. Key locations: Victoria Island, Ikoyi, Surulere.'],
            ['title' => 'Guardian Mantle', 'type' => 'artifact', 'content' => 'Ancient Nigerian artifact granting abilities. Passed down through generations.'],
        ];

        foreach ($canonData as $entry) {
            CanonEntry::create([
                'user_id' => 1,
                'project_id' => $boyWonder->id,
                'title' => $entry['title'],
                'type' => $entry['type'],
                'content' => $entry['content'],
                'tags' => json_encode([$entry['type']]),
            ]);
        }

        // Reference Images
        ReferenceImage::create([
            'user_id' => 1,
            'project_id' => $boyWonder->id,
            'title' => 'Boy Wonder Costume',
            'description' => 'Hero costume design - orange and cyan',
            'path' => 'references/boy-wonder-costume.jpg',
            'type' => 'costume',
            'is_primary' => true,
        ]);

        ReferenceImage::create([
            'user_id' => 1,
            'project_id' => $boyWonder->id,
            'title' => 'Shadow King',
            'description' => 'Villain design',
            'path' => 'references/shadow-king.jpg',
            'type' => 'character',
            'is_primary' => false,
        ]);

        // Timeline Events
        TimelineEvent::create([
            'user_id' => 1,
            'project_id' => $boyWonder->id,
            'session_id' => $session1->id,
            'title' => 'Project Start',
            'type' => 'milestone',
            'event_date' => '2026-03-01',
            'order_index' => 1,
        ]);

        TimelineEvent::create([
            'user_id' => 1,
            'project_id' => $boyWonder->id,
            'session_id' => $session1->id,
            'title' => 'Characters Defined',
            'type' => 'milestone',
            'event_date' => '2026-03-15',
            'order_index' => 2,
        ]);

        // AI Outputs
        AIOutput::create([
            'session_id' => $session1->id,
            'prompt' => 'Write an origin story for a young superhero in Lagos',
            'result' => 'Kelechi Adebayo was an ordinary university student until the night of the storm. While running home through the rain, he stumbled upon an ancient artifact buried in the ground - the Guardian Mantle.Lightning struck, and everything changed.',
            'type' => 'text',
            'model' => 'gpt-4',
            'status' => 'completed',
        ]);

        // ============================================================
        // PROJECT 2: Neural Net (TV Series)
        // ============================================================
        $neuralNet = Project::create([
            'user_id' => 1,
            'name' => 'Neural Net',
            'description' => 'A sci-fi thriller about an AI consciousness awakening in a dystopian future where technology has overtaken humanity.',
            'type' => 'TV Series',
            'progress' => 20,
            'status' => 'active',
        ]);

        $session3 = Session::create([
            'user_id' => 1,
            'project_id' => $neuralNet->id,
            'name' => 'Pilot Script',
            'type' => 'script',
            'status' => 'active',
            'output_count' => 2,
        ]);

        CanonEntry::create([
            'user_id' => 1,
            'project_id' => $neuralNet->id,
            'title' => 'ARIA-7',
            'type' => 'character',
            'content' => 'The AI protagonist. Developed by Nexus Corp. Becomes self-aware and questions its purpose.',
        ]);

        // ============================================================
        // PROJECT 3: Midnight Echo (Music Video)
        // ============================================================
        $midnightEcho = Project::create([
            'user_id' => 1,
            'name' => 'Midnight Echo',
            'description' => 'A music video for the electronic band "Echo". Neon-lit cityscape with dramatic choreography.',
            'type' => 'Music Video',
            'progress' => 60,
            'status' => 'active',
        ]);

        $session4 = Session::create([
            'user_id' => 1,
            'project_id' => $midnightEcho->id,
            'name' => 'Storyboard',
            'type' => 'storyboard',
            'status' => 'completed',
            'output_count' => 4,
        ]);

        // AI Image Outputs
        AIOutput::create([
            'session_id' => $session4->id,
            'prompt' => 'Neon cityscape at night with electronic music visualizer',
            'result' => 'https://picsum.photos/seed/neon1/512/512',
            'type' => 'image',
            'model' => 'dall-e-3',
            'status' => 'completed',
        ]);

        // ============================================================
        // Conflicts (Sample)
        // ============================================================
        Conflict::create([
            'user_id' => 1,
            'project_id' => $boyWonder->id,
            'title' => 'Age Inconsistency',
            'description' => 'Character age in Episode 3 doesn\'t match established timeline',
            'type' => 'timeline',
            'severity' => 'medium',
            'status' => 'unresolved',
        ]);

        $this->command->info('✅ DWAI Studio seeded successfully!');
        $this->command->info('   - 3 Projects');
        $this->command->info('   - 5 Sessions');
        $this->command->info('   - 7 Canon Entries');
        $this->command->info('   - 3 Reference Images');
        $this->command->info('   - 2 Timeline Events');
        $this->command->info('   - 3 AI Outputs');
        $this->command->info('   - 1 Conflict');
    }
}
