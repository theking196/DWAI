<?php

namespace App\Services\DWAI;

use App\Models\TimelineEvent;
use App\Models\Project;

class TimelineService
{
    public function create(int $projectId, array $data): TimelineEvent
    {
        return TimelineEvent::create([
            'project_id' => $projectId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'order_index' => $data['order_index'] ?? 0,
            'timestamp' => $data['timestamp'] ?? null,
        ]);
    }

    public function update(TimelineEvent $event, array $data): TimelineEvent
    {
        $event->update($data);
        return $event;
    }

    public function reorder(int $projectId, array $order): void
    {
        foreach ($order as $index => $eventId) {
            TimelineEvent::where('id', $eventId)->update(['order_index' => $index]);
        }
    }

    public function getProjectTimeline(int $projectId): array
    {
        return TimelineEvent::where('project_id', $projectId)
            ->orderBy('order_index')
            ->get()
            ->toArray();
    }

    public function validate(int $projectId): array
    {
        $events = TimelineEvent::where('project_id', $projectId)->orderBy('order_index')->get();
        $issues = [];

        // Check order
        for ($i = 1; $i < $events->count(); $i++) {
            if ($events[$i]->order_index <= $events[$i-1]->order_index) {
                $issues[] = [
                    'type' => 'order',
                    'message' => 'Events not in sequential order',
                ];
                break;
            }
        }

        return $issues;
    }

    public function autoOrder(int $projectId): void
    {
        $events = TimelineEvent::where('project_id', $projectId)->get();
        
        foreach ($events as $index => $event) {
            $event->update(['order_index' => $index]);
        }
    }
}
