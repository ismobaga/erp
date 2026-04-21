<?php

namespace App\Jobs;

use App\Models\ReportSchedule;
use App\Services\AuditTrailService;
use App\Services\ReportExportService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateScheduledReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $scheduleId,
    ) {}

    public function handle(ReportExportService $exportService, AuditTrailService $auditTrail): void
    {
        /** @var ReportSchedule|null $schedule */
        $schedule = ReportSchedule::find($this->scheduleId);

        if (!$schedule || !$schedule->isActive()) {
            return;
        }

        $start = $schedule->start_date
            ? Carbon::parse($schedule->start_date)->startOfDay()
            : now()->startOfYear()->startOfDay();

        $end = $schedule->end_date
            ? Carbon::parse($schedule->end_date)->endOfDay()
            : now()->endOfDay();

        $result = $exportService->generate(
            $start,
            $end,
            (array) ($schedule->selected_modules ?? []),
            (string) $schedule->export_format,
            (bool) $schedule->include_charts,
            $schedule->owner_id,
        );

        $schedule->forceFill([
            'last_executed_at' => now(),
            'last_path' => $result['path'],
            'next_execution_at' => $schedule->nextRun(),
        ])->save();

        $auditTrail->log('scheduled_report_generated', null, [
            'schedule_id' => $schedule->id,
            'path' => $result['path'],
            'generated_at' => $result['generatedAt'],
            'email' => $schedule->schedule_email,
        ], $schedule->owner_id);
    }

    public function failed(Throwable $exception): void
    {
        $schedule = ReportSchedule::find($this->scheduleId);

        if ($schedule) {
            app(AuditTrailService::class)->log('scheduled_report_failed', null, [
                'schedule_id' => $schedule->id,
                'error' => $exception->getMessage(),
            ], $schedule->owner_id);
        }
    }
}
