<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('cards:purge-unconfirmed')->daily();
Schedule::command('sources:archive-missing')->dailyAt('03:00')->withoutOverlapping();
