<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use Illuminate\Database\Seeder;

class DWAIStudioSeeder extends Seeder
{
    public function run(): void
    {
        // Create Boy Wonder project
        $project = Project::create([
            'name' => 'Boy Wonder',
            'description' => 'A young hero\'s journey from ordinary to extraordinary. Set in modern Lagos.',
            'type' => 'Comic',
            'thumbnail' => '🦸',
            'progress' => 35,
            'status' => 'active',
        ]);

        // Create Sessions
        $session1 = Session::create([
            'project_id' => $project->id,
            'name' => 'Episode 1',
            'description' => 'The origin story begins',
            'type' => 'script',
            'status' => 'completed',
            'output_count' => 5,
        ]);

        $session2 = Session::create([
            'project_id' => $project->id,
            'name' => 'Episode 2',
            'description' => 'The first battle',
            'type' => 'storyboard',
            'status' => 'active',
            'output_count' => 3,
        ]);

        // Create Canon Entries
        $canonEntries = [
            ['title' => 'The Lightning Strike', 'type' => 'event', 'content' => 'During a fierce storm, young Kelechi discovers an ancient artifact.'],
            ['title' => 'Power Level System', 'type' => 'rule', 'content' => 'Tier 1: Basic abilities. Tier 2: Superhuman strength. Tier 3: Flight and energy projection.'],
            ['title' => 'The Shadow King', 'type' => 'character', 'content' => 'Main antagonist. A corrupt business magnate using dark technology.'],
            ['title' => 'Lagos Skyline', 'type' => 'location', 'content' => 'Modern Lagos as the primary setting.'],
            ['title' => 'Guardian Mantle', 'type' => 'artifact', 'content' => 'Ancient Nigerian artifact granting abilities.'],
        ];

        foreach ($canonEntries as $entry) {
            CanonEntry::create([
                'project_id' => $project->id,
                'title' => $entry['title'],
                'type' => $entry['type'],
                'content' => $entry['content'],
                'tags' => json_encode([$entry['type']]),
            ]);
        }

        // Create Reference Images
        ReferenceImage::create([
            'project_id' => $project->id,
            'title' => 'Boy Wonder Costume',
            'description' => 'Hero costume design',
            'path' => '/assets/images/boy-wonder-costume.png',
            'type' => 'character',
        ]);

        ReferenceImage::create([
            'project_id' => $project->id,
            'title' => 'Shadow King',
            'description' => 'Villain design',
            'path' => '/assets/images/shadow-king.png',
            'type' => 'villain',
        ]);

        echo "✅ Seeded: Project, 2 Sessions, 5 Canon Entries, 2 Reference Images\n";
    }
}