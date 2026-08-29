<?php

declare(strict_types=1);

namespace App\Domain\Venues\Controllers;

use App\Domain\Venues\DTOs\VenueDTO;
use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Requests\DuplicateVenueRequest;
use App\Domain\Venues\Requests\StoreVenueRequest;
use App\Domain\Venues\Requests\UpdateVenueRequest;
use App\Domain\Venues\Services\VenueService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function __construct(
        private readonly VenueService $service,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Venue::class);

        $organizationId = (int) $request->user()->currentOrganizationId();
        $venues = $this->service->listForOrganization($organizationId);

        return Inertia::render('Venues/Index', [
            'venues' => $venues,
        ]);
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        Gate::authorize('create', Venue::class);

        $organizationId = (int) $request->user()->currentOrganizationId();

        $dto = new VenueDTO(
            name: $request->string('name')->toString(),
            description: $request->input('description'),
            address: $request->input('address'),
            city: $request->input('city'),
            state: $request->input('state'),
            country: $request->input('country'),
            zip: $request->input('zip'),
            latitude: $request->float('latitude'),
            longitude: $request->float('longitude'),
            phone: $request->input('phone'),
            website: $request->input('website'),
            isActive: (bool) $request->boolean('is_active', true),
            layout: $request->array('layout'),
        );

        $venue = $this->service->create($organizationId, $dto);

        return redirect()->route('venues.show', $venue->slug);
    }

    public function show(Venue $venue): Response
    {
        Gate::authorize('view', $venue);

        $venue->load(['halls.sections.rows.seats']);

        return Inertia::render('Venues/Show', [
            'venue' => $venue,
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        Gate::authorize('update', $venue);

        $dto = new VenueDTO(
            name: $request->string('name', $venue->name)->toString(),
            description: $request->input('description', $venue->description),
            address: $request->input('address', $venue->address),
            city: $request->input('city', $venue->city),
            state: $request->input('state', $venue->state),
            country: $request->input('country', $venue->country),
            zip: $request->input('zip', $venue->zip),
            latitude: $request->float('latitude', $venue->latitude),
            longitude: $request->float('longitude', $venue->longitude),
            phone: $request->input('phone', $venue->phone),
            website: $request->input('website', $venue->website),
            isActive: (bool) $request->boolean('is_active', $venue->is_active),
        );

        $this->service->update($venue, $dto);

        return redirect()->route('venues.show', $venue->slug);
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        Gate::authorize('delete', $venue);

        $this->service->delete($venue);

        return redirect()->route('venues.index');
    }

    public function duplicate(DuplicateVenueRequest $request, Venue $venue): RedirectResponse
    {
        Gate::authorize('duplicate', $venue);

        $organizationId = (int) $request->user()->currentOrganizationId();
        $copy = $this->service->duplicateAsTemplate($venue, $organizationId);

        return redirect()->route('venues.show', $copy->slug);
    }
}
