<?php

namespace App\Services\AI;

use App\Models\Conflict;

class ConflictResolutionAssistant
{
    public function suggestResolution(Conflict $conflict): array
    {
        return match($conflict->type) {
            'duplicate_character' => $this->suggestDuplicateCharacter($conflict),
            'timestamp_order' => $this->suggestTimestampOrder($conflict),
            'self_contradiction' => $this->suggestContradiction($conflict),
            'missing_reference' => $this->suggestMissingReference($conflict),
            'new_character_in_content' => $this->suggestNewCharacter($conflict),
            'orphaned_references' => $this->suggestOrphanedReferences($conflict),
            'multiple_active_sessions' => $this->suggestActiveSessions($conflict),
            default => $this->genericSuggestion($conflict),
        };
    }

    protected function suggestDuplicateCharacter(Conflict $conflict): array
    {
        return [
            'suggestions' => [
                ['type' => 'merge', 'description' => 'Merge both entries into one', 'action' => 'Edit primary entry', 'safety' => 'safe'],
                ['type' => 'keep_latest', 'description' => 'Keep most recent, remove duplicates', 'action' => 'Delete old entries', 'safety' => 'caution'],
                ['type' => 'separate', 'description' => 'Treat as different characters', 'action' => 'Rename to be unique', 'safety' => 'safe'],
            ],
            'recommended' => 'merge',
            'reason' => 'Merging preserves all information',
        ];
    }

    protected function suggestTimestampOrder(Conflict $conflict): array
    {
        return [
            'suggestions' => [
                ['type' => 'reorder', 'description' => 'Auto-reorder by timestamps', 'action' => 'Run auto-order', 'safety' => 'safe'],
                ['type' => 'adjust', 'description' => 'Edit timestamps manually', 'action' => 'Update event times', 'safety' => 'safe'],
            ],
            'recommended' => 'reorder',
            'reason' => 'Quick and maintains sequence',
        ];
    }

    protected function suggestContradiction(Conflict $conflict): array
    {
        return [
            'suggestions' => [
                ['type' => 'clarify', 'description' => 'Clarify current state', 'action' => 'Edit entry', 'safety' => 'safe'],
                ['type' => 'split', 'description' => 'Split into past and present', 'action' => 'Create timeline event', 'safety' => 'caution'],
            ],
            'recommended' => 'clarify',
            'reason' => 'Prevents future confusion',
        ];
    }

    protected function suggestMissingReference(Conflict $conflict): array
    {
        return [
            'suggestions' => [
                ['type' => 'reupload', 'description' => 'Re-upload missing file', 'action' => 'Upload new', 'safety' => 'safe'],
                ['type' => 'remove', 'description' => 'Remove link', 'action' => 'Clear image field', 'safety' => 'caution'],
            ],
            'recommended' => 'reupload',
            'reason' => 'Preserves original intent',
        ];
    }

    protected function suggestNewCharacter(Conflict $conflict): array
    {
        return [
            'suggestions' => [
                ['type' => 'add_canon', 'description' => 'Add to canon after validate', 'action' => 'Create character entry', 'safety' => 'safe'],
                ['type' => 'check_exists', 'description' => 'Check for existing match', 'action' => 'Search canon', 'safety' => 'safe'],
            ],
            'recommended' => 'add_canon',
            'reason' => 'Makes character available for future',
        ];
    }

    protected function suggestOrphanedReferences(Conflict $conflict): array
    {
        return [
            'suggestions' => [
                ['type' => 'link_session', 'description' => 'Link to session', 'action' => 'Associate with session', 'safety' => 'safe'],
                ['type' => 'link_canon', 'description' => 'Link to canon', 'action' => 'Associate with entry', 'safety' => 'safe'],
            ],
            'recommended' => 'link_session',
            'reason' => 'Makes references usable',
        ];
    }

    protected function suggestActiveSessions(Conflict $conflict): array
    {
        return [
            'suggestions' => [
                ['type' => 'organize', 'description' => 'Give each a purpose', 'action' => 'Update names/notes', 'safety' => 'safe'],
            ],
            'recommended' => 'organize',
            'reason' => 'Prevents timeline confusion',
        ];
    }

    protected function genericSuggestion(Conflict $conflict): array
    {
        return [
            'suggestions' => [['type' => 'review', 'description' => 'Manual review required', 'action' => 'Manual check', 'safety' => 'safe']],
            'recommended' => 'review',
            'reason' => 'Unknown conflict type',
        ];
    }

    public function explainConflict(Conflict $conflict): string
    {
        $explanations = [
            'duplicate_character' => 'Same character defined multiple times - causes AI confusion.',
            'timestamp_order' => 'Timeline events not in chronological order - story continuity risk.',
            'self_contradiction' => 'Entry has contradictory info - leads to inconsistent output.',
            'missing_reference' => 'Referenced file not found - may not display correctly.',
        ];
        return $explanations[$conflict->type] ?? 'Requires manual review.';
    }
}