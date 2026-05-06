<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditTrailService
{
    /** Maximum length for user-agent strings stored in activity_logs.user_agent (matches column size). */
    private const USER_AGENT_MAX_LENGTH = 512;

    public function log(string $action, ?Model $subject = null, array $meta = [], ?int $userId = null): ?ActivityLog
    {
        try {
            $ipAddress = null;
            $userAgent = null;

            if (app()->bound('request') && !app()->runningInConsole()) {
                $request = Request::instance();
                $ipAddress = $request->ip();
                $userAgent = substr((string) $request->userAgent(), 0, self::USER_AGENT_MAX_LENGTH);
            }

            return ActivityLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'meta_json' => $meta,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
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
