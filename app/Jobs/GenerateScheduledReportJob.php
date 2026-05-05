<?php

namespace App\Jobs;

use App\Mail\ReportReadyMail;
use App\Models\Company;
use App\Models\ReportSchedule;
use App\Services\AuditTrailService;
use App\Services\ReportExportService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class GenerateScheduledReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $scheduleId,
        public readonly int $companyId = 0,
    ) {
    }

    public function handle(ReportExportService $exportService, AuditTrailService $auditTrail): void
    {
        // Bind the owning company so that all HasCompanyScope queries inside
        // this job are correctly scoped to the right tenant.
        $company = Company::find($this->companyId);

        if ($company === null) {
            return;
        }

        app()->instance('currentCompany', $company);

        /** @var ReportSchedule|null $schedule */
        $schedule = ReportSchedule::find($this->scheduleId);

        if (!$schedule || !in_array($schedule->status, ['active', 'processing'], true)) {
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
            'status' => 'active',
        ])->save();

        if (filled($schedule->schedule_email)) {
            Mail::to($schedule->schedule_email)
                ->queue(new ReportReadyMail($result['path'], $result['generatedAt']));
        }

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
            $schedule->forceFill([
                'status' => 'active',
                'next_execution_at' => $schedule->nextRun(),
            ])->save();

            app(AuditTrailService::class)->log('scheduled_report_failed', null, [
                'schedule_id' => $schedule->id,
                'error' => $exception->getMessage(),
            ], $schedule->owner_id);
        }
    }
}
