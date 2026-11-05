<?php
namespace App\Console\Commands;
use App\Models\Company;
use App\Services\TrustScoreService;
use Illuminate\Console\Command;

class RecalcTrust extends Command {
    protected $signature = 'gobiz:recalc-trust';
    protected $description = 'محاسبه‌ی دوباره‌ی امتیاز اعتماد همه‌ی شرکت‌های فعال';
    public function handle(TrustScoreService $svc): int {
        $n = 0;
        Company::where('status', 'active')->chunkById(200, function ($cs) use ($svc, &$n) { foreach ($cs as $c) { $svc->recalculate($c); $n++; } });
        $this->info("{$n} شرکت به‌روز شد.");
        return 0;
    }
}
