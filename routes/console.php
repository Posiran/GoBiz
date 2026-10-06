<?php
use Illuminate\Support\Facades\Schedule;

// نیاز به cron:  * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('procurement:housekeeping')->hourly()->withoutOverlapping();
