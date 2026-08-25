<?php

use App\Enums\InquiryStatus;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists inquiries newest first', function () {
    Inquiry::factory()->create(['name' => 'Older', 'created_at' => now()->subWeek()]);
    Inquiry::factory()->create(['name' => 'Newer', 'created_at' => now()]);

    $this->get(route('inquiries.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('inquiries/Index')
            ->where('inquiries.0.name', 'Newer'));
});

it('converts an inquiry into a client and a project', function () {
    $inquiry = Inquiry::factory()->create();

    $this->post(route('inquiries.convert', $inquiry))->assertRedirect();

    expect(Client::query()->count())->toBe(1);
    expect(Project::query()->count())->toBe(1);
    expect($inquiry->fresh()->status)->toBe(InquiryStatus::Converted);
});

it('shows the portal link exactly once after converting', function () {
    $inquiry = Inquiry::factory()->create();

    $redirect = $this->post(route('inquiries.convert', $inquiry));
    $project = Project::query()->firstOrFail();

    $this->followingRedirects();

    $this->get($redirect->headers->get('Location'))
        ->assertInertia(fn ($page) => $page->whereNot('portalToken', null));

    // Flash data is gone on the next request, and only the hash was stored, so
    // there is no path back to the link from here.
    $this->get(route('projects.show', $project))
        ->assertInertia(fn ($page) => $page->where('portalToken', null));
});

it('refuses to set converted status by hand', function () {
    $inquiry = Inquiry::factory()->create();

    // Converted is a consequence of running the conversion, not a label anyone
    // can apply. Allowing it would leave an inquiry marked done with no client.
    $this->put(route('inquiries.status', $inquiry), [
        'status' => InquiryStatus::Converted->value,
    ])->assertSessionHasErrors('status');

    expect($inquiry->fresh()->status)->toBe(InquiryStatus::New);
});

it('accepts the other statuses', function () {
    $inquiry = Inquiry::factory()->create();

    $this->put(route('inquiries.status', $inquiry), [
        'status' => InquiryStatus::Contacted->value,
    ])->assertSessionHasNoErrors();

    expect($inquiry->fresh()->status)->toBe(InquiryStatus::Contacted);
});
