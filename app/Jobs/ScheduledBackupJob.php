<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScheduledBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public ?int $userId = null,
        public bool $compress = true,
        public int $retentionDays = 30
    ) {}

    public function handle(): void
    {
        $service = app(\App\Services\BackupService::class);
        
        try {
            $backup = $service->createBackup($this->userId);
            
            Log::info('Scheduled backup completed', [
                'filename' => $backup['filename'],
                'size' => $backup['size'],
            ]);

            // Clean old backups
            $this->cleanupOldBackups($service);

            return $backup;
            
        } catch (\Exception $e) {
            Log::error('Scheduled backup failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function cleanupOldBackups(BackupService $service): void
    {
        $backups = $service->listBackups();
        $cutoff = now()->subDays($this->retentionDays);

        foreach ($backups as $backup) {
            if ($backup['modified'] < $cutoff->timestamp) {
                // Would delete old backup
                Log::info("Would delete old backup: {$backup['filename']}");
            }
        }
    }
}
