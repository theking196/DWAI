<?php

namespace App\Services;

use App\Jobs\ScheduledBackupJob;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class ScheduledBackupService
{
    /**
     * Check if scheduled backups are enabled.
     */
    public function isEnabled(): bool
    {
        return Setting::getBool('backup.scheduled_enabled', false);
    }

    /**
     * Get schedule configuration.
     */
    public function getSchedule(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'frequency' => Setting::get('backup.frequency', 'daily'), // daily, weekly, monthly
            'time' => Setting::get('backup.time', '02:00'), // HH:MM
            'day' => Setting::get('backup.day', 'sunday'), // for weekly
            'retention_days' => Setting::getInt('backup.retention_days', 30),
            'compress' => Setting::getBool('backup.compress', true),
        ];
    }

    /**
     * Configure scheduled backup.
     */
    public function configure(array $config): void
    {
        if (isset($config['enabled'])) {
            Setting::set('backup.scheduled_enabled', $config['enabled'], 'boolean');
        }
        if (isset($config['frequency'])) {
            Setting::set('backup.frequency', $config['frequency']);
        }
        if (isset($config['time'])) {
            Setting::set('backup.time', $config['time']);
        }
        if (isset($config['day'])) {
            Setting::set('backup.day', $config['day']);
        }
        if (isset($config['retention_days'])) {
            Setting::set('backup.retention_days', $config['retention_days'], 'integer');
        }
        if (isset($config['compress'])) {
            Setting::set('backup.compress', $config['compress'], 'boolean');
        }
    }

    /**
     * Trigger manual backup (queue it).
     */
    public function triggerManual(?int $userId = null): array
    {
        $schedule = $this->getSchedule();
        
        ScheduledBackupJob::dispatch($userId, $schedule['compress'], $schedule['retention_days']);
        
        return [
            'queued' => true,
            'schedule' => $schedule,
        ];
    }

    /**
     * Get cron expression for schedule.
     */
    public function getCronExpression(): string
    {
        $schedule = $this->getSchedule();
        
        $time = explode(':', $schedule['time']);
        $hour = $time[0] ?? '2';
        $minute = $time[1] ?? '0';

        return match($schedule['frequency']) {
            'daily' => "{$minute} {$hour} * * *",
            'weekly' => "{$minute} {$hour} * * " . match($schedule['day']) {
                'sunday' => 0,
                'monday' => 1,
                default => 0,
            },
            'monthly' => "{$minute} {$hour} 1 * *",
            default => "{$minute} {$hour} * * *",
        };
    }
}
