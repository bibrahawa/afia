<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RapportsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('rapports')
            ->name('rapports.')
            ->group(base_path('routes/rapports.php'));
    }
}
