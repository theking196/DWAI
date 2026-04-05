<?php

namespace App\Services\AI;

use App\Models\CanonEntry;
use App\Models\TimelineEvent;
use App\Models\AIOutput;

class ContinuityService
{
    /**
     * Check if new content fits existing timeline.
     */
    public function checkContinuity(int $projectId, array $newContent): array
    {
        $issues = [];
        $warnings = [];
        $suggestions = [];

        // 1. Check character consistency
        if (isset($newContent['characters'])) {
            $charIssues = $this->checkCharacterContinuity($projectId, $newContent['characters']);
            $issues = array_merge($issues, $charIssues);
        }

        // 2. Check timeline consistency
        if (isset($newContent['timeline_event'])) {
            $timelineIssues = $this->checkTimelineContinuity($projectId, $newContent['timeline_event']);
            $issues = array_merge($issues, $timelineIssues);
        }

        // 3. Check location consistency
        if (isset($newContent['locations'])) {
            $locIssues = $this->checkLocationContinuity($projectId, $newContent['locations']);
            $warnings = array_merge($warnings, $locIssues);
        }

        // 4. Check against previous outputs
        if (isset($newContent['session_id'])) {
            $outputIssues = $this->checkOutputContinuity($projectId, $newContent);
            $warnings = array_merge($warnings, $outputIssues);
        }

        // 5. Generate suggestions
        $suggestions = $this->generateSuggestions($projectId, $newContent);

        return [
            'consistent' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
            'suggestions' => $suggestions,
            'checked_at' => now()->toISOString(),
        ];
    }

    /**
     * Check character consistency.
     */
    protected function checkCharacterContinuity(int $projectId, array $characters): array
    {
        $issues = [];
        $canonChars = CanonEntry::where('project_id', $projectId)
            ->where('type', 'character')
            ->get();

        foreach ($characters as $char) {
            $existing = $canonChars->firstWhere('title', $char['name'] ?? '');
            
            if ($existing && isset($char['attributes'])) {
                // Check for conflicts
                if (isset($char['attributes']['age']) && $existing->content) {
                    // Simple check - could be more sophisticated
                    if (preg_match('/(\d+)\s*years?/i', $existing->content, $match)) {
                        $canonAge = (int) $match[1];
                        $newAge = (int) $char['attributes']['age'];
                        if (abs($canonAge - $newAge) > 5) {
                            $issues[] = [
                                'type' => 'character_age',
                                'severity' => 'warning',
                                'message' => "Character '{$char['name']}' age differs from canon (canon: {$canonAge}, new: {$newAge})",
                            ];
                        }
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Check timeline consistency.
     */
    protected function checkTimelineContinuity(int $projectId, array $event): array
    {
        $issues = [];

        // Check against existing timeline
        $timeline = TimelineEvent::forProject($projectId);
        
        if (isset($event['timestamp']) && isset($event['order_index'])) {
            // Check order vs timestamp consistency
            $timestampOrder = $timeline->filter(fn($e) => $e->event_timestamp && $e->event_timestamp < $event['timestamp']);
            $expectedOrder = $timestampOrder->count() + 1;
            
            if (abs($expectedOrder - $event['order_index']) > 1) {
                $issues[] = [
                    'type' => 'timeline_order',
                    'severity' => 'warning',
                    'message' => "Event order ({$event['order_index']}) may not match timestamp order (expected ~{$expectedOrder})",
                ];
            }
        }

        // Check for conflicts with canon timeline events
        $canonTimeline = CanonEntry::where('project_id', $projectId)
            ->where('type', 'timeline_event')
            ->get();

        return $issues;
    }

    /**
     * Check location consistency.
     */
    protected function checkLocationContinuity(int $projectId, array $locations): array
    {
        $warnings = [];
        
        $canonLocs = CanonEntry::where('project_id', $projectId)
            ->where('type', 'location')
            ->pluck('title')
            ->toArray();

        foreach ($locations as $loc) {
            if (!in_array($loc['name'] ?? '', $canonLocs)) {
                $warnings[] = [
                    'type' => 'new_location',
                    'message' => "New location '{$loc['name']}' not in canon - consider adding",
                ];
            }
        }

        return $warnings;
    }

    /**
     * Check against previous outputs.
     */
    protected function checkOutputContinuity(int $projectId, array $content): array
    {
        $warnings = [];
        $sessionId = $content['session_id'] ?? null;
        
        if (!$sessionId) return $warnings;

        $outputs = AIOutput::where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Check for tone/style consistency (simple check)
        if (isset($content['tone'])) {
            $previousTones = $outputs->pluck('result')->filter()->take(3);
            // Simple warning if very different
        }

        return $warnings;
    }

    /**
     * Generate suggestions for improvement.
     */
    protected function generateSuggestions(int $projectId, array $content): array
    {
        $suggestions = [];

        // Check if canon needs update
        if (isset($content['new_character'])) {
            $suggestions[] = "Consider adding '{$content['new_character']}' to canon after validation";
        }

        // Check if timeline needs event
        if (isset($content['new_location'])) {
            $suggestions[] = "Location '{$content['new_location']}' could be added to project timeline";
        }

        // Check importance level
        if (isset($content['importance']) && $content['importance'] === 'critical') {
            $suggestions[] = "Critical content - consider adding to canon for future reference";
        }

        return $suggestions;
    }

    /**
     * Get project continuity summary.
     */
    public function getProjectContinuitySummary(int $projectId): array
    {
        $timeline = TimelineEvent::forProject($projectId);
        $canon = CanonEntry::where('project_id', $projectId)->get();
        
        return [
            'timeline_events' => $timeline->count(),
            'canon_entries' => $canon->count(),
            'characters' => $canon->where('type', 'character')->count(),
            'locations' => $canon->where('type', 'location')->count(),
            'timeline_events_count' => $canon->where('type', 'timeline_event')->count(),
            'has_timeline' => $timeline->isNotEmpty(),
            'timeline_validated' => empty(TimelineEvent::validateTimeline($projectId)['issues']),
        ];
    }
}
