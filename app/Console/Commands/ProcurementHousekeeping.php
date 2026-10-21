<?php
namespace App\Console\Commands;
use App\Support\Procurement;
use Illuminate\Console\Command;

class ProcurementHousekeeping extends Command {
    protected $signature = 'procurement:housekeeping';
    protected $description = 'منقضی‌کردن پیش‌فاکتورهای گذشته و تکمیل خودکار سفارش‌های تحویل‌شده پس از پایان مهلت بازرسی';
    public function handle(): int {
        $r = Procurement::housekeeping();
        $this->info("پیش‌فاکتور منقضی‌شده: {$r['expired_offers']} | سفارش تکمیل‌شده خودکار: {$r['auto_completed']}");
        return 0;
    }
}
