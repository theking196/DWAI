<?php

namespace App\Http\Controllers\DWAI;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Services\ExportService;
use App\Services\ImportService;
use App\Services\ScheduledBackupService;
use Illuminate\Http\Request;

class ImportExportController extends Controller
{
    // Backup
    public function createBackup()
    {
        $service = app(BackupService::class);
        return response()->json($service->createBackup(auth()->id()));
    }

    public function listBackups()
    {
        return response()->json(app(BackupService::class)->listBackups());
    }

    public function restoreBackup(Request $request)
    {
        return response()->json(app(BackupService::class)->restoreBackup($request->filename));
    }

    // Export
    public function exportProject(int $id)
    {
        return response()->json(app(ExportService::class)->exportProject($id));
    }

    public function exportSession(int $id)
    {
        return response()->json(app(ExportService::class)->exportSession($id));
    }

    // Import
    public function importProject(Request $request)
    {
        return response()->json(app(ImportService::class)->importProjectPackage(
            $request->file('file'),
            $request->all()
        ));
    }

    public function importToSession(Request $request, int $id)
    {
        return response()->json(app(ImportService::class)->importTextToSession(
            $id,
            $request->content,
            $request->get('target', 'notes'),
            $request->get('mode', 'append')
        ));
    }

    // Scheduled
    public function getSchedule()
    {
        return response()->json(app(ScheduledBackupService::class)->getSchedule());
    }

    public function configureSchedule(Request $request)
    {
        app(ScheduledBackupService::class)->configure($request->all());
        return response()->json(['success' => true]);
    }

    public function triggerBackup()
    {
        return response()->json(app(ScheduledBackupService::class)->triggerManual(auth()->id()));
    }
}
