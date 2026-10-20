<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\ModerationPanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    ModerationPanelProvider::class,
    HorizonServiceProvider::class,
];
