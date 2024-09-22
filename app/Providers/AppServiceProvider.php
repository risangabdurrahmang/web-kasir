<?php

namespace App\Providers;

use App\Models\OrderItem;
use App\Observers\OrderItemObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        OrderItem::observe(OrderItemObserver::class);
    }
}
