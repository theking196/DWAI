<?php

namespace App\Services\AI;

use App\Models\CanonEntry;
use App\Models\TimelineEvent;
use App\Models\ReferenceImage;

class ConflictDetectionService
{
    /**
     * Run all conflict checks for a project.
     */
    public function detectAllConflicts(int $projectId): array
    {
        return [
            'canon_contradictions' => $this->detectCanonContradictions($projectId),
            'timeline_clashes' => $this->detectTimelineClashes($projectId),
            'missing_references' => $this->detectMissingReferences($projectId),
            'invalid_character_usage' => $this->detectInvalidCharacterUsage($projectId),
            'invalid_location_usage' => $this->detectInvalidLocationUsage($projectId),
        ];
    }

    /**
     * Detect canon contradictions.
     */
    public function detectCanonContradictions(int $projectId): array
    {
        $contradictions = [];
        $canon = CanonEntry::where('project_id', $projectId)->get();

        // Group by type
        $characters = $canon->where('type', 'character');
        
        // Check for duplicate names
        $names = $characters->pluck('title')->toArray();
        $duplicates = array_unique(array_diff_assoc($names, array_unique($names)));
        
        if (!empty($duplicates)) {
            foreach ($duplicates as $name) {
                $entries = $characters->where('title', $name);
                if ($entries->count() > 1) {
                    $contradictions[] = [
                        'type' => 'duplicate_character',
                        'severity' => 'error',
                        'message' => "Character '{$name}' defined {$entries->count()} times",
                        'entries' => $entries->pluck('id')->toArray(),
                    ];
                }
            }
        }

        // Check for conflicting attributes (simple check)
        foreach ($characters as $char) {
            if ($char->content && $char->metadata) {
                // Could add more sophisticated conflict detection
            }
        }

        // Check timeline events for conflicts
        $timeline = $canon->where('type', 'timeline_event');
        foreach ($timeline as $event) {
            if ($event->content) {
                // Check for contradictory language
                $contradictions[] = $this->checkContradictoryLanguage($event);
            }
        }

        return array_filter($contradictions);
    }

    /**
     * Check for contradictory language in content.
     */
    protected function checkContradictoryLanguage($entry): ?array
    {
        // Very simple check - could be expanded
        $content = strtolower($entry->content ?? '');
        
        // Example: "is dead" and "is alive" in same entry
        if (str_contains($content, 'is dead') && str_contains($content, 'is alive')) {
            return [
                'type' => 'self_contradiction',
                'severity' => 'warning',
                'entry_id' => $entry->id,
                'message' => "Entry contains contradictory statements about life/death",
            ];
        }

        return null;
    }

    /**
     * Detect timeline clashes.
     */
    public function detectTimelineClashes(int $projectId): array
    {
        $clashes = [];

        // Check timeline events order vs timestamp
        $events = TimelineEvent::forProject($projectId);
        
        $timestamps = $events->filter(fn($e) => $e->event_timestamp);
        $ordered = $events->filter(fn($e) => $e->order_index > 0);

        // Check for timestamp order mismatch
        foreach ($events as $i => $event) {
            if ($event->event_timestamp && $i > 0) {
                $prev = $events[$i - 1];
                if ($prev->event_timestamp && $event->event_timestamp < $prev->event_timestamp) {
                    $clashes[] = [
                        'type' => 'timestamp_order',
                        'severity' => 'error',
                        'event_1' => ['id' => $prev->id, 'title' => $prev->title, 'timestamp' => $prev->event_timestamp->toISOString()],
                        'event_2' => ['id' => $event->id, 'title' => $event->title, 'timestamp' => $event->event_timestamp->toISOString()],
                        'message' => "Event '{$event->title}' has earlier timestamp than '{$prev->title}'",
                    ];
                }
            }
        }

        // Check for overlapping sessions
        $sessions = \App\Models\Session::where('project_id', $projectId)->where('status', 'active')->get();
        if ($sessions->count() > 1) {
            $clashes[] = [
                'type' => 'multiple_active_sessions',
                'severity' => 'warning',
                'sessions' => $sessions->pluck('id')->toArray(),
                'message' => "Multiple active sessions - may cause timeline confusion",
            ];
        }

        return $clashes;
    }

