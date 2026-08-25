<?php

namespace App\Http\Controllers;

use App\Actions\ConvertInquiry;
use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function index(): Response
    {
        $inquiries = Inquiry::query()
            ->with(['convertedClient:id,name,company', 'convertedProject:id,title'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Inquiry $inquiry): array => [
                'id' => $inquiry->id,
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'company' => $inquiry->company,
                'message' => $inquiry->message,
                'budget_range' => $inquiry->budget_range,
                'received_at' => $inquiry->created_at?->toDateString(),
                'status' => [
                    'value' => $inquiry->status->value,
                    'label' => $inquiry->status->label(),
                    'color' => $inquiry->status->color(),
                ],
                'converted_project' => $inquiry->convertedProject === null ? null : [
                    'id' => $inquiry->convertedProject->id,
                    'title' => $inquiry->convertedProject->title,
                ],
            ]);

        return Inertia::render('inquiries/Index', [
            'inquiries' => $inquiries,
            'statuses' => array_map(
                fn (InquiryStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                ],
                InquiryStatus::cases(),
            ),
        ]);
    }

    public function updateStatus(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $validated = $request->validate([
            // Converted is not settable by hand: it is a consequence of running
            // the conversion, not a label somebody can apply.
            'status' => ['required', Rule::enum(InquiryStatus::class)->except(InquiryStatus::Converted)],
        ]);

        // forceFill, not update: status is deliberately outside #[Fillable] so
        // that no request body can ever set it. Mass assignment would drop it
        // here without saying anything, which is how this was found.
        $inquiry->forceFill($validated)->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inquiry updated.')]);

        return to_route('inquiries.index');
    }

    public function convert(Inquiry $inquiry, ConvertInquiry $convertInquiry): RedirectResponse
    {
        $result = $convertInquiry->handle($inquiry);

        // The plain token exists for exactly this one response. After the
        // redirect there is only a hash, and no way back to the link.
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['portal_token'] === null
                ? __('Already converted. Opening the project.')
                : __('Client and project created. Copy the portal link now.'),
        ]);

        if ($result['portal_token'] !== null) {
            session()->flash('portal_token', $result['portal_token']);
        }

        return to_route('projects.show', $result['project']);
    }
}
