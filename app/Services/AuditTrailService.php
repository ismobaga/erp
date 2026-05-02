<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditTrailService
{
    public function log(string $action, ?Model $subject = null, array $meta = [], ?int $userId = null): ?ActivityLog
    {
        try {
            return ActivityLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'meta_json' => $meta,
            ]);
        } catch (\Throwable $e) {
            // Audit failures must never roll back the primary financial operation.
            Log::error('AuditTrailService failed to write log entry', [
                'action' => $action,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
