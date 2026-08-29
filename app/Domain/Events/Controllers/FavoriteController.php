<?php

declare(strict_types=1);

namespace App\Domain\Events\Controllers;

use App\Domain\Events\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController
{
    public function index(Request $request): Response
    {
        $events = $request->user()
            ->favorites()
            ->with('organization')
            ->orderByPivot('created_at', 'desc')
            ->get()
            ->map(fn (Event $event) => EventDTO::fromModel($event->load('sessions.ticketTypes'))->toArray());

        return Inertia::render('Events/Favorites', [
            'events' => $events->values(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $request->user()->favorites()->syncWithoutDetaching([
            $event->id => ['created_at' => now()],
        ]);

        return redirect()->back()->with('success', 'Event saved to your favorites.');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $request->user()->favorites()->detach($event->id);

        return redirect()->back()->with('success', 'Event removed from your favorites.');
    }
}
