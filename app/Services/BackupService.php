<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Session;
use App\Models\CanonEntry;
use App\Models\ReferenceImage;
use App\Models\AIOutput;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Models\Conflict;
use App\Models\TimelineEvent;
use App\Models\Embedding;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    /**
     * Create a full backup of all data.
     */
    public function createBackup(?int $userId = null): array
    {
        $timestamp = now()->format('Y-m-d_His');
        $filename = "dwai_backup_{$timestamp}.json";
        
        $data = [
            'metadata' => [
                'version' => '1.0',
                'created_at' => now()->toISOString(),
                'user_id' => $userId,
            ],
            'projects' => $this->exportProjects($userId),
            'sessions' => $this->exportSessions($userId),
            'canon' => $this->exportCanon($userId),
            'references' => $this->exportReferences($userId),
            'outputs' => $this->exportOutputs($userId),
            'settings' => $this->exportSettings(),
            'activity_log' => $this->exportActivityLog($userId),
            'conflicts' => $this->exportConflicts($userId),
            'timeline' => $this->exportTimeline($userId),
        ];

        // Store locally
        $path = "backups/{$filename}";
        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT));

        return [
            'filename' => $filename,
            'path' => $path,
            'size' => strlen(json_encode($data)),
            'created_at' => now()->toISOString(),
        ];
    }

    protected function exportProjects(?int $userId): array
    {
        $query = Project::query();
        if ($userId) $query->where('user_id', $userId);
        
        return $query->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'type' => $p->type,
                'visual_style_image' => $p->visual_style_image,
                'visual_style_description' => $p->visual_style_description,
                'style_images' => $p->style_images,
                'metadata' => $p->metadata,
                'status' => $p->status,
                'created_at' => $p->created_at->toISOString(),
            ];
        })->toArray();
    }

    protected function exportSessions(?int $userId): array
    {
        $query = Session::query();
        if ($userId) $query->where('user_id', $userId);
        
        return $query->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'project_id' => $s->project_id,
                'name' => $s->name,
                'description' => $s->description,
                'notes' => $s->notes,
                'temp_notes' => $s->temp_notes,
                'draft_text' => $s->draft_text,
                'session_references' => $s->session_references,
                'status' => $s->status,
                'type' => $s->type,
                'created_at' => $s->created_at->toISOString(),
            ];
        })->toArray();
    }

    protected function exportCanon(?int $userId): array
    {
        $query = CanonEntry::query();
        if ($userId) $query->where('user_id', $userId);
        
        return $query->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'project_id' => $c->project_id,
                'title' => $c->title,
                'type' => $c->type,
                'content' => $c->content,
                'tags' => $c->tags,
                'importance' => $c->importance,
                'metadata' => $c->metadata,
                'created_at' => $c->created_at->toISOString(),
            ];
        })->toArray();
    }

    protected function exportReferences(?int $userId): array
    {
        $query = ReferenceImage::query();
        if ($userId) $query->where('user_id', $userId);
        
        return $query->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'project_id' => $r->project_id,
                'title' => $r->title,
                'description' => $r->description,
                'url' => $r->url,
                'path' => $r->path,
                'tags' => $r->tags,
                'is_style_reference' => $r->is_style_reference,
                'created_at' => $r->created_at->toISOString(),
            ];
        })->toArray();
    }

    protected function exportOutputs(?int $userId): array
    {
        $query = AIOutput::query();
        if ($userId) $query->whereHas('session', fn($q) => $q->where('user_id', $userId));
        
        return $query->get()->map(function ($o) {
            return [
                'id' => $o->id,
                'session_id' => $o->session_id,
                'prompt' => $o->prompt,
                'result' => $o->result,
                'type' => $o->type,
                'model' => $o->model,
                'status' => $o->status,
                'version' => $o->version,
                'created_at' => $o->created_at->toISOString(),
            ];
        })->toArray();
    }

    protected function exportSettings(): array
    {
        return Setting::all()->map(fn($s) => [
            'key' => $s->key,
            'value' => $s->value,
            'type' => $s->type,
        ])->toArray();
    }

    protected function exportActivityLog(?int $userId): array
    {
        $query = ActivityLog::query();
        if ($userId) $query->where('user_id', $userId);
        
        return $query->orderBy('created_at', 'desc')->limit(1000)->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'event_type' => $a->event_type,
                'entity_type' => $a->entity_type,
                'entity_id' => $a->entity_id,
                'description' => $a->description,
                'created_at' => $a->created_at->toISOString(),
            ])->toArray();
    }

    protected function exportConflicts(?int $userId): array
    {
        $query = Conflict::query();
        if ($userId) $query->where('user_id', $userId);
        
        return $query->get()->map(fn($c) => [
            'id' => $c->id,
            'project_id' => $c->project_id,
            'type' => $c->type,
            'description' => $c->description,
            'severity' => $c->severity,
            'status' => $c->status,
            'created_at' => $c->created_at->toISOString(),
        ])->toArray();
    }

    protected function exportTimeline(?int $userId): array
    {
        $query = TimelineEvent::query();
        if ($userId) $query->whereHas('project', fn($q) => $q->where('user_id', $userId));
        
        return $query->get()->map(fn($t) => [
            'id' => $t->id,
            'project_id' => $t->project_id,
            'title' => $t->title,
            'description' => $t->description,
            'order_index' => $t->order_index,
            'timestamp' => $t->timestamp,
            'created_at' => $t->created_at->toISOString(),
        ])->toArray();
    }

    /**
     * List available backups.
     */
    public function listBackups(): array
    {
        $files = Storage::disk('local')->files('backups');
        
        return array_map(fn($f) => [
            'filename' => basename($f),
            'path' => $f,
            'size' => Storage::disk('local')->size($f),
            'modified' => Storage::disk('local)->lastModified($f),
        ], $files);
    }

    /**
     * Restore from backup.
     */
    public function restoreBackup(string $filename): bool
    {
        $path = "backups/{$filename}";
        
        if (!Storage::disk('local')->exists($path)) {
            return false;
        }

        $data = json_decode(Storage::disk('local')->get($path), true);
        
        // Would implement restore logic here
        // For safety, this should be a separate admin action
        
        return true;
    }
}