    /**
     * Detect missing references.
     */
    public function detectMissingReferences(int $projectId): array
    {
        $missing = [];

        // Check canon entries that reference images but don't have them
        $canonWithImages = CanonEntry::where('project_id', $projectId)
            ->whereNotNull('image')
            ->get();

        foreach ($canonWithImages as $entry) {
            if ($entry->image && !file_exists(public_path('storage/' . $entry->image))) {
                $missing[] = [
                    'type' => 'missing_canon_image',
                    'severity' => 'warning',
                    'entry_id' => $entry->id,
                    'image_path' => $entry->image,
                    'message' => "Canon image not found for '{$entry->title}'",
                ];
            }
        }

        // Check for orphaned references (references not linked to anything)
        $refs = ReferenceImage::where('project_id', $projectId)
            ->whereNull('session_id')
            ->whereNull('canon_entry_id')
            ->get();

        if ($refs->isNotEmpty()) {
            $missing[] = [
                'type' => 'orphaned_references',
                'severity' => 'info',
                'count' => $refs->count(),
                'message' => "{$refs->count()} reference images not linked to any session or canon",
            ];
        }

        return $missing;
    }

    /**
     * Detect invalid character usage in content.
     */
    public function detectInvalidCharacterUsage(int $projectId): array
    {
        $invalid = [];

        // Get all characters from canon
        $characters = CanonEntry::where('project_id', $projectId)
            ->where('type', 'character')
            ->pluck('title')
            ->toArray();

        if (empty($characters)) {
            return $invalid;
        }

        // Check session drafts and notes for undefined characters
        $sessions = \App\Models\Session::where('project_id', $projectId)->get();

        foreach ($sessions as $session) {
            $content = ($session->draft_text ?? '') . ($session->temp_notes ?? '') . ($session->notes ?? '');
            
            foreach ($characters as $char) {
                // Check if character mentioned with "new" or "introduce"
                if (preg_match("/new.*" . preg_quote($char, '/') . "/i", $content)) {
                    $invalid[] = [
                        'type' => 'new_character_in_content',
                        'severity' => 'info',
                        'session_id' => $session->id,
                        'character' => $char,
                        'message' => "Character '{$char}' mentioned as 'new' in session '{$session->name}' - consider adding to canon",
                    ];
                }
            }
        }

        return $invalid;
    }

    /**
     * Detect invalid location usage.
     */
    public function detectInvalidLocationUsage(int $projectId): array
    {
        $invalid = [];

        $locations = CanonEntry::where('project_id', $projectId)
            ->where('type', 'location')
            ->pluck('title')
            ->toArray();

        if (empty($locations)) {
            return $invalid;
        }

        $sessions = \App\Models\Session::where('project_id', $projectId)->get();

        foreach ($sessions as $session) {
            $content = ($session->draft_text ?? '') . ($session->temp_notes ?? '');

            foreach ($locations as $loc) {
                if (preg_match("/travel.*" . preg_quote($loc, '/') . "/i", $content)) {
                    $invalid[] = [
                        'type' => 'travel_to_defined_location',
                        'severity' => 'info',
                        'session_id' => $session->id,
                        'location' => $loc,
                        'message' => "Travel to location '{$loc}' in session '{$session->name}'",
                    ];
                }
            }
        }

        return $invalid;
    }

    /**
     * Get conflict summary.
     */
    public function getSummary(int $projectId): array
    {
        $conflicts = $this->detectAllConflicts($projectId);
        
        $total = 0;
        $bySeverity = ['error' => 0, 'warning' => 0, 'info' => 0];

        foreach ($conflicts as $category) {
            foreach ($category as $conflict) {
                $total++;
                $severity = $conflict['severity'] ?? 'info';
                $bySeverity[$severity] = ($bySeverity[$severity] ?? 0) + 1;
            }
        }

        return [
            'total_conflicts' => $total,
            'by_severity' => $bySeverity,
            'has_errors' => $bySeverity['error'] > 0,
            'has_warnings' => $bySeverity['warning'] > 0,
            'checked_at' => now()->toISOString(),
        ];
    }
}
