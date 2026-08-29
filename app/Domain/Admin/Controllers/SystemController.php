<?php

declare(strict_types=1);

namespace App\Domain\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function health(): Response
    {
        $queueSize = Cache::get('laravel:queue:size', 0);
        $cacheDriver = config('cache.default');
        $queueDriver = config('queue.default');

        return Inertia::render('Admin/SystemHealth', [
            'queue_size' => $queueSize,
            'cache_driver' => $cacheDriver,
            'queue_driver' => $queueDriver,
            'maintenance_mode' => app()->isDownForMaintenance(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);
    }

    public function clearCache(): RedirectResponse
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');

        return redirect()->back()->with('success', 'Cache cleared successfully.');
    }

    public function toggleMaintenance(Request $request): RedirectResponse
    {
        $secret = (string) $request->input('secret', bin2hex(random_bytes(8)));

        if (app()->isDownForMaintenance()) {
            Artisan::call('up');

            return redirect()->back()->with('success', 'Application is now live.');
        }

        Artisan::call('down', ['--secret' => $secret]);

        return redirect()->back()->with('success', "Maintenance mode enabled. Secret: {$secret}");
    }
}
