<?php

namespace App\Providers;

use App\Domain\Events\Models\Event;
use App\Domain\Events\Policies\EventPolicy;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Organizations\Policies\OrganizationPolicy;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Services\PaymentGatewayManager;
use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Policies\VenuePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->bind(PaymentGateway::class, function ($app) {
            return $app->make(PaymentGatewayManager::class)->driver();
        });
    }

    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelFqcn): string {
            $modelName = class_basename($modelFqcn);

            return 'Database\\Factories\\'.$modelName.'Factory';
        });

        Vite::prefetch(concurrency: 3);

        Schema::defaultStringLength(191);

        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Venue::class, VenuePolicy::class);

        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-booking', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
