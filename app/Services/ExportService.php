<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\TimelineEvent;
use App\Models\AIOutput;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    /**
     * Export entire project as package.
     */
    public function exportProject(int $projectId): array
    {
        $project = Project::findOrFail($projectId);
        
        $data = [
            'metadata' => [
                'type' => 'dwai_project',
                'version' => '1.0',
                'exported_at' => now()->toISOString(),
                'project_id' => $project->id,
                'project_name' => $project->name,
            ],
            'project' => $this->exportProjectData($project),
            'sessions' => $this->exportProjectSessions($projectId),
            'canon' => $this->exportProjectCanon($projectId),
            'references' => $this->exportProjectReferences($projectId),
            'timeline' => $this->exportProjectTimeline($projectId),
        ];

        $filename = $this->sanitizeFilename($project->name) . '_' . now()->format('Y-m-d') . '.dwai';
        
        Storage::disk('local')->put("exports/{$filename}", json_encode($data, JSON_PRETTY_PRINT));

        return [
            'filename' => $filename,
            'path' => "exports/{$filename}",
            'size' => strlen(json_encode($data)),
            'project_name' => $project->name,
        ];
    }

    /**
     * Export single session as package.
     */
    public function exportSession(int $sessionId): array
    {
        $session = Session::findOrFail($sessionId);
        $project = $session->project;
        
        $data = [
            'metadata' => [
                'type' => 'dwai_session',
                'version' => '1.0',
                'exported_at' => now()->toISOString(),
                'session_id' => $session->id,
                'session_name' => $session->name,
                'project_name' => $project->name ?? null,
            ],
            'session' => $this->exportSessionData($session),
            'canon' => $this->exportSessionCanon($sessionId),
            'references' => $this->exportSessionReferences($sessionId),
            'outputs' => $this->exportSessionOutputs($sessionId),
        ];

        $filename = $this->sanitizeFilename($session->name) . '_' . now()->format('Y-m-d') . '.dwai';
        
        Storage::disk('local')->put("exports/{$filename}", json_encode($data, JSON_PRETTY_PRINT));

        return [
            'filename' => $filename,
            'path' => "exports/{$filename}",
            'size' => strlen(json_encode($data)),
            'session_name' => $session->name,
        ];
    }

    protected function exportProjectData(Project $p): array
    {
        return [
            'name' => $p->name,
            'description' => $p->description,
            'type' => $p->type,
            'visual_style_image' => $p->visual_style_image,
            'visual_style_description' => $p->visual_style_description,
            'style_images' => $p->style_images,
            'metadata' => $p->metadata,
        ];
    }

    protected function exportProjectSessions(int $projectId): array
    {
        return Session::where('project_id', $projectId)->get()
            ->map(fn($s) => $this->exportSessionData($s))
            ->toArray();
    }

    protected function exportProjectCanon(int $projectId): array
    {
        return CanonEntry::where('project_id', $projectId)->get()
            ->map(fn($c) => [
                'title' => $c->title,
                'type' => $c->type,
                'content' => $c->content,
                'tags' => $c->tags,
                'importance' => $c->importance,
                'metadata' => $c->metadata,
            ])
            ->toArray();
    }

    protected function exportProjectReferences(int $projectId): array
    {
        return ReferenceImage::where('project_id', $projectId)->get()
            ->map(fn($r) => [
                'title' => $r->title,
                'description' => $r->description,
                'url' => $r->url,
                'tags' => $r->tags,
                'is_style_reference' => $r->is_style_reference,
            ])
            ->toArray();
    }

    protected function exportProjectTimeline(int $projectId): array
    {
        return TimelineEvent::where('project_id', $projectId)->get()
            ->map(fn($t) => [
                'title' => $t->title,
                'description' => $t->description,
                'order_index' => $t->order_index,
                'timestamp' => $t->timestamp,
            ])
            ->toArray();
    }

    protected function exportSessionData(Session $s): array
    {
        return [
            'name' => $s->name,
            'description' => $s->description,
            'notes' => $s->notes,
            'temp_notes' => $s->temp_notes,
            'draft_text' => $s->draft_text,
            'session_references' => $s->session_references,
            'status' => $s->status,
            'type' => $s->type,
        ];
    }

    protected function exportSessionCanon(int $sessionId): array
    {
        // Get canon referenced in session
        $session = Session::find($sessionId);
        $refs = $session->session_references ?? [];
        
        return CanonEntry::whereIn('id', $refs)->get()
            ->map(fn($c) => [
                'title' => $c->title,
                'type' => $c->type,
                'content' => $c->content,
            ])
            ->toArray();
    }

    protected function exportSessionReferences(int $sessionId): array
    {
        $session = Session::find($sessionId);
        $refs = $session->session_references ?? [];
        
        return ReferenceImage::whereIn('id', $refs)->get()
            ->map(fn($r) => [
                'title' => $r->title,
                'url' => $r->url,
            ])
            ->toArray();
    }

    protected function exportSessionOutputs(int $sessionId): array
    {
        return AIOutput::where('session_id', $sessionId)->get()
            ->map(fn($o) => [
                'prompt' => $o->prompt,
                'result' => $o->result,
                'type' => $o->type,
                'model' => $o->model,
                'status' => $o->status,
            ])
            ->toArray();
    }

    protected function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($name));
    }

    /**
     * List exports.
     */
    public function listExports(): array
    {
        $files = Storage::disk('local')->files('exports');
        
        return array_map(fn($f) => [
            'filename' => basename($f),
            'size' => Storage::disk('local')->size($f),
            'modified' => Storage::disk('local')->lastModified($f),
        ], $files);
    }
}
