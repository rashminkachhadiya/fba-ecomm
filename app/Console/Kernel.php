<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('updateAccessToken:amazon')->hourlyAt(3)->withoutOverlapping();

        $schedule->command('app:fetch-amazon-product')->hourly()->withoutOverlapping();

        $schedule->command('app:get-amazon-product-detail')->everyTwoMinutes()->withoutOverlapping();

        $schedule->command('app:fetch-amazon-fba-inventory')->cron('7,37 * * * *')->withoutOverlapping();

        $schedule->command('app:fetch-amazon-order-report')->everyFifteenMinutes()->withoutOverlapping();

        $schedule->command('app:calculate-sales-velocity')->everyFifteenMinutes()->withoutOverlapping();

        // $schedule->command('app:fetch-fba-estimated-fees')->twiceDaily(7,8)->withoutOverlapping();

        $schedule->command('app:fetch-product-buybox-price')->everyMinute()->withoutOverlapping();

        $schedule->command('app:calculate-profit-margin-supplier-products')->everyMinute()->withoutOverlapping();

        $schedule->command('app:fba-shipment-reverse-sync')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('app:fba-shipment-items-reverse-sync')->everyMinute()->withoutOverlapping();

        // $schedule->command('app:fetchproduct-shopify')->hourly()->withoutOverlapping();
        // $schedule->command('app:fetchorders-shopify')->hourly()->withoutOverlapping();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
