<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(): Response
    {
        $clients = Client::query()
            ->withCount('projects')
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'company' => $client->company,
                'projects_count' => $client->projects_count,
                'has_portal_access' => $client->hasActivePortalAccess(),
            ]);

        return Inertia::render('clients/Index', ['clients' => $clients]);
    }

    public function create(): Response
    {
        return Inertia::render('clients/Form', ['client' => null]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client created.')]);

        return to_route('clients.edit', $client);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('clients/Form', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'company' => $client->company,
                'phone' => $client->phone,
                'has_portal_access' => $client->hasActivePortalAccess(),
                'portal_expires_at' => $client->portal_token_expires_at?->toDateString(),
            ],
            'projects' => $client->projects()->orderByDesc('created_at')->get()
                ->map(fn ($project): array => [
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => [
                        'value' => $project->status->value,
                        'label' => $project->status->label(),
                        'color' => $project->status->color(),
                    ],
                ]),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client updated.')]);

        return to_route('clients.edit', $client);
    }

    public function destroy(Client $client): RedirectResponse
    {
        // Projects and milestones cascade. That is the intent: a deleted client
        // should not leave a portal link pointing at orphaned work.
        $client->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client deleted.')]);

        return to_route('clients.index');
    }
}
