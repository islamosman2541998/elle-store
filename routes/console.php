<?php

use Illuminate\Support\Facades\Schedule;

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Safety net for carrier webhooks that never arrived.
Schedule::command('carriers:sync')->everyThirtyMinutes()->withoutOverlapping();
