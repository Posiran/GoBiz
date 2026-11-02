<?php
namespace App\Support;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/** ثبت «چه کسی، چه کاری، روی چه چیزی» برای اقدام‌های حساس. شکست ثبت هرگز درخواست اصلی را خراب نمی‌کند. */
class Audit {
    public static function record(string $action, ?Model $subject = null, array $meta = [], ?int $userId = null): void {
        try {
            AuditLog::create([
                'user_id' => $userId ?? auth()->id(), 'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null, 'subject_id' => $subject?->getKey(),
                'meta' => $meta ?: null, 'ip' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::error('audit failed: '.$e->getMessage(), ['action' => $action]);
        }
    }
}
